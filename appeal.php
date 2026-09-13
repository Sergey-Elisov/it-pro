<?php
session_start();

// Проверяем авторизацию
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Настройки БД
$host = 'localhost';
$dbname = 'selisooi_t_educa';
$db_user = 'selisooi_t_educa';
$db_pass = 'QAZwsx123!@#';

$message = '';
$messageType = ''; // 'success' или 'error'

// Подключение к БД
try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    error_log("DB connection error: " . $e->getMessage());
    $message = 'Ошибка подключения к базе данных.';
    $messageType = 'error';
}

// === ОБРАБОТКА ОТПРАВКИ ФОРМЫ ===
if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($message)) {
    $subject = trim($_POST['subject'] ?? '');
    $message_text = trim($_POST['message'] ?? '');
    $user_id = $_SESSION['user_id'];

    // Валидация
    if (empty($subject) || empty($message_text)) {
        $message = 'Заполните все поля!';
        $messageType = 'error';
    } elseif (mb_strlen($subject) > 255) {
        $message = 'Тема обращения слишком длинная (макс. 255 символов).';
        $messageType = 'error';
    } elseif (mb_strlen($message_text) > 5000) {
        $message = 'Сообщение слишком длинное (макс. 5000 символов).';
        $messageType = 'error';
    } else {
        try {
            // Вставляем обращение в базу
            $stmt = $pdo->prepare("
                INSERT INTO support_tickets 
                (user_id, subject, message_text, status, created_at) 
                VALUES (?, ?, ?, 'new', NOW())
            ");
            $stmt->execute([$user_id, $subject, $message_text]);
            
            $message = '✅ Обращение успешно отправлено! Мы ответим в ближайшее время.';
            $messageType = 'success';
            
            // Очищаем поля формы после успешной отправки
            $subject = '';
            $message_text = '';
            
        } catch (PDOException $e) {
            error_log("Appeal insert error: " . $e->getMessage());
            $message = 'Ошибка при сохранении обращения. Попробуйте позже.';
            $messageType = 'error';
        }
    }
}

// === ПОЛУЧАЕМ ИСТОРИЮ ОБРАЩЕНИЙ ПОЛЬЗОВАТЕЛЯ ===
$appeals = [];
if (empty($message)) {
    try {
        $stmt = $pdo->prepare("
            SELECT id, subject, message_text, status, created_at, admin_response 
            FROM support_tickets 
            WHERE user_id = ? 
            ORDER BY created_at DESC 
            LIMIT 20
        ");
        $stmt->execute([$_SESSION['user_id']]);
        $appeals = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Appeals fetch error: " . $e->getMessage());
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Обращения в поддержку | IT-pro</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .appeal-page { max-width: 800px; margin: 0 auto; padding: 20px; }
        .appeal-page h1 { margin: 0 0 25px; color: #0f172a; }
        
        .form-card {
            background: #fff;
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 30px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
            border: 1px solid #e2e8f0;
        }
        
        .form-group { margin-bottom: 18px; }
        .form-group label { display: block; margin-bottom: 6px; font-weight: 500; color: #334155; }
        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            font-size: 1rem;
            font-family: inherit;
            box-sizing: border-box;
            transition: border-color 0.2s;
        }
        .form-group input:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }
        .form-group textarea { min-height: 120px; resize: vertical; }
        
        .btn-submit {
            background: #2563eb;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.2s;
        }
        .btn-submit:hover { background: #1d4ed8; }
        
        .message {
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-weight: 500;
        }
        .message.success {
            background: #f0fdf4;
            color: #166534;
            border-left: 4px solid #22c55e;
        }
        .message.error {
            background: #fef2f2;
            color: #dc2626;
            border-left: 4px solid #ef4444;
        }
        
        .appeals-list h2 {
            margin: 30px 0 15px;
            color: #0f172a;
            font-size: 1.3rem;
        }
        
        .appeal-card {
            background: #fff;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 15px;
            border: 1px solid #e2e8f0;
            border-left: 4px solid #94a3b8;
        }
        .appeal-card.status-new { border-left-color: #f59e0b; }
        .appeal-card.status-in_progress { border-left-color: #3b82f6; }
        .appeal-card.status-resolved { border-left-color: #22c55e; }
        
        .appeal-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 15px;
            margin-bottom: 10px;
            flex-wrap: wrap;
        }
        .appeal-subject {
            font-weight: 600;
            color: #0f172a;
            font-size: 1.05rem;
            margin: 0;
        }
        .appeal-meta {
            font-size: 0.85rem;
            color: #64748b;
            white-space: nowrap;
        }
        .status-badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
        }
        .status-new { background: #fff7ed; color: #c2410c; }
        .status-in_progress { background: #eff6ff; color: #1d4ed8; }
        .status-resolved { background: #f0fdf4; color: #166534; }
        
        .appeal-message {
            color: #334155;
            line-height: 1.6;
            margin: 10px 0;
            white-space: pre-wrap;
        }
        
        .admin-response {
            margin-top: 15px;
            padding: 12px 15px;
            background: #f8fafc;
            border-radius: 8px;
            border-left: 3px solid #2563eb;
        }
        .admin-response-title {
            font-weight: 600;
            color: #0f172a;
            margin-bottom: 5px;
            font-size: 0.95rem;
        }
        .admin-response-text {
            color: #334155;
            line-height: 1.5;
            margin: 0;
            white-space: pre-wrap;
        }
        
        .empty-state {
            text-align: center;
            padding: 30px;
            color: #64748b;
            background: #f8fafc;
            border-radius: 10px;
            border: 1px dashed #cbd5e1;
        }
        
        @media (max-width: 600px) {
            .appeal-header { flex-direction: column; align-items: flex-start; }
            .appeal-meta { order: 2; }
            .status-badge { order: 1; }
        }
    </style>
</head>
<body>

<?php include 'header.php'; ?>

<main class="appeal-page">
    <h1>💬 Обращения в поддержку</h1>
    
    <?php if ($message): ?>
        <div class="message <?= $messageType ?>">
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>
    
    <!-- Форма создания обращения -->
    <div class="form-card">
        <form method="POST" action="appeal.php">
            <div class="form-group">
                <label for="subject">Тема обращения *</label>
                <input type="text" id="subject" name="subject" 
                       value="<?= htmlspecialchars($subject ?? '') ?>" 
                       required placeholder="Кратко опишите суть проблемы">
            </div>
            
            <div class="form-group">
                <label for="message">Сообщение *</label>
                <textarea id="message" name="message" required 
                          placeholder="Опишите вашу проблему подробно..."><?= htmlspecialchars($message_text ?? '') ?></textarea>
            </div>
            
            <button type="submit" class="btn-submit">Отправить обращение</button>
        </form>
    </div>
    
    <!-- История обращений -->
    <div class="appeals-list">
        <h2>Ваши обращения (<?= count($appeals) ?>)</h2>
        
        <?php if (empty($appeals)): ?>
            <div class="empty-state">
                <p>У вас пока нет обращений.</p>
                <p style="font-size: 0.9rem; margin-top: 5px;">Заполните форму выше, чтобы создать новое.</p>
            </div>
        <?php else: ?>
            <?php foreach ($appeals as $appeal): ?>
            <div class="appeal-card status-<?= htmlspecialchars($appeal['status']) ?>">
                <div class="appeal-header">
                    <h3 class="appeal-subject"><?= htmlspecialchars($appeal['subject']) ?></h3>
                    <div style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
                        <span class="status-badge status-<?= htmlspecialchars($appeal['status']) ?>">
                            <?= match($appeal['status']) {
                                'new' => '🟡 Новое',
                                'in_progress' => '🔵 В работе',
                                'resolved' => '🟢 Решено',
                                default => '⚪ Неизвестно'
                            } ?>
                        </span>
                        <span class="appeal-meta"><?= date('d.m.Y H:i', strtotime($appeal['created_at'])) ?></span>
                    </div>
                </div>
                
                <p class="appeal-message"><?= htmlspecialchars($appeal['message_text']) ?></p>
                
                <?php if (!empty($appeal['admin_response'])): ?>
                <div class="admin-response">
                    <div class="admin-response-title">👨‍💻 Ответ поддержки:</div>
                    <p class="admin-response-text"><?= nl2br(htmlspecialchars($appeal['admin_response'])) ?></p>
                </div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</main>

<?php include 'footer.php'; ?>
<script src="theme.js"></script>
</body>
</html>