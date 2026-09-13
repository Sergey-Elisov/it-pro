<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Вход и регистрация</title>
</head>
<body>
    <h1>Добро пожаловать!</h1>

    <h2>Вход</h2>
    <form action="login.php" method="POST">
        Email: <input type="email" name="email" required><br>
        Пароль: <input type="password" name="password" required><br>
        <button type="submit">Войти</button>
    </form>

    <h2>Регистрация</h2>
    <form action="register.php" method="POST">
        Имя пользователя: <input type="text" name="username" required><br>
        Email: <input type="email" name="email" required><br>
        Пароль: <input type="password" name="password" required><br>
        <button type="submit">Зарегистрироваться</button>
    </form>
</body>
</html>