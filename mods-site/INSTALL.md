# GameMods Platform - Инструкция по установке на FastPanel

## 📋 Требования

- PHP 8.0+
- MySQL 8.0+
- Веб-сервер (Nginx/Apache)
- SSL сертификат

## 🚀 Установка на FastPanel

### 1. Создание сайта в FastPanel

1. Войдите в панель управления FastPanel
2. Перейдите в раздел **Сайты** → **Добавить сайт**
3. Укажите доменное имя
4. Выберите PHP версию (8.0 или выше)
5. Включите SSL (Let's Encrypt)

### 2. Создание базы данных

1. В FastPanel перейдите в раздел **Базы данных**
2. Создайте новую базу данных:
   - Имя БД: `game_mods_db`
   - Пользователь: `gamemods_user`
   - Пароль: сгенерируйте надежный пароль
3. Запишите данные для подключения

### 3. Импорт структуры БД

1. Откройте phpMyAdmin в FastPanel
2. Выберите созданную базу данных `game_mods_db`
3. Нажмите **Импорт** и загрузите файл `database.sql`
4. Или выполните SQL напрямую:
```bash
mysql -u gamemods_user -p game_mods_db < database.sql
```

### 4. Настройка конфигурации

Откройте файл `config.php` и измените настройки:

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'game_mods_db');
define('DB_USER', 'gamemods_user');
define('DB_PASS', 'ваш_пароль_от_бд');
define('APP_URL', 'https://ваш-домен.com');
define('APP_DEBUG', false); // true только для разработки
```

### 5. Настройка прав доступа

Установите правильные права на папки:

```bash
chmod 755 /path/to/site/
chmod 755 /path/to/site/uploads/
chmod 755 /path/to/site/uploads/avatars/
chmod 755 /path/to/site/uploads/mods/
chmod 644 /path/to/site/config.php
```

Или через FastPanel:
- Файловый менеджер → выделите папки → Права → 755

### 6. Настройка Nginx (опционально)

В настройках сайта в FastPanel добавьте:

```nginx
location / {
    try_files $uri $uri/ /index.html;
}

location ~ \.php$ {
    fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
    fastcgi_index index.php;
    include fastcgi_params;
    fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
}

# Защита конфигов
location ~ /\. {
    deny all;
}

location ~* \.(env|config\.php)$ {
    deny all;
}
```

## 🔐 Данные администратора по умолчанию

После импорта `database.sql` создан администратор:

- **Логин:** `admin`
- **Email:** `admin@gamemods.local`
- **Пароль:** `admin123`

⚠️ **ВАЖНО:** Сразу после входа смените пароль!

## 📁 Структура файлов

```
mods-site/
├── config.php          # Конфигурация (настроить!)
├── index.html          # Главная страница
├── login.php           # Страница входа
├── register.php        # Страница регистрации
├── profile.php         # Профиль пользователя
├── logout.php          # Выход из системы
├── database.sql        # Дамп базы данных
├── css/
│   └── style.css       # Стили
├── js/
│   └── main.js         # JavaScript
├── images/             # Изображения
└── uploads/
    ├── avatars/        # Аватарки пользователей
    └── mods/           # Файлы модов
```

## 🎯 Основные возможности

### Для пользователей:
- ✅ Регистрация и авторизация
- ✅ Личный профиль с аватаром
- ✅ Просмотр и скачивание модов
- ✅ Оценка и комментирование
- ✅ История активности

### Для администратора:
- ✅ Управление пользователями
- ✅ Модерация модов
- ✅ Статистика платформы
- ✅ Бэйджи ролей (ADMIN/MOD)

## 🔧 Дополнительные настройки

### Увеличение лимита загрузки

В `php.ini` или настройках PHP в FastPanel:
```ini
upload_max_filesize = 100M
post_max_size = 100M
max_execution_time = 300
memory_limit = 256M
```

### Настройка SMTP для уведомлений

Добавьте в `config.php`:
```php
define('SMTP_HOST', 'smtp.yourprovider.com');
define('SMTP_PORT', 587);
define('SMTP_USER', 'noreply@yourdomain.com');
define('SMTP_PASS', 'your_smtp_password');
```

## 🛡️ Безопасность

1. Смените пароль администратора
2. Отключите `APP_DEBUG` в продакшене
3. Настройте регулярные бэкапы БД
4. Обновляйте PHP и зависимости
5. Используйте сильные пароли

## 📊 Мониторинг

FastPanel предоставляет:
- Мониторинг нагрузки
- Логи ошибок
- Статистику посещений
- Управление базами данных

## 🆘 Решение проблем

### Ошибка подключения к БД
- Проверьте данные в `config.php`
- Убедитесь, что БД создана
- Проверьте права пользователя БД

### Ошибка 404 на страницах PHP
- Проверьте настройки Nginx/Apache
- Убедитесь, что PHP обработчик настроен

### Не загружаются аватарки
- Проверьте права на папку `uploads/avatars/`
- Убедитесь, что лимиты PHP позволяют загрузку

## 📞 Поддержка

При возникновении проблем:
1. Проверьте логи ошибок в FastPanel
2. Включите `APP_DEBUG = true` временно
3. Проверьте логи PHP и MySQL

---

**GameMods Platform** © 2024 - Платформа модов для игр
