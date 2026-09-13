<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Карта сайта</title>
    <link rel="stylesheet" href="style.css">
</head>
<?php include 'header.php'; ?>
<main>
    <h1>Карта сайта</h1>
    <ul>
        <li><a href="index.php">Главная</a></li>
        <li><a href="about.php">О нас</a></li>
        <li><a href="services.php">Услуги</a></li>
        <li><a href="documents.php">Документы</a></li>
        <li><a href="contacts.php">Контакты</a></li>
        <li><a href="appeal.php">Обращения</a></li>
        <li><a href="dashboard.php">Личный кабинет</a></li>
        <li><a href="trends_2026.php">Тренды в IT на 2026 год</a></li>
        <li><a href="burnout_it.php">Психология выгорания в IT</a></li>
        <li><a href="browser_extensions.php">Полезные расширения для браузера</a></li>
        </li>
    </ul>
</main>
<?php include 'footer.php'; ?>
<script src="theme.js"></script>
</body>
</html>