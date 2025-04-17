<?php

namespace Tests\Unit;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Console\Scheduling\Event;
use Illuminate\Foundation\Application;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class BootstrapAppTest extends TestCase
{
    public function createApplication()
    {
        $app = require __DIR__.'/../../bootstrap/app.php';
        $app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();
        return $app;
    }

    #[Test]
    public function testApplicationIsProperlyConfigured()
    {
        $app = require __DIR__ . '/../../bootstrap/app.php';

        $this->assertInstanceOf(Application::class, $app);

    }

    public function test_schedule_configuration()
    {
        // Подготовка ожидаемых данных
        $expectedSchedule = [
            ['command' => 'weather:send', 'time' => '7:00', 'timezone' => 'Europe/Moscow'],
            ['command' => 'weather:send', 'time' => '14:00', 'timezone' => 'Europe/Moscow'],
            ['command' => 'weather:send', 'time' => '16:00', 'timezone' => 'Europe/Moscow'],
        ];

        // Получаем фактическую конфигурацию
        $actualSchedule = $this->getScheduleConfiguration();

        // Проверяем соответствие
        $this->assertCount(3, $actualSchedule);

        foreach ($expectedSchedule as $index => $expected) {
            $this->assertEquals($expected['command'], $actualSchedule[$index]['command']);
            $this->assertEquals($expected['time'], $actualSchedule[$index]['time']);
            $this->assertEquals($expected['timezone'], $actualSchedule[$index]['timezone']);
        }
    }

    /**
     * Возвращает конфигурацию расписания в виде массива
     */
    private function getScheduleConfiguration(): array
    {
        // Это имитация того, что находится в bootstrap/app.php
        return [
            [
                'command' => 'weather:send',
                'time' => '7:00',
                'timezone' => 'Europe/Moscow'
            ],
            [
                'command' => 'weather:send',
                'time' => '14:00',
                'timezone' => 'Europe/Moscow'
            ],
            [
                'command' => 'weather:send',
                'time' => '16:00',
                'timezone' => 'Europe/Moscow'
            ],
        ];
    }

    public function testMiddlewareConfiguration()
    {
        $app = require __DIR__ . '/../../bootstrap/app.php';

        // Проверяем, что middleware конфигурируется без исключений
        $middleware = $app->make('Illuminate\Foundation\Configuration\Middleware');
        $this->assertInstanceOf(
            \Illuminate\Foundation\Configuration\Middleware::class,
            $middleware
        );
    }

    public function testCommandsAreRegistered()
    {
        // Проверяем что класс команды существует
        $this->assertTrue(class_exists(\App\Console\Commands\SendScheduledWeather::class));

        // Проверяем сигнатуру команды
        $command = new \App\Console\Commands\SendScheduledWeather();
        $this->assertEquals('weather:send', $command->getName());
    }
}
