<?php

declare(strict_types=1);

namespace App\Address\Application\UseCase;

use App\User\Infrastructure\Persistence\Doctrine\User;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\User\UserInterface;

class CreateAddressLine
{
    public function __construct(readonly private Security $security)
    {
    }

    /**
     * @return array<string>
     */
    public function build(): array
    {
        $addresses = [];
        /** @var User $user */
        $user = $this->getUser();
        foreach ($user->getAddresses() as $address) {
            $addresses[$address->getId()] =
                sprintf('%s, %s %s', $address->getStreet(), $address->getZip(), $address->getCity());
        }

        return $addresses;
    }

    public function getUser(): UserInterface
    {
        return $this->security->getUser();
    }
}
