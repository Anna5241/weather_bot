<?php

namespace Tests\Unit;

use App\Console\Commands\Telegram\UnsubscribeConcreteCityCommand;
use App\Models\WeatherSubscription;
use PHPUnit\Framework\Attributes\Test;
use Telegram\Bot\Api;
use Telegram\Bot\Objects\Message;
use Telegram\Bot\Objects\Update;
use Tests\TestCase;
use Mockery;

class UnsubscribeConcreteCityCommandTest extends TestCase
{
    private function createCommandWithMocks($telegramMock = null)
    {
        $command = new UnsubscribeConcreteCityCommand();

        if ($telegramMock) {
            $reflection = new \ReflectionClass($command);
            $property = $reflection->getProperty('telegram');
            $property->setAccessible(true);
            $property->setValue($command, $telegramMock);
        }

        return $command;
    }

    private function createUpdate($text, $chatId = 12345)
    {
        return new Update([
            'update_id' => 1,
            'message' => new Message([
                'message_id' => 1,
                'chat' => ['id' => $chatId],
                'date' => time(),
                'text' => $text,
            ]),
        ]);
    }

    #[Test]
    public function it_requires_city_name()
    {
        $telegramMock = Mockery::mock(Api::class);
        $command = $this->createCommandWithMocks($telegramMock);

        // Устанавливаем update через reflection
        $reflection = new \ReflectionClass($command);
        $property = $reflection->getProperty('update');
        $property->setAccessible(true);
        $property->setValue($command, $this->createUpdate('/unsubscribe_concrete_city'));

        // Настраиваем ожидания
        $telegramMock->shouldReceive('sendMessage')
            ->once()
            ->with([
                'chat_id' => 12345,
                'text' => "ℹ️ Пожалуйста, укажите город для отписки.\nНапример: /unsubscribe_concrete_city Выборг",
            ]);

        $command->handle();

        // Проверяем, что все ожидания Mockery выполнены
        $this->assertTrue(Mockery::getContainer()->mockery_getExpectationCount() > 0);
        Mockery::close();
    }

    #[Test]
    public function it_unsubscribes_from_city_successfully()
    {
        $telegramMock = Mockery::mock(Api::class);
        $command = $this->createCommandWithMocks($telegramMock);

        // Мокаем модель WeatherSubscription
        $weatherSubscriptionMock = Mockery::mock('overload:' . WeatherSubscription::class);
        $weatherSubscriptionMock->shouldReceive('where->where->delete')->andReturn(1);
        $weatherSubscriptionMock->shouldReceive('where->count')->andReturn(0);

        // Устанавливаем update
        $reflection = new \ReflectionClass($command);
        $property = $reflection->getProperty('update');
        $property->setAccessible(true);
        $property->setValue($command, $this->createUpdate('/unsubscribe_concrete_city Moscow'));

        // Ожидаем основной вызов
        $telegramMock->shouldReceive('sendMessage')
            ->once()
            ->with([
                'chat_id' => 12345,
                'text' => '✅ Вы успешно отписались от рассылки погоды для города <b>Moscow</b>.',
                'parse_mode' => 'HTML'
            ]);

        $command->handle();

        // Проверяем, что все ожидания выполнены
        Mockery::close();

        // Явное утверждение для PHPUnit
        $this->assertTrue(true);
    }

    #[Test]
    public function it_shows_error_when_no_subscription_exists()
    {
        $telegramMock = Mockery::mock(Api::class);
        $command = $this->createCommandWithMocks($telegramMock);

        // Мокаем модель WeatherSubscription
        $weatherSubscriptionMock = Mockery::mock('overload:' . WeatherSubscription::class);
        $weatherSubscriptionMock->shouldReceive('where->where->delete')->andReturn(0);

        // Устанавливаем update
        $reflection = new \ReflectionClass($command);
        $property = $reflection->getProperty('update');
        $property->setAccessible(true);
        $property->setValue($command, $this->createUpdate('/unsubscribe_concrete_city London'));

        $telegramMock->shouldReceive('sendMessage')
            ->once()
            ->with([
                'chat_id' => 12345,
                'text' => "⚠️ У вас нет активной подписки на город <b>London</b>.\n\nПроверьте свои подписки: /check_subscriptions",
                'parse_mode' => 'HTML'
            ]);

        $command->handle();

        $this->assertTrue(Mockery::getContainer()->mockery_getExpectationCount() > 0,
            'Не все ожидания Mockery были выполнены');
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
