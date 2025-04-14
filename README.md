# 🌦️ Погодный бот для Telegram

Laravel-бот для Telegram, предоставляющий прогноз погоды и AI-генерацию изображений с погодой.

## 📌 Содержание
- [Функционал](#-функционал)
- [Архитектура](#-архитектура)  
- [Установка](#-установка)
  - [Локальный запуск](#локальный-запуск)
  - [Запуск через Docker](#запуск-через-docker)
- [Конфигурация](#-конфигурация)
- [Документация API](#-документация-api)
- [Тестирование](#-тестирование)
- [Поддержка](#-поддержка)

## 🌟 Функционал
- `/start` - Начать работу с ботом
- `/help` - Показать все команды
- `/check_weather [город]` - Текущая погода + AI-изображение
- `/subscribe_for_weather_in_city` - Подписка на рассылку (3 раза/день)
- `/unsubscribe_all_cities` - Отписаться от всех рассылок
- `/check_subscriptions` - Активные подписки

## 🏗 Архитектура
### Основной стек
| Компонент       | Технология |
|----------------|------------|
| Бэкенд         | Laravel 12 |
| База данных    | SQLite/MySQL |
| Очереди        | Redis      |
| Планировщик    | Laravel Scheduler |
| База данных    | Redis |

### Внешние сервисы
- [WeatherAPI](https://www.weatherapi.com/) - Данные о погоде
- [FusionBrain](https://fusionbrain.ai/) - Генерация изображений

## 💻 Установка

### Локальный запуск
```bash
# Установка зависимостей
composer install

# Настройка окружения
cp .env.example .env
nano .env  # Редактируем настройки

# Запуск сервера
php artisan serve

# В отдельных терминалах:
php artisan queue:work --timeout=3500
php artisan schedule:work

# Настройка ngrok
ngrok http 8000
curl -F "url=<NGROK_URL>" "https://api.telegram.org/bot<TELEGRAM_BOT_TOKEN>/setWebhook"

### Локальный запуск через Docker
```bash
docker compose up -d

# Получаем ngrok URL
docker compose logs ngrok --tail=100

# Устанавливаем вебхук (замените параметры)
curl -F "url=<NGROK_URL>" "https://api.telegram.org/bot<TELEGRAM_BOT_TOKEN>/setWebhook"
