<?php
session_start();
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['user_id'])) {
    header('Location: catalog.php'); exit();
}

//Функция для безопасной загрузки переменных из файла .env
if (!function_exists('loadEnv')) {
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
}

// Загружаем .env из текущей директории
loadEnv(__DIR__ . '/.env');

// 3. Получаем настройки БД из переменных окружения
$host = $_ENV['DB_HOST'] ?? 'localhost';
$dbname = $_ENV['DB_NAME'];
$db_user = $_ENV['DB_USER'];
$db_pass = $_ENV['DB_PASS'];

$user_id = $_SESSION['user_id'];
$course_id = $_POST['course_id'] ?? 0;

// Берём цену из БД (защита от подмены)
$stmt = $pdo->prepare("SELECT price FROM courses WHERE id = ? AND is_published = 1");
$stmt->execute([$course_id]);
$course = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$course || $course['price'] <= 0) {
    die('Некорректный курс.');
}

// Проверяем, не куплен ли уже
$stmt = $pdo->prepare("SELECT id FROM enrollments WHERE user_id = ? AND course_id = ? AND status = 'active'");
$stmt->execute([$user_id, $course_id]);
if ($stmt->fetch()) {
    header('Location: dashboard.php?msg=already_enrolled'); exit();
}

// Фиксируем оплату (в боевом режиме здесь будет вызов API шлюза)
$stmt = $pdo->prepare("INSERT INTO payments (user_id, course_id, amount, status) VALUES (?, ?, ?, 'success')");
$stmt->execute([$user_id, $course_id, $course['price']]);

// Открываем доступ к курсу
$stmt = $pdo->prepare("INSERT INTO enrollments (user_id, course_id, status, progress_percent, enrolled_at) VALUES (?, ?, 'active', 0, NOW())");
$stmt->execute([$user_id, $course_id]);

header('Location: dashboard.php?payment=success');
exit();
