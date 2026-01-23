<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Client extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'organization_id', 'phone', 'first_name', 'last_name', 'patronymic',
        'email', 'city', 'country', 'birth_date', 'discount', 'extra_data', 'comment',
    ];

    protected $casts = [
        'extra_data' => 'array',
        'birth_date' => 'date',
        'discount' => 'decimal:2',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function dialogs(): HasMany
    {
        return $this->hasMany(Dialog::class);
    }

    public function getFullNameAttribute(): string
    {
        return trim("{$this->last_name} {$this->first_name} {$this->patronymic}");
    }
}
