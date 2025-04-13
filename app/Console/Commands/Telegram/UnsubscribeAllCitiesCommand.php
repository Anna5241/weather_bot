<?php


namespace App\Console\Commands\Telegram;

use App\Models\WeatherSubscription;
use Telegram\Bot\Commands\Command;
use Telegram\Bot\Commands\CommandInterface;

class UnsubscribeAllCitiesCommand extends Command implements CommandInterface
{
    protected string $name = 'unsubscribe_all_cities';
    protected string $description = '💣 Отписаться от всех рассылок погоды';

    public function handle()
    {
        $update = $this->getUpdate();
        $chatId = $update->getMessage()->getChat()->getId();

        $deleted = WeatherSubscription::where('chat_id', $chatId)->delete();

        $message = $deleted
            ? '❌Вы отписались от рассылки погоды.'
            : '⚠️У вас не было активной подписки.';

        $this->replyWithMessage(['text' => $message]);
    }
}
