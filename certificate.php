<?php
session_start();

// Защита: только авторизованные
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Загрузка .env
function loadEnv($path) {
    if (!file_exists($path)) die('Критическая ошибка: файл .env не найден.');
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if (empty($line) || strpos($line, '#') === 0) continue;
        $parts = explode('=', $line, 2);
        if (count($parts) === 2) {
            $_ENV[trim($parts[0])] = trim($parts[1], " \t\n\r\0\x0B\"'");
        }
    }
}
loadEnv(__DIR__ . '/.env');

// 2. Подключение к БД
$host = $_ENV['DB_HOST'] ?? 'localhost';
$dbname = $_ENV['DB_NAME'];
$db_user = $_ENV['DB_USER'];
$db_pass = $_ENV['DB_PASS'];

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Certificate DB Error: " . $e->getMessage());
    die("Ошибка подключения к базе данных.");
}

// Получаем данные: пользователь + завершённая запись на курс
$course_id = (int)($_GET['course_id'] ?? 0);
$user_id = (int)$_SESSION['user_id'];

$stmt = $pdo->prepare("
    SELECT u.username, u.email, c.title AS course_title, c.id AS course_id,
           e.id AS enrollment_id, e.status, e.enrolled_at
    FROM enrollments e
    JOIN users u ON e.user_id = u.id
    JOIN courses c ON e.course_id = c.id
    WHERE e.user_id = ? AND e.course_id = ?
");
$stmt->execute([$user_id, $course_id]);
$data = $stmt->fetch();

// Сертификат выдаём ТОЛЬКО за завершённый курс
if (!$data || $data['status'] !== 'completed') {
    header('Location: dashboard.php');
    exit();
}

// Формируем номер сертификата (уникальный и проверяемый)
$cert_number = sprintf('ITPRO-%s-%06d', date('Y'), $data['enrollment_id']);
$issue_date = date('d.m.Y');
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Сертификат <?= htmlspecialchars($cert_number) ?> | IT-pro</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: Georgia, 'Times New Roman', serif;
            background: #e2e8f0;
            padding: 40px 20px;
        }

        /* Сам сертификат */
        .certificate {
            max-width: 900px;
            margin: 0 auto;
            background: #fffdf7;
            border: 12px double #1e40af;
            padding: 60px 50px;
            text-align: center;
            position: relative;
            box-shadow: 0 10px 40px rgba(0,0,0,0.15);
        }
        .certificate::before {
            content: '';
            position: absolute;
            inset: 12px;
            border: 2px solid #c7a44a;
            pointer-events: none;
        }

        .cert-logo { font-size: 2.5rem; margin-bottom: 10px; }
        .cert-org { 
            text-transform: uppercase; 
            letter-spacing: 3px; 
            color: #64748b; 
            font-size: 0.9rem; 
            margin-bottom: 30px; 
        }
        .cert-title {
            font-size: 2.6rem;
            color: #1e40af;
            letter-spacing: 6px;
            text-transform: uppercase;
            margin-bottom: 30px;
        }
        .cert-text { font-size: 1.1rem; color: #334155; margin-bottom: 15px; }
        .cert-name {
            font-size: 2.2rem;
            color: #0f172a;
            font-style: italic;
            border-bottom: 2px solid #c7a44a;
            display: inline-block;
            padding: 0 40px 8px;
            margin-bottom: 25px;
        }
        .cert-course {
            font-size: 1.3rem;
            color: #1e293b;
            font-weight: bold;
            margin-bottom: 40px;
            line-height: 1.5;
        }
        .cert-footer {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            margin-top: 50px;
            font-size: 0.95rem;
            color: #475569;
        }
        .cert-footer div { text-align: left; }
        .cert-footer .right { text-align: right; }
        .cert-sign {
            font-size: 1.6rem;
            font-style: italic;
            color: #1e40af;
            margin-bottom: 4px;
        }
        .cert-line { border-top: 1px solid #94a3b8; padding-top: 6px; }
        .cert-seal {
            position: absolute;
            right: 60px;
            bottom: 110px;
            width: 110px;
            height: 110px;
            border: 3px solid #c7a44a;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #c7a44a;
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            transform: rotate(-12deg);
            text-align: center;
            line-height: 1.3;
        }

        /* Панель действий (не печатается) */
        .actions {
            max-width: 900px;
            margin: 25px auto 0;
            display: flex;
            gap: 12px;
            justify-content: center;
        }
        .btn {
            padding: 12px 24px;
            border-radius: 8px;
            border: none;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            color: #fff;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        .btn-print { background: #16a34a; }
        .btn-back { background: #64748b; }

        /* Печать: скрываем всё лишнее */
        @media print {
            body { background: #fff; padding: 0; }
            .actions { display: none !important; }
            .certificate { box-shadow: none; margin: 0; max-width: 100%; }
        }

        @media (max-width: 600px) {
            .certificate { padding: 30px 20px; }
            .cert-title { font-size: 1.6rem; letter-spacing: 3px; }
            .cert-name { font-size: 1.5rem; padding: 0 10px 6px; }
            .cert-footer { flex-direction: column; gap: 20px; align-items: center; }
            .cert-footer div { text-align: center; }
            .cert-seal { display: none; }
        }
    </style>
</head>
<body>

<div class="certificate">
    <div class="cert-logo">🎓</div>
    <div class="cert-org">Образовательная платформа IT-PRO</div>
    <div class="cert-title">Сертификат</div>
    
    <p class="cert-text">Настоящим подтверждается, что</p>
    <div class="cert-name"><?= htmlspecialchars($data['username']) ?></div>
    
    <p class="cert-text">успешно завершил(а) курс</p>
    <div class="cert-course">«<?= htmlspecialchars($data['course_title']) ?>»</div>
    
    <div class="cert-seal">IT-PRO<br>✔ verified</div>
    
    <div class="cert-footer">
        <div>
            <div class="cert-sign">IT-PRO Academy</div>
            <div class="cert-line">Подпись организации</div>
        </div>
        <div class="right">
            <div>№ <?= htmlspecialchars($cert_number) ?></div>
            <div>Дата выдачи: <?= $issue_date ?></div>
            <div class="cert-line">Регистрационные данные</div>
        </div>
    </div>
</div>

<div class="actions">
    <button class="btn btn-print" onclick="window.print()">🖨 Печать / Сохранить в PDF</button>
    <a class="btn btn-back" href="dashboard.php">← В личный кабинет</a>
</div>

</body>
</html>
