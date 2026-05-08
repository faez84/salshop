<?php

declare(strict_types=1);

namespace App\User\Application\Port\Persistence;

use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;


interface IUserRepository
{
    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void;
}
