<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Cell;
use App\Models\User;
use App\Notifications\LateReportReminderNotification;
use Carbon\Carbon;

class SendLateReportReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reports:send-reminders';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send automatic email reminders to leaders who are 24+ hours late submitting their cell report';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting late report reminder check...');

        // Map spanish days to Carbon days
        $daysMap = [
            'Lunes' => Carbon::MONDAY,
            'Martes' => Carbon::TUESDAY,
            'Miércoles' => Carbon::WEDNESDAY,
            'Jueves' => Carbon::THURSDAY,
            'Viernes' => Carbon::FRIDAY,
            'Sábado' => Carbon::SATURDAY,
            'Domingo' => Carbon::SUNDAY,
        ];

        // Only active cells
        $cells = Cell::where('status', 'active')->whereNotNull('meeting_day')->whereNotNull('meeting_time')->get();

        $count = 0;

        foreach ($cells as $cell) {
            if (!isset($daysMap[$cell->meeting_day])) {
                continue; // invalid day string
            }

            // Parse the time. e.g. "07:00 PM"
            try {
                $meetingTime = Carbon::createFromFormat('h:i A', $cell->meeting_time);
            } catch (\Exception $e) {
                // If it fails parsing time, skip
                continue;
            }

            // Find the most recent occurrence of this day of week
            // We use 'previous or current' to get the last one.
            $carbonDay = $daysMap[$cell->meeting_day];
            
            // Generate the exact Carbon instance for the last meeting
            $lastMeeting = Carbon::parse('last ' . $cell->meeting_day)
                ->setTime($meetingTime->hour, $meetingTime->minute, 0);
            
            // If the meeting day is today and time has passed, the last meeting was actually today!
            // E.g., meeting is Monday 7 PM. Today is Monday 8 PM. 'last Monday' gives last week.
            $todayMeeting = Carbon::today()->setTime($meetingTime->hour, $meetingTime->minute, 0);
            if (now()->dayOfWeek === $carbonDay && now()->greaterThanOrEqualTo($todayMeeting)) {
                $lastMeeting = $todayMeeting;
            }

            // Check if 24 hours have passed since the meeting
            if (now()->diffInHours($lastMeeting) >= 24 && now()->greaterThan($lastMeeting)) {
                
                // Check if we already sent a reminder for this exact meeting (e.g. in the last 6 days)
                if ($cell->last_reminder_sent_at && Carbon::parse($cell->last_reminder_sent_at)->greaterThanOrEqualTo($lastMeeting)) {
                    // Already reminded for this meeting
                    continue;
                }

                // Check if the leader has submitted any report since the meeting started
                // Or simply in the last 6 days to be safe
                $leader = User::where('cell_id', $cell->id)->where('role', 'leader')->first();

                if ($leader) {
                    $hasReport = $leader->reports()
                        ->where('created_at', '>=', $lastMeeting->copy()->startOfDay())
                        ->exists();

                    if (!$hasReport) {
                        // Send notification
                        $leader->notify(new LateReportReminderNotification());
                        
                        // Update cell timestamp so we don't spam them every hour
                        $cell->last_reminder_sent_at = now();
                        $cell->save();

                        $this->info("Reminder sent to {$leader->email} for Cell: {$cell->name}");
                        $count++;
                    }
                }
            }
        }

        $this->info("Finished. Sent {$count} reminders.");
    }
}
