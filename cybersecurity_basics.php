<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Основы кибербезопасности</title>
    <link rel="stylesheet" href="style.css" id="theme">
    <style>
        /* Общий стиль */
        body {
            font-family: 'Arial', sans-serif;
            line-height: 1.6;
            color: #333;
            margin: 0;
            padding: 0;
            background-color: #f9f9f9;
        }

        main {
            max-width: 1200px; /* Ограничение ширины контента */
            margin: 0 auto; /* Центрирование контента */
            padding: 20px; /* Отступы внутри контейнера */
            box-sizing: border-box; /* Учитываем padding в ширину */
        }

        h1 {
            text-align: center;
            margin-bottom: 20px;
            color: #007bff;
            animation: fadeIn 2s ease-in-out;
        }

        p {
            margin-bottom: 20px;
        }

        nav[aria-label="breadcrumb"] li {
            display: inline;
        }

        nav[aria-label="breadcrumb"] a {
            text-decoration: none;
            color: #007bff;
            transition: color 0.3s ease;
        }

        nav[aria-label="breadcrumb"] a:hover {
            color: #0056b3;
        }

        /* Карточки для правил */
        .rules-container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .rule-card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
            padding: 20px;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .rule-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .rule-card h3 {
            margin-top: 0;
            color: #007bff;
            font-size: 18px;
            display: flex;
            align-items: center;
        }

        .rule-card h3::before {
            content: '';
            display: inline-block;
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background-color: #007bff;
            margin-right: 10px;
        }

        .rule-card p {
            color: #666;
            line-height: 1.5;
            margin-bottom: 0;
        }

        /* Градиентные кнопки */
        .cta-button {
            display: inline-block;
            padding: 10px 20px;
            font-size: 16px;
            font-weight: bold;
            color: white;
            background: linear-gradient(90deg, #007bff, #00d2ff);
            border: none;
            border-radius: 25px;
            text-decoration: none;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .cta-button:hover {
            transform: scale(1.05);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }

        /* Полезная информация */
        .info-section {
            background-color: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
        }

        .info-section h2 {
            color: #007bff;
            margin-bottom: 10px;
        }

        .info-section p {
            color: #666;
        }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>
    
    <main>

        <!-- Анимированный заголовок -->
        <h1><strong>Основы кибербезопасности для начинающих</strong></h1>

        <p>Кибербезопасность — это практика защиты систем, сетей, устройств и информации от цифровых атак, повреждений или несанкционированного доступа. Вот основные вещи, которые стоит знать каждому пользователю интернета:</p>

        <!-- Правила в виде карточек -->
        <div class="rules-container">
            <div class="rule-card">
                <h3>Создавайте надёжные пароли</h3>
                <p>Используйте уникальные, сложные пароли для каждого аккаунта. Рассмотрите использование менеджера паролей.</p>
            </div>
            <div class="rule-card">
                <h3>Включите двухфакторную аутентификацию (2FA)</h3>
                <p>Где бы ни было возможно, включайте 2FA для дополнительного уровня защиты.</p>
            </div>
            <div class="rule-card">
                <h3>Будьте бдительны: распознавайте фишинг</h3>
                <p>Не переходите по подозрительным ссылкам и не скачивайте вложения из неизвестных писем.</p>
            </div>
            <div class="rule-card">
                <h3>Обновляйте программное обеспечение</h3>
                <p>Регулярно устанавливайте обновления для ОС, браузера и других программ.</p>
            </div>
            <div class="rule-card">
                <h3>Используйте антивирусное программное обеспечение</h3>
                <p>Установите и поддерживайте в актуальном состоянии надёжный антивирус.</p>
            </div>
            <div class="rule-card">
                <h3>Будьте осторожны в общественных Wi-Fi сетях</h3>
                <p>Избегайте важных действий, используйте VPN.</p>
            </div>
            <div class="rule-card">
                <h3>Делайте резервные копии важных данных</h3>
                <p>Регулярно копируйте файлы на внешний диск или в облако.</p>
            </div>
            <div class="rule-card">
                <h3>Проверяйте приложения перед установкой</h3>
                <p>Устанавливайте только из официальных магазинов.</p>
            </div>
            <div class="rule-card">
                <h3>Ограничьте доступ к информации</h3>
                <p>Настройте приватность в соцсетях.</p>
            </div>
            <div class="rule-card">
                <h3>Обучайтесь и будьте в курсе</h3>
                <p>Следите за новостями и регулярно обучайтесь.</p>
            </div>
        </div>

        <!-- Полезная информация -->
        <div class="info-section">
            <h2>Совет</h2>
            <p>
                Соблюдение этих основных правил значительно снижает риск стать жертвой кибератак.
            </p>
        </div>
    </main>

    <?php include 'footer.php'; ?>
    <script src="/magnifier.js"></script> 
    <script src="/theme.js"></script> 
</body>
</html>