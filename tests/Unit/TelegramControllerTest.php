<?php

namespace Tests\Unit;

use App\Http\Controllers\TelegramController;
use Illuminate\Http\Request;
use PHPUnit\Framework\Attributes\Test;
use Telegram\Bot\Objects\Update;
use Telegram\Bot\Objects\Message;
use Telegram\Bot\Objects\Chat;
use Tests\TestCase;
use ReflectionClass;

class TelegramControllerTest extends TestCase
{
    #[Test]
    public function test_handle_unknown_command()
    {
        // 1. Подготавливаем данные
        $chat = new Chat(['id' => 123]);
        $message = new Message([
            'message_id' => 1,
            'text' => '/unknowncommand',
            'chat' => $chat,
            'entities' => [
                ['type' => 'bot_command', 'offset' => 0, 'length' => 15]
            ]
        ]);
        $update = new Update(['update_id' => 1, 'message' => $message]);

        // 2. Мокаем Telegram фасад
        $telegramMock = \Mockery::mock('alias:Telegram\Bot\Laravel\Facades\Telegram');

        // Ожидаем вызов commandsHandler - возвращаем update с неизвестной командой
        $telegramMock->shouldReceive('commandsHandler')
            ->once()
            ->with(true)
            ->andReturn($update);

        // Ожидаем вызов sendMessage с нужными параметрами
        $telegramMock->shouldReceive('sendMessage')
            ->once()
            ->with([
                'chat_id' => 123,
                'text' => "Извините, я не знаю такой команды.\nНажмите /help для списка доступных команд.",
            ]);

        // 3. Используем рефлексию для проверки protected методов
        $controller = new TelegramController();
        $reflector = new \ReflectionClass($controller);

        // Проверяем wasCommandProcessed - должен вернуть true (это команда)
        $wasCommandProcessed = $reflector->getMethod('wasCommandProcessed');
        $wasCommandProcessed->setAccessible(true);
        $this->assertTrue($wasCommandProcessed->invoke($controller, $update));

        // 4. Проверяем handleUnknownCommand
        $handleUnknownCommand = $reflector->getMethod('handleUnknownCommand');
        $handleUnknownCommand->setAccessible(true);
        $handleUnknownCommand->invoke($controller, $update);

        // 5. Вызываем основной метод handle
        $request = Request::create('/telegram/handle', 'POST');
        $response = $controller->handle($request);

        // 6. Проверяем ответ
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals(['status' => 'success'], $response->getData(true));

        // 7. Проверяем что все ожидаемые вызовы были сделаны
        \Mockery::close();
    }

    #[Test]
    public function test_handle_regular_text_message()
    {
        // Подготавливаем данные
        $message = new Message([
            'text' => 'Просто текст',
            'chat' => new Chat(['id' => 123]),
            'entities' => []
        ]);

        $update = new Update(['update_id' => 1, 'message' => $message]);

        // Мокаем Telegram фасад
        $this->mockTelegramFacade($update);

        $controller = new TelegramController();
        $request = Request::create('/telegram/handle', 'POST');

        // Вызываем метод
        $response = $controller->handle($request);

        // Проверяем ответ
        $this->assertEquals(200, $response->getStatusCode());
    }

    #[Test]
    public function test_handle_error_case()
    {
        // Мокаем Telegram фасад чтобы выбросить исключение
        $telegramMock = \Mockery::mock('alias:Telegram\Bot\Laravel\Facades\Telegram');
        $telegramMock->shouldReceive('commandsHandler')
            ->once()
            ->with(true)
            ->andThrow(new \Exception('Test error'));

        $controller = new TelegramController();
        $request = Request::create('/telegram/handle', 'POST');

        // Вызываем метод
        $response = $controller->handle($request);

        // Проверяем ответ с ошибкой
        $this->assertEquals(500, $response->getStatusCode());
        $responseData = $response->getData(true);
        $this->assertEquals('error', $responseData['status']);
        $this->assertEquals('Test error', $responseData['message']);
    }

    #[Test]
    public function test_was_command_processed_positive()
    {
        $controller = new TelegramController();

        $message = new Message([
            'text' => '/start',
            'entities' => [
                ['type' => 'bot_command', 'offset' => 0, 'length' => 6]
            ]
        ]);

        $update = new Update(['message' => $message]);

        // Используем рефлексию для вызова protected метода
        $reflection = new ReflectionClass($controller);
        $method = $reflection->getMethod('wasCommandProcessed');
        $method->setAccessible(true);

        $this->assertTrue($method->invoke($controller, $update));
    }

    #[Test]
    public function test_was_command_processed_negative()
    {
        $controller = new TelegramController();

        $message = new Message([
            'text' => 'Просто текст',
            'entities' => []
        ]);

        $update = new Update(['message' => $message]);

        // Используем рефлексию для вызова protected метода
        $reflection = new ReflectionClass($controller);
        $method = $reflection->getMethod('wasCommandProcessed');
        $method->setAccessible(true);

        $this->assertFalse($method->invoke($controller, $update));
    }

    protected function mockTelegramFacade($update)
    {
        $telegramMock = \Mockery::mock('alias:Telegram\Bot\Laravel\Facades\Telegram');
        $telegramMock->shouldReceive('commandsHandler')
            ->once()
            ->with(true)
            ->andReturn($update);

        $telegramMock->shouldReceive('sendMessage')
            ->once();
    }
}
