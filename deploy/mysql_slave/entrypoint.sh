#!/bin/bash
set -eo pipefail

# Функция для безопасного логирования (скрывает пароли в логах)
log_message() {
  local safe_message=$(echo "$1" | sed "s/$MYSQL_ROOT_PASSWORD/*****/g" | sed "s/$MYSQL_MASTER_PASSWORD/*****/g")
  echo "$(date '+%Y-%m-%d %H:%M:%S') - $safe_message"
}

# Проверка соединения с мастер-сервером
check_master_connection() {
  log_message "Checking master server connection..."

  for i in {1..10}; do
    if mysql -h"$MYSQL_MASTER_HOST" -u"$MYSQL_MASTER_USER" -p"$MYSQL_MASTER_PASSWORD" \
      --ssl-mode=PREFERRED -NB -e "SELECT 1" 2>/dev/null | grep -q "1"; then
      log_message "Master server is available."
      return 0
    fi
    log_message "Retrying master connection... (attempt $i/10)"
    sleep 5
  done

  log_message "ERROR: Failed to connect to master after 10 attempts."
  return 1
}

# Создание дампа данных с мастер-сервера
create_master_dump() {
  log_message "Creating master data dump..."

  if ! mysqldump -h"$MYSQL_MASTER_HOST" -u"$MYSQL_MASTER_USER" -p"$MYSQL_MASTER_PASSWORD" \
    --all-databases \
    --single-transaction \
    --master-data=1 \
    --gtid \
    --flush-logs \
    --routines \
    --events \
    --triggers \
    --ssl-mode=PREFERRED \
    > /tmp/master_dump.sql 2>/tmp/dump_error.log; then

    log_message "ERROR: Failed to create master dump. Error: $(cat /tmp/dump_error.log)"
    rm -f /tmp/dump_error.log
    return 1
  fi
  rm -f /tmp/dump_error.log

  log_message "Master dump created successfully."
  return 0
}

# Импорт дампа данных в slave
import_master_dump() {
  log_message "Importing master data dump..."

  if ! mysql -uroot -p"$MYSQL_ROOT_PASSWORD" < /tmp/master_dump.sql 2>/tmp/import_error.log; then
    log_message "ERROR: Failed to import master dump. Error: $(cat /tmp/import_error.log)"
    rm -f /tmp/import_error.log /tmp/master_dump.sql
    return 1
  fi
  rm -f /tmp/import_error.log /tmp/master_dump.sql

  log_message "Master data imported successfully."
  return 0
}

# Настройка репликации
setup_replication() {
  log_message "Setting up replication..."

  mysql -uroot -p"$MYSQL_ROOT_PASSWORD" -e "
    STOP REPLICA;
    RESET REPLICA ALL;
    CHANGE REPLICATION SOURCE TO
      SOURCE_HOST='$MYSQL_MASTER_HOST',
      SOURCE_USER='$MYSQL_MASTER_USER',
      SOURCE_PASSWORD='$MYSQL_MASTER_PASSWORD',
      SOURCE_AUTO_POSITION=1,
      SOURCE_CONNECT_RETRY=10,
      SOURCE_SSL=1,
      SOURCE_RETRY_COUNT=86400;
    START REPLICA;" 2>/tmp/repl_error.log

  if [ $? -ne 0 ]; then
    log_message "ERROR: Failed to setup replication. Error: $(cat /tmp/repl_error.log)"
    rm -f /tmp/repl_error.log
    return 1
  fi
  rm -f /tmp/repl_error.log

  # Проверяем статус репликации
  sleep 5
  log_message "Replication status:"
  mysql -uroot -p"$MYSQL_ROOT_PASSWORD" -e "SHOW REPLICA STATUS\G" | \
    grep -E 'Replica_IO_Running:|Replica_SQL_Running:|Last_IO_Error:|Last_SQL_Error:'
}

# Основной код выполнения
log_message "Initializing MySQL replica..."

# Проверяем, первый ли это запуск
FIRST_RUN=0
if [ ! -d "/var/lib/mysql/mysql" ]; then
  FIRST_RUN=1
  log_message "First run detected - initializing MySQL data directory."
fi

# Запускаем MySQL в фоновом режиме без read-only
log_message "Starting MySQL temporarily in write mode..."
/usr/local/bin/docker-entrypoint.sh mysqld \
  --server-id=${SERVER_ID:-$(date +%s%N | cut -b1-9)} \
  --gtid-mode=ON \
  --enforce-gtid-consistency=ON \
  --skip-replica-start \
  --pid-file=/var/run/mysql/mysqld.pid \
  --log-slave-updates=ON \
  --relay-log=relay-bin \
  --relay-log-index=relay-bin.index &

# Ждем инициализации MySQL
timeout=120
while [ $timeout -gt 0 ]; do
  # Проверяем доступность сокета MySQL
  if [ ! -S /var/run/mysqld/mysqld.sock ]; then
    log_message "MySQL socket not found, waiting..."
    sleep 2
    timeout=$((timeout-2))
    continue
  fi

  # Проверяем подключение с паролем
  output=$(mysql -uroot -p"$MYSQL_ROOT_PASSWORD" -e "SELECT 1" 2>&1)
  status=$?

  if [ $status -eq 0 ]; then
    log_message "MySQL is ready and password is correct."
    break
  else
    # Анализируем ошибку
    if grep -q "Access denied" <<< "$output"; then
      log_message "ERROR: Incorrect root password detected!"
      log_message "Please check MYSQL_ROOT_PASSWORD environment variable."
      exit 1
    elif grep -q "Can't connect" <<< "$output"; then
      log_message "MySQL starting up..."
    else
      log_message "MySQL connection attempt failed: $output"
    fi

    sleep 2
    timeout=$((timeout-2))
    log_message "Waiting for MySQL to accept connections... ($timeout seconds remaining)"
  fi
done

if [ $timeout -le 0 ]; then
  log_message "ERROR: MySQL failed to start within the given timeout."
  exit 1
fi

# Настраиваем репликацию если master доступен
if [ -n "$MYSQL_MASTER_HOST" ]; then
  log_message "Master host configured: $MYSQL_MASTER_HOST"

  if [ -z "$MYSQL_MASTER_USER" ] || [ -z "$MYSQL_MASTER_PASSWORD" ]; then
    log_message "WARNING: Master credentials not provided. Skipping replication setup."
  else
    if check_master_connection; then
      if [ "$FIRST_RUN" -eq 1 ]; then
        log_message "First run - initializing from master..."
        if create_master_dump && import_master_dump; then
          setup_replication
        else
          log_message "ERROR: Failed to initialize from master. Starting in standalone mode."
        fi
      else
        log_message "Resuming existing replication..."
        setup_replication
      fi
    else
      log_message "Master not available. Starting in standalone mode."
    fi
  fi
else
  log_message "No master host configured. Starting in standalone mode."
fi

# Включаем read-only режим после всех настроек
log_message "Enabling super_read_only mode..."
mysql -uroot -p"$MYSQL_ROOT_PASSWORD" -e "SET GLOBAL super_read_only=1;"

# Переключаем на основной процесс MySQL с read-only
log_message "Starting final MySQL process in read-only mode..."
exec /usr/local/bin/docker-entrypoint.sh mysqld \
  --server-id=${SERVER_ID:-$(date +%s%N | cut -b1-9)} \
  --gtid-mode=ON \
  --enforce-gtid-consistency=ON \
  --read-only=ON \
  --super-read-only=ON \
  --pid-file=/var/run/mysql/mysqld.pid \
  --log-slave-updates=ON \
  --relay-log=relay-bin \
  --relay-log-index=relay-bin.index
