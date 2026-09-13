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

// Получаем настройки БД из переменных окружения
$servername = $_ENV['DB_HOST'] ?? 'localhost';
$dbname = $_ENV['DB_NAME'];
$username_db = $_ENV['DB_USER'];
$password_db = $_ENV['DB_PASS'];

try {
    // Используем utf8mb4 для полной поддержки Unicode
    $pdo = new PDO("mysql:host=$servername;dbname=$dbname;charset=utf8mb4", $username_db, $password_db);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    // Проверка роли (строгая проверка: 1, '1' или 'admin')
    $stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();

    if (!$user || ($user['role'] != 1 && $user['role'] !== 'admin')) {
        header("Location: dashboard.php");
        exit();
    }
} catch (PDOException $e) {
    // Никогда не выводим детали ошибки пользователю на продакшене
    error_log("DB Connection Error (edit_about.php): " . $e->getMessage());
    die("Ошибка подключения к базе данных.");
}

$page = 'about';
$content_dir = __DIR__ . '/content'; // Абсолютный путь для надежности
$file_path = $content_dir . "/{$page}.html";
$message = '';
$messageType = '';

// Редактирование содержимого
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Получаем контент (разрешаем HTML, так как это админский редактор)
    $content = $_POST['content'] ?? '';

    // Гарантируем, что директория существует и имеет правильные права
    if (!is_dir($content_dir)) {
        if (!mkdir($content_dir, 0755, true)) {
            $message = '❌ Не удалось создать папку content/. Проверьте права доступа на сервере.';
            $messageType = 'error';
        }
    }

    // Если папка есть (или успешно создана), пытаемся записать файл
    if (empty($message)) {
        if (file_put_contents($file_path, $content) !== false) {
            $message = '✅ Содержимое страницы «О нас» успешно сохранено!';
            $messageType = 'success';
        } else {
            $message = '❌ Ошибка при записи файла. Возможно, нет прав на запись в папку content/.';
            $messageType = 'error';
        }
    }
}

// Читаем текущий контент для отображения в форме
$current_content = file_exists($file_path) ? file_get_contents($file_path) : '<p>Здесь будет текст о компании...</p>';
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Редактировать "О нас" | Админ-панель</title>
    <link rel="stylesheet" href="/style.css">
    <style>
        /* Стили для админской формы редактирования */
        .edit-container { max-width: 900px; margin: 30px auto; padding: 30px; background: #fff; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); }
        .edit-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .edit-header h1 { margin: 0; color: #1e40af; font-size: 1.5rem; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: 600; color: #334155; }
        .form-group textarea { 
            width: 100%; min-height: 400px; padding: 15px; border: 1px solid #cbd5e1; 
            border-radius: 8px; font-size: 1rem; font-family: monospace; line-height: 1.5;
            transition: border-color 0.2s; box-sizing: border-box;
        }
        .form-group textarea:focus { outline: none; border-color: #2563eb; box-shadow: 0 0 0 3px rgba(37,99,235,0.1); }
        .btn-save { 
            padding: 12px 24px; background: #16a34a; color: white; border: none; 
            border-radius: 8px; font-size: 1rem; font-weight: 600; cursor: pointer; transition: 0.2s; 
        }
        .btn-save:hover { background: #15803d; }
        .message { padding: 12px 16px; border-radius: 8px; margin-bottom: 20px; font-weight: 500; }
        .message.success { background: #f0fdf4; color: #166534; border-left: 4px solid #16a34a; }
        .message.error { background: #fef2f2; color: #dc2626; border-left: 4px solid #dc2626; }
    </style>
</head>
<body>

<?php include 'header.php'; ?>

<main class="edit-container">
    <div class="edit-header">
        <h1>✏️ Редактирование страницы "О нас"</h1>
        <a href="/about.php" target="_blank" style="color: #2563eb; text-decoration: none; font-size: 0.9rem;">👁️ Просмотреть на сайте</a>
    </div>

    <?php if ($message): ?>
        <div class="message <?= htmlspecialchars($messageType) ?>">
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>

    <form method="POST">
        <div class="form-group">
            <label for="content">HTML-содержимое страницы:</label>
            <textarea id="content" name="content" placeholder="<h1>О нас</h1><p>Текст...</p>"><?= htmlspecialchars($current_content, ENT_QUOTES, 'UTF-8') ?></textarea>
            <small style="color: #64748b; display: block; margin-top: 6px;">
                💡 Вы можете использовать HTML-теги. Будьте осторожны с тегами &lt;script&gt;.
            </small>
        </div>

        <button type="submit" class="btn-save">💾 Сохранить изменения</button>
    </form>
</main>

<?php include 'footer.php'; ?>
<script src="/theme.js"></script>
</body>
</html>