<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Dialog extends Model
{
    protected $fillable = [
        'client_id', 'campaign_id', 'channel', 'external_chat_id',
        'current_status', 'is_ai_active', 'messages_count',
        'last_message_at', 'last_client_message_at',
    ];

    protected $casts = [
        'is_ai_active' => 'boolean',
        'last_message_at' => 'datetime',
        'last_client_message_at' => 'datetime',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    public function statuses(): HasMany
    {
        return $this->hasMany(DialogStatus::class)->orderBy('created_at', 'desc');
    }

    public function updateStatus(string $newStatus, string $source = 'system', array $metadata = []): void
    {
        $previousStatus = $this->current_status;

        DialogStatus::create([
            'dialog_id' => $this->id,
            'status' => $newStatus,
            'previous_status' => $previousStatus,
            'source' => $source,
            'metadata' => $metadata,
        ]);

        $this->update(['current_status' => $newStatus]);
    }
}
