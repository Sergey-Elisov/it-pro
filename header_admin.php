<?php

?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>IT-PRO</title>
    <link rel="stylesheet" href="style.css" id="theme"> 
    <script>
        // Применяем тему при загрузке страницы
        document.addEventListener('DOMContentLoaded', function () {
            const themeLink = document.getElementById('theme');
            if (!themeLink) {
                console.error("Элемент <link> с id='theme' не найден!");
                return;
            }

            // Проверяем состояние из localStorage
            const isAccessibilityMode = localStorage.getItem('accessibilityMode') === 'true';
            themeLink.setAttribute('href', isAccessibilityMode ? 'accessible.css' : 'style.css'); 
            // Добавляем класс для body, если включена версия для слабовидящих
            document.body.classList.toggle('accessibility-mode', isAccessibilityMode);

            // Изменяем текст кнопки в зависимости от состояния
            const toggleButton = document.getElementById('accessibility-toggle');
            if (toggleButton) {
                toggleButton.textContent = isAccessibilityMode ? 'Обычная версия' : 'Версия для слабовидящих';
            }
        });

        // Переключение режима слабовидящих
        function toggleTheme() {
            const themeLink = document.getElementById('theme');
            if (!themeLink) {
                console.error("Элемент <link> с id='theme' не найден!");
                return;
            }

            // Проверяем текущее состояние
            const isAccessibilityMode = localStorage.getItem('accessibilityMode') === 'true';

            // Переключаем состояние
            const newMode = !isAccessibilityMode;
            localStorage.setItem('accessibilityMode', newMode);

            // Меняем стиль темы
            themeLink.setAttribute('href', newMode ? 'accessible.css' : 'style.css'); 

            // Добавляем или убираем класс для body
            document.body.classList.toggle('accessibility-mode', newMode); 

            // Изменяем текст кнопки
            const toggleButton = document.getElementById('accessibility-toggle');
            if (toggleButton) {
                toggleButton.textContent = newMode ? 'Обычная версия' : 'Версия для слабовидящих';
            }
        }
    </script>
</head>
<body>
    <header>
        <nav>
            <ul>
                <li class="logo-item">
                    <img src="photo/logo.jpg" alt="Жилищный комитет" style="height: 50px;">
                </li>
                <li><a href="index.php">Главная</a></li>
                <li><a href="about.php">О нас</a></li>
                <li><a href="services.php">Услуги</a></li>
                <li><a href="documents.php">Документы</a></li>
                <li><a href="contacts.php">Контакты</a></li>

                <!-- Для администратора -->
                <li><a href="admin.php">Панель администратора</a></li>
                <li><a href="dashboard.php">Личный кабинет</a></li>
                <li><a href="logout.php">Выйти</a></li>

                <li class="search-form-item">
                <form class="search-form" action="search.php" method="GET">
                <div class="search-input-wrapper">
                <input type="text" name="query" placeholder="Поиск..." required>
                <button type="submit">
                <img src="photo/search.png" alt="Поиск" style="height: 16px;">
                </button>
                </div>
                </form>
                </li>

                <li>
                    <button id="accessibility-toggle" onclick="toggleTheme()">Версия для слабовидящих</button>
                </li>
            </ul>
        </nav>
    </header>
</body>
</html>