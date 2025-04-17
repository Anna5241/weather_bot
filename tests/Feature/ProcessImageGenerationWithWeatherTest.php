<?php

namespace Tests\Feature;

use App\Jobs\ProcessImageGenerationWithWeather;
use App\Services\Text2ImageService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;
use Mockery;

class ProcessImageGenerationWithWeatherTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Устанавливаем тестовые переменные окружения
        putenv('WEATHER_API_KEY=weather_test_api_key');
        putenv('TELEGRAM_BOT_TOKEN=dummy_token');

        // Подавляем вывод ошибок логгера, чтобы тест не прерывался
        Log::shouldReceive('error')->zeroOrMoreTimes();
    }

    public function test_handle_successful_generation_integration()
    {
        $chatId = 123456;
        $city   = 'TestCity';

        // Фейковый ответ от weatherapi
        Http::fake([
            "http://api.weatherapi.com/v1/current.json?key=weather_test_api_key&q={$city}&lang=ru" =>
                Http::response([
                    'current' => [
                        'temp_c'    => 22,
                        'condition' => ['text' => 'Clear'],
                        'humidity'  => 55,
                        'wind_kph'  => 10,
                    ]
                ], 200),
            // Фейковый ответ для запросов к Telegram (например, sendPhoto)
            "https://api.telegram.org/*" =>
                Http::response(['ok' => true], 200),
        ]);

        // Фейковый сервис генерации изображений
        $text2ImageMock = Mockery::mock('overload:' . Text2ImageService::class);
        $text2ImageMock->shouldReceive('getModels')->andReturn('model_test');
        $text2ImageMock->shouldReceive('generate')->andReturn('request_test');
        $text2ImageMock->shouldReceive('checkGeneration')->andReturn([base64_encode('fake_image_data')]);

        // Запускаем job. Если в процессе не будет необработанных исключений – тест считается успешным.
        $job = new ProcessImageGenerationWithWeather($chatId, $city);

        try {
            $job->handle();
            $this->assertTrue(true, 'Job выполнен без необработанных исключений');
        } catch (\Exception $e) {
            $this->fail("Job выбросил исключение: " . $e->getMessage());
        }
    }

    public function test_handle_weather_failure_integration()
    {
        // Для данного сценария ожидаем, что при ошибке получения погоды
        // job попытается отправить сообщение через Telegram, но из-за фиктивного токена
        // выбросится исключение с текстом "Not Found".
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Not Found');

        $chatId = 654321;
        $city   = 'NowhereCity';

        // Фейковый неуспешный ответ от weatherapi (например, город не найден)
        Http::fake([
            "http://api.weatherapi.com/v1/current.json?key=weather_test_api_key&q={$city}&lang=ru" =>
                Http::response(null, 404),
            // Фейковый ответ для Telegram запросов – в данном случае он не используется,
            // так как из-за ошибки погоды выполнение доходит до вызова Telegram и там выбрасывается исключение.
            "https://api.telegram.org/*" =>
                Http::response(['ok' => true], 200),
        ]);

        // Убеждаемся, что методы сервиса генерации не вызываются
        $text2ImageMock = Mockery::mock('overload:' . Text2ImageService::class);
        $text2ImageMock->shouldNotReceive('getModels');
        $text2ImageMock->shouldNotReceive('generate');
        $text2ImageMock->shouldNotReceive('checkGeneration');

        $job = new ProcessImageGenerationWithWeather($chatId, $city);
        $job->handle();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
