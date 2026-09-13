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
        // Пропускаем пустые строки и комментарии
        if (empty($line) || strpos($line, '#') === 0) {
            continue;
        }
        
        $parts = explode('=', $line, 2);
        if (count($parts) === 2) {
            $key = trim($parts[0]);
            // Убираем пробелы и возможные кавычки вокруг значения
            $value = trim($parts[1], " \t\n\r\0\x0B\"'");
            
            $_ENV[$key] = $value;
            putenv("$key=$value");
        }
    }
}

// Загружаем .env из текущей директории
loadEnv(__DIR__ . '/.env');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 2. Получаем данные из переменных окружения
    $servername = $_ENV['DB_HOST'] ?? 'localhost';
    $username = $_ENV['DB_USER'];
    $password_db = $_ENV['DB_PASS'];
    $dbname = $_ENV['DB_NAME'];

    try {
        // Создаем подключение (используем utf8mb4 для полной поддержки Unicode, включая эмодзи)
        $pdo = new PDO("mysql:host=$servername;dbname=$dbname;charset=utf8mb4", $username, $password_db);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Получаем данные из формы
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        // Проверяем, что поля заполнены
        if (empty($email) || empty($password)) {
            $error = "Заполните все поля.";
        } else {
            // Ищем пользователя в таблице
            $stmt = $pdo->prepare("SELECT id, username, password FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && password_verify($password, $user['password'])) {
                // Сохраняем данные пользователя в сессии
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];

                // Перенаправляем на личный кабинет
                header("Location: dashboard.php");
                exit();
            } else {
                $error = "Неверный email или пароль.";
            }
        }
    } catch (PDOException $e) {
        // В продакшене лучше логировать $e->getMessage() в файл, а пользователю показывать общую ошибку
        error_log("Ошибка БД при входе: " . $e->getMessage());
        $error = "Произошла ошибка при подключении к системе. Попробуйте позже.";
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Вход</title>
    <link rel="stylesheet" href="style.css">
</head>
<?php include 'header.php'; ?>
<body>
    <main>
        <div class="form-container">
            <h1>Вход в систему</h1>
            <form action="login.php" method="POST">
                <label for="email">Email:</label>
                <input type="email" id="email" name="email" required><br>
                
                <label for="password">Пароль:</label>
                <input type="password" id="password" name="password" required><br>
                
                <button type="submit">Войти</button>
            </form>
            <?php if (isset($error)): ?>
                <p style="color: red; margin-top: 10px;"><?php echo htmlspecialchars($error); ?></p>
            <?php endif; ?>
        </div>
    </main>
    <?php include 'footer.php'; ?>
    <script src="magnifier.js"></script> 
    <script src="theme.js"></script> 
</body>
</html>