<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<?php session_start(); ?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Страница не найдена | IT-pro</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<?php include 'header.php'; ?>

<main class="error-page">
    <div class="error-content">
        <h1>Ошибка 404</h1>
        <p>Извините, запрашиваемая страница не найдена или была перемещена.</p>
        <div class="error-actions">
            <a href="index.php" class="btn btn-primary">Вернуться на главную</a>
            <button onclick="history.back()" class="btn btn-outline">Вернуться назад</button>
        </div>
    </div>
</main>

<?php include 'footer.php'; ?>
<script src="theme.js"></script>
</body>
</html>