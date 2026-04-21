<?php
// admin/view.php
require_once '../config.php';

// Проверка ID
$id = $_GET['id'] ?? 0;
if (!$id) {
    header('Location: index.php');
    exit;
}

// Получаем данные пользователя
$stmt = $pdo->prepare("
    SELECT u.*, GROUP_CONCAT(pl.name SEPARATOR ', ') as languages
    FROM users u
    LEFT JOIN user_languages ul ON u.id = ul.user_id
    LEFT JOIN programming_languages pl ON ul.language_id = pl.id
    WHERE u.id = ?
    GROUP BY u.id
");
$stmt->execute([$id]);
$user = $stmt->fetch();

if (!$user) {
    header('Location: index.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Просмотр пользователя</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f5f5;
            padding: 20px;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #2c3e50;
            border-bottom: 2px solid #3498db;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: 10px;
            margin-bottom: 20px;
        }
        .label {
            font-weight: bold;
            color: #7f8c8d;
        }
        .value {
            color: #2c3e50;
        }
        .languages {
            display: flex;
            flex-wrap: wrap;
            gap: 5px;
        }
        .lang-badge {
            background: #3498db;
            color: white;
            padding: 5px 10px;
            border-radius: 3px;
            font-size: 0.9em;
        }
        .actions {
            margin-top: 30px;
            display: flex;
            gap: 10px;
        }
        .btn {
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 5px;
            color: white;
        }
        .btn-back { background: #7f8c8d; }
        .btn-edit { background: #f39c12; }
        .btn-delete { background: #e74c3c; }
        .btn:hover { opacity: 0.8; }
    </style>
</head>
<body>
    <div class="container">
        <h1>👤 Пользователь #<?= $user['id'] ?>: <?= htmlspecialchars($user['full_name']) ?></h1>
        
        <div class="info-grid">
            <div class="label">ID:</div>
            <div class="value"><?= $user['id'] ?></div>
            
            <div class="label">ФИО:</div>
            <div class="value"><?= htmlspecialchars($user['full_name']) ?></div>
            
            <div class="label">Телефон:</div>
            <div class="value"><?= htmlspecialchars($user['phone']) ?></div>
            
            <div class="label">Email:</div>
            <div class="value"><?= htmlspecialchars($user['email']) ?></div>
            
            <div class="label">Дата рождения:</div>
            <div class="value"><?= htmlspecialchars($user['birth_date']) ?></div>
            
            <div class="label">Пол:</div>
            <div class="value"><?= $user['gender'] == 'male' ? 'Мужской' : 'Женский' ?></div>
            
            <div class="label">Биография:</div>
            <div class="value"><?= nl2br(htmlspecialchars($user['biography'] ?? 'Не указана')) ?></div>
            
            <div class="label">Языки:</div>
            <div class="value">
                <div class="languages">
                    <?php 
                    $langs = explode(', ', $user['languages'] ?? '');
                    foreach ($langs as $lang): 
                    ?>
                        <span class="lang-badge"><?= htmlspecialchars($lang) ?></span>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <div class="label">Контракт:</div>
            <div class="value"><?= $user['contract_accepted'] ? 'Принят' : 'Не принят' ?></div>
            
            <div class="label">Дата регистрации:</div>
            <div class="value"><?= date('d.m.Y H:i:s', strtotime($user['created_at'])) ?></div>
        </div>
        
        <div class="actions">
            <a href="index.php" class="btn btn-back">← Назад</a>
            <a href="edit.php?id=<?= $user['id'] ?>" class="btn btn-edit">✏️ Редактировать</a>
            <a href="delete.php?id=<?= $user['id'] ?>" class="btn btn-delete" onclick="return confirm('Удалить?')">🗑️ Удалить</a>
        </div>
    </div>
</body>
</html>