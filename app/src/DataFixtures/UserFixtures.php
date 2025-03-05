<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Factory\UserFactory;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactory;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $factory = new PasswordHasherFactory([
            'common' => ['algorithm' => 'auto'],
            'sodium' => ['algorithm' => 'sodium'],
        ]);
        // retrieve the hasher using bcrypt
        $hasher = $factory->getPasswordHasher('common');
        $hash = $hasher->hash('1Qq!1111');
        UserFactory::createOne(["email" => "admin@admin.com", "password" => $hash]);

        $manager->flush();
    }
}
