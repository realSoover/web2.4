<?php
// admin/edit.php
require_once '../config.php';

$id = $_GET['id'] ?? 0;
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Обновление данных
    try {
        $pdo->beginTransaction();
        
        // Обновляем пользователя
        $stmt = $pdo->prepare("
            UPDATE users 
            SET full_name = ?, phone = ?, email = ?, birth_date = ?, gender = ?, biography = ?
            WHERE id = ?
        ");
        $stmt->execute([
            $_POST['full_name'],
            $_POST['phone'],
            $_POST['email'],
            $_POST['birth_date'],
            $_POST['gender'],
            $_POST['biography'],
            $id
        ]);
        
        // Обновляем языки
        $stmt = $pdo->prepare("DELETE FROM user_languages WHERE user_id = ?");
        $stmt->execute([$id]);
        
        $languages = $_POST['languages'] ?? [];
        if (!empty($languages)) {
            $stmt = $pdo->prepare("SELECT id FROM programming_languages WHERE name = ?");
            $langStmt = $pdo->prepare("INSERT INTO user_languages (user_id, language_id) VALUES (?, ?)");
            
            foreach ($languages as $langName) {
                $stmt->execute([$langName]);
                $langId = $stmt->fetchColumn();
                if ($langId) {
                    $langStmt->execute([$id, $langId]);
                }
            }
        }
        
        $pdo->commit();
        $message = '<div class="success">✅ Данные успешно обновлены!</div>';
        
    } catch (Exception $e) {
        $pdo->rollBack();
        $message = '<div class="error">❌ Ошибка: ' . $e->getMessage() . '</div>';
    }
}

// Получаем данные пользователя
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$id]);
$user = $stmt->fetch();

if (!$user) {
    header('Location: index.php');
    exit;
}

// Получаем языки пользователя
$stmt = $pdo->prepare("
    SELECT pl.name 
    FROM user_languages ul
    JOIN programming_languages pl ON ul.language_id = pl.id
    WHERE ul.user_id = ?
");
$stmt->execute([$id]);
$userLanguages = $stmt->fetchAll(PDO::FETCH_COLUMN);
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Редактирование пользователя</title>
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
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #555;
        }
        input, select, textarea {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 1em;
        }
        select[multiple] {
            height: 150px;
        }
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1em;
            margin-right: 10px;
        }
        .btn-save {
            background: #27ae60;
            color: white;
        }
        .btn-cancel {
            background: #7f8c8d;
            color: white;
            text-decoration: none;
            padding: 10px 20px;
        }
        .btn:hover {
            opacity: 0.8;
        }
        .success {
            background: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .error {
            background: #f8d7da;
            color: #721c24;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>✏️ Редактирование пользователя #<?= $id ?></h1>
        
        <?= $message ?>
        
        <form method="POST">
            <div class="form-group">
                <label>ФИО:</label>
                <input type="text" name="full_name" value="<?= htmlspecialchars($user['full_name']) ?>" required>
            </div>
            
            <div class="form-group">
                <label>Телефон:</label>
                <input type="text" name="phone" value="<?= htmlspecialchars($user['phone']) ?>" required>
            </div>
            
            <div class="form-group">
                <label>Email:</label>
                <input type="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" required>
            </div>
            
            <div class="form-group">
                <label>Дата рождения:</label>
                <input type="date" name="birth_date" value="<?= $user['birth_date'] ?>" required>
            </div>
            
            <div class="form-group">
                <label>Пол:</label>
                <select name="gender">
                    <option value="male" <?= $user['gender'] == 'male' ? 'selected' : '' ?>>Мужской</option>
                    <option value="female" <?= $user['gender'] == 'female' ? 'selected' : '' ?>>Женский</option>
                </select>
            </div>
            
            <div class="form-group">
                <label>Языки программирования:</label>
                <select name="languages[]" multiple size="6">
                    <?php
                    $allLangs = ['Pascal', 'C', 'C++', 'JavaScript', 'PHP', 'Python', 'Java', 'Haskell', 'Clojure', 'Prolog', 'Scala', 'Go'];
                    foreach ($allLangs as $lang):
                    ?>
                        <option value="<?= $lang ?>" <?= in_array($lang, $userLanguages) ? 'selected' : '' ?>>
                            <?= $lang ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <small>Удерживайте Ctrl для выбора нескольких</small>
            </div>
            
            <div class="form-group">
                <label>Биография:</label>
                <textarea name="biography" rows="5"><?= htmlspecialchars($user['biography'] ?? '') ?></textarea>
            </div>
            
            <div class="form-group">
                <label>
                    <input type="checkbox" name="contract_accepted" value="1" <?= $user['contract_accepted'] ? 'checked' : '' ?>>
                    Контракт принят
                </label>
            </div>
            
            <button type="submit" class="btn btn-save">💾 Сохранить изменения</button>
            <a href="index.php" class="btn btn-cancel">← Отмена</a>
        </form>
    </div>
</body>
</html>