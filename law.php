<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Словарь терминов</title>
    <link rel="stylesheet" href="style.css" id="theme">
    <script>
        function toggleTheme() {
            const theme = document.getElementById('theme');
            if (theme.getAttribute('href') === 'style.css') {
                theme.setAttribute('href', 'accessible.css');
            } else {
                theme.setAttribute('href', 'style.css');
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

        /* Стиль для списков внутри details */
        details ul {
            list-style-type: none;
            padding-left: 0;
        }

        details li {
            margin-bottom: 1em;
            padding: 0.5em;
            border-left: 3px solid #0074D9; /* Добавляет акцентный цвет слева */
            background-color: #f9f9f9; /* Светлый фон для каждого термина */
        }

        /* Кнопка "Наверх" */
        .back-to-top {
            position: fixed;
            bottom: 40px;
            right: 40px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            transition: background-color 0.3s ease;
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

        <h1>Словарь терминов (IT-глоссарий)</h1>

        <p>Этот словарь поможет вам лучше понять основные термины, используемые в сфере информационных технологий.</p>

        <!-- Выпадающие списки -->
        <details>
            <summary>А</summary>
            <div>
                <ul>
                    <li><strong>API (Application Programming Interface)</strong> – Интерфейс программирования приложений. Набор определений и правил, по которым взаимодействуют между собой программные компоненты.</li>
                    <li><strong>Алгоритм (Algorithm)</strong> – Последовательность шагов для выполнения задачи или решения проблемы.</li>
                    <li><strong>Архитектура ПО (Software Architecture)</strong> – Структура программного обеспечения, определяющая его компоненты и их взаимодействие.</li>
                </ul>
            </div>
        </details>

        <details>
            <summary>Б</summary>
            <div>
                <ul>
                    <li><strong>База данных (Database)</strong> – Упорядоченный набор структурированной информации или данных, который обычно хранится в электронном виде на компьютерной системе.</li>
                    <li><strong>Бэкенд (Backend)</strong> – Серверная часть приложения, которая работает "за кулисами". Обрабатывает данные, логику, взаимодействие с базами данных.</li>
                    <li><strong>Браузер (Browser)</strong> – Компьютерная программа, которая позволяет пользователям просматривать веб-страницы в интернете.</li>
                </ul>
            </div>
        </details>

        <details>
            <summary>В</summary>
            <div>
                <ul>
                    <li><strong>Версионный контроль (Version Control)</strong> – Система, отслеживающая изменения в файлах за определённый период времени, позволяющая вернуться к предыдущим версиям.</li>
                    <li><strong>Веб-разработка (Web Development)</strong> – Процесс создания веб-сайтов и веб-приложений.</li>
                    <li><strong>Виртуализация (Virtualization)</strong> – Технология, позволяющая создавать виртуальные версии аппаратного обеспечения, операционных систем и других ресурсов.</li>
                </ul>
            </div>
        </details>

        <details>
            <summary>Г</summary>
            <div>
                <ul>
                    <li><strong>Графический интерфейс (GUI)</strong> – Интерфейс, который позволяет пользователям взаимодействовать с программным обеспечением через графические элементы.</li>
                    <li><strong>Грид (Grid)</strong> – Сетка, используемая для разметки макетов в веб-дизайне.</li>
                </ul>
            </div>
        </details>

        <details>
            <summary>Д</summary>
            <div>
                <ul>
                    <li><strong>Данные (Data)</strong> – Информация, представленная в формате, подходящем для обработки компьютером.</li>
                    <li><strong>ДевOps (DevOps)</strong> – Комбинация практик, направленных на автоматизацию и улучшение процессов между командами разработки и операций.</li>
                </ul>
            </div>
        </details>

        <details>
            <summary>И</summary>
            <div>
                <ul>
                    <li><strong>Интерфейс (Interface)</strong> – Точка взаимодействия между двумя системами или компонентами.</li>
                    <li><strong>Инфраструктура как код (IaC)</strong> – Подход к управлению инфраструктурой через код.</li>
                    <li><strong>Искусственный интеллект (AI)</strong> – Технология, имитирующая человеческое мышление и поведение.</li>
                </ul>
            </div>
        </details>

        <details>
            <summary>К</summary>
            <div>
                <ul>
                    <li><strong>Кибербезопасность (Cybersecurity)</strong> – Защита компьютерных систем и данных от несанкционированного доступа.</li>
                    <li><strong>Кластеризация (Clustering)</strong> – Группировка данных на основе их схожести.</li>
                    <li><strong>Контейнеризация (Containerization)</strong> – Технология, позволяющая запускать приложения в изолированных средах.</li>
                </ul>
            </div>
        </details>

        <details>
            <summary>Л</summary>
            <div>
                <ul>
                    <li><strong>Логирование (Logging)</strong> – Запись событий системы для последующего анализа.</li>
                    <li><strong>Лямбда-функции (Lambda Functions)</strong> – Анонимные функции, которые могут быть использованы для выполнения коротких операций.</li>
                </ul>
            </div>
        </details>

        <details>
            <summary>М</summary>
            <div>
                <ul>
                    <li><strong>Машинное обучение (Machine Learning)</strong> – Раздел искусственного интеллекта, связанный с созданием алгоритмов, способных обучаться на данных.</li>
                    <li><strong>Микросервисы (Microservices)</strong> – Архитектурный подход, при котором приложение состоит из небольших независимых сервисов.</li>
                </ul>
            </div>
        </details>

        <details>
            <summary>Н</summary>
            <div>
                <ul>
                    <li><strong>Нейронная сеть (Neural Network)</strong> – Модель машинного обучения, имитирующая работу биологических нейронов.</li>
                    <li><strong>Нормализация (Normalization)</strong> – Процесс приведения данных к стандартному виду.</li>
                </ul>
            </div>
        </details>

        <details>
            <summary>О</summary>
            <div>
                <ul>
                    <li><strong>Оптимизация (Optimization)</strong> – Процесс улучшения производительности или эффективности системы.</li>
                    <li><strong>Облачные вычисления (Cloud Computing)</strong> – Использование удалённых серверов для хранения, управления и обработки данных.</li>
                </ul>
            </div>
        </details>

        <details>
            <summary>П</summary>
            <div>
                <ul>
                    <li><strong>Плагин (Plugin)</strong> – Компонент программного обеспечения, который добавляет конкретную функцию к уже существующему приложению.</li>
                    <li><strong>Протокол (Protocol)</strong> – Набор правил, определяющих формат и способ передачи данных между устройствами.</li>
                    <li><strong>Python</strong> – Высокоуровневый язык программирования общего назначения.</li>
                </ul>
            </div>
        </details>

        <details>
            <summary>Р</summary>
            <div>
                <ul>
                    <li><strong>Репозиторий (Repository)</strong> – Хранилище кода и связанных файлов.</li>
                    <li><strong>REST API</strong> – Архитектурный стиль для распределённых систем, основанный на HTTP-протоколе.</li>
                </ul>
            </div>
        </details>

        <details>
            <summary>С</summary>
            <div>
                <ul>
                    <li><strong>Скрипт (Script)</strong> – Программа, написанная на интерпретируемом языке программирования.</li>
                    <li><strong>СУБД (Система Управления Базами Данных)</strong> – Программное обеспечение для создания, обслуживания и использования баз данных.</li>
                    <li><strong>Сервер (Server)</strong> – Компьютер или программа, предоставляющая ресурсы, данные или услуги другим устройствам.</li>
                </ul>
            </div>
        </details>

        <details>
            <summary>Т</summary>
            <div>
                <ul>
                    <li><strong>Тестирование (Testing)</strong> – Процесс проверки корректности работы программного обеспечения.</li>
                    <li><strong>Транзакция (Transaction)</strong> – Единая операция или набор операций, выполняемых в базе данных.</li>
                </ul>
            </div>
        </details>

        <details>
            <summary>Ф</summary>
            <div>
                <ul>
                    <li><strong>Фреймворк (Framework)</strong> – Структура, на которой разработчики могут создавать программное обеспечение.</li>
                    <li><strong>Фронтенд (Frontend)</strong> – Клиентская сторона веб-приложения, которую видит пользователь.</li>
                </ul>
            </div>
        </details>

        <details>
            <summary>Х</summary>
            <div>
                <ul>
                    <li><strong>Хостинг (Hosting)</strong> – Услуга по размещению файлов веб-сайта на сервере.</li>
                    <li><strong>Хэш-функция (Hash Function)</strong> – Функция, преобразующая входные данные в фиксированную строку символов.</li>
                </ul>
            </div>
        </details>

        <details>
            <summary>Я</summary>
            <div>
                <ul>
                    <li><strong>Язык программирования (Programming Language)</strong> – Искусственный язык, разработанный для написания компьютерных программ.</li>
                </ul>
            </div>
        </details>
    </main>

 <!-- Кнопка "Наверх" -->
        <button class="back-to-top" onclick="window.scrollTo({ top: 0, behavior: 'smooth' });">
            ↑
        </button>
    </main>

    <?php include 'footer.php'; ?>
</body>
</html>