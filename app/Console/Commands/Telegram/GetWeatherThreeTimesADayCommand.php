<?php

namespace App\Console\Commands\Telegram;

use App\Models\WeatherSubscription;
use Telegram\Bot\Commands\Command;
use Telegram\Bot\Commands\CommandInterface;

class GetWeatherThreeTimesADayCommand extends Command implements CommandInterface
{
    protected string $name = 'get_weather_three_times_a_day';
    protected string $description = '🔔 Подписаться на рассылку погоды (3 раза в день)';

    public function handle()
    {
        $update = $this->getUpdate();
        $chatId = $update->getMessage()->getChat()->getId();
        $text = $update->getMessage()->getText();

        $city = trim(str_replace('/get_weather_three_times_a_day', '', $text));

        if (empty($city)) {
            $this->replyWithMessage([
                'text' => 'ℹ️ Пожалуйста, укажите город. Например: /get_weather_three_times_a_day Москва',
            ]);
            return;
        }

        $subscriptionExists = WeatherSubscription::where('chat_id', $chatId)
            ->where('city', $city)
            ->exists();


        // Проверка существующей подписки на город
        $existingSubscription = WeatherSubscription::where('chat_id', $chatId)
            ->where('city', $city)
            ->first();

        if ($existingSubscription) {
            $this->replyWithMessage([
                'text' => "⚠️ Вы уже подписаны на рассылку для города <b>{$city}</b> (ID: {$existingSubscription->id}).\n\n".
                    "Используйте /check_subscriptions для просмотра всех подписок",
                'parse_mode' => 'HTML'
            ]);
            return;
        }

        // Создание новой подписки
        $subscription = WeatherSubscription::create([
            'chat_id' => $chatId,
            'city' => $city
        ]);

        $this->replyWithMessage([
            'text' => "✅ Вы подписались на рассылку погоды для города {$city}.\n\n" .
                "Вы будете получать уведомления в 7:00, 14:00 и 16:00.\n\n" .
                "Всего подписок: " . WeatherSubscription::where('chat_id', $chatId)->count() . "\n" .
                "🔍 Для просмотра подписок: /check_subscriptions\n" .
                "🔕Для отмены всех подписок: /unsubscribe_all_cities\n".
                "🔕Для отмены подписки на конкретный город: /unsubscribe_concrete_city",
            'parse_mode' => 'HTML'
        ]);


    }
}
