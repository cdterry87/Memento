<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('emails:send-daily')->hourly()->withoutOverlapping();
