<?php

namespace App\Console\Commands;

use App\Models\WeatherSubscription;
use Illuminate\Console\Command;
use Telegram\Bot\Laravel\Facades\Telegram;
use Telegram\Bot\Objects\Update;


class SendScheduledWeather extends Command
{
    protected $signature = 'weather:send';
    protected $description = 'Send weather updates to subscribed users';

    public function handle()
    {
        $subscriptions = WeatherSubscription::all();

        foreach ($subscriptions as $subscription) {
            // Создаём фейковое Update-сообщение
            $update = new Update([
                'update_id' => rand(1, 100000),
                'message' => [
                    'message_id' => rand(1, 1000),
                    'chat' => [
                        'id' => $subscription->chat_id,
                        'type' => 'private'
                    ],
                    'text' => '/check_weather ' . $subscription->city,
                    'date' => time(),
                ]
            ]);

            Telegram::triggerCommand('check_weather', $update);
        }
    }
}
