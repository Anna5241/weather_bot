<?php

namespace App\Services;

use Exception;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

class Text2ImageService
{
    private $url;
    private $authHeaders;
    private $client; // Добавляем свойство для хранения клиента


    public function __construct($apiKey, $secretKey, Client $client = null)
    {
        $this->url = 'https://api-key.fusionbrain.ai/';
        $this->authHeaders = [
            'X-Key' => "Key $apiKey",
            'X-Secret' => "Secret $secretKey"
        ];
        $this->client = $client ?? new Client();
    }

    public function getModels()
    {
        $response = $this->client->get($this->url . 'key/api/v1/pipelines', [
            'headers' => $this->authHeaders
        ]);
        $data = json_decode($response->getBody(), true);
        return $data[0]['id'];
    }

    public function generate($prompt, $model, $images, $width, $height, $style)
    {
        $styles = ["KADINSKY", "UHD", "ANIME", "DEFAULT"];
        $params = [
            'type' => "GENERATE",
            'numImages' => $images,
            'width' => $width,
            'height' => $height,
            'style' => $styles[$style],
            'generateParams' => [
                'query' => $prompt
            ]
        ];

        //        $formData = [
        //            [
        //                'name' => 'model_id',
        //                'contents' => $model
        //            ],
        //            [
        //                'name' => 'params',
        //                'contents' => json_encode($params),
        //                'headers' => ['Content-Type' => 'application/json']
        //            ]
        //
        //        ];
        $formData = [
        [
            'name' => 'pipeline_id',
            'contents' => $model
        ],
        [
            'name' => 'params',
            'contents' => json_encode($params),
            'headers' => ['Content-Type' => 'application/json']
        ]
    ];

        $response = $this->client->post($this->url . 'key/api/v1/pipeline/run', [
            'multipart' => $formData,
            'headers' => $this->authHeaders
        ]);

        $data = json_decode($response->getBody(), true);
        return $data['uuid'];
    }

    public function checkGeneration($requestId, $attempts = 30, $delay = 10)
    {
        while ($attempts > 0) {
            //            Log::info('Попытка генерации №', $attempts);
            try {

                $response = $this->client->get($this->url . 'key/api/v1/pipeline/status/' . $requestId, [
                    'headers' => $this->authHeaders
                ]);
                $data = json_decode($response->getBody(), true);
                if ($data['status'] === 'DONE') {
                    //                    Log::info('Изображение сгенерировано с попытки ', $attempts);
                    return $data['result']['files'];

                }
            } catch (Exception $e) {
                throw new Exception('Error: ' . $e->getMessage());
            }
            $attempts--;
            sleep($delay);
        }
        //        Log::info('Не сгенерировано(');
        return null;
    }


}
