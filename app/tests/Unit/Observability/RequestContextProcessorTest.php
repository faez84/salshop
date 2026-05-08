<?php

declare(strict_types=1);

namespace App\Tests\Unit\Observability;

use App\Observability\RequestContext;
use App\Observability\RequestContextProcessor;
use Monolog\Level;
use Monolog\LogRecord;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

final class RequestContextProcessorTest extends TestCase
{
    public function testProcessorAddsRequestContextToLogRecordExtras(): void
    {
        $request = Request::create('/basket/payment', 'POST');
        $request->attributes->set('_route', 'basket_payment');

        $context = new RequestContext();
        $context->setFromRequest($request, 'req-123');
        $processor = new RequestContextProcessor($context);

        $record = [
            'message' => 'Checkout started',
            'context' => [],
            'level' => 200,
            'level_name' => 'INFO',
            'channel' => 'checkout',
            'datetime' => new \DateTimeImmutable(),
            'extra' => [],
        ];

        $processed = $processor($record);

        self::assertSame('req-123', $processed['extra']['request_id']);
        self::assertSame('basket_payment', $processed['extra']['request_route']);
        self::assertSame('POST', $processed['extra']['request_method']);
        self::assertSame('/basket/payment', $processed['extra']['request_path']);
    }

    public function testProcessorAddsRequestContextToMonologLogRecord(): void
    {
        $request = Request::create('/basket/payment', 'POST');
        $request->attributes->set('_route', 'basket_payment');

        $context = new RequestContext();
        $context->setFromRequest($request, 'req-456');
        $processor = new RequestContextProcessor($context);

        $record = new LogRecord(
            datetime: new \DateTimeImmutable(),
            channel: 'checkout',
            level: Level::Info,
            message: 'Checkout started',
            context: [],
            extra: []
        );

        $processed = $processor($record);

        self::assertInstanceOf(LogRecord::class, $processed);
        self::assertSame('req-456', $processed->extra['request_id']);
        self::assertSame('basket_payment', $processed->extra['request_route']);
        self::assertSame('POST', $processed->extra['request_method']);
        self::assertSame('/basket/payment', $processed->extra['request_path']);
    }
}
