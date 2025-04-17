<?php

namespace Tests\Feature;

use App\Console\Commands\Telegram\GetWeatherThreeTimesADayCommand;
use App\Models\WeatherSubscription;
use Mockery;
use Telegram\Bot\Objects\Update;
use Tests\TestCase;

class GetWeatherThreeTimesADayCommandTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * Сценарий: Пользователь не указал город.
     */
    public function test_empty_city()
    {
        // Arrange
        $chatId = 11111;
        // Текст сообщения содержит только команду без города
        $messageText = '/get_weather_three_times_a_day';

        // Мокаем Update: получение chatId и текста сообщения
        $update = Mockery::mock(Update::class);
        $update->shouldReceive('getMessage->getChat->getId')
            ->once()
            ->andReturn($chatId);
        $update->shouldReceive('getMessage->getText')
            ->once()
            ->andReturn($messageText);

        // Создаем частичный мок команды для подмены getUpdate и replyWithMessage
        $command = Mockery::mock(GetWeatherThreeTimesADayCommand::class)->makePartial();
        $command->shouldReceive('getUpdate')
            ->once()
            ->andReturn($update);

        // Ожидаем, что будет вызван replyWithMessage с сообщением о необходимости указать город
        $command->shouldReceive('replyWithMessage')
            ->once()
            ->with(\Mockery::on(function ($argument) {
                return isset($argument['text']) &&
                    $argument['text'] === 'ℹ️ Пожалуйста, укажите город. Например: /get_weather_three_times_a_day Москва';
            }));

        // Act
        $command->handle();

        // Фиктивное утверждение для прохождения теста
        $this->addToAssertionCount(1);
    }

    /**
     * Сценарий: Пользователь пытается подписаться, но подписка уже существует.
     */
    public function test_existing_subscription()
    {
        // Arrange
        $chatId = 22222;
        $city = 'Moscow';
        $messageText = '/get_weather_three_times_a_day ' . $city;

        // Мокаем Update
        $update = Mockery::mock(Update::class);
        $update->shouldReceive('getMessage->getChat->getId')
            ->once()
            ->andReturn($chatId);
        $update->shouldReceive('getMessage->getText')
            ->once()
            ->andReturn($messageText);

        $command = Mockery::mock(GetWeatherThreeTimesADayCommand::class)->makePartial();
        $command->shouldReceive('getUpdate')
            ->once()
            ->andReturn($update);

        // Мокаем модель WeatherSubscription через alias
        $weatherSubscriptionMock = Mockery::mock('alias:' . WeatherSubscription::class);

        // Первой цепочкой: вызов exists() должен вернуть true
        $weatherSubscriptionMock->shouldReceive('where')
            ->with('chat_id', $chatId)
            ->andReturnSelf();
        $weatherSubscriptionMock->shouldReceive('where')
            ->with('city', $city)
            ->andReturnSelf();
        $weatherSubscriptionMock->shouldReceive('exists')
            ->once()
            ->andReturn(true);

        // Второй цепочкой: вызов first() возвращает существующую подписку
        $weatherSubscriptionMock->shouldReceive('where')
            ->with('chat_id', $chatId)
            ->andReturnSelf();
        $weatherSubscriptionMock->shouldReceive('where')
            ->with('city', $city)
            ->andReturnSelf();
        $fakeSubscription = (object)[
            'id' => 99,
            'city' => $city,
            'chat_id' => $chatId,
        ];
        $weatherSubscriptionMock->shouldReceive('first')
            ->once()
            ->andReturn($fakeSubscription);

        // Ожидаем, что будет вызван replyWithMessage с сообщением о том, что подписка уже существует
        $command->shouldReceive('replyWithMessage')
            ->once()
            ->with(\Mockery::on(function ($argument) use ($city, $fakeSubscription) {
                $expectedText = "⚠️ Вы уже подписаны на рассылку для города <b>{$city}</b> (ID: {$fakeSubscription->id}).\n\n" .
                    "Используйте /check_subscriptions для просмотра всех подписок";
                return isset($argument['text']) &&
                    $argument['text'] === $expectedText &&
                    isset($argument['parse_mode']) &&
                    $argument['parse_mode'] === 'HTML';
            }));

        // Act
        $command->handle();

        $this->addToAssertionCount(1);
    }

    /**
     * Сценарий: Создается новая подписка.
     */
    public function test_new_subscription()
    {
        // Arrange
        $chatId = 33333;
        $city = 'London';
        $messageText = '/get_weather_three_times_a_day ' . $city;

        // Мокаем Update
        $update = Mockery::mock(Update::class);
        $update->shouldReceive('getMessage->getChat->getId')
            ->once()
            ->andReturn($chatId);
        $update->shouldReceive('getMessage->getText')
            ->once()
            ->andReturn($messageText);

        $command = Mockery::mock(GetWeatherThreeTimesADayCommand::class)->makePartial();
        $command->shouldReceive('getUpdate')
            ->once()
            ->andReturn($update);

        // Мокаем модель WeatherSubscription через alias
        $weatherSubscriptionMock = Mockery::mock('alias:' . WeatherSubscription::class);

        // Для проверки существования подписки: exists() вернет false
        $weatherSubscriptionMock->shouldReceive('where')
            ->with('chat_id', $chatId)
            ->andReturnSelf();
        $weatherSubscriptionMock->shouldReceive('where')
            ->with('city', $city)
            ->andReturnSelf();
        $weatherSubscriptionMock->shouldReceive('exists')
            ->once()
            ->andReturn(false);

        // Затем, при вызове first(), подписки не найдется – возвращается null
        $weatherSubscriptionMock->shouldReceive('where')
            ->with('chat_id', $chatId)
            ->andReturnSelf();
        $weatherSubscriptionMock->shouldReceive('where')
            ->with('city', $city)
            ->andReturnSelf();
        $weatherSubscriptionMock->shouldReceive('first')
            ->once()
            ->andReturn(null);

        // При вызове create() модель должна вернуть новый объект-подписку
        $weatherSubscriptionMock->shouldReceive('create')
            ->once()
            ->with([
                'chat_id' => $chatId,
                'city'    => $city,
            ])
            ->andReturn((object)[
                'id' => 123,
                'chat_id' => $chatId,
                'city' => $city,
            ]);

        // И при подсчете количества подписок возвращается число (например, 1)
        $weatherSubscriptionMock->shouldReceive('where')
            ->with('chat_id', $chatId)
            ->andReturnSelf();
        $weatherSubscriptionMock->shouldReceive('count')
            ->once()
            ->andReturn(1);

        // Ожидаем, что будет вызван replyWithMessage с сообщением об успешном оформлении подписки
        $command->shouldReceive('replyWithMessage')
            ->once()
            ->with(\Mockery::on(function ($argument) use ($city) {
                $expectedText = "✅ Вы подписались на рассылку погоды для города {$city}.\n\n" .
                    "Вы будете получать уведомления в 7:00, 14:00 и 16:00.\n\n" .
                    "Всего подписок: 1\n" .
                    "🔍 Для просмотра подписок: /check_subscriptions\n" .
                    "🔕Для отмены всех подписок: /unsubscribe_all_cities\n" .
                    "🔕Для отмены подписки на конкретный город: /unsubscribe_concrete_city";
                return isset($argument['text']) &&
                    $argument['text'] === $expectedText &&
                    isset($argument['parse_mode']) &&
                    $argument['parse_mode'] === 'HTML';
            }));

        // Act
        $command->handle();

        $this->addToAssertionCount(1);
    }
}
