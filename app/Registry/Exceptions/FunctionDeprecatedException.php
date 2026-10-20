<?php

namespace App\Registry\Exceptions;

use RuntimeException;

class FunctionDeprecatedException extends RuntimeException
{
    public function __construct(public readonly string $functionId)
    {
        parent::__construct("Fonksiyon artık kullanımdan kaldırıldı (deprecated): {$functionId}");
    }
}
