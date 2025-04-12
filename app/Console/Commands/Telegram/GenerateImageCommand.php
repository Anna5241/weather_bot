<?php

namespace App\Console\Commands\Telegram;

use Illuminate\Support\Facades\Log;
use Telegram\Bot\Commands\Command;
use Telegram\Bot\Commands\CommandInterface;
use App\Jobs\ProcessImageGeneration;
use Telegram\Bot\Api;

class GenerateImageCommand extends Command implements CommandInterface
{
    protected string $name = 'generate_image';
    protected string $description = '🎨 Сгенерировать изображение';
    protected ?Api $telegram;

    public function __construct()
    {
        $this->telegram = new Api(env('TELEGRAM_BOT_TOKEN'));
    }

    public function handle()
    {
        // Получаем объект Update
        $update = $this->getUpdate();

        // Извлекаем chatId из сообщения
        $chatId = $update->getMessage()->getChat()->getId();

        // Извлекаем текст сообщения (если нужно)
        $text = $update->getMessage()->getText();

        // Убираем команду /check_weather из текста
        $prompt = trim(str_replace('/generate_image', '', $text));

        if (empty($prompt)) {
            $this->telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => "🖌️ Пожалуйста, укажите описание изображения.\n\n".
                        "🌄  Пример: /generate_image закат над Кайфоградом\n",
            ]);
            return response()->json(['status' => 'success']);
        }

        // Отправляем промежуточный ответ
        $this->telegram->sendMessage([
            'chat_id' => $chatId,
            'text' => "🎨 Принято! Начинаю генерацию изображения по запросу:\n".
                "\"<i>{$prompt}</i>\"\n\n".
                "⏳ Это может занять некоторое время...",
            'parse_mode' => 'HTML',
        ]);

        // Отправляем задачу в очередь
        ProcessImageGeneration::dispatch($chatId, $prompt);
        Log::info('Отправили на генерацию');

        // Возвращаем успешный ответ (если нужно)
        return response()->json(['status' => 'success']);
    }
}
