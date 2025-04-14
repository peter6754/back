<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Backpack\CRUD\app\Models\Traits\CrudTrait;

class Translation extends Model
{
    use CrudTrait, HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'translations';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'key',
        'description',
        'group',
        'translations',
        'is_active',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'translations' => 'array',
    ];

    /**
     * Get the attributes that should be typed.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'translations' => 'array',
        ];
    }

    /**
     * Scope a query to only include active translations.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to filter by group.
     */
    public function scopeGroup($query, $group)
    {
        return $query->where('group', $group);
    }

    /**
     * Scope a query to search by key or description.
     */
    public function scopeSearch($query, $search)
    {
        return $query->where('key', 'like', "%{$search}%")
            ->orWhere('description', 'like', "%{$search}%");
    }

    /**
     * Get translation for a specific language.
     */
    public function getTranslation(string $locale): ?string
    {
        return $this->translations['translation_'.$locale] ?? null;
    }

    /**
     * Set translation for a specific language.
     */
    public function setTranslation(string $locale, string $value): void
    {
        $translations = $this->translations ?? [];
        $translations[$locale] = $value;
        $this->translations = $translations;
    }

    /**
     * Get available locales from translations.
     */
    public function getAvailableLocales(): array
    {
        return array_keys($this->translations ?? []);
    }

    /**
     * Check if translation exists for a specific locale.
     */
    public function hasTranslation(string $locale): bool
    {
        return isset($this->translations[$locale]);
    }

    /**
     * Accessor for formatted translations display.
     */
    public function getTranslationsDisplayAttribute(): string
    {
        if (empty($this->translations)) {
            return 'No translations';
        }

        $display = [];
        foreach ($this->translations as $locale => $translation) {
            $display[] = strtoupper($locale).': '.substr($translation, 0, 50).(strlen($translation) > 50 ? '...' : '');
        }

        return implode(' | ', $display);
    }

    /**
     * Accessor for active status display.
     */
    public function getActiveStatusAttribute(): string
    {
        return $this->is_active ? 'Active' : 'Inactive';
    }
}