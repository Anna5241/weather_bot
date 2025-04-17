<?php

namespace Tests\Unit;

use App\Console\Commands\Telegram\UnsubscribeAllCitiesCommand;
use App\Models\WeatherSubscription;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Telegram\Bot\Api;
use Telegram\Bot\Objects\Chat;
use Telegram\Bot\Objects\Message;
use Telegram\Bot\Objects\Update;
use Mockery;

class UnsubscribeAllCitiesCommandTest extends TestCase
{
    #[Test]
    public function it_has_correct_name_and_description()
    {
        $command = new UnsubscribeAllCitiesCommand();

        $this->assertEquals('unsubscribe_all_cities', $command->getName());
        $this->assertEquals('💣 Отписаться от всех рассылок погоды', $command->getDescription());
    }

    #[Test]
    public function it_deletes_all_subscriptions_when_they_exist()
    {
        // 1. Создаем мок Builder
        $builder = \Mockery::mock(\Illuminate\Database\Eloquent\Builder::class);
        $builder->shouldReceive('delete')->once()->andReturn(1);

        // 2. Создаем мок модели
        $modelMock = \Mockery::mock('overload:' . WeatherSubscription::class);
        $modelMock->shouldReceive('where')
            ->once()
            ->with('chat_id', 123456789)
            ->andReturn($builder);

        // 3. Мокируем Telegram
        $telegram = $this->createMock(Api::class);
        $update = $this->createUpdateMock(123456789);

        $telegram->expects($this->once())
            ->method('sendMessage')
            ->with([
                'chat_id' => 123456789,
                'text' => '❌Вы отписались от рассылки погоды.'
            ]);

        // 4. Создаем команду
        $command = new UnsubscribeAllCitiesCommand();

        // 5. Устанавливаем зависимости через рефлексию
        $this->setProtectedProperty($command, 'telegram', $telegram);
        $this->setProtectedProperty($command, 'update', $update);

        // 6. Выполняем команду
        $command->handle();
    }

    #[Test]
    public function it_shows_warning_when_no_subscriptions_exist()
    {
        // 1. Подготовка моков
        $telegramMock = Mockery::mock(Api::class);
        $command = new UnsubscribeAllCitiesCommand();

        // Устанавливаем telegram клиент через reflection
        $reflection = new \ReflectionClass($command);
        $telegramProperty = $reflection->getProperty('telegram');
        $telegramProperty->setAccessible(true);
        $telegramProperty->setValue($command, $telegramMock);

        // 2. Мокируем модель WeatherSubscription
        $weatherSubscriptionMock = Mockery::mock('overload:'.WeatherSubscription::class);
        $queryMock = Mockery::mock(\Illuminate\Database\Eloquent\Builder::class);

        // Настраиваем цепочку вызовов: where()->delete()
        $weatherSubscriptionMock->shouldReceive('where')
            ->with('chat_id', 12345)
            ->once()
            ->andReturn($queryMock);

        $queryMock->shouldReceive('delete')
            ->once()
            ->andReturn(0); // Ничего не удалено

        // 3. Настраиваем тестовые данные
        $update = new Update([
            'update_id' => 1,
            'message' => new Message([
                'message_id' => 1,
                'chat' => ['id' => 12345],
                'date' => time(),
                'text' => '/unsubscribe_all_cities',
            ]),
        ]);

        // Устанавливаем update в команду через reflection
        $updateProperty = $reflection->getProperty('update');
        $updateProperty->setAccessible(true);
        $updateProperty->setValue($command, $update);

        // 4. Ожидаем сообщение с предупреждением
        $telegramMock->shouldReceive('sendMessage')
            ->once()
            ->with([
                'chat_id' => 12345,
                'text' => '⚠️У вас не было активной подписки.',
            ]);

        // 5. Выполнение тестируемого метода
        $command->handle();

        // 6. Проверки
        try {
            Mockery::close();
            $this->assertTrue(true);
        } catch (\Exception $e) {
            $this->fail('Ожидания Mockery не выполнены: '.$e->getMessage());
        }
    }

    private function createUpdateMock(int $chatId): Update
    {
        $chat = new Chat(['id' => $chatId]);
        $message = new Message(['chat' => $chat]);

        return new Update(['message' => $message]);
    }

    private function setProtectedProperty(object $object, string $property, $value): void
    {
        $reflection = new \ReflectionClass($object);
        $property = $reflection->getProperty($property);
        $property->setAccessible(true);
        $property->setValue($object, $value);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
