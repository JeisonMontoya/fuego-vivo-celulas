<?php

namespace App\Models;

use Database\Factories\CellFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cell extends Model
{
    /** @use HasFactory<CellFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'leader_id',
        'name',
        'address',
        'meeting_day',
        'meeting_time',
        'status',
    ];

    /**
     * Get the leader associated with the cell.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function leader(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'leader_id');
    }

    public function members(): HasMany
    {
        return $this->hasMany(CellMember::class, 'cell_id');
    }

    public function reports(): HasMany
    {
        return $this->hasMany(Report::class, 'cell_id');
    }
}
