<?php

declare(strict_types=1);

namespace App\Exceptions;

class OutOfStockException extends \Exception
{
    public function __construct()
    {
        parent::__construct(sprintf("out Of Stock"));
    }
}
