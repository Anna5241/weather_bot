<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Telegram\Bot\Laravel\Facades\Telegram;
use Illuminate\Support\Facades\Log;
use Telegram\Bot\Objects\Update;

class TelegramController extends Controller
{
    public function handle(Request $request)
    {
        // Логируем входящий запрос
        Log::info('Входящий запрос от Telegram:', $request->all());

        try {
            // Обрабатываем команды
            $update = Telegram::commandsHandler(true);

            // Если команда не была обработана
            if (!$this->wasCommandProcessed($update)) {
                $this->handleUnknownCommand($update);
            }

            // Возвращаем успешный ответ
            return response()->json(['status' => 'success']);
        } catch (\Exception $e) {
            // Логируем ошибку
            Log::error('Ошибка при обработке запроса от Telegram: ' . $e->getMessage());

            // Возвращаем ошибку
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }
    /**
     * Проверяет, была ли обработана какая-либо команда
     */
    protected function wasCommandProcessed($update): bool
    {
        // Если это объект Update и содержит сообщение с сущностью команды
        if ($update instanceof Update && $update->getMessage() && $update->getMessage()->get('entities')) {
            foreach ($update->getMessage()->get('entities') as $entity) {
                if ($entity['type'] === 'bot_command') {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Обрабатывает неизвестные команды
     */
    protected function handleUnknownCommand(Update $update): void
    {
        $message = $update->getMessage();
        $chatId = $message->getChat()->getId();
        $text = $message->getText();

        // Проверяем, начинается ли текст с "/" (возможно, это неизвестная команда)
        if (str_starts_with($text, '/')) {
            Telegram::sendMessage([
                'chat_id' => $chatId,
                'text' => "Извините, я не знаю такой команды.\nНажмите /help для списка доступных команд.",
            ]);
        } else {
            // Если это просто текст, а не команда
            Telegram::sendMessage([
                'chat_id' => $chatId,
                'text' => "Я понимаю только команды.\nНажмите /help для списка доступных команд.",
            ]);
        }
    }
}
