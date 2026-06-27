<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Report extends Model
{
    protected $fillable = [
        'user_id',
        'cell_id',
        'meeting_date',
        'attendance_count',
        'guests_count',
        'notes',
        'host_name',
        'tithes',
        'offerings',
        'score',
        'days_late',
    ];

    protected $casts = [
        'meeting_date' => 'date',
        'tithes' => 'decimal:2',
        'offerings' => 'decimal:2',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function attendees()
    {
        return $this->belongsToMany(CellMember::class, 'report_attendances');
    }

    public function cell()
    {
        return $this->belongsTo(Cell::class);
    }
}
