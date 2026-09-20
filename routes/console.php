<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Needs one cron entry on the server: * * * * * cd /path && php artisan schedule:run
Schedule::command('orders:sweep-expired')->everyTwoMinutes()->withoutOverlapping();
Schedule::command('purchases:prune-pending')->daily();
