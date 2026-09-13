document.addEventListener('DOMContentLoaded', function () {
    const toggleButton = document.getElementById('accessibility-toggle');

    if (!toggleButton) {
        console.error("Кнопка с id='accessibility-toggle' не найдена!");
        return;
    }

    // Проверяем состояние из localStorage
    let isAccessibilityMode = localStorage.getItem('accessibilityMode') === 'true';

    // Применяем состояние при загрузке страницы
    document.body.classList.toggle('accessibility-mode', isAccessibilityMode);
    toggleButton.textContent = isAccessibilityMode
        ? 'Обычная версия'
        : 'Версия для слабовидящих';

    // Переключение режима слабовидящих
    toggleButton.addEventListener('click', function () {
        isAccessibilityMode = !isAccessibilityMode;
        document.body.classList.toggle('accessibility-mode', isAccessibilityMode);

        // Сохраняем состояние в localStorage
        localStorage.setItem('accessibilityMode', isAccessibilityMode);

        // Изменяем текст кнопки
        toggleButton.textContent = isAccessibilityMode
            ? 'Обычная версия'
            : 'Версия для слабовидящих';
    });
});