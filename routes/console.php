<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

/*
|--------------------------------------------------------------------------
| Console Routes
|--------------------------------------------------------------------------
|
| This file is where you may define all of your Closure based console
| commands. Each Closure is bound to a command instance allowing a
| simple approach to interacting with each command's IO methods.
|
*/

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('kanban:arquivar-concluidas')->dailyAt('02:00');
Schedule::command('logs:limpar-erros')->dailyAt('03:00');
Schedule::command('mensagens:limpar-logs')->dailyAt('03:30');
Schedule::command('os:avisar-prazos')->dailyAt('08:00');
