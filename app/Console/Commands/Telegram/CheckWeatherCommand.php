<?php

namespace App\Console\Commands\Telegram;
use App\Jobs\ProcessImageGenerationWithWeather;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Telegram\Bot\Commands\Command;
use Telegram\Bot\Commands\CommandInterface;
use Telegram\Bot\Api;



class CheckWeatherCommand extends Command implements CommandInterface
{

    protected string $name = 'check_weather';
    protected string $description = 'Получить текущую погоду';

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

        // Извлекаем текст сообщения (например, город)
        $text = $update->getMessage()->getText();

        // Убираем команду /check_weather из текста
        $city = trim(str_replace('/check_weather', '', $text));

        if (empty($city)) {
            $this->telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => 'Пожалуйста, укажите город. Например: /check_weather Москва',
            ]);
            return response()->json(['status' => 'success']);
        }

        // Получаем погоду
        $weather = $this->getWeather($city);

        if ($weather) {
            $message = "Погода в городе {$city}:\n";
            $message .= "Температура: {$weather['temp_c']}°C\n";
            $message .= "Состояние: {$weather['condition']['text']}\n";
            $message .= "Влажность: {$weather['humidity']}%\n";
            $message .= "Скорость ветра: {$weather['wind_kph']} км/ч";
        } else {
            $message = 'Не удалось получить данные о погоде. Проверьте название города.';
        }


        $text = $weather['condition']['text'];

        Log::info('chat_id=' . $chatId);

        // Отправляем промежуточный ответ
        $this->telegram->sendMessage([
            'chat_id' => $chatId,
            'text' => 'Проверяем погоду, подождите, пожалуйста...',
        ]);

        // Отправляем задачу в очередь
        ProcessImageGenerationWithWeather::dispatch($chatId, $text, $message);
        Log::info('Отправили на генерацию');

        // Возвращаем успешный ответ (если нужно)
        return response()->json(['status' => 'success']);
    }

    protected function getWeather($city)
    {
        $apiKey = env('WEATHER_API_KEY');
        $url = "http://api.weatherapi.com/v1/current.json?key={$apiKey}&q={$city}&lang=ru";

        try {
            $response = Http::get($url);

            if ($response->successful()) {
                return $response->json()['current'];
            } else {
                Log::error('Ошибка при запросе погоды:', [
                    'city' => $city,
                    'response' => $response->body(),
                ]);
                return null;
            }
        } catch (\Exception $e) {
            Log::error('Ошибка при запросе погоды:', [
                'city' => $city,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

}
