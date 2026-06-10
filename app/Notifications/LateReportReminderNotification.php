<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LateReportReminderNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct()
    {
        //
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
                    ->error()
                    ->subject('Aviso de Retraso: Reporte de Célula Pendiente')
                    ->greeting('Hola ' . $notifiable->name . ',')
                    ->line('Este es un aviso automático para informarte que han pasado más de 24 horas desde el horario programado para tu célula y el sistema aún no registra el reporte de esta semana.')
                    ->line('Recuerda que es vital mantener la información actualizada para el correcto seguimiento y apoyo de nuestros miembros.')
                    ->action('Enviar mi reporte ahora', url('/reports/create'))
                    ->line('Si ya enviaste el reporte o tuviste algún inconveniente, por favor contacta a tu supervisor.');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'late_report_reminder'
        ];
    }
}
