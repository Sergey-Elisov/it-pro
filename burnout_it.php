<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Психология выгорания в IT</title>
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
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        h1 {
            text-align: center;
            margin-bottom: 20px;
            color: #007bff;
        }

        /* Выпадающие списки */
        details {
            margin-bottom: 15px;
            border: 1px solid #ddd;
            border-radius: 8px;
            background-color: white;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
        }

        summary {
            font-size: 18px;
            font-weight: bold;
            color: #007bff;
            padding: 15px;
            cursor: pointer;
            list-style: none; /* Убираем маркер */
        }

        summary::-webkit-details-marker {
            display: none; /* Убираем стандартный маркер */
        }

        details[open] summary {
            background-color: #f0f8ff;
            border-bottom: 1px solid #ddd;
        }

        details div {
            padding: 15px;
            color: #666;
            line-height: 1.5;
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
    </style>
</head>
<body>
    <?php include 'header.php'; ?>
    
    <main>
        <h1>Психология выгорания в IT</h1>

        <p>
            Выгорание — это синдром, вызванный хроническим стрессом на работе, когда человек чувствует себя эмоционально истощённым, циничным (деперсонализированным) и неэффективным в профессиональной деятельности. 
            Это состояние особенно актуально для сферы информационных технологий (IT).
        </p>

        <!-- Выпадающие списки -->
        <details>
            <summary>Почему выгорание часто встречается в IT?</summary>
            <div>
                <ul>
                    <li><strong>Высокая нагрузка и дедлайны:</strong> Постоянное давление, необходимость быстро решать сложные задачи и соблюдать жёсткие сроки.</li>
                    <li><strong>Интенсивная умственная работа:</strong> Работа программистов, аналитиков, тестировщиков требует высокой концентрации и когнитивных усилий, что быстро истощает.</li>
                    <li><strong>Быстро меняющиеся технологии:</strong> Необходимость постоянного обучения и адаптации к новым инструментам, языкам и фреймворкам.</li>
                    <li><strong>Работа в команде и коммуникация:</strong> Сложности взаимодействия, "токсичные" коллеги, неэффективное управление проектами.</li>
                    <li><strong>Низкая автономия:</strong> Ограниченный контроль над рабочим процессом, отсутствие влияния на решения.</li>
                    <li><strong>"Always-on" культура:</strong> Ожидание немедленного отклика, работа в вечернее/выходное время.</li>
                    <li><strong>Отсутствие признания:</strong> Чувство, что вклад в общее дело не оценивается должным образом.</li>
                    <li><strong>Неудовлетворённость работой:</strong> Отсутствие интереса к проектам, несоответствие ожиданиям, отсутствие перспектив роста.</li>
                </ul>
            </div>
        </details>

        <details>
            <summary>Симптомы выгорания</summary>
            <div>
                <ul>
                    <li>Эмоциональное и физическое истощение.</li>
                    <li>Цинизм, отстранение от работы и коллег.</li>
                    <li>Снижение чувства личной эффективности.</li>
                    <li>Повышенная утомляемость, проблемы со сном и концентрацией.</li>
                    <li>Физические симптомы стресса.</li>
                    <li>Желание избегать рабочих задач.</li>
                </ul>
            </div>
        </details>

        <details>
            <summary>Последствия выгорания</summary>
            <div>
                <ul>
                    <li>Ухудшение качества работы и продуктивности.</li>
                    <li>Повышенный риск ошибок.</li>
                    <li>Конфликты в коллективе.</li>
                    <li>Увольнение.</li>
                    <li>Проблемы с физическим и психическим здоровьем.</li>
                </ul>
            </div>
        </details>

        <details>
            <summary>Как бороться с выгоранием</summary>
            <div>
                <ul>
                    <li><strong>Признать проблему.</strong></li>
                    <li><strong>Установить границы:</strong> Чётко разграничьте рабочее и личное время.</li>
                    <li><strong>Позаботиться о себе:</strong> Регулярный сон, физическая активность, правильное питание, хобби.</li>
                    <li><strong>Обратиться за поддержкой:</strong> Поговорить с близкими или обратиться к психологу.</li>
                    <li><strong>Обсудить ситуацию с руководством.</strong></li>
                    <li><strong>Искать смысл:</strong> Напомнить себе, почему вы выбрали эту профессию.</li>
                    <li><strong>Планировать отдых и перерывы.</strong></li>
                    <li><strong>Рассмотреть смену работы или проекта.</strong></li>
                </ul>
            </div>
        </details>

        <p>Понимание и признание проблемы выгорания — первый шаг к её преодолению. Важно заботиться о своём психическом здоровье.</p>
    </main>

    <?php include 'footer.php'; ?>
    <script src="/magnifier.js"></script> 
    <script src="/theme.js"></script> 
</body>
</html>