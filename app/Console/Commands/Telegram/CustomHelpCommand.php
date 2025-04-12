<?php

namespace App\Console\Commands\Telegram;

use Telegram\Bot\Commands\Command;
use Telegram\Bot\Commands\CommandInterface;

class CustomHelpCommand extends Command implements CommandInterface
{
    protected string $name = 'help';
    protected string $description = '📚 Показать список всех доступных команд и их описание';

    public function handle()
    {
        $commands = $this->telegram->getCommands();

        $response = "🔹 <b>Доступные команды:</b>\n\n";

        foreach ($commands as $name => $command) {
            $response .= sprintf("/%s - %s\n", $name, $command->getDescription());
        }

        $response .= "\nℹ Выберите команду для взаимодействия с ботом";

        $this->replyWithMessage([
            'text' => $response,
            'parse_mode' => 'HTML',
            'disable_web_page_preview' => true
        ]);
    }
}
