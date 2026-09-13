<?php
session_start();

// Если уже авторизован — ведем в ЛК
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit();
}

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
$host = $_ENV['DB_HOST'] ?? 'localhost';
$dbname = $_ENV['DB_NAME'];
$db_user = $_ENV['DB_USER'];
$db_pass = $_ENV['DB_PASS'];

$error = '';

// Обрабатываем только POST-запросы 
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Используем utf8mb4 для полной поддержки Unicode
        $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $db_user, $db_pass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Получаем и чистим данные
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? ''); // <-- НОВОЕ ПОЛЕ
        $password = $_POST['password'] ?? '';

        // Валидация
        if (empty($username) || empty($email) || empty($phone) || empty($password)) {
            $error = 'Заполните все обязательные поля';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Некорректный Email';
        } elseif (!preg_match('/^[0-9\+\-\s\(\)]{7,20}$/', $phone)) { // <-- ПРОВЕРКА ФОРМАТА ТЕЛЕФОНА
            $error = 'Некорректный формат телефона (допустимы цифры, +, -, пробелы и скобки)';
        } elseif (strlen($password) < 5) {
            $error = 'Пароль минимум 5 символов';  
        } else {
            // Проверка на дубликат Email
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $error = 'Email уже зарегистрирован';
            } else {
                // Проверка на уникальность Username
                $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
                $stmt->execute([$username]);
                if ($stmt->fetch()) {
                    $error = 'Такое имя пользователя уже занято';
                } else {
                    // Хешируем пароль
                    $password_hash = password_hash($password, PASSWORD_DEFAULT);
                    
                    // ID роли "Студент" 
                    $default_role_id = 3;
                    
                    // ВСТАВКА: добавлено поле phone в запрос
                    $stmt = $pdo->prepare("
                        INSERT INTO users (username, email, phone, password, role, is_active, created_at) 
                        VALUES (?, ?, ?, ?, ?, 1, NOW())
                    ");
                    // <-- Добавлен $phone в массив значений
                    $stmt->execute([$username, $email, $phone, $password_hash, $default_role_id]);
                    
                    // Получаем ID и авторизуем
                    $user_id = $pdo->lastInsertId();
                    $_SESSION['user_id'] = $user_id;
                    $_SESSION['username'] = $username;
                    
                    // Редирект (обязательно до любого вывода!)
                    header('Location: dashboard.php?msg=registered');
                    exit();
                }
            }
        }
    } catch (PDOException $e) {
        error_log("Register error: " . $e->getMessage());
        $error = 'Ошибка регистрации. Попробуйте позже.';
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Регистрация | IT-pro</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .auth-container { max-width: 400px; margin: 50px auto; padding: 30px; background: #fff; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.08); }
        .auth-container h1 { margin: 0 0 25px; text-align: center; color: #0f172a; }
        .form-group { margin-bottom: 18px; }
        .form-group label { display: block; margin-bottom: 6px; font-weight: 500; color: #334155; }
        .form-group input { width: 100%; padding: 10px 12px; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 1rem; box-sizing: border-box; }
        .form-group input:focus { outline: none; border-color: #2563eb; box-shadow: 0 0 0 3px rgba(37,99,235,0.1); }
        .error-msg { background: #fef2f2; color: #dc2626; padding: 12px; border-radius: 8px; margin-bottom: 20px; font-size: 0.9rem; border-left: 4px solid #dc2626; }
        .btn-submit { width: 100%; padding: 12px; background: #2563eb; color: #fff; border: none; border-radius: 8px; font-size: 1rem; font-weight: 600; cursor: pointer; transition: 0.2s; }
        .btn-submit:hover { background: #1d4ed8; }
        .auth-link { text-align: center; margin-top: 20px; font-size: 0.9rem; color: #64748b; }
        .auth-link a { color: #2563eb; text-decoration: none; }
        .auth-link a:hover { text-decoration: underline; }
    </style>
</head>
<body>

<?php include 'header.php'; ?>

<main>
    <div class="auth-container">
        <h1>Регистрация</h1>
        
        <?php if ($error): ?>
            <div class="error-msg">❌ <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <form method="POST" action="register.php">
            <div class="form-group">
                <label for="username">Имя пользователя *</label>
                <input type="text" id="username" name="username" value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" required>
            </div>
            
            <div class="form-group">
                <label for="email">Email *</label>
                <input type="email" id="email" name="email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
            </div>

            <!-- НОВОЕ ПОЛЕ: ТЕЛЕФОН -->
            <div class="form-group">
                <label for="phone">Телефон *</label>
                <input type="tel" id="phone" name="phone" value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>" placeholder="+7 (999) 000-00-00" required>
            </div>
            
            <div class="form-group">
                <label for="password">Пароль *</label>
                <input type="password" id="password" name="password" required minlength="5">
            </div>
            
            <button type="submit" class="btn-submit">Зарегистрироваться</button>
        </form>
        
        <div class="auth-link">
            Уже есть аккаунт? <a href="login.php">Войти</a>
        </div>
    </div>
</main>

<?php include 'footer.php'; ?>
<script src="theme.js"></script>
</body>
</html>