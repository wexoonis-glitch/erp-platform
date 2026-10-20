<?php

namespace App\Registry;

use App\Models\FunctionRegistry;

final class FunctionSpec
{
    public function __construct(
        public readonly string $id,
        public readonly string $domain,
        public readonly string $subprocess,
        public readonly string $name,
        public readonly string $type,
        public readonly string $version,
        public readonly string $status,
        public readonly ?string $owner,
        public readonly ?int $slaMs,
        public readonly array $spec,
    ) {}

    public static function fromModel(FunctionRegistry $row): self
    {
        return new self(
            id:         $row->id,
            domain:     $row->domain,
            subprocess: $row->subprocess,
            name:       $row->name,
            type:       $row->type,
            version:    $row->version,
            status:     $row->status,
            owner:      $row->owner,
            slaMs:      $row->sla_ms,
            spec:       $row->spec ?? [],
        );
    }

    public function moduleId(): string
    {
        return $this->domain;
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isDeprecated(): bool
    {
        return $this->status === 'deprecated';
    }

    public function toArray(): array
    {
        return [
            'id'         => $this->id,
            'domain'     => $this->domain,
            'subprocess' => $this->subprocess,
            'name'       => $this->name,
            'type'       => $this->type,
            'version'    => $this->version,
            'status'     => $this->status,
            'owner'      => $this->owner,
            'sla_ms'     => $this->slaMs,
        ];
    }
}
