<?php

namespace App\Registry\Exceptions;

use RuntimeException;

class FunctionNotInstalledException extends RuntimeException
{
    public function __construct(
        public readonly string $functionId,
        public readonly string $moduleId,
        public readonly ?string $tenantId,
    ) {
        parent::__construct(
            "Fonksiyon tenant'ta kurulu değil: {$functionId} (modül: {$moduleId}, tenant: {$tenantId})"
        );
    }
}
