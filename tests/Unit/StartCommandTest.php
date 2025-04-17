<?php

namespace Tests\Unit;

use App\Console\Commands\Telegram\StartCommand;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Telegram\Bot\Commands\Command;
use Telegram\Bot\Api;
use Telegram\Bot\Objects\Update;
use Telegram\Bot\Objects\Message;
use Telegram\Bot\Objects\Chat;

class StartCommandTest extends TestCase
{
    #[Test]
    public function it_extends_base_command_class()
    {
        $command = new StartCommand();
        $this->assertInstanceOf(Command::class, $command);
    }

    #[Test]
    public function it_has_correct_name_and_description()
    {
        $command = new StartCommand();

        $this->assertEquals('start', $command->getName());
        $this->assertEquals('🚀 Начать работу с ботом', $command->getDescription());
    }

    #[Test]
    public function it_returns_correct_response_when_handled()
    {
        // 1. Создаем мок API Telegram
        $telegram = $this->createMock(Api::class);

        // 2. Создаем фейковый Update объект
        $chat = new Chat([
            'id' => 123456789,
            'first_name' => 'Test',
            'type' => 'private'
        ]);

        $message = new Message([
            'message_id' => 1,
            'chat' => $chat,
            'text' => '/start'
        ]);

        $update = new Update([
            'update_id' => 1,
            'message' => $message
        ]);

        // 3. Создаем экземпляр команды через конструктор
        $command = new StartCommand();

        // 4. Устанавливаем зависимости через рефлексию
        $this->setProtectedProperty($command, 'telegram', $telegram);
        $this->setProtectedProperty($command, 'update', $update);

        // 5. Ожидаем вызов replyWithMessage
        $expectedResponse = "Привет!👋 \nЯ погодный телеграмм-бот!\nЧтобы увидеть команды нажми /help";

        $telegram->expects($this->once())
            ->method('sendMessage')
            ->with([
                'chat_id' => 123456789,
                'text' => $expectedResponse,
                // Убрали parse_mode, так как он не передается в реальном вызове
            ]);

        // 6. Вызываем handle метод
        $command->handle();
    }

    private function setProtectedProperty(object $object, string $property, $value): void
    {
        $reflection = new \ReflectionClass($object);
        $property = $reflection->getProperty($property);
        $property->setAccessible(true);
        $property->setValue($object, $value);
    }
}
