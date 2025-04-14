<?php

namespace App\Jobs;

use App\Services\Text2ImageService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Telegram\Bot\Api;

class ProcessImageGenerationWithWeather implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $chatId;
    protected $city;

    public function __construct($chatId, $city)
    {
        $this->chatId = $chatId;
        $this->city = $city;
    }

    public function handle()
    {
        $apiKey = env('FUSION_BRAIN_API_KEY');
        $secretKey = env('FUSION_BRAIN_SECRET_KEY');
        $text2ImageService = new Text2ImageService($apiKey, $secretKey);

        $telegram = new Api(env('TELEGRAM_BOT_TOKEN'));

        // Получаем погоду
        $weather = $this->getWeather($this->city);

        if ($weather) {
            $message = "🌡️ Температура: <b>{$weather['temp_c']}°C</b>\n";
            $message .= "☁️ Состояние: <b>{$weather['condition']['text']}</b>\n";
            $message .= "💧 Влажность: <b>{$weather['humidity']}%</b>\n";
            $message .= "🌬️ Ветер: <b>{$weather['wind_kph']} км/ч</b>";
        } else {
            $telegram->sendMessage([
                'chat_id' => $this->chatId,
                'text' => "❌ <b>Не удалось получить погоду для {$this->city}</b>\n\nПроверьте название города и попробуйте снова.",
                'parse_mode' => 'HTML'
            ]);
            return;
        }



        $prompt = $weather['condition']['text'];

        try {
            $modelId = $text2ImageService->getModels();
            $requestId = $text2ImageService->generate($prompt, $modelId, 1, 1024, 1024, 3); // 3 соответствует стилю "DEFAULT"

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
                        'caption' => "🖼️ <b>Готово! Погода в городе {$this->city}</b>\n\n{$message}",
                        'photo' => fopen($tempFile, 'r'),
                        'parse_mode' => 'HTML'
                    ]);

                    unlink($tempFile);
                }
            } else {
                Log::info('Not generated');
                $telegram->sendMessage([
                    'chat_id' => $this->chatId,
                    'text' => "🖼️ <b>Погода в городе {$this->city}/</b>\n\n{$message}\n\n😞 <i>Не удалось сгенерировать изображение</i>",
                    'parse_mode' => 'HTML'
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Ошибка при отправке сообщения в Telegram', [
                'chat_id' => $this->chatId,
                'error' => $e->getMessage(),
                'exception' => $e,
                'trace' => $e->getTraceAsString() // полный трейс ошибки
            ]);
        }
    }
    protected function getWeather($city)
    {
        $apiKey = env('WEATHER_API_KEY');
        $url = "http://api.weatherapi.com/v1/current.json?key={$apiKey}&q={$city}&lang=ru";

        try {
            $response = Http::get($url);

            if ($response->successful()) {
                return $response->json()['current'];
            }
            Log::error('Ошибка при запросе погоды:', [
                'city' => $city,
                'response' => $response->body(),
            ]);
            return null;
            
        } catch (\Exception $e) {
            Log::error('Ошибка при запросе погоды:', [
                'city' => $city,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }
}
