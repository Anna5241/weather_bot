<?php

namespace App\Console\Commands\Telegram;

use App\Models\WeatherSubscription;
use Telegram\Bot\Commands\Command;
use Telegram\Bot\Commands\CommandInterface;

class CheckSubscriptionsCommand extends Command implements CommandInterface
{
    protected string $name = 'check_subscriptions';
    protected string $description = '📋 Проверить мои подписки на погоду';

    public function handle()
    {
        $update = $this->getUpdate();
        $chatId = $update->getMessage()->getChat()->getId();

        // Получаем все подписки пользователя
        $subscriptions = WeatherSubscription::where('chat_id', $chatId)->get();

        if ($subscriptions->isEmpty()) {
            $this->replyWithMessage([
                'text' => 'У вас нет активных подписок на погоду.',
                'parse_mode' => 'HTML'
            ]);
            return;
        }

        $message = "📋 <b>Ваши активные подписки:</b>\n\n";

        foreach ($subscriptions as $subscription) {
            $message .= "🌆 Город: <b>{$subscription->city}</b>\n";
        }

        $message .= "\n❌Для отмены всех подписок: /unsubscribe_all_cities\n".
                "💣Для отмены подписки на конкретный город: /unsubscribe_concrete_city";

        $this->replyWithMessage([
            'text' => $message,
            'parse_mode' => 'HTML'
        ]);
    }
}
