<?php
// Функция для безопасной загрузки переменных из файла .env
function loadEnv($path) {
    if (!file_exists($path)) {
        die('Критическая ошибка: файл конфигурации .env не найден.');
    }
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if (empty($line) || strpos($line, '#') === 0) {
            continue;
        }
        $parts = explode('=', $line, 2);
        if (count($parts) === 2) {
            $key = trim($parts[0]);
            $value = trim($parts[1], " \t\n\r\0\x0B\"'");
            $_ENV[$key] = $value;
            putenv("$key=$value");
        }
    }
}

// Загружаем .env из текущей директории
loadEnv(__DIR__ . '/.env');

// 2. Получаем настройки БД из переменных окружения
$host = $_ENV['DB_HOST'] ?? 'localhost';
$dbname = $_ENV['DB_NAME'];
$username = $_ENV['DB_USER']; // Имя пользователя БД (например, selisooi_t_educa)
$password = $_ENV['DB_PASS']; // Пароль пользователя БД

try {
    // Создание подключения через PDO с кодировкой utf8mb4 (полная поддержка Unicode)
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);

    // Настройка режима ошибок PDO (выбрасывать исключения)
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Хорошая практика: возвращать массивы по умолчанию при fetch, чтобы не писать это в каждом запросе
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    error_log("DB Connection Error (db.php): " . $e->getMessage());
    die("Ошибка подключения к базе данных. Попробуйте позже.");
}
?>