<?php
// admin/index.php - ИСПРАВЛЕННАЯ ВЕРСИЯ
require_once '../config.php';  // Подключаем существующий config.php

// Получаем список всех пользователей
$stmt = $pdo->query("
    SELECT u.*, GROUP_CONCAT(pl.name SEPARATOR ', ') as languages
    FROM users u
    LEFT JOIN user_languages ul ON u.id = ul.user_id
    LEFT JOIN programming_languages pl ON ul.language_id = pl.id
    GROUP BY u.id
    ORDER BY u.created_at DESC
");
$users = $stmt->fetchAll();

// Статистика по языкам
$langStats = $pdo->query("
    SELECT 
        pl.name,
        COUNT(ul.user_id) as user_count
    FROM programming_languages pl
    LEFT JOIN user_languages ul ON pl.id = ul.language_id
    GROUP BY pl.id
    ORDER BY user_count DESC
")->fetchAll();

// Общая статистика
$totalUsers = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$totalWithContract = $pdo->query("SELECT COUNT(*) FROM users WHERE contract_accepted = 1")->fetchColumn();
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Админ-панель</title>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f5f5;
            padding: 20px;
        }
        
        .container {
            max-width: 1400px;
            margin: 0 auto;
        }
        
        header {
            background: #2c3e50;
            color: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        h1 {
            font-weight: 300;
        }
        
        .logout-btn {
            background: #e74c3c;
            color: white;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 5px;
            transition: background 0.3s;
        }
        
        .logout-btn:hover {
            background: #c0392b;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .stat-card h3 {
            color: #2c3e50;
            margin-bottom: 15px;
            border-bottom: 2px solid #3498db;
            padding-bottom: 10px;
        }
        
        .stat-number {
            font-size: 2.5em;
            color: #3498db;
            font-weight: bold;
        }
        
        .lang-stats {
            list-style: none;
        }
        
        .lang-stats li {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #eee;
        }
        
        .lang-name {
            font-weight: 500;
        }
        
        .lang-count {
            background: #3498db;
            color: white;
            padding: 2px 10px;
            border-radius: 15px;
            font-size: 0.9em;
        }
        
        .users-table {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow-x: auto;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        th {
            background: #34495e;
            color: white;
            padding: 12px;
            text-align: left;
            font-weight: 500;
        }
        
        td {
            padding: 12px;
            border-bottom: 1px solid #eee;
        }
        
        tr:hover {
            background: #f8f9fa;
        }
        
        .actions {
            display: flex;
            gap: 5px;
        }
        
        .btn-edit, .btn-delete, .btn-view {
            padding: 5px 10px;
            border-radius: 3px;
            text-decoration: none;
            font-size: 0.9em;
            color: white;
            border: none;
            cursor: pointer;
        }
        
        .btn-view {
            background: #3498db;
        }
        
        .btn-edit {
            background: #f39c12;
        }
        
        .btn-delete {
            background: #e74c3c;
        }
        
        .btn-view:hover { background: #2980b9; }
        .btn-edit:hover { background: #e67e22; }
        .btn-delete:hover { background: #c0392b; }
        
        .contract-badge {
            background: #27ae60;
            color: white;
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 0.8em;
        }
        
        .lang-badge {
            background: #3498db;
            color: white;
            padding: 2px 5px;
            border-radius: 3px;
            margin: 2px;
            display: inline-block;
            font-size: 0.8em;
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <h1>👑 Административная панель</h1>
            <div>
                <span style="margin-right: 20px;">Вы вошли как: admin</span>
                <a href="logout.php" class="logout-btn">Выйти</a>
            </div>
        </header>
        
        <!-- Статистика -->
        <div class="stats-grid">
            <div class="stat-card">
                <h3>Всего пользователей</h3>
                <div class="stat-number"><?= $totalUsers ?></div>
            </div>
            
            <div class="stat-card">
                <h3>Приняли контракт</h3>
                <div class="stat-number"><?= $totalWithContract ?></div>
                <p style="color: #7f8c8d; margin-top: 10px;">
                    <?= $totalUsers > 0 ? round(($totalWithContract / $totalUsers) * 100, 1) : 0 ?>% от всех
                </p>
            </div>
            
            <div class="stat-card">
                <h3>Статистика по языкам</h3>
                <ul class="lang-stats">
                    <?php foreach ($langStats as $lang): ?>
                        <li>
                            <span class="lang-name"><?= htmlspecialchars($lang['name']) ?></span>
                            <span class="lang-count"><?= $lang['user_count'] ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
        
        <!-- Таблица пользователей -->
        <div class="users-table">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>ФИО</th>
                        <th>Телефон</th>
                        <th>Email</th>
                        <th>Дата рождения</th>
                        <th>Пол</th>
                        <th>Языки</th>
                        <th>Контракт</th>
                        <th>Дата регистрации</th>
                        <th>Действия</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                    <tr>
                        <td>#<?= $user['id'] ?></td>
                        <td><?= htmlspecialchars($user['full_name']) ?></td>
                        <td><?= htmlspecialchars($user['phone']) ?></td>
                        <td><?= htmlspecialchars($user['email']) ?></td>
                        <td><?= htmlspecialchars($user['birth_date']) ?></td>
                        <td><?= $user['gender'] == 'male' ? 'Мужской' : 'Женский' ?></td>
                        <td>
                            <?php 
                            $langs = explode(', ', $user['languages'] ?? '');
                            foreach ($langs as $lang): 
                            ?>
                                <span class="lang-badge"><?= htmlspecialchars($lang) ?></span>
                            <?php endforeach; ?>
                        </td>
                        <td>
                            <?php if ($user['contract_accepted']): ?>
                                <span class="contract-badge">✓ Принят</span>
                            <?php else: ?>
                                <span style="background: #95a5a6; color: white; padding: 3px 8px; border-radius: 3px;">✗ Не принят</span>
                            <?php endif; ?>
                        </td>
                        <td><?= date('d.m.Y H:i', strtotime($user['created_at'])) ?></td>
                        <td class="actions">
                            <a href="view.php?id=<?= $user['id'] ?>" class="btn-view">👁️</a>
                            <a href="edit.php?id=<?= $user['id'] ?>" class="btn-edit">✏️</a>
                            <a href="delete.php?id=<?= $user['id'] ?>" 
                               class="btn-delete" 
                               onclick="return confirm('Удалить пользователя <?= htmlspecialchars($user['full_name']) ?>?')">🗑️</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>