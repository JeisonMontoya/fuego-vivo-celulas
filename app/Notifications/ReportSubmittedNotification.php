<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\Report;

class ReportSubmittedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public $report;

    /**
     * Create a new notification instance.
     */
    public function __construct(Report $report)
    {
        $this->report = $report;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
                    ->subject('Comprobante de Reporte de Célula')
                    ->greeting('Hola ' . $notifiable->name . ',')
                    ->line('Hemos recibido exitosamente tu reporte de célula para la fecha: ' . $this->report->meeting_date->format('d/m/Y') . '.')
                    ->line('Resumen del reporte:')
                    ->line('- Asistentes regulares: ' . $this->report->attendance_count)
                    ->line('- Invitados: ' . $this->report->guests_count)
                    ->line('- Ofrendas: $' . number_format($this->report->offerings, 2))
                    ->action('Ver mi reporte', url('/dashboard'))
                    ->line('¡Gracias por tu dedicación y excelente trabajo!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'report_id' => $this->report->id,
            'type' => 'report_submitted'
        ];
    }
}
