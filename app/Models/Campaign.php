<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Campaign extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'organization_id', 'user_id', 'product_id', 'name', 'description',
        'status', 'trigger_action', 'target_url', 'utm_source', 'utm_medium',
        'utm_campaign', 'max_retries', 'retry_interval_minutes',
        'send_time_from', 'send_time_to', 'send_days', 'tags',
        'started_at', 'completed_at',
    ];

    protected $casts = [
        'send_days' => 'array',
        'tags' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function mailings(): HasMany
    {
        return $this->hasMany(Mailing::class);
    }

    public function dialogs(): HasMany
    {
        return $this->hasMany(Dialog::class);
    }
}
