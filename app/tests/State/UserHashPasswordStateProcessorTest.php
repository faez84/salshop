<?php

namespace App\Tests\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\User;
use App\State\UserHashPasswordStateProcessor;
use PHPUnit\Framework\TestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserHashPasswordStateProcessorTest extends TestCase
{
    public function testSomething(): void
    {
        $this->assertTrue(true);
    }

    public function testProcess(): void 
    {
        $hasher = $this->getMockBuilder(UserPasswordHasherInterface::class)->getMock();
        $hasher->expects($this->once())->method("hashPassword")->willReturn('uuuuuuu');

        $internalProcess = $this->getMockBuilder(ProcessorInterface::class)->getMock();
        $internalProcess->expects($this->once())->method("process");

        $process = $this->getMockBuilder(Operation::class)->getMock();

        $userHashPasswordStateProcessor = new UserHashPasswordStateProcessor($internalProcess, $hasher);

        $user = new User();
        $user->setEmail("testAdmin@admin.com");
        $user->setPassword("1Qq!2222");
        $userHashPasswordStateProcessor->process($user, $process);

    }
}
