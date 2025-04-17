<?php

namespace Tests\Feature;

use App\Console\Commands\Telegram\CheckSubscriptionsCommand;
use App\Models\WeatherSubscription;
use Mockery;
use Telegram\Bot\Objects\Update;
use Tests\TestCase;

class CheckSubscriptionsCommandTestWithoutSub extends TestCase
{
    /**
     * Тест команды без подписок.
     *
     * @return void
     */
    public function test_check_subscriptions_with_no_subscriptions()
    {
        // Arrange: Подготовка данных
        $chatId = 12345;

        // Мокаем WeatherSubscription
        $weatherSubscriptionMock = Mockery::mock('alias:App\Models\WeatherSubscription');
        $weatherSubscriptionMock->shouldReceive('where')
            ->with('chat_id', $chatId)
            ->andReturnSelf();
        $weatherSubscriptionMock->shouldReceive('get')
            ->andReturn(collect([]));

        // Создаем частичный мок для команды
        $command = Mockery::mock(CheckSubscriptionsCommand::class)->makePartial();

        // Мокаем Update
        $update = Mockery::mock(Update::class);
        $update->shouldReceive('getMessage->getChat->getId')
            ->andReturn($chatId);

        // Подставляем мок Update
        $command->shouldReceive('getUpdate')->andReturn($update);

        // Ожидаем вызов replyWithMessage с корректным сообщением об отсутствии подписок
        $command->shouldReceive('replyWithMessage')
            ->once()
            ->with(Mockery::on(function ($argument) {
                return $argument['text'] === '⚠️У вас нет активных подписок на погоду.' &&
                    $argument['parse_mode'] === 'HTML';
            }));

        // Act: Выполняем команду
        $command->handle();

        // Добавляем фиктивное утверждение для прохождения теста
        $this->addToAssertionCount(1);
    }
}
