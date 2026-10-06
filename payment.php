<?php
session_start();

// Если не авторизован — отправляем на вход
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php?redirect=payment.php');
    exit();
}

// Загрузка .env
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
loadEnv(__DIR__ . '/.env');

// Подключение к БД
$host = $_ENV['DB_HOST'] ?? 'localhost';
$dbname = $_ENV['DB_NAME'];
$db_user = $_ENV['DB_USER'];
$db_pass = $_ENV['DB_PASS'];

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Payment DB Error: " . $e->getMessage());
    die("Ошибка подключения к базе данных.");
}

$message = '';
$messageType = '';
$course = null;

// БРАБОТКА ОПЛАТЫ (ЗАГЛУШКА ДЛЯ ТЕСТИРОВАНИЯ)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['course_id'], $_POST['amount'])) {
    $course_id = (int)$_POST['course_id'];
    $amount = (float)$_POST['amount'];
    $user_id = (int)$_SESSION['user_id'];
    
    // Получаем данные курса
    $stmt = $pdo->prepare("SELECT id, title, price FROM courses WHERE id = ? AND is_published = 1");
    $stmt->execute([$course_id]);
    $course = $stmt->fetch();
    
    if (!$course) {
        $message = '❌ Курс не найден или не опубликован.';
        $messageType = 'error';
    } elseif ($course['price'] != $amount) {
        $message = '❌ Несоответствие суммы оплаты.';
        $messageType = 'error';
    } else {
        try {
            // Проверяем, не записан ли уже пользователь на этот курс
            $stmt = $pdo->prepare("SELECT id FROM enrollments WHERE user_id = ? AND course_id = ?");
            $stmt->execute([$user_id, $course_id]);
            
            if ($stmt->fetch()) {
                $message = '⚠️ Вы уже записаны на этот курс.';
                $messageType = 'warning';
            } else {
                //  СОЗДАЕМ ЗАПИСЬ О ЗАЧИСЛЕНИИ (ЗАГЛУШКА)
                $stmt = $pdo->prepare("
                    INSERT INTO enrollments (user_id, course_id, status, enrolled_at, progress_percent) 
                    VALUES (?, ?, 'active', NOW(), 0)
                ");
                $stmt->execute([$user_id, $course_id]);
                
                $message = '✅ Оплата прошла успешно! Вы записаны на курс.';
                $messageType = 'success';
                
                // Перенаправляем на страницу курса через 2 секунды
                header("refresh:2;url=course.php?slug=" . urlencode($course['slug']));
            }
        } catch (PDOException $e) {
            error_log("Enrollment error: " . $e->getMessage());
            $message = '❌ Ошибка при зачислении на курс.';
            $messageType = 'error';
        }
    }
}

// Если GET-запрос — показываем форму оплаты
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['course_id'])) {
    $course_id = (int)$_GET['course_id'];
    $stmt = $pdo->prepare("SELECT id, title, price FROM courses WHERE id = ? AND is_published = 1");
    $stmt->execute([$course_id]);
    $course = $stmt->fetch();
    
    if (!$course || $course['price'] <= 0) {
        header('Location: catalog.php');
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Оплата курса | IT-pro</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .payment-page { 
            max-width: 500px; 
            margin: 60px auto; 
            padding: 30px; 
            background: #fff; 
            border-radius: 12px; 
            box-shadow: 0 4px 20px rgba(0,0,0,0.08); 
        }
        .payment-page h1 { 
            margin-top: 0; 
            font-size: 1.8rem; 
            color: #0f172a;
            text-align: center;
        }
        .course-summary { 
            background: #f8fafc; 
            padding: 20px; 
            border-radius: 8px; 
            margin: 25px 0; 
            text-align: center;
            border: 1px solid #e2e8f0;
        }
        .course-summary strong {
            display: block;
            font-size: 1.1rem;
            margin-bottom: 10px;
            color: #334155;
        }
        .price-tag { 
            font-size: 2.2rem; 
            font-weight: 800; 
            color: #0f172a; 
        }
        .btn-pay { 
            width: 100%; 
            padding: 16px; 
            background: #2563eb; 
            color: #fff; 
            border: none; 
            border-radius: 8px; 
            font-size: 1.1rem; 
            font-weight: 600; 
            cursor: pointer; 
            transition: all 0.2s;
        }
        .btn-pay:hover { 
            background: #1d4ed8; 
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);
        }
        .secure-badge {
            text-align: center; 
            margin-top: 20px; 
            color: #64748b; 
            font-size: 0.85rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
        }
        .message {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-weight: 500;
            text-align: center;
        }
        .message.success {
            background: #f0fdf4;
            color: #166534;
            border-left: 4px solid #16a34a;
        }
        .message.error {
            background: #fef2f2;
            color: #dc2626;
            border-left: 4px solid #dc2626;
        }
        .message.warning {
            background: #fffbeb;
            color: #92400e;
            border-left: 4px solid #f59e0b;
        }
        .test-badge {
            background: #fef3c7;
            color: #92400e;
            padding: 8px 12px;
            border-radius: 6px;
            font-size: 0.85rem;
            text-align: center;
            margin-bottom: 20px;
            border: 1px solid #fde68a;
        }
    </style>
</head>
<body>

<?php include 'header.php'; ?>

<main>
    <div class="payment-page">
        
        <?php if ($message): ?>
            <div class="message <?= $messageType ?>">
                <?= htmlspecialchars($message) ?>
                <?php if ($messageType === 'success'): ?>
                    <br><small>Перенаправление на курс через 2 секунды...</small>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        
        <?php if ($course && $_SERVER['REQUEST_METHOD'] === 'GET'): ?>
            <h1>💳 Оформление оплаты</h1>
            
            <div class="course-summary">
                <strong><?= htmlspecialchars($course['title']) ?></strong>
                <div class="price-tag"><?= number_format($course['price'], 0, ',', ' ') ?> ₽</div>
            </div>
            
            <form action="payment.php" method="POST">
                <input type="hidden" name="course_id" value="<?= (int)$course['id'] ?>">
                <input type="hidden" name="amount" value="<?= (float)$course['price'] ?>">
                <button type="submit" class="btn-pay">✅ Оплатить </button>
            </form>
            
            <div class="secure-badge">
                <span>🔒</span> Безопасная оплата. Данные защищены шифрованием.
            </div>
        <?php elseif (!$course && $_SERVER['REQUEST_METHOD'] === 'GET'): ?>
            <h1>Курс не найден</h1>
            <p style="text-align:center; color:#64748b;">
                <a href="catalog.php" style="color:#2563eb;">← Вернуться в каталог</a>
            </p>
        <?php endif; ?>
    </div>
</main>

<?php include 'footer.php'; ?>
<script src="theme.js"></script>
</body>
</html>
