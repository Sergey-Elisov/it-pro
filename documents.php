<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Полезное</title>
    <link rel="stylesheet" href="style.css">
</head>
<?php include 'header.php'; ?>
   <main>
    <h1>Полезное</h1>
    <ul>
        <li><a href="law.php">Словарь терминов (IT-глоссарий).</a></li>
        <li><a href="repair.php">Топ-10 навыков, которые ищут работодатели.</a></li>
        <li><a href="browser_extensions.php">Полезные расширения для браузера.</a></li>
        <li><a href="trends_2026.php">Тренды в IT на 2026 год.</a></li>
        <li><a href="burnout_it.php">Психология выгорания в IT.</a></li>
        <li><a href="cybersecurity_basics.php">Основы кибербезопасности.</a></li>
    </ul>
</main>
    <?php include 'footer.php'; ?>
    <script src="magnifier.js"></script>
    <script src="theme.js"></script>

