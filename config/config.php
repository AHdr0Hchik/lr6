<?php
define('DB_HOST', 'localhost');
define('DB_NAME', 'glucose_db');
define('DB_USER', 'root');         // подставь пользователя MySQL
define('DB_PASS', 'password');             // пароль, если есть

define('BASE_URL', 'http://glucose/'); // твой виртуальный хост

// Общий ключ для эмулятора и устройств
define('API_SHARED_KEY', 'MY_SUPER_SECRET_API_KEY');

// Включить отображение ошибок в режиме разработки
error_reporting(E_ALL);
ini_set('display_errors', 1);