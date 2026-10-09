<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    use HasFactory;

    protected $fillable = [
        'key',
        'value',
        'group',
        'type',
        'display_name',
        'description',
    ];

    public static function get(string $key, $default = null)
    {
        $setting = self::where('key', $key)->first();
        if (!$setting) {
            return $default;
        }

        return match ($setting->type) {
            'boolean' => filter_var($setting->value, FILTER_VALIDATE_BOOLEAN),
            'integer' => (int) $setting->value,
            'json' => json_decode($setting->value, true),
            default => $setting->value,
        };
    }

    public static function set(string $key, $value, ?string $group = 'general', ?string $type = 'string'): self
    {
        $stringValue = is_array($value) ? json_encode($value) : (string) $value;
        return self::updateOrCreate(
            ['key' => $key],
            [
                'value' => $stringValue,
                'group' => $group ?? 'general',
                'type' => $type ?? 'string',
            ]
        );
    }
}
