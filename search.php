<?php
// search.php

$query = $_GET['query'] ?? '';

if (empty($query)) {
    header("Location: php/index.php"); // Переадресация в папку php
    exit();
}

// Список файлов для поиска 
$pages = array(
    'php/index.php' => 'Главная',
    'php/about.php' => 'О нас',
    'php/services.php' => 'Услуги',
    'php/documents.php' => 'Документы',
    'php/contacts.php' => 'Контакты',
    'php/appeal.php' => 'Обращения',
    'php/dashboard.php' => 'Личный кабинет',
    'php/tariffs.php' => 'Тарифы на ЖКУ',
    'php/instructions.php' => 'Инструкции для граждан',
);

$results = array();

// Приводим запрос к нижнему регистру 
$queryLower = mb_strtolower($query, 'UTF-8');

foreach ($pages as $file => $title) {
    // Полный путь к файлу
    $filePath = $_SERVER['DOCUMENT_ROOT'] . '/' . $file;

    // Проверяем, существует ли файл
    if (file_exists($filePath)) {
        $content = file_get_contents($filePath);

        // Ищем заголовки <title> и <h1> 
        $titlePattern = '/<title>(.*?)<\/title>/is';
        $h1Pattern = '/<h1>(.*?)<\/h1>/is';

        preg_match($titlePattern, $content, $titleMatches);
        preg_match($h1Pattern, $content, $h1Matches);

        $pageTitle = isset($titleMatches[1]) ? trim(strip_tags($titleMatches[1])) : $title;
        $pageH1 = isset($h1Matches[1]) ? trim(strip_tags($h1Matches[1])) : '';

        // Приводим заголовки к нижнему регистру
        $pageTitleLower = mb_strtolower($pageTitle, 'UTF-8');
        $pageH1Lower = mb_strtolower($pageH1, 'UTF-8');

        // Проверяем, есть ли запрос в заголовке или в содержимом 
        $contentWithoutTags = strip_tags($content);
        $contentLower = mb_strtolower($contentWithoutTags, 'UTF-8');

        if (strpos($pageTitleLower, $queryLower) !== false || 
            strpos($pageH1Lower, $queryLower) !== false || 
            strpos($contentLower, $queryLower) !== false) {
            $results[] = array(
                'file' => $file,
                'title' => htmlspecialchars($pageTitle),
                'h1' => htmlspecialchars($pageH1),
            );
        }
    }
}
?>
<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Результаты поиска</title>
    <link rel="stylesheet" href="style.css">
</head>
<?php include 'header.php'; ?>
<main>
    <h1>Результаты поиска по запросу: "<?php echo htmlspecialchars($query); ?>"</h1>

    <?php if (empty($results)): ?>
        <p>Ничего не найдено.</p>
    <?php else: ?>
        <ul>
            <?php foreach ($results as $result): ?>
                <li>
                    <a href="/<?php echo $result['file']; ?>">
                        <strong><?php echo $result['title']; ?></strong>
                        <?php if (!empty($result['h1']) && $result['h1'] !== $result['title']): ?>
                            <br><span style="color: #666;"><?php echo $result['h1']; ?></span>
                        <?php endif; ?>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</main>
<?php include 'footer.php'; ?>
<script src="theme.js"></script>
</body>
</html>