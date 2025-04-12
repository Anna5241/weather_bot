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

class ProcessImageGeneration implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $chatId;
    protected $prompt;

    public function __construct($chatId, $prompt)
    {
        $this->chatId = $chatId;
        $this->prompt = $prompt;
    }

    public function handle()
    {
        $apiKey = env('FUSION_BRAIN_API_KEY');
        $secretKey = env('FUSION_BRAIN_SECRET_KEY');
        $text2ImageService = new Text2ImageService($apiKey, $secretKey);

        $telegram = new Api(env('TELEGRAM_BOT_TOKEN'));

        try {
            $modelId = $text2ImageService->getModels();
            $requestId = $text2ImageService->generate($this->prompt, $modelId, 1, 1024, 1024, 3); // 3 соответствует стилю "DEFAULT"

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
                        'caption' => "🖼️ <b>Готово!</b>\n".
                            "🎨 <i>".htmlspecialchars($this->prompt)."</i>",
                        'photo' => fopen($tempFile, 'r'),
                        'parse_mode' => 'HTML'
                    ]);

                    unlink($tempFile);
                }
            } else {
                Log::info('Not generated');
                $telegram->sendMessage([
                    'chat_id' => $this->chatId,
                    'text' => "😞 <b>Не удалось сгенерировать изображение</b>\n\n".
                        "Попробуйте изменить запрос или повторить позже.",
                    'parse_mode' => 'HTML'
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Ошибка генерации: '.$e->getMessage());
        }
    }
}
