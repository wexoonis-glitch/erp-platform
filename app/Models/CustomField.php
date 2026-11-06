<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class CustomField extends Model
{
    use HasUuids;

    protected $table = 'custom_field';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = ['tenant_id', 'entity', 'code', 'label', 'data_type', 'config'];
    protected $casts = ['config' => 'array'];

    public static function forEntity(string $entity): Collection
    {
        return self::where('entity', $entity)->orderBy('label')->get();
    }

    /**
     * Livewire için `customFields.*` altında validation kuralları üretir.
     */
    public static function validateCustomFields(string $entity, array $values = []): array
    {
        $rules = [];
        foreach (self::forEntity($entity) as $f) {
            $key = "customFields.{$f->code}";
            $rule = [];

            $required = (bool) ($f->config['required'] ?? false);
            $rule[] = $required ? 'required' : 'nullable';

            match ($f->data_type) {
                'number' => $rule[] = 'numeric',
                'date'   => $rule[] = 'date',
                'bool'   => $rule[] = 'boolean',
                'json'   => $rule[] = 'string',
                'enum'   => null,
                default  => $rule[] = 'string',
            };

            if ($f->data_type === 'enum' && !empty($f->config['options'])) {
                $rule[] = 'in:' . implode(',', $f->config['options']);
            }

            $rules[$key] = implode('|', $rule);
        }
        return $rules;
    }

    /**
     * Liste/PDF gibi yerlerde okunabilir değer.
     */
    public static function renderValue(CustomField $field, mixed $value): string
    {
        if ($value === null || $value === '') return '—';
        if ($field->data_type === 'bool') return $value ? 'Evet' : 'Hayır';
        if ($field->data_type === 'json') return is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : (string) $value;
        return (string) $value;
    }
}
