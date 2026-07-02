<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

// Manually-managed national holiday calendar (no external API fetch).
// Internal campus holidays are not regulated yet (STIKES policy point 4)
// and are intentionally out of scope for this table.
class Holiday extends Model
{
    protected $fillable = [
        'date',
        'name',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
        ];
    }
}
