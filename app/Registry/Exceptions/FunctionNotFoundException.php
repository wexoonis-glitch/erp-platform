<?php

namespace App\Registry\Exceptions;

use RuntimeException;

class FunctionNotFoundException extends RuntimeException
{
    public function __construct(public readonly string $functionId)
    {
        parent::__construct("Fonksiyon katalogda bulunamadı: {$functionId}");
    }
}
