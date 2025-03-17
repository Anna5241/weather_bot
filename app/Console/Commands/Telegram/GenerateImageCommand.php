<?php

namespace App\Console\Commands\Telegram;

use Illuminate\Support\Facades\Log;
use Telegram\Bot\Commands\Command;
use Telegram\Bot\Commands\CommandInterface;
use App\Services\Text2ImageService;
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

        Log::info('chat_id='.$chatId);

        $apiKey = env('FUSION_BRAIN_API_KEY');
        $secretKey = env('FUSION_BRAIN_SECRET_KEY');

        $text2ImageService = new Text2ImageService($apiKey, $secretKey);
        Log::info('Создали Text2ImageService');

        try {
            $modelId = $text2ImageService->getModels();
            $requestId = $text2ImageService->generate('apple', $modelId, 1, 1024, 1024, 3); // 3 соответствует стилю "DEFAULT"
            Log::info('Начинаем генерацию');

            $images = $text2ImageService->checkGeneration($requestId);

            if ($images) {
                Log::info('Сгенерировали');
                foreach ($images as $image) {
                    // Декодируем base64
                    $imageData = base64_decode($image);

                    // Создаем временный файл
                    $tempFile = tempnam(sys_get_temp_dir(), 'image') . '.jpg'; // Укажите правильное расширение файла
                    file_put_contents($tempFile, $imageData);

                    // Отправляем фото
                    Log::info('Отправляем фото');
                    $this->telegram->sendPhoto([
                        'chat_id' => $chatId,
                        'photo' => fopen($tempFile, 'r'), // Отправляем файл как поток данных
                    ]);
                    Log::info('Отправили фото');

                    // Удаляем временный файл
                    unlink($tempFile);
                }
            } else {
                Log::info('Не сгенерировали');
                $this->telegram->sendMessage([
                    'chat_id' => $chatId,
                    'text' => 'Не удалось сгенерировать изображение.',
                ]);
            }
        } catch (\Exception $e) {
            $this->telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => 'Произошла ошибка: ' . $e->getMessage(),
            ]);
        }
    }
}
