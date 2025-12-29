<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Console\Scheduling\Schedule;
use Carbon\Carbon;

// Artisan::command('inspire', function () {
//     $this->comment(Inspiring::quote());
// })->purpose('Display an inspiring quote')->hourly();

$schedule = app(Schedule::class);


$schedule->command('app:reset-users-active')
    ->timezone('America/Lima')
    ->monthlyOn(5, '00:10');

// $schedule->command('app:range-list-bulk')
//     ->timezone('America/Lima')
//     ->at('6:00');

// $schedule->command('app:range-list-bulk')
//     ->timezone('America/Lima')
//     ->at('12:00');

// $schedule->command('app:range-list-bulk')
//     ->timezone('America/Lima')
//     ->at('18:00');

// $schedule->command('app:range-list-bulk')
//     ->timezone('America/Lima')
//     ->at('23:00');

$schedule->command('app:range-list-bulk')
    ->timezone('America/Lima')
    ->hourly()
    ->skip(function () { // 2. Pero se saltará si...
        $now = Carbon::now();

        // Condición 1: Es el último día del mes Y son las 23:00 hrs
        // $lastDayMonth = $now->day == $now->daysInMonth && $now->hour == 23;

        // Condición 2: Es el primer día del mes Y son las 00:00 hrs
        $initDayMonth = $now->day == 1 && $now->hour == 0;

        // Si CUALQUIERA de las dos condiciones es verdadera, 
        // la tarea NO se ejecutará (return true = skip)
        return $initDayMonth;
    });

$schedule->command('app:range-list-bulk')
    ->timezone('America/Lima')
    ->at('23:30');

$schedule->command('app:user-temp-send-email')
    ->timezone('America/Lima')
    ->everyThirtyMinutes();