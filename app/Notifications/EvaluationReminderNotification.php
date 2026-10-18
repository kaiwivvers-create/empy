<?php

namespace App\Notifications;

use App\Models\EvaluationPeriod;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class EvaluationReminderNotification extends Notification
{
    use Queueable;

    /**
     * @param  array<int, string>  $employeeNames
     */
    public function __construct(
        public EvaluationPeriod $period,
        public int $pendingCount,
        public array $employeeNames = [],
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject(__('app.reminders.mail_subject', ['period' => $this->period->name]))
            ->greeting(__('app.reminders.mail_greeting', ['name' => $notifiable->name]))
            ->line(__('app.reminders.mail_intro', [
                'count' => $this->pendingCount,
                'period' => $this->period->name,
            ]));

        foreach (array_slice($this->employeeNames, 0, 10) as $name) {
            $mail->line('• '.$name);
        }

        return $mail
            ->action(__('app.reminders.mail_action'), route('evaluations.create'))
            ->line(__('app.reminders.mail_footer'));
    }
}
