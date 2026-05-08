<?php

declare(strict_types=1);

namespace App\Controller;

use App\Checkout\Application\Port\Payment\PaymentGatewayResolver;
use App\Checkout\Application\UseCase\Order\CancelOrder;
use App\Checkout\Application\UseCase\Order\CreateOrder;
use App\Checkout\Application\UseCase\Order\FinalizeOrder;
use App\Checkout\Domain\Service\PaymentMethodValidator;
use App\Checkout\Domain\ValueObject\PaymentMethod;
use App\Checkout\Infrastructure\Payment\PaypalCallbackSignature;
use App\Form\PaymentFormType;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Uid\Uuid;

class PaymentController extends AbstractController
{
    public function __construct(
        protected PaymentMethodValidator $paymentMethodValidator,
        protected PaymentGatewayResolver $paymentMethodFactory,
        protected FinalizeOrder $finalizeOrder,
        protected CreateOrder $createOrder,
        protected CancelOrder $cancelOrder,
        private readonly PaypalCallbackSignature $paypalCallbackSignature,
        #[Autowire(service: 'limiter.checkout_finalize')]
        private readonly RateLimiterFactory $checkoutFinalizeLimiter,
        #[Autowire(service: 'limiter.checkout_callback')]
        private readonly RateLimiterFactory $checkoutCallbackLimiter
    ) {
    }

    #[Route(path: '/basket/payment', name: "basket_payment")]
    public function basketPayment(Request $request): Response
    {
        $addressId = $request->get('addressId');
        $idempotencyKey = $this->resolveIdempotencyKey($request);
        $promoCode = $this->resolvePromoCode($request);
        $form = $this->createForm(PaymentFormType::class, null, [
            'methods' => PaymentMethod::formChoices(),
            'addressId' => $addressId,
            'idempotencyKey' => $idempotencyKey,
            'promoCode' => $promoCode,
        ]);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            if ($this->isRateLimited($request, $this->checkoutFinalizeLimiter, 'checkout-finalize')) {
                $this->addFlash('notice', 'Too many checkout attempts. Please retry in a moment.');

                return $this->redirectToRoute('display_basket');
            }

            $data = $form->getData();
            $selectedPaymentMethod = (string) ($data['paymentMethod'] ?? '');
            if (!$this->paymentMethodValidator->validate($selectedPaymentMethod)) {
                $this->addFlash('notice', 'Unsupported payment method selected.');

                return $this->redirectToRoute('display_basket');
            }

            $addressId = (string) ($data['addressId'] ?? '');
            $paymentMethod = $this->paymentMethodFactory->getPaymentMethod($selectedPaymentMethod);
            $idempotencyKey = (string) ($data['idempotencyKey'] ?? '');
            $promoCode = (string) ($data['promoCode'] ?? '');
            $callbackSignature = $this->paypalCallbackSignature->sign($idempotencyKey);
            $paypalReturnUrl = $this->generateUrl(
                'paypal_payment_success',
                ['ik' => $idempotencyKey, 'sig' => $callbackSignature],
                UrlGeneratorInterface::ABSOLUTE_URL
            );
            $paypalCancelUrl = $this->generateUrl(
                'paypal_payment_cancel',
                ['ik' => $idempotencyKey, 'sig' => $callbackSignature],
                UrlGeneratorInterface::ABSOLUTE_URL
            );
            $result = $this->finalizeOrder->execute(
                $paymentMethod,
                $addressId,
                $idempotencyKey,
                $promoCode,
                $paypalReturnUrl,
                $paypalCancelUrl
            );
            if ($result->hasRedirectUrl()) {
                return $this->redirect((string) $result->getRedirectUrl());
            }

            if ($result->isSuccess()) {
                return $this->redirectToRoute('order_execute');
            }
            $this->addFlash(
                'notice',
                $result->getMessage()
            );
            return $this->redirectToRoute('display_basket');
        }
        return $this->render('payment/list.html.twig', [
            'payments' => array_keys(PaymentMethod::formChoices()),
            'form' => $form,
            'addressId' => $addressId,
        ]);
    }

    #[Route(path: '/basket/payment/paypal/success', name: "paypal_payment_success", methods: ['GET'])]
    public function paypalSuccess(Request $request): Response
    {
        if ($this->isRateLimited($request, $this->checkoutCallbackLimiter, 'paypal-success')) {
            $this->addFlash('notice', 'Too many PayPal callback attempts. Please retry shortly.');

            return $this->redirectToRoute('display_basket');
        }

        $providerOrderId = (string) $request->query->get('token', '');
        $expectedIdempotencyKey = (string) $request->query->get('ik', '');
        $callbackSignature = (string) $request->query->get('sig', '');

        if (!$this->paypalCallbackSignature->isValid($expectedIdempotencyKey, $callbackSignature)) {
            $this->addFlash('notice', 'PayPal callback signature is invalid.');

            return $this->redirectToRoute('display_basket');
        }

        $result = $this->createOrder->completePaypalOrder($providerOrderId, $expectedIdempotencyKey);
        if ($result->isSuccess()) {
            return $this->redirectToRoute('order_execute');
        }

        $this->addFlash('notice', $result->getMessage());

        return $this->redirectToRoute('display_basket');
    }

    #[Route(path: '/basket/payment/paypal/cancel', name: "paypal_payment_cancel", methods: ['GET'])]
    public function paypalCancel(Request $request): Response
    {
        if ($this->isRateLimited($request, $this->checkoutCallbackLimiter, 'paypal-cancel')) {
            $this->addFlash('notice', 'Too many PayPal callback attempts. Please retry shortly.');

            return $this->redirectToRoute('display_basket');
        }

        $providerOrderId = (string) $request->query->get('token', '');
        $expectedIdempotencyKey = (string) $request->query->get('ik', '');
        $callbackSignature = (string) $request->query->get('sig', '');

        if (!$this->paypalCallbackSignature->isValid($expectedIdempotencyKey, $callbackSignature)) {
            $this->addFlash('notice', 'PayPal callback signature is invalid.');

            return $this->redirectToRoute('display_basket');
        }

        $result = $this->cancelOrder->cancelPaypalOrder($providerOrderId, $expectedIdempotencyKey);
        $this->addFlash('notice', $result->getMessage());

        return $this->redirectToRoute('display_basket');
    }

    private function resolveIdempotencyKey(Request $request): string
    {
        $formData = $request->request->all('payment_form');
        if (is_array($formData) && isset($formData['idempotencyKey'])) {
            $candidate = trim((string) $formData['idempotencyKey']);
            if ('' !== $candidate) {
                return $candidate;
            }
        }

        return Uuid::v4()->toRfc4122();
    }

    private function resolvePromoCode(Request $request): string
    {
        $formData = $request->request->all('payment_form');
        if (is_array($formData) && isset($formData['promoCode'])) {
            return trim((string) $formData['promoCode']);
        }

        return trim((string) $request->query->get('promoCode', ''));
    }

    private function isRateLimited(Request $request, RateLimiterFactory $limiterFactory, string $namespace): bool
    {
        $key = sprintf('%s:%s', $namespace, $this->resolveRateLimitKey($request));
        $limit = $limiterFactory->create($key)->consume(1);

        return !$limit->isAccepted();
    }

    private function resolveRateLimitKey(Request $request): string
    {
        if ($request->hasSession()) {
            return 'session:' . $request->getSession()->getId();
        }

        return 'ip:' . ($request->getClientIp() ?? 'unknown');
    }
}
