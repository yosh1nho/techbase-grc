<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use App\Http\Controllers\AssetController; // 👈 IMPORTAÇÃO QUE FALTAVA!

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// 👈 CHAMADA CORRETA E BLINDADA
Schedule::call(function () {
    app(AssetController::class)->syncWazuh();
})->hourly();