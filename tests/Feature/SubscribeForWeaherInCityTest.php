<?php


namespace Tests\Feature;

use App\Console\Commands\Telegram\SubscribeForWeaherInCity;
use App\Models\WeatherSubscription;
use Mockery;
use Telegram\Bot\Objects\Update;
use Tests\TestCase;

class SubscribeForWeaherInCityTest extends TestCase
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
        $chatId = 10101;
        // Текст сообщения содержит только команду без указания города
        $messageText = '/subscribe_for_weather_in_city';

        // Мокаем объект Update, чтобы вернуть chatId и текст сообщения
        $update = Mockery::mock(Update::class);
        $update->shouldReceive('getMessage->getChat->getId')
            ->once()
            ->andReturn($chatId);
        $update->shouldReceive('getMessage->getText')
            ->once()
            ->andReturn($messageText);

        // Создаем частичный мок команды для подмены метода getUpdate
        $command = Mockery::mock(SubscribeForWeaherInCity::class)->makePartial();
        $command->shouldReceive('getUpdate')
            ->once()
            ->andReturn($update);

        // Ожидаем, что вызов replyWithMessage выполнится с сообщением о необходимости указать город
        $command->shouldReceive('replyWithMessage')
            ->once()
            ->with(\Mockery::on(function ($argument) {
                return isset($argument['text']) &&
                    $argument['text'] === 'ℹ️ Пожалуйста, укажите город. Например: /subscribe_for_weather_in_city Москва';
            }));

        // Act
        $command->handle();

        // Фиктивное утверждение для прохождения теста
        $this->addToAssertionCount(1);
    }

    /**
     * Сценарий: Попытка подписаться, но подписка для указанного города уже существует.
     */
    public function test_existing_subscription()
    {
        // Arrange
        $chatId = 20202;
        $city = 'Moscow';
        $messageText = '/subscribe_for_weather_in_city ' . $city;

        // Мокаем Update
        $update = Mockery::mock(Update::class);
        $update->shouldReceive('getMessage->getChat->getId')
            ->once()
            ->andReturn($chatId);
        $update->shouldReceive('getMessage->getText')
            ->once()
            ->andReturn($messageText);

        $command = Mockery::mock(SubscribeForWeaherInCity::class)->makePartial();
        $command->shouldReceive('getUpdate')
            ->once()
            ->andReturn($update);

        // Мокаем модель WeatherSubscription через alias для перехвата статических методов
        $subscriptionAlias = 'alias:' . WeatherSubscription::class;
        $weatherSubscriptionMock = Mockery::mock($subscriptionAlias);

        // Проверяем существование подписки: exists() возвращает true
        $weatherSubscriptionMock->shouldReceive('where')
            ->with('chat_id', $chatId)
            ->andReturnSelf();
        $weatherSubscriptionMock->shouldReceive('where')
            ->with('city', $city)
            ->andReturnSelf();
        $weatherSubscriptionMock->shouldReceive('exists')
            ->once()
            ->andReturn(true);

        // Затем, при вызове first() возвращаем существующую подписку (фиктивный объект)
        $weatherSubscriptionMock->shouldReceive('where')
            ->with('chat_id', $chatId)
            ->andReturnSelf();
        $weatherSubscriptionMock->shouldReceive('where')
            ->with('city', $city)
            ->andReturnSelf();
        $fakeSubscription = (object)[
            'id'      => 555,
            'city'    => $city,
            'chat_id' => $chatId,
        ];
        $weatherSubscriptionMock->shouldReceive('first')
            ->once()
            ->andReturn($fakeSubscription);

        // Ожидаем, что replyWithMessage вызовется с сообщением о том, что подписка уже существует
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
     * Сценарий: Создаем новую подписку, если для указанного города подписки нет.
     */
    public function test_new_subscription()
    {
        // Arrange
        $chatId = 30303;
        $city = 'London';
        $messageText = '/subscribe_for_weather_in_city ' . $city;

        // Мокаем Update
        $update = Mockery::mock(Update::class);
        $update->shouldReceive('getMessage->getChat->getId')
            ->once()
            ->andReturn($chatId);
        $update->shouldReceive('getMessage->getText')
            ->once()
            ->andReturn($messageText);

        $command = Mockery::mock(SubscribeForWeaherInCity::class)->makePartial();
        $command->shouldReceive('getUpdate')
            ->once()
            ->andReturn($update);

        // Мокаем модель WeatherSubscription через alias
        $subscriptionAlias = 'alias:' . WeatherSubscription::class;
        $weatherSubscriptionMock = Mockery::mock($subscriptionAlias);

        // Проверка существования подписки: exists() вернет false
        $weatherSubscriptionMock->shouldReceive('where')
            ->with('chat_id', $chatId)
            ->andReturnSelf();
        $weatherSubscriptionMock->shouldReceive('where')
            ->with('city', $city)
            ->andReturnSelf();
        $weatherSubscriptionMock->shouldReceive('exists')
            ->once()
            ->andReturn(false);

        // При вызове first() возвращается null
        $weatherSubscriptionMock->shouldReceive('where')
            ->with('chat_id', $chatId)
            ->andReturnSelf();
        $weatherSubscriptionMock->shouldReceive('where')
            ->with('city', $city)
            ->andReturnSelf();
        $weatherSubscriptionMock->shouldReceive('first')
            ->once()
            ->andReturn(null);

        // При создании подписки метод create() возвращает новый объект-подписку
        $weatherSubscriptionMock->shouldReceive('create')
            ->once()
            ->with([
                'chat_id' => $chatId,
                'city'    => $city,
            ])
            ->andReturn((object)[
                'id'      => 777,
                'chat_id' => $chatId,
                'city'    => $city,
            ]);

        // При подсчете количества подписок возвращается число (например, 1)
        $weatherSubscriptionMock->shouldReceive('where')
            ->with('chat_id', $chatId)
            ->andReturnSelf();
        $weatherSubscriptionMock->shouldReceive('count')
            ->once()
            ->andReturn(1);

        // Ожидаем, что replyWithMessage будет вызван с сообщением подтверждения успешной подписки
        $command->shouldReceive('replyWithMessage')
            ->once()
            ->with(\Mockery::on(function ($argument) use ($city) {
                $expectedText = "✅ Вы подписались на рассылку погоды для города {$city}.\n\n" .
                    "Вы будете получать уведомления в 7:00, 14:00 и 16:00.\n\n" .
                    "Всего подписок: 1\n" .
                    "🔍 Для просмотра подписок: /check_subscriptions\n" .
                    "❌Для отмены всех подписок: /unsubscribe_all_cities\n" .
                    "💣Для отмены подписки на конкретный город: /unsubscribe_concrete_city";
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

