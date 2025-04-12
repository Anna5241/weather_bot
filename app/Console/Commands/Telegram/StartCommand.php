<?php

namespace App\Console\Commands\Telegram;

use Telegram\Bot\Commands\Command;

class StartCommand extends Command
{

    protected string $name = 'start';
    protected string $description = '🚀 Начать работу с ботом';

    public function handle()
    {
        $response = "Привет!👋 \nЯ погодный телеграмм-бот!\nЧтобы увидеть команды нажми /help";
        $this->replyWithMessage(['text' => $response]);
    }
}
