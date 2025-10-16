<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Contact extends Model
{
    use HasUuids;

    protected $fillable = [
        'user_id',
        'first_name',
        'last_name',
        'email',
        'position',
        'company_name',
        'phone',
        'work_phone',
        'home_phone',
        'address',
        'additional_addresses',
        'city',
        'latitude',
        'longitude',
        'timezone',
        'title',
        'role',
        'website_url',
        'birthday',
        'notes',
        'tags',
        'industries',
        'socials',
        'search_index',
        'on_platform',
        'has_sync',
        'needs_sync',
    ];

    protected $casts = [
        'tags' => 'array',
        'industries' => 'array',
        'socials' => 'array',
        'on_platform' => 'boolean',
        'has_sync' => 'boolean',
        'needs_sync' => 'boolean',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
    ];

    /**
     * Get the user that owns the contact.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Build search index from contact fields.
     */
    public function buildSearchIndex(): string
    {
        $fields = [
            $this->first_name,
            $this->last_name,
            $this->email,
            $this->position,
            ...$this->tags ?? [],
        ];

        return strtolower(trim(implode(' ', array_filter($fields))));
    }

    /**
     * Boot the model and set up event listeners.
     */
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($contact) {
            // Auto-generate search index on save
            $contact->search_index = $contact->buildSearchIndex();
        });
    }
}
