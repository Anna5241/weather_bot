<?php

namespace Tests\Unit;

use App\Services\Text2ImageService;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;

class Text2ImageServiceTest extends TestCase
{
    private $apiKey = 'test_api_key';
    private $secretKey = 'test_secret_key';
    private $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new Text2ImageService($this->apiKey, $this->secretKey);
    }

    public function testGetModels()
    {
        // 1. Создаем мок-обработчик с ожидаемым ответом
        $mock = new MockHandler([
            new Response(200, [], json_encode([
                [
                    'id' => 'model_123',
                    'name' => 'Test Model'
                ]
            ])),
        ]);

        // 2. Создаем обработчик для клиента
        $handlerStack = HandlerStack::create($mock);

        // 3. Создаем клиент с мок-обработчиком
        $client = new Client(['handler' => $handlerStack]);

        // 4. Создаем экземпляр сервиса с мок-клиентом
        $service = new Text2ImageService($this->apiKey, $this->secretKey, $client);

        // 5. Вызываем тестируемый метод
        $modelId = $service->getModels();

        // 6. Проверяем результат
        $this->assertEquals('model_123', $modelId);
    }

    public function testGenerate()
    {
        $mock = new MockHandler([
            new Response(200, [], json_encode(['uuid' => 'request_123'])),
        ]);

        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        $reflection = new \ReflectionClass($this->service);
        $property = $reflection->getProperty('client');
        $property->setAccessible(true);
        $property->setValue($this->service, $client);

        $requestId = $this->service->generate(
            'test prompt',
            'model_123',
            1,
            512,
            512,
            0
        );

        $this->assertEquals('request_123', $requestId);
    }

    public function testCheckGenerationSuccess()
    {
        $mock = new MockHandler([
            new Response(200, [], json_encode([
                'status' => 'DONE',
                'result' => ['files' => ['image_url_1', 'image_url_2']]
            ])),
        ]);

        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        $reflection = new \ReflectionClass($this->service);
        $property = $reflection->getProperty('client');
        $property->setAccessible(true);
        $property->setValue($this->service, $client);

        $result = $this->service->checkGeneration('request_123', 1);

        $this->assertEquals(['image_url_1', 'image_url_2'], $result);
    }

    public function testCheckGenerationFailure()
    {
        $mock = new MockHandler([
            new Response(200, [], json_encode(['status' => 'PENDING'])),
        ]);

        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        $reflection = new \ReflectionClass($this->service);
        $property = $reflection->getProperty('client');
        $property->setAccessible(true);
        $property->setValue($this->service, $client);

        $result = $this->service->checkGeneration('request_123', 1);

        $this->assertNull($result);
    }

    public function testCheckGenerationException()
    {
        $this->expectException(\Exception::class);

        $mock = new MockHandler([
            new Response(500, [], 'Internal Server Error'),
        ]);

        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        $reflection = new \ReflectionClass($this->service);
        $property = $reflection->getProperty('client');
        $property->setAccessible(true);
        $property->setValue($this->service, $client);

        $this->service->checkGeneration('request_123', 1);
    }
}
