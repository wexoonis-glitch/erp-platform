<?php

namespace App\Plugins;

use Illuminate\Validation\ValidationException;

final class PluginManifest
{
    public function __construct(
        public readonly string $id,
        public readonly string $name,
        public readonly string $version,
        public readonly string $vendor,
        public readonly string $description,
        public readonly string $namespace,
        public readonly array $requires,
        public readonly array $eventsConsumed,
        public readonly array $eventsPublished,
        public readonly array $extensionPointsUsed,
        public readonly array $listeners,
        public readonly string $path,
        public readonly string $checksum,
    ) {}

    public static function fromArray(array $data, string $path): self
    {
        $checksum = hash('sha256', json_encode($data));

        return new self(
            id:                   $data['id'],
            name:                 $data['name'],
            version:              $data['version'],
            vendor:               $data['vendor'] ?? '',
            description:          $data['description'] ?? '',
            namespace:            $data['namespace'] ?? '',
            requires:             $data['requires'] ?? [],
            eventsConsumed:       $data['events_consumed'] ?? [],
            eventsPublished:      $data['events_published'] ?? [],
            extensionPointsUsed:  $data['extension_points_used'] ?? [],
            listeners:            $data['classes']['listeners'] ?? [],
            path:                 $path,
            checksum:             $checksum,
        );
    }

    public static function fromFile(string $path): self
    {
        if (!file_exists($path)) {
            throw new \RuntimeException("Manifest dosyası yok: {$path}");
        }

        $json = file_get_contents($path);
        $data = json_decode($json, true);

        if (!is_array($data)) {
            throw ValidationException::withMessages([
                'manifest' => "Geçersiz JSON: {$path}",
            ]);
        }

        $errors = [];
        foreach (['id', 'name', 'version'] as $field) {
            if (empty($data[$field])) {
                $errors[$field] = "Manifest alanı zorunlu: {$field}";
            }
        }
        if (!empty($data['id']) && !preg_match('/^[a-z][a-z0-9_]{0,49}$/', $data['id'])) {
            $errors['id'] = 'id küçük harfle başlamalı; sadece küçük harf, rakam ve alt çizgi.';
        }
        if (!empty($errors)) {
            throw ValidationException::withMessages($errors);
        }

        return self::fromArray($data, dirname($path));
    }

    public function toArray(): array
    {
        return [
            'id'                   => $this->id,
            'name'                 => $this->name,
            'version'              => $this->version,
            'vendor'               => $this->vendor,
            'description'          => $this->description,
            'namespace'            => $this->namespace,
            'requires'             => $this->requires,
            'events_consumed'      => $this->eventsConsumed,
            'events_published'     => $this->eventsPublished,
            'extension_points_used' => $this->extensionPointsUsed,
            'classes'              => ['listeners' => $this->listeners],
        ];
    }
}
