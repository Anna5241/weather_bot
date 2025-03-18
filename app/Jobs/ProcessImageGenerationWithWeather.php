<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Services\Text2ImageService;
use Illuminate\Support\Facades\Log;
use Telegram\Bot\Api;

class ProcessImageGenerationWithWeather implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $chatId;
    protected $weather;
    protected $message;

    public function __construct($chatId, $weather, $message)
    {
        $this->chatId = $chatId;
        $this->weather = $weather;
        $this->message = $message;
    }

    public function handle()
    {
        $apiKey = env('FUSION_BRAIN_API_KEY');
        $secretKey = env('FUSION_BRAIN_SECRET_KEY');
        $text2ImageService = new Text2ImageService($apiKey, $secretKey);

        $telegram = new Api(env('TELEGRAM_BOT_TOKEN'));

        try {
            $modelId = $text2ImageService->getModels();
            $requestId = $text2ImageService->generate($this->weather, $modelId, 1, 1024, 1024, 3); // 3 соответствует стилю "DEFAULT"

            Log::info('Начинаем генерацию');
            $images = $text2ImageService->checkGeneration($requestId);
            Log::info('Закончили генерацию');

            if ($images) {
                foreach ($images as $image) {
                    $imageData = base64_decode($image);
                    $tempFile = tempnam(sys_get_temp_dir(), 'image') . '.jpg';
                    file_put_contents($tempFile, $imageData);

                    $telegram->sendPhoto([
                        'chat_id' => $this->chatId,
                        'caption' => $this->message,
                        'photo' => fopen($tempFile, 'r'),
                    ]);

                    unlink($tempFile);
                }
            } else {
                Log::info('Not generated');
                $telegram->sendMessage([
                    'chat_id' => $this->chatId,
                    'text' => $this->message,
                ]);
            }
        } catch (\Exception $e) {
            $telegram->sendMessage([
                'chat_id' => $this->chatId,
                'text' => 'Произошла ошибка: ' . $e->getMessage(),
            ]);
        }
    }
}
