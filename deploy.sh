#!/bin/bash

# Очистка локальных изменений и принудительное обновление из репозитория
git reset --hard             # Сброс всех локальных изменений
git pull origin main --force # Принудительное обновление из ветки main

# Установка правильных прав для веб-сервера
chown -R www-data:www-data ./

# Обновление PHP зависимостей и очистка кеша
php composer.phar update # Обновление Composer пакетов

# Очистка кеша Laravel
php artisan config:clear # Очистка кеша конфигурации
php artisan route:clear  # Очистка кеша маршрутов
php artisan cache:clear  # Очистка кеша приложения
php artisan view:clear   # Очистка кеша шаблонов

# Пересборка кеша (только для production)
php artisan config:cache # Кеширование конфигурации
php artisan route:cache  # Кеширование маршрутов

# Генерируем swagger документацию
php artisan l5-swagger:generate

# Обновление Node.js зависимостей и сборка фронтенда
npm install   # Установка/обновление npm пакетов
npm run build # Сборка фронтенда (production)
