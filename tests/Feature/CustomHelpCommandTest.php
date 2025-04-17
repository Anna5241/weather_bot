<?php


namespace Tests\Feature;

use App\Console\Commands\Telegram\CustomHelpCommand;
use Mockery;
use ReflectionClass;
use Telegram\Bot\Api;
use Tests\TestCase;

class CustomHelpCommandTest extends TestCase
{
    /**
     * Устанавливает значение защищённого свойства telegram через Reflection.
     *
     * @param CustomHelpCommand $command
     * @param Api $telegramInstance
     * @return void
     */
    protected function setTelegramProperty(CustomHelpCommand $command, Api $telegramInstance): void
    {
        $reflectedCommand = new ReflectionClass($command);
        $property = $reflectedCommand->getProperty('telegram');
        $property->setAccessible(true);
        $property->setValue($command, $telegramInstance);
    }

    /**
     * Тест, проверяющий работу команды CustomHelpCommand.
     *
     * @return void
     */
    public function test_custom_help_command_displays_available_commands()
    {
        // Arrange: Создаем фиктивные команды с описаниями.
        $commandStart = Mockery::mock();
        $commandStart->shouldReceive('getDescription')
            ->andReturn('Запустить бота');
        $commandWeather = Mockery::mock();
        $commandWeather->shouldReceive('getDescription')
            ->andReturn('Узнать текущую погоду');

        // Список команд, который должен вернуть Telegram API.
        $commandsArray = [
            'start'   => $commandStart,
            'weather' => $commandWeather,
        ];

        // Мокаем Telegram API как partial mock, чтобы объект оставался экземпляром Telegram\Bot\Api.
        $telegramMock = Mockery::mock(Api::class)->makePartial();
        // Отключаем конструктор при создании объекта.
        $telegramMock->shouldAllowMockingProtectedMethods();
        $telegramMock->shouldReceive('getCommands')
            ->once()
            ->andReturn($commandsArray);

        // Создаем частичный мок для команды CustomHelpCommand, чтобы подменить метод replyWithMessage.
        $command = Mockery::mock(CustomHelpCommand::class)->makePartial();
        $command->shouldReceive('replyWithMessage')
            ->once()
            ->with(\Mockery::on(function ($argument) {
                $text = $argument['text'];

                // Формирование ожидаемого ответа.
                $expectedHeader = "🔹 <b>Доступные команды:</b>\n\n";
                $expectedStart  = "/start - Запустить бота\n";
                $expectedWeather = "/weather - Узнать текущую погоду\n";
                $expectedFooter = "\nℹ Выберите команду для взаимодействия с ботом";

                // Проверяем, что все фрагменты присутствуют в ответе.
                return strpos($text, $expectedHeader) !== false &&
                    strpos($text, $expectedStart) !== false &&
                    strpos($text, $expectedWeather) !== false &&
                    strpos($text, $expectedFooter) !== false &&
                    $argument['parse_mode'] === 'HTML' &&
                    $argument['disable_web_page_preview'] === true;
            }))
            ->andReturnNull();

        // Подставляем наш partial mock Telegram API через Reflection.
        $this->setTelegramProperty($command, $telegramMock);

        // Act: Выполняем команду.
        $command->handle();

        // Добавляем фиктивное утверждение, чтобы избежать предупреждения PHPUnit.
        $this->addToAssertionCount(1);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}

