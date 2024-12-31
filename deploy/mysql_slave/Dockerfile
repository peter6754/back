# Используем официальный образ MySQL
FROM mysql:8.0

# Копируем наш entrypoint скрипт
COPY entrypoint.sh /usr/local/bin/entrypoint.sh

# Делаем скрипт исполняемым
RUN chmod +x /usr/local/bin/entrypoint.sh

# Устанавливаем наш скрипт как точку входа
ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]

# Команда по умолчанию (будет запущена нашим entrypoint)
CMD ["mysqld"]
