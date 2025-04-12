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
    protected string $description = '⛅ Узнать текущую погоду';

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
                'text' => "🌍 Пожалуйста, укажите город.\nПример: /check_weather Москва",
            ]);
            return response()->json(['status' => 'success']);
        }
        if ($city === "Кайфоград") {
            $city = "Невинномысск";
        }






        // Отправляем промежуточный ответ
        $this->telegram->sendMessage([
            'chat_id' => $chatId,
            'text' => "⏳ <b>Проверяем текущую погоду в городе {$city}...</b>\n\n🌤️ Результат появится здесь через несколько секунд!",
            'parse_mode' => 'HTML'
        ]);

        // Отправляем задачу в очередь
        ProcessImageGenerationWithWeather::dispatch($chatId, $city);
        Log::info('Отправили на генерацию');

        // Возвращаем успешный ответ (если нужно)
        return response()->json(['status' => 'success']);
    }



}
