<?php
session_start();
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['user_id'])) {
    header('Location: catalog.php'); exit();
}

$host = 'localhost'; $dbname = 'selisooi_t_educa';
$db_user = 'selisooi_t_educa'; $db_pass = 'QAZwsx123!@#';
$pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $db_user, $db_pass);

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