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
- `/unsubscribe_concrete_city` - Отписаться от рассылки погоды одного города

## 🏗 Архитектура
### Основной стек
| Компонент       | Технология |
|----------------|------------|
| Бэкенд         | Laravel 12 |
| База данных    | SQLite/MySQL |
| Очереди        | Redis      |
| Планировщик    | Laravel Scheduler |

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
php artisan serve --port=8000

# В отдельных терминалах:
php artisan queue:work --timeout=3500
php artisan schedule:work

# Настройка ngrok
ngrok http 8000
curl -F "url=<NGROK_URL>" "https://api.telegram.org/bot<TELEGRAM_BOT_TOKEN>/setWebhook"
```

### Запуск через Docker
```bash
docker compose up -d

# Получаем ngrok URL
docker compose logs ngrok --tail=100

# Устанавливаем вебхук (замените параметры)
curl -F "url=<NGROK_URL>" "https://api.telegram.org/bot<TELEGRAM_BOT_TOKEN>/setWebhook"
```

### ⚙ Конфигурация
```ini
TELEGRAM_BOT_TOKEN=ваш_токен
WEATHER_API_KEY=ключ_weatherapi
FUSION_BRAIN_API_KEY=ключ_kandinsky
FUSION_BRAIN_SECRET_KEY=секрет_kandinsky
NGROK_AUTH_TOKEN=ваш_ngrok_токен

# База данных (по умолчанию SQLite)
DB_CONNECTION=sqlite
DB_DATABASE=/полный/путь/к/database.sqlite

# Очереди
QUEUE_CONNECTION=redis
```

### 🧪 Тестирование
```bash
php artisan test
```

### 📚 Документация API 
### 📬 Поддержка
- Проблемы: GitHub Issues
- Контакты в telegram: @the_best_bitch @KravtsovaMarina @KuroNeckojinja



