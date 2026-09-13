<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php?redirect=payment.php');
    exit();
}

$host = 'localhost'; $dbname = 'selisooi_t_educa';
$db_user = 'selisooi_t_educa'; $db_pass = 'QAZwsx123!@#';
$pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $db_user, $db_pass);

$course_id = $_GET['course_id'] ?? 0;
$stmt = $pdo->prepare("SELECT id, title, price FROM courses WHERE id = ? AND is_published = 1");
$stmt->execute([$course_id]);
$course = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$course || $course['price'] <= 0) {
    header('Location: catalog.php'); exit();
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Оплата курса | IT-pro</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .payment-page { max-width: 500px; margin: 60px auto; padding: 30px; background: #fff; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.08); }
        .payment-page h1 { margin-top: 0; font-size: 1.8rem; }
        .course-summary { background: #f8fafc; padding: 15px; border-radius: 8px; margin: 20px 0; }
        .price-tag { font-size: 2rem; font-weight: 800; color: #0f172a; }
        .btn-pay { width: 100%; padding: 14px; background: #2563eb; color: #fff; border: none; border-radius: 8px; font-size: 1.1rem; font-weight: 600; cursor: pointer; }
        .btn-pay:hover { background: #1d4ed8; }
    </style>
</head>
<body>
<?php include 'header.php'; ?>
<div class="payment-page">
    <h1>Оплата курса</h1>
    <div class="course-summary">
        <strong><?= htmlspecialchars($course['title']) ?></strong>
        <div class="price-tag"><?= number_format($course['price'], 0, ',', ' ') ?> ₽</div>
    </div>
    <form action="process_payment.php" method="POST">
        <input type="hidden" name="course_id" value="<?= $course['id'] ?>">
        <input type="hidden" name="amount" value="<?= $course['price'] ?>">
        <button type="submit" class="btn-pay">💳 Оплатить картой</button>
    </form>
    <p style="text-align:center; margin-top:15px; color:#64748b; font-size:0.9rem;">
        🔒 Безопасная оплата. Данные защищены.
    </p>
</div>
<?php include 'footer.php'; ?>
</body>
</html>