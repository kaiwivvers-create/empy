<?php

namespace App\Support;

use App\Models\Employee;
use App\Models\Evaluation;
use App\Models\EvaluationPeriod;
use App\Notifications\EvaluationReminderNotification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Notification;

class ReminderSender
{
    /**
     * Notify managers who still have unevaluated team members.
     *
     * @return Collection<int, array{manager: string, pending: int}>
     */
    public static function send(EvaluationPeriod $period): Collection
    {
        $submittedEmployeeIds = Evaluation::where('period_id', $period->id)
            ->where('status', 'submitted')
            ->pluck('employee_id')
            ->all();

        $managers = Employee::query()
            ->active()
            ->whereNotNull('user_id')
            ->whereHas('subordinates')
            ->with(['user', 'subordinates' => fn ($query) => $query->active()->evaluable()])
            ->get();

        $sent = collect();

        foreach ($managers as $manager) {
            $pending = $manager->subordinates
                ->whereNotIn('id', $submittedEmployeeIds)
                ->values();

            if ($pending->isEmpty() || ! $manager->user) {
                continue;
            }

            Notification::send(
                $manager->user,
                new EvaluationReminderNotification($period, $pending->count(), $pending->pluck('name')->all()),
            );

            $sent->push(['manager' => $manager->name, 'pending' => $pending->count()]);
        }

        return $sent;
    }
}
