<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PricingRule extends Model
{
    protected $guarded = [];

    public function pitch(): BelongsTo
    {
        return $this->belongsTo(Pitch::class);
    }
}