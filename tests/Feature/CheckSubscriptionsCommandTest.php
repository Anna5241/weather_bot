<?php

namespace Tests\Feature;

use App\Console\Commands\Telegram\CheckSubscriptionsCommand;
use App\Models\WeatherSubscription;
use Illuminate\Support\Collection;
use Mockery;
use Telegram\Bot\Objects\Update;
use Tests\TestCase;

class CheckSubscriptionsCommandTest extends TestCase
{
    /**
     * Тест команды с активными подписками.
     *
     * @return void
     */
    public function test_check_subscriptions_with_subscriptions()
    {
        // Arrange: Подготовка данных
        $chatId = 12345;
        $subscriptions = [
            (object)['city' => 'Moscow'],
            (object)['city' => 'London'],
        ];

        // Мокаем WeatherSubscription, заменяя статический вызов
        $weatherSubscriptionMock = Mockery::mock('alias:App\Models\WeatherSubscription');
        $weatherSubscriptionMock->shouldReceive('where')
            ->with('chat_id', $chatId)
            ->andReturnSelf();
        $weatherSubscriptionMock->shouldReceive('get')
            ->andReturn(collect($subscriptions));

        // Создаем частичный мок для команды, чтобы можно было подменить getUpdate и replyWithMessage
        $command = Mockery::mock(CheckSubscriptionsCommand::class)->makePartial();

        // Мокаем объект Update
        $update = Mockery::mock(Update::class);
        $update->shouldReceive('getMessage->getChat->getId')
            ->andReturn($chatId);

        // Подставляем мок Update
        $command->shouldReceive('getUpdate')->andReturn($update);

        // Ожидаем вызов replyWithMessage с нужными параметрами
        $command->shouldReceive('replyWithMessage')
            ->once()
            ->with(Mockery::on(function ($argument) use ($subscriptions) {
                $text = $argument['text'];
                $parseMode = $argument['parse_mode'];

                // Проверяем, что parse_mode установлен в HTML
                if ($parseMode !== 'HTML') {
                    return false;
                }

                // Проверяем наличие заголовка и городов в сообщении
                if (strpos($text, '📋 <b>Ваши активные подписки:</b>') === false) {
                    return false;
                }
                foreach ($subscriptions as $subscription) {
                    if (strpos($text, "🌆 Город: <b>{$subscription->city}</b>") === false) {
                        return false;
                    }
                }

                // Проверяем наличие инструкций
                return strpos($text, '❌Для отмены всех подписок: /unsubscribe_all_cities') !== false &&
                    strpos($text, '💣Для отмены подписки на конкретный город: /unsubscribe_concrete_city') !== false;
            }));

        // Act: Выполняем команду
        $command->handle();

        // Чтобы PHPUnit не жаловался на отсутствие утверждений, можно добавить следующую строку:
        $this->addToAssertionCount(1);
    }
}
