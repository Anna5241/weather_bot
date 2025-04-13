<?php


namespace App\Console\Commands\Telegram;

use App\Models\WeatherSubscription;
use Telegram\Bot\Commands\Command;
use Telegram\Bot\Commands\CommandInterface;

class UnsubscribeConcreteCityCommand extends Command implements CommandInterface
{
    protected string $name = 'unsubscribe_concrete_city';
    protected string $description = '❌ Отписаться от рассылки погоды одного города';

    public function handle()
    {
        $update = $this->getUpdate();
        $chatId = $update->getMessage()->getChat()->getId();
        // Извлекаем текст сообщения (если нужно)
        $text = $update->getMessage()->getText();

        // Убираем команду /check_weather из текста
        $city = trim(str_replace('/unsubscribe_concrete_city', '', $text));

        if (empty($city)) {
            $this->telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => "ℹ️ Пожалуйста, укажите город для отписки.\nНапример: /unsubscribe_concrete_city Выборг",
            ]);
            return response()->json(['status' => 'success']);
        }

        // Удаляем конкретную подписку
        $deletedCount = WeatherSubscription::where('chat_id', $chatId)
            ->where('city', $city)
            ->delete();

        if ($deletedCount > 0) {
            $this->replyWithMessage([
                'text' => "✅ Вы успешно отписались от рассылки погоды для города <b>{$city}</b>.",
                'parse_mode' => 'HTML'
            ]);

            // Показываем оставшиеся подписки
            $remainingSubscriptions = WeatherSubscription::where('chat_id', $chatId)->count();
            if ($remainingSubscriptions > 0) {
                $this->replyWithMessage([
                    'text' => "📋 У вас осталось подписок: {$remainingSubscriptions}\n".
                        "Для просмотра: /check_subscriptions",
                    'parse_mode' => 'HTML'
                ]);
            }
        } else {
            $this->replyWithMessage([
                'text' => "⚠️ У вас нет активной подписки на город <b>{$city}</b>.\n\n".
                    "Проверьте свои подписки: /check_subscriptions",
                'parse_mode' => 'HTML'
            ]);
        }

    }

}
