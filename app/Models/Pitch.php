<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Pitch extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    public function turf(): BelongsTo
    {
        return $this->belongsTo(Turf::class);
    }
}