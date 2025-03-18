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
    protected string $description = 'Команда для генерации изображения';
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

        Log::info('chat_id=' . $chatId);

        // Отправляем промежуточный ответ
        $this->telegram->sendMessage([
            'chat_id' => $chatId,
            'text' => 'Начинаем генерацию...',
        ]);

        // Отправляем задачу в очередь
        ProcessImageGeneration::dispatch($chatId, $text);
        Log::info('Отправили на генерацию');

        // Возвращаем успешный ответ (если нужно)
        return response()->json(['status' => 'success']);
    }
}
