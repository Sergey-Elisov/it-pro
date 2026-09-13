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

// 2. Получаем настройки БД из переменных окружения
$servername = $_ENV['DB_HOST'] ?? 'localhost';
$username = $_ENV['DB_USER'];
$password_db = $_ENV['DB_PASS'];
$dbname = $_ENV['DB_NAME'];

try {
    // Используем utf8mb4 для полной поддержки Unicode
    $pdo = new PDO("mysql:host=$servername;dbname=$dbname;charset=utf8mb4", $username, $password_db);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    error_log("DB Connection Error: " . $e->getMessage());
    die("Ошибка подключения к базе данных.");
}

// Проверка, является ли пользователь авторизованным
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Проверка, является ли пользователь администратором
// ⚠️ ВАЖНО: В вашем коде было 'tablic'. Я заменил на 'users', чтобы соответствовать остальным файлам.
// Если у вас действительно есть отдельная таблица 'tablic' для админов, верните это название.
$stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user || $user['role'] !== 'admin' && $user['role'] !== '1') { // Проверка и на строку '1', и на 'admin'
    header("Location: dashboard.php");
    exit();
}

$error = '';

// Создание пользователя
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Получаем и очищаем данные
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? 'user'; // Защита от подмены роли через DevTools

    // Валидация
    if (empty($name) || empty($email) || empty($password)) {
        $error = "Заполните все обязательные поля.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Некорректный формат Email.";
    } elseif (strlen($password) < 5) {
        $error = "Пароль должен содержать минимум 5 символов.";
    } else {
        try {
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            
            
            $stmt = $pdo->prepare("
                INSERT INTO users (username, email, password, role, is_active, created_at) 
                VALUES (?, ?, ?, ?, 1, NOW())
            ");
            $stmt->execute([$name, $email, $password_hash, $role]);

            header("Location: manage_users.php"); // Убедитесь, что этот файл существует
            exit();
        } catch (PDOException $e) {
            // Если email уже существует (уникальный индекс в БД)
            if ($e->errorInfo[1] == 1062) {
                $error = "Пользователь с таким Email уже существует.";
            } else {
                error_log("Create user error: " . $e->getMessage());
                $error = "Ошибка при создании пользователя. Попробуйте позже.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Создать пользователя | Админ-панель</title>
    <link rel="stylesheet" href="/style.css">
    <style>
        /* Добавим немного стилей для формы, чтобы она выглядела аккуратно, как в register.php */
        .admin-form-container { max-width: 500px; margin: 30px auto; padding: 30px; background: #fff; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.08); }
        .form-group { margin-bottom: 18px; }
        .form-group label { display: block; margin-bottom: 6px; font-weight: 500; color: #334155; }
        .form-group input, .form-group select { width: 100%; padding: 10px 12px; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 1rem; box-sizing: border-box; }
        .form-group input:focus, .form-group select:focus { outline: none; border-color: #2563eb; box-shadow: 0 0 0 3px rgba(37,99,235,0.1); }
        .error-msg { background: #fef2f2; color: #dc2626; padding: 12px; border-radius: 8px; margin-bottom: 20px; font-size: 0.9rem; border-left: 4px solid #dc2626; }
        .btn-submit { width: 100%; padding: 12px; background: #2563eb; color: #fff; border: none; border-radius: 8px; font-size: 1rem; font-weight: 600; cursor: pointer; transition: 0.2s; }
        .btn-submit:hover { background: #1d4ed8; }
    </style>
</head>
<body>

<?php include 'header_admin.php'; ?>

<main>
    <div class="admin-form-container">
        <h1>Создать нового пользователя</h1>
        
        <?php if (!empty($error)): ?>
            <div class="error-msg">❌ <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-group">
                <label for="name">Имя пользователя (Login):</label>
                <input type="text" name="name" id="name" value="<?= htmlspecialchars($_POST['name'] ?? '') ?>" required>
            </div>

            <div class="form-group">
                <label for="email">Email:</label>
                <input type="email" name="email" id="email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
            </div>

            <div class="form-group">
                <label for="password">Пароль:</label>
                <input type="password" name="password" id="password" required minlength="5">
            </div>

            <div class="form-group">
                <label for="role">Роль:</label>
                <select name="role" id="role">
                    <option value="3" <?= (($_POST['role'] ?? '') === '3') ? 'selected' : '' ?>>Студент (3)</option>
                    <option value="2" <?= (($_POST['role'] ?? '') === '2') ? 'selected' : '' ?>>Преподаватель (2)</option>
                    <option value="1" <?= (($_POST['role'] ?? '') === '1') ? 'selected' : '' ?>>Администратор (1)</option>
                </select>
            </div>

            <button type="submit" class="btn-submit">Создать пользователя</button>
        </form>
        
        <p style="text-align: center; margin-top: 20px;">
            <a href="manage_users.php" style="color: #64748b; text-decoration: none;">← Вернуться к списку пользователей</a>
        </p>
    </div>
</main>

<?php include 'footer.php'; ?>
<script src="/theme.js"></script>
</body>
</html>