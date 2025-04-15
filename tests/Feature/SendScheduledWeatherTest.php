<?php

namespace Tests\Feature;

use App\Models\WeatherSubscription;
use Illuminate\Support\Collection;
use Mockery;
use Telegram\Bot\Laravel\Facades\Telegram;
use Telegram\Bot\Objects\Update;
use Tests\TestCase;

class SendScheduledWeatherTest extends TestCase
{
    /**
     * Тест отправки погоды с подписками.
     */
    public function testSendScheduledWeather()
    {
        // Arrange: Подготовка тестовых данных
        $subscriptions = [
            (object) ['chat_id' => 123, 'city' => 'Moscow'],
            (object) ['chat_id' => 456, 'city' => 'London'],
        ];

        // Мокируем WeatherSubscription::all()
        $weatherSubscriptionMock = Mockery::mock('alias:App\Models\WeatherSubscription');
        $weatherSubscriptionMock->shouldReceive('all')
            ->once()
            ->andReturn(collect($subscriptions));

        // Мокируем Telegram::triggerCommand()
        $telegramMock = Mockery::mock('alias:Telegram\Bot\Laravel\Facades\Telegram');
        $telegramMock->shouldReceive('triggerCommand')
            ->twice()
            ->andReturnUsing(function ($command, $update) use ($subscriptions) {
                static $index = 0;

                // Проверяем имя команды
                $this->assertEquals('check_weather', $command);

                // Проверяем, что $update — объект класса Update
                $this->assertInstanceOf(Update::class, $update);

                // Получаем сообщение из объекта Update
                $message = $update->getMessage();

                // Проверяем chat_id
                $this->assertEquals($subscriptions[$index]->chat_id, $message->getChat()->getId());

                // Проверяем текст команды
                $this->assertEquals('/check_weather ' . $subscriptions[$index]->city, $message->getText());

                $index++;
            });

        // Act: Выполняем команду и проверяем результат
        $result = $this->artisan('weather:send');

        // Assert: Проверяем, что команда завершилась успешно
        $result->assertExitCode(0);
    }

    /**
     * Тест отправки погоды без подписок.
     */
    public function testSendScheduledWeatherWithNoSubscriptions()
    {
        // Arrange: Пустая коллекция подписок
        $weatherSubscriptionMock = Mockery::mock('alias:App\Models\WeatherSubscription');
        $weatherSubscriptionMock->shouldReceive('all')
            ->once()
            ->andReturn(collect([]));

        // Мокируем Telegram::triggerCommand, ожидаем, что он не будет вызван
        $telegramMock = Mockery::mock('alias:Telegram\Bot\Laravel\Facades\Telegram');
        $telegramMock->shouldReceive('triggerCommand')
            ->never();

        // Act: Выполняем команду и проверяем результат
        $result = $this->artisan('weather:send');

        // Assert: Проверяем, что команда завершилась успешно
        $result->assertExitCode(0);
    }

    /**
     * Очистка моков после каждого теста.
     */
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
