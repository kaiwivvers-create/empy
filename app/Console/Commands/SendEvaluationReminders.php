<?php

namespace App\Console\Commands;

use App\Models\EvaluationPeriod;
use App\Support\ReminderSender;
use Illuminate\Console\Command;

class SendEvaluationReminders extends Command
{
    protected $signature = 'evaluations:remind {--period= : Evaluation period ID}';

    protected $description = 'Send reminder emails to managers with pending evaluations';

    public function handle(): int
    {
        $period = $this->option('period')
            ? EvaluationPeriod::find($this->option('period'))
            : EvaluationPeriod::where('status', 'active')->latest('start_date')->first();

        if (! $period) {
            $this->error(__('app.reminders.no_period'));

            return self::FAILURE;
        }

        $sent = ReminderSender::send($period);

        if ($sent->isEmpty()) {
            $this->info(__('app.reminders.none_pending'));

            return self::SUCCESS;
        }

        $this->table(
            [__('app.common.manager'), __('app.dashboard.pending')],
            $sent->map(fn (array $row) => [$row['manager'], $row['pending']])->all(),
        );

        $this->info(__('app.reminders.sent', ['count' => $sent->count(), 'period' => $period->name]));

        return self::SUCCESS;
    }
}
