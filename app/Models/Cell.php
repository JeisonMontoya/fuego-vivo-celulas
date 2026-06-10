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
        'name',
        'address',
        'meeting_day',
        'meeting_time',
        'status',
    ];

    /**
     * Get the leaders associated with the cell.
     *
     * @return HasMany<User, Cell>
     */
    public function leaders(): HasMany
    {
        return $this->hasMany(User::class, 'cell_id');
    }

    public function members(): HasMany
    {
        return $this->hasMany(CellMember::class, 'cell_id');
    }
}
