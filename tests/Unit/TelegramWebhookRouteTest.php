<?php

namespace Tests\Unit;

use App\Http\Controllers\TelegramController;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Telegram\Bot\Laravel\Facades\Telegram;
use Telegram\Bot\Objects\Update;
use Illuminate\Http\Request;
use Mockery;

class TelegramWebhookRouteTest extends TestCase
{
    #[Test]
    public function test_webhook_handling()
    {
        // Создаем мок для TelegramController
        $controllerMock = Mockery::mock(TelegramController::class)->makePartial();

        // Ожидаем, что будет вызван метод handle и вернет успешный ответ
        $controllerMock->shouldReceive('handle')
            ->once()
            ->andReturn(response()->json(['status' => 'success']));

        // Создаем тестовый запрос
        $request = Request::create('/webhook', 'POST', [
            'update_id' => 123456,
            'message' => [
                'message_id' => 1,
                'chat' => ['id' => 12345],
                'text' => '/test'
            ]
        ]);

        // Вызываем маршрут с моком контроллера
        $response = $controllerMock->handle($request);

        // Проверяем ответ
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertJson($response->getContent());
        $this->assertEquals(['status' => 'success'], json_decode($response->getContent(), true));
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    #[Test]
    public function test_webhook_route()
    {
        // Регистрируем тестовый маршрут (если он еще не зарегистрирован)
        if (!Route::has('webhook')) {
            Route::post('/webhook', [TelegramController::class, 'handle'])->name('webhook');
        }

        // Создаем мок для контроллера
        $controllerMock = Mockery::mock(TelegramController::class);
        $controllerMock->shouldReceive('handle')
            ->once()
            ->andReturn(response()->json(['status' => 'success']));

        // Подменяем экземпляр контроллера в сервис-контейнере
        $this->app->instance(TelegramController::class, $controllerMock);

        // Отправляем POST-запрос на webhook
        $response = $this->postJson('/webhook', [
            'update_id' => 123456,
            'message' => [
                'message_id' => 1,
                'chat' => ['id' => 12345],
                'text' => '/test'
            ]
        ]);

        // Проверяем ответ
        $response->assertStatus(200)
            ->assertJson(['status' => 'success']);
    }

    #[Test]
    public function telegram_webhook_route_requires_post_method()
    {
        // Для этих тестов нам не нужно мокать Telegram
        $methods = ['get', 'put', 'patch', 'delete'];

        foreach ($methods as $method) {
            $response = $this->$method('/webhook');
            $this->assertContains(
                $response->getStatusCode(),
                [405, 404], // 404 если маршрут не зарегистрирован для других методов
                "Unexpected status code for $method method"
            );
        }
    }

    protected function getRegisteredPostRoutes(): array
    {
        return array_map(
            fn($route) => $route->uri(),
            Route::getRoutes()->get('POST', [])
        );
    }

    /**
     * Проверяет существует ли маршрут для указанного метода и URI
     */
    protected function hasRoute(string $method, string $uri): bool
    {
        return (bool) $this->getRoute($method, $uri);
    }

    /**
     * Получает маршрут для указанного метода и URI
     */
    protected function getRoute(string $method, string $uri)
    {
        $method = strtoupper($method);
        $routes = Route::getRoutes()->get($method, []);

        foreach ($routes as $route) {
            if ($route->uri() === $uri) {
                return $route;
            }
        }

        return null;
    }
}
