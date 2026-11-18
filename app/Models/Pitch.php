<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Pitch extends Model
{
    public function pricingRules(): HasMany
{
    return $this->hasMany(PricingRule::class);
}
    use SoftDeletes;

    protected $guarded = [];

    public function turf(): BelongsTo
    {
        return $this->belongsTo(Turf::class);
    }
}