<?php

namespace App\Console\Commands;

use App\Services\DailyEmails;
use Carbon\CarbonImmutable;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('emails:send-daily')]
#[Description('Queue the check-in reminders and on this day emails that are due this hour')]
class SendDailyEmails extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(DailyEmails $emails): int
    {
        $sent = $emails->sendDue(CarbonImmutable::now());

        $this->info("Queued {$sent} ".str('email')->plural($sent).'.');

        return self::SUCCESS;
    }
}
