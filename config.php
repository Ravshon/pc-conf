<?php
// Настройки подключения к базе данных
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASSWORD', '');
define('DB_NAME', 'pc_configurator');

// Подключение к базе данных
$conn = mysqli_connect(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);

// Проверка подключения
if (!$conn) {
    die("Ошибка подключения к базе данных: " . mysqli_connect_error());
}

// Установка кодировки
mysqli_set_charset($conn, "utf8mb4");

// Функция для очистки данных
function clean($data) {
    global $conn;
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    $data = mysqli_real_escape_string($conn, $data);
    return $data;
}

// Настройки сайта
define('SITE_NAME', 'Конфигуратор ПК');
define('BASE_URL', 'http://localhost/pc-configurator');