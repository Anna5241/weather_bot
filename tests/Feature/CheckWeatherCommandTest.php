<?php

namespace Tests\Feature;

use App\Console\Commands\Telegram\CheckWeatherCommand;
use App\Jobs\ProcessImageGenerationWithWeather;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Bus;
use Mockery;
use ReflectionClass;
use Telegram\Bot\Api;
use Telegram\Bot\Objects\Update;
use Tests\TestCase;

class CheckWeatherCommandTest extends TestCase
{
    /**
     * Устанавливает значение защищённого свойства telegram через Reflection.
     *
     * @param CheckWeatherCommand $command
     * @param Api $telegramMock
     * @return void
     */
    protected function setTelegramProperty(CheckWeatherCommand $command, Api $telegramMock): void
    {
        $reflectedCommand = new ReflectionClass($command);
        $property = $reflectedCommand->getProperty('telegram');
        $property->setAccessible(true);
        $property->setValue($command, $telegramMock);
    }

    /**
     * Тест: если город не указан, команда отправляет сообщение с просьбой указать город.
     *
     * @return void
     */
    public function test_check_weather_no_city()
    {
        // Arrange: Подготовка данных
        $chatId = 12345;
        // Текст сообщения содержит только команду, без указания города.
        $messageText = '/check_weather';

        // Мокаем объект Update для получения chatId и текста сообщения.
        $update = Mockery::mock(Update::class);
        $update->shouldReceive('getMessage->getChat->getId')
            ->once()
            ->andReturn($chatId);
        $update->shouldReceive('getMessage->getText')
            ->once()
            ->andReturn($messageText);

        // Создаем частичный мок команды для подмены метода getUpdate.
        $command = Mockery::mock(CheckWeatherCommand::class)->makePartial();
        $command->shouldReceive('getUpdate')
            ->once()
            ->andReturn($update);

        // Создаем мок для Telegram API.
        $telegramMock = Mockery::mock(Api::class);
        $telegramMock->shouldReceive('sendMessage')
            ->once()
            ->with([
                'chat_id' => $chatId,
                'text'    => "🌍 Пожалуйста, укажите город.\nПример: /check_weather Москва",
            ]);

        // Используем Reflection для установки защищённого свойства telegram.
        $this->setTelegramProperty($command, $telegramMock);

        // Act: Выполняем команду
        $response = $command->handle();

        // Assert: Проверяем, что возвращается JSON-ответ с статусом success.
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(['status' => 'success'], $response->getData(true));

        // Добавляем фиктивное утверждение, чтобы избежать предупреждения PHPUnit.
        $this->addToAssertionCount(1);
    }

    /**
     * Тест: если город указан, команда отправляет сообщение о проверке погоды и диспетчирует задание.
     *
     * @return void
     */
    public function test_check_weather_with_valid_city()
    {
        // Arrange: Подготовка данных
        $chatId = 67890;
        // Текст сообщения содержит команду и название города, например "Москва".
        $inputCity = 'Москва';
        $messageText = '/check_weather ' . $inputCity;

        // Фейкаем диспетчеризацию заданий.
        Bus::fake();

        // Мокаем объект Update для получения chatId и текста сообщения.
        $update = Mockery::mock(Update::class);
        $update->shouldReceive('getMessage->getChat->getId')
            ->once()
            ->andReturn($chatId);
        $update->shouldReceive('getMessage->getText')
            ->once()
            ->andReturn($messageText);

        // Создаем частичный мок команды для подмены метода getUpdate.
        $command = Mockery::mock(CheckWeatherCommand::class)->makePartial();
        $command->shouldReceive('getUpdate')
            ->once()
            ->andReturn($update);

        // Мокаем объект Telegram API.
        $telegramMock = Mockery::mock(Api::class);
        $expectedText = "⏳ <b>Проверяем текущую погоду в городе {$inputCity}...</b>\n\n🌤️ Результат появится здесь через несколько секунд!";
        $telegramMock->shouldReceive('sendMessage')
            ->once()
            ->with(\Mockery::on(function ($argument) use ($chatId, $expectedText) {
                return $argument['chat_id'] === $chatId &&
                    $argument['text'] === $expectedText &&
                    isset($argument['parse_mode']) &&
                    $argument['parse_mode'] === 'HTML';
            }));

        // Используем Reflection для установки защищённого свойства telegram.
        $this->setTelegramProperty($command, $telegramMock);

        // Act: Выполняем команду
        $response = $command->handle();

        // Assert: Проверяем, что возвращается JSON-ответ с статусом success.
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(['status' => 'success'], $response->getData(true));

        // Проверяем, что задание ProcessImageGenerationWithWeather было задиспатчено с корректными параметрами.
        Bus::assertDispatched(ProcessImageGenerationWithWeather::class, function ($job) use ($chatId, $inputCity) {
            $jobReflection = new ReflectionClass($job);

            // Получаем защищённое свойство "chatId"
            $chatIdProperty = $jobReflection->getProperty('chatId');
            $chatIdProperty->setAccessible(true);
            $jobChatId = $chatIdProperty->getValue($job);

            // Получаем защищённое свойство "city"
            $cityProperty = $jobReflection->getProperty('city');
            $cityProperty->setAccessible(true);
            $jobCity = $cityProperty->getValue($job);

            return $jobChatId === $chatId && $jobCity === $inputCity;
        });

        // Добавляем фиктивное утверждение для прохождения теста.
        $this->addToAssertionCount(1);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
