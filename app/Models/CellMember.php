<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CellMember extends Model
{
    protected $fillable = [
        'cell_id',
        'name',
        'phone',
        'email',
        'address',
        'age',
        'is_new',
        'went_to_encounter',
        'is_baptized',
        'attends_church',
        'attends_school',
        'ministry',
    ];

    protected $casts = [
        'is_new' => 'boolean',
        'went_to_encounter' => 'boolean',
        'is_baptized' => 'boolean',
        'attends_church' => 'boolean',
        'attends_school' => 'boolean',
    ];

    public function cell()
    {
        return $this->belongsTo(Cell::class);
    }

    public function reports()
    {
        return $this->belongsToMany(Report::class, 'report_attendances');
    }

    protected static function booted()
    {
        static::saved(function ($member) {
            \Illuminate\Support\Facades\Cache::forget('admin.dashboard.members_stats');
        });

        static::deleted(function ($member) {
            \Illuminate\Support\Facades\Cache::forget('admin.dashboard.members_stats');
        });
    }
}
