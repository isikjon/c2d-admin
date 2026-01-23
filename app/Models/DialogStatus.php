<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DialogStatus extends Model
{
    protected $fillable = [
        'dialog_id', 'status', 'previous_status', 'source', 'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    public function dialog(): BelongsTo
    {
        return $this->belongsTo(Dialog::class);
    }
}
