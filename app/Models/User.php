<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Carbon\Carbon;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The model's default values for attributes.
     *
     * @var array
     */
    protected $attributes = [
        'compliance_percentage' => 100,
        'rating' => 5,
        'reports_count' => 0,
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'document',
        'email',
        'password',
        'phone',
        'address',
        'photo_path',
        'cell_id',
        'sector',
        'entry_date',
        'supervisor_id',
        'role',
        'status',
        'timezone',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'entry_date' => 'date',
            'rating' => 'decimal:1',
            'compliments' => 'array',
        ];
    }

    /**
     * Get the cell that the leader belongs to.
     *
     * @return BelongsTo<Cell, User>
     */
    public function cell(): BelongsTo
    {
        return $this->belongsTo(Cell::class, 'cell_id');
    }

    public function reports()
    {
        return $this->hasMany(Report::class);
    }

    public function getExpectedReportsCount(): int
    {
        $tz = $this->timezone ?? config('app.timezone');
        $startDate = Carbon::parse($this->entry_date ?? $this->created_at, $tz);
        $expectedReports = 0;

        $cell = $this->cell;
        if ($cell && $cell->meeting_day) {
            $daysMap = [
                'Lunes' => Carbon::MONDAY, 'Martes' => Carbon::TUESDAY, 'Miércoles' => Carbon::WEDNESDAY,
                'Jueves' => Carbon::THURSDAY, 'Viernes' => Carbon::FRIDAY, 'Sábado' => Carbon::SATURDAY, 'Domingo' => Carbon::SUNDAY,
            ];

            $dayOfWeek = $daysMap[$cell->meeting_day] ?? Carbon::FRIDAY;
            $currentDate = clone $startDate;

            if (! $currentDate->isDayOfWeek($dayOfWeek)) {
                $currentDate->next($dayOfWeek);
            }

            while (true) {
                $deadline = $currentDate->clone()->addDay()->endOfDay();
                if (now($tz)->isAfter($deadline)) {
                    $expectedReports++;
                    $currentDate->addWeek();
                } else {
                    break;
                }
            }
        } else {
            $expectedReports = floor($startDate->diffInDays(now($tz)) / 7);
        }

        return (int) $expectedReports;
    }

    public function getMissingReportsCount(): int
    {
        return max(0, $this->getExpectedReportsCount() - $this->reports()->count());
    }

    /**
     * Recalculate and update the leader's metrics based on their reports.
     */
    public function recalculateMetrics()
    {
        $reports = $this->reports()->get();
        $reportsCount = $reports->count();

        $expectedReports = $this->getExpectedReportsCount();
        $missingReports = max(0, $expectedReports - $reportsCount);

        if ($expectedReports == 0 && $reportsCount == 0) {
            $compliance = 100; // Un nuevo usuario que aún no tiene su primera fecha límite empieza con 100%
        } else {
            $maxPossiblePoints = max($expectedReports, $reportsCount) * 10;
            $pointsEarned = $reports->sum('score') - ($missingReports * 10);
            $compliance = max(0, min(100, round(($pointsEarned / $maxPossiblePoints) * 100)));
        }

        // Calculate stars based on percentage
        $rating = 1; // Default
        if ($compliance >= 90) {
            $rating = 5;
        } elseif ($compliance >= 70) {
            $rating = 4;
        } elseif ($compliance >= 50) {
            $rating = 3;
        } elseif ($compliance >= 30) {
            $rating = 2;
        } else {
            $rating = 1;
        }

        $this->forceFill([
            'reports_count' => $reportsCount,
            'compliance_percentage' => $compliance,
            'rating' => $rating,
        ])->save();
    }

    /**
     * Get the supervisor assigned to the leader.
     *
     * @return BelongsTo<User, User>
     */
    public function supervisor(): BelongsTo
    {
        return $this->belongsTo(self::class, 'supervisor_id');
    }

    /**
     * Get the leaders under supervision.
     *
     * @return HasMany<User, User>
     */
    public function subordinates(): HasMany
    {
        return $this->hasMany(self::class, 'supervisor_id');
    }

    /**
     * Check if the user is an admin.
     */
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    /**
     * Check if the user is a supervisor.
     */
    public function isSupervisor(): bool
    {
        return $this->role === 'supervisor';
    }

    /**
     * Check if the user is a leader.
     */
    public function isLeader(): bool
    {
        return $this->role === 'leader';
    }

    /**
     * Check if the user status is active.
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Check if the user status is pending.
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }
}
