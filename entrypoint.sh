#!/bin/bash
set -e

DB_DIR="/var/www/private/_hidden_db_"
DB_FILE="$DB_DIR/database.db"

# Создаем папку БД если её нет (важно при первом запуске)
if [ ! -d "$DB_DIR" ]; then
    mkdir -p "$DB_DIR"
fi

# Проверяем права на папку с базой
chown -R www-data:www-data "$DB_DIR"
chmod -R 775 "$DB_DIR"

# Если базы нет — инициируем (твой скрипт)
if [ ! -s "$DB_FILE" ]; then
    if [ -f "/init_db.php" ]; then
        php "/init_db.php"
        chown www-data:www-data "$DB_FILE"
    fi
fi

exec php-fpm