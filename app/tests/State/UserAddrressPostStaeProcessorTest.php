<?php

namespace App\Tests\State;

use ApiPlatform\Metadata\GraphQl\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\Address;
use App\State\UserAddrressPostStaeProcessor;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\SecurityBundle\Security;

final class UserAddrressPostStaeProcessorTest extends TestCase
{

    public function testProcess(): void 
    {
        $userMock = $this->getMockBuilder(Address::class)->getMock();
        $userMock->expects($this->once())->method('setUser');

        $internalProcess = $this->getMockBuilder(ProcessorInterface::class)->getMock();
        $internalProcess->expects($this->once())->method('process');

        $security = $this->getMockBuilder(Security::class)->disableOriginalConstructor()->getMock();
         $operation = $this->getMockBuilder(Operation::class)->getMock();


        $processor = new UserAddrressPostStaeProcessor($internalProcess, $security);
        $processor->process($userMock, $operation); // replace null with Operation object when available
    }
}
