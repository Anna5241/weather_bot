<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TelegramController;

Route::post('/webhook', [TelegramController::class, 'handle']);
