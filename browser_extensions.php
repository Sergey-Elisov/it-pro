<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Полезные расширения для браузера в IT сфере</title>
    <link rel="stylesheet" href="style.css" id="theme">
    <style>
        /* Общий стиль */
        body {
            font-family: 'Arial', sans-serif;
            line-height: 1.6;
            color: #333;
            margin: 0;
            padding: 0;
        }

        main {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
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

        /* Карточки с расширениями */
        .extensions-container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin: 20px 0;
        }

        .extension-card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
            padding: 20px;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .extension-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .extension-card h3 {
            margin-top: 0;
            color: #007bff;
            font-size: 18px;
        }

        .extension-card p {
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
        <h1>Полезные расширения для браузера в IT сфере</h1>
        <p>
            Эти расширения помогут вам ускорить разработку, упростить отладку, повысить продуктивность и исследовать веб-технологии.
        </p>

        <!-- Расширения в виде карточек -->
        <div class="extensions-container">
            <div class="extension-card">
                <h3>React Developer Tools</h3>
                <p>Необходимо для разработчиков, использующих React. Позволяет отлаживать приложения, просматривать дерево компонентов, состояние (state), props и т.д.</p>
                <a href="https://chrome.google.com/webstore/detail/react-developer-tools/fmkadmapgofadopljbjfkapdkoienihi" target="_blank" class="cta-button">Установить</a>
            </div>
            <div class="extension-card">
                <h3>Redux DevTools</h3>
                <p>Для разработчиков, использующих Redux. Позволяет отслеживать изменения состояния приложения.</p>
                <a href="https://chrome.google.com/webstore/detail/redux-devtools/lmhkpmbekcpmknklioeibfkpmmfibljd" target="_blank" class="cta-button">Установить</a>
            </div>
            <div class="extension-card">
                <h3>Octotree / GitHub Plus</h3>
                <p>Улучшают навигацию и просмотр кода на GitHub, добавляя боковое дерево файлов.</p>
                <a href="https://chrome.google.com/webstore/detail/octotree/bkhaagjahfmjljalopjnoealnfndnagc" target="_blank" class="cta-button">Установить</a>
            </div>
            <div class="extension-card">
                <h3>JSON Viewer</h3>
                <p>Удобно форматирует и отображает JSON-ответы от API.</p>
                <a href="https://chrome.google.com/webstore/detail/json-viewer/gbmdgpbipfallnflgajpaliibnhdgobh" target="_blank" class="cta-button">Установить</a>
            </div>
            <div class="extension-card">
                <h3>Wappalyzer</h3>
                <p>Показывает технологии, используемые на веб-сайте.</p>
                <a href="https://chrome.google.com/webstore/detail/wappalyzer/technology-profiling/gppongmhjkpfnbhagpmjfkannfbllamg" target="_blank" class="cta-button">Установить</a>
            </div>
            <div class="extension-card">
                <h3>ColorZilla / WhatFont</h3>
                <p>ColorZilla — пипетка для выбора цвета. WhatFont — определение шрифта на элементе.</p>
                <a href="https://chrome.google.com/webstore/detail/colorzilla/bhlhnicpbhignbdhedgjhgdocnmhomnp" target="_blank" class="cta-button">Установить</a>
            </div>
            <div class="extension-card">
                <h3>Window Resizer</h3>
                <p>Позволяет тестировать адаптивность веб-сайта.</p>
                <a href="https://chrome.google.com/webstore/detail/window-resizer/kkelicaakdanhinnmakfedkjnhlfneme" target="_blank" class="cta-button">Установить</a>
            </div>
            <div class="extension-card">
                <h3>User-Agent Switcher</h3>
                <p>Позволяет изменить User-Agent браузера для тестирования.</p>
                <a href="https://chrome.google.com/webstore/detail/user-agent-switcher-for-c/djflhoibgkdhkhhcedjiklpkjnoahfmg" target="_blank" class="cta-button">Установить</a>
            </div>
        </div>

        <!-- Полезная информация -->
        <div class="info-section">
            <h2>Совет</h2>
            <p>
                Используйте только те расширения, которые действительно необходимы для вашей работы. Чрезмерное количество расширений может замедлить браузер.
            </p>
        </div>
    </main>

    <?php include 'footer.php'; ?>
    <script src="/magnifier.js"></script> 
    <script src="/theme.js"></script> 
</body>
</html>