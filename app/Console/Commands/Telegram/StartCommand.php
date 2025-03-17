<?php

namespace App\Console\Commands\Telegram;

use Telegram\Bot\Commands\Command;

class StartCommand extends Command
{

    protected string $name = 'start';
    protected string $description = 'Команда для начала работы с ботом';

    public function handle()
    {
        $response = "Привет! Я погодный телеграмм-бот!";
        $this->replyWithMessage(['text' => $response]);
    }
}
