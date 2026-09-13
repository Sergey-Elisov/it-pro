<?php
session_start();

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

// Проверка авторизации
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Проверка метода и ID курса
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['course_id'])) {
    header('Location: catalog.php');
    exit();
}

// Строгая типизация ID для безопасности
$course_id = (int)($_POST['course_id'] ?? 0);
$user_id = (int)$_SESSION['user_id'];

if ($course_id <= 0) {
    header('Location: catalog.php');
    exit();
}

// Получаем настройки БД из переменных окружения
$host = $_ENV['DB_HOST'] ?? 'localhost';
$dbname = $_ENV['DB_NAME'];
$db_user = $_ENV['DB_USER'];
$db_pass = $_ENV['DB_PASS'];

try {
    // Используем utf8mb4 для полной поддержки Unicode
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    
    // Проверяем, существует ли вообще курс и не записан ли уже пользователь
    $stmt = $pdo->prepare("
        SELECT 
            c.id AS course_exists,
            c.is_published,
            e.id AS already_enrolled
        FROM courses c
        LEFT JOIN enrollments e ON e.course_id = c.id AND e.user_id = ?
        WHERE c.id = ?
    ");
    $stmt->execute([$user_id, $course_id]);
    $result = $stmt->fetch();
    
    // Курс не найден или не опубликован
    if (!$result || !$result['course_exists'] || !$result['is_published']) {
        header('Location: catalog.php?msg=course_not_found');
        exit();
    }
    
    // Пользователь уже записан — просто ведем в ЛК
    if ($result['already_enrolled']) {
        header('Location: dashboard.php');
        exit();
    }

    // Делаем запись в БД
    $stmt = $pdo->prepare("
        INSERT INTO enrollments (user_id, course_id, status, progress_percent, enrolled_at) 
        VALUES (?, ?, 'active', 0, NOW())
    ");
    $stmt->execute([$user_id, $course_id]);

    // 7. Успех — ведем в ЛК с сообщением об успехе
    header('Location: dashboard.php?msg=enrolled');
    exit();

} catch (PDOException $e) {
    // ВАЖНО: Никогда не выводим детали ошибки пользователю на продакшене
    error_log("Enroll error (user_id={$user_id}, course_id={$course_id}): " . $e->getMessage());
    
    // Если это дубликат записи (на случай гонки запросов)
    if ($e->getCode() == 23000) {
        header('Location: dashboard.php');
    } else {
        header('Location: catalog.php?msg=error');
    }
    exit();
}
?>