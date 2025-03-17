<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Telegram\Bot\Laravel\Facades\Telegram;
use Illuminate\Support\Facades\Log;

class TelegramController extends Controller
{
    public function handle(Request $request)
    {
        // Логируем входящий запрос
        Log::info('Входящий запрос от Telegram:', $request->all());

        try {
            // Обрабатываем команды
            $update = Telegram::commandsHandler(true);

            // Возвращаем успешный ответ
            return response()->json(['status' => 'success']);
        } catch (\Exception $e) {
            // Логируем ошибку
            Log::error('Ошибка при обработке запроса от Telegram: ' . $e->getMessage());

            // Возвращаем ошибку
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }
}
