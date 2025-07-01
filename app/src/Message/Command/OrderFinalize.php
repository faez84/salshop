<?php

namespace App\Message\Command;

use Stringable;

readonly class OrderFinalize implements Stringable
{
    public function __construct(private string $id = "22", private string $paymentName = "paypal")
    {
    }
    public function getId(): string
    {
        return $this->id;
    }
    
    public function getPymentName(): string 
    {
        return $this->paymentName;
    }

    public function __toString(): string
    {
        return "Finalize Order #{$this->id}";
    }

}