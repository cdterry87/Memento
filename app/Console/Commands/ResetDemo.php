<?php

namespace App\Console\Commands;

use App\Services\DemoAccount;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use RuntimeException;

#[Signature('demo:reset')]
#[Description('Create the demo account, or restore it with fresh sample entries')]
class ResetDemo extends Command
{
    public function handle(DemoAccount $demo): int
    {
        if (! config('memento.demo_mode')) {
            $this->components->error('Demo mode is off. Set DEMO_MODE=true to use the demo account.');

            return self::FAILURE;
        }

        try {
            $user = $demo->reset();
        } catch (RuntimeException $exception) {
            $this->components->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->components->info("The demo account {$user->email} has been reset with {$user->memories()->count()} entries.");

        return self::SUCCESS;
    }
}
