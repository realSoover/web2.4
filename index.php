<?php
// index.php - Главная страница с формой регистрации


require_once 'config.php';
require_once 'auth.php';

// Генерация CSRF токена
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Проверяем, авторизован ли пользователь как администратор (для отображения ссылки)
$isAdmin = false;
if (isset($_SERVER['PHP_AUTH_USER']) && isset($_SERVER['PHP_AUTH_PW'])) {
    require_once 'admin_auth.php';
    $isAdmin = checkAdminCredentials($_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW']);
}

// Получаем ошибки из cookies (если есть)
$errors = [];
$errorCookie = $_COOKIE['form_errors'] ?? '';
if ($errorCookie) {
    $errors = json_decode($errorCookie, true) ?: [];
    // Удаляем cookie после чтения
    setcookie('form_errors', '', time() - 3600, '/', '', false, true);
}

// Получаем старые значения из cookies (если были ошибки)
$old = [];
$oldCookie = $_COOKIE['form_old'] ?? '';
if ($oldCookie) {
    $old = json_decode($oldCookie, true) ?: [];
    setcookie('form_old', '', time() - 3600, '/', '', false, true);
}

// Получаем сохраненные значения из cookies (на год)
$savedData = [];
$savedCookie = $_COOKIE['saved_form_data'] ?? '';
if ($savedCookie) {
    $savedData = json_decode($savedCookie, true) ?: [];
}

// Если пользователь авторизован, загружаем его данные из БД
$userData = null;
if (isAuthenticated()) {
    $userId = getCurrentUserId();
    $userData = getUserData($userId, $pdo);
    if ($userData) {
        // Подготавливаем данные для отображения в форме
        $old = [
            'full_name' => htmlspecialchars($userData['full_name'], ENT_QUOTES, 'UTF-8'),
            'phone' => htmlspecialchars($userData['phone'], ENT_QUOTES, 'UTF-8'),
            'email' => htmlspecialchars($userData['email'], ENT_QUOTES, 'UTF-8'),
            'birth_date' => htmlspecialchars($userData['birth_date'], ENT_QUOTES, 'UTF-8'),
            'gender' => htmlspecialchars($userData['gender'], ENT_QUOTES, 'UTF-8'),
            'languages' => $userData['languages'] ?? [],
            'biography' => htmlspecialchars($userData['biography'], ENT_QUOTES, 'UTF-8'),
            'contract_accepted' => $userData['contract_accepted'] ? '1' : ''
        ];
    }
}

// Объединяем: сначала старые значения (при ошибке), потом сохраненные (при первом заходе)
if (empty($old) && !empty($savedData)) {
    $old = $savedData;
}

// Список языков программирования
$languagesList = ['Pascal', 'C', 'C++', 'JavaScript', 'PHP', 'Python', 
                  'Java', 'Haskell', 'Clojure', 'Prolog', 'Scala', 'Go'];
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Регистрационная форма</title>
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="referrer" content="strict-origin-when-cross-origin">
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #0a0f1f;
            min-height: 100vh;
            padding: 20px;
            display: flex;
            justify-content: center;
            align-items: center;
            position: relative;
            overflow-x: hidden;
        }
        
        /* Анимированный фон */
        body::before {
            content: '';
            position: absolute;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle at 30% 50%, rgba(0, 180, 255, 0.1) 0%, transparent 50%),
                        radial-gradient(circle at 70% 50%, rgba(255, 0, 180, 0.1) 0%, transparent 50%);
            animation: gradientShift 20s ease infinite;
            z-index: 0;
        }
        
        @keyframes gradientShift {
            0%, 100% { transform: translate(-10%, -10%) rotate(0deg); }
            50% { transform: translate(10%, 10%) rotate(5deg); }
        }
        
        .container {
            max-width: 800px;
            width: 100%;
            background: rgba(18, 25, 45, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 30px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5),
                        0 0 0 1px rgba(0, 255, 255, 0.2) inset,
                        0 0 20px rgba(0, 255, 255, 0.2);
            padding: 40px;
            position: relative;
            z-index: 1;
            animation: slideUp 0.6s cubic-bezier(0.23, 1, 0.32, 1);
            border: 1px solid rgba(0, 255, 255, 0.3);
        }
        
        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px) scale(0.98);
            }
            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }
        
        h1 {
            text-align: center;
            color: #fff;
            margin-bottom: 35px;
            font-weight: 600;
            font-size: 2.2rem;
            letter-spacing: 1px;
            text-shadow: 0 0 10px rgba(0, 255, 255, 0.5);
            border-bottom: 2px solid;
            border-image: linear-gradient(90deg, transparent, #00ffff, #ff00ff, transparent) 1;
            padding-bottom: 20px;
            animation: glowPulse 3s infinite;
        }
        
        @keyframes glowPulse {
            0%, 100% { text-shadow: 0 0 10px rgba(0, 255, 255, 0.5); }
            50% { text-shadow: 0 0 20px rgba(255, 0, 255, 0.8); }
        }
        
        .header-buttons {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-bottom: 20px;
        }
        
        .auth-btn {
            color: #00ffff;
            text-decoration: none;
            padding: 8px 20px;
            border: 2px solid #00ffff;
            border-radius: 25px;
            transition: all 0.3s;
            display: inline-block;
            font-size: 0.9rem;
        }
        
        .auth-btn:hover {
            background: rgba(0, 255, 255, 0.1);
            transform: translateY(-2px);
            box-shadow: 0 0 15px rgba(0, 255, 255, 0.3);
        }
        
        .admin-btn {
            border-color: #ff00ff;
            color: #ff00ff;
        }
        
        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        
        .form-group {
            margin-bottom: 20px;
            position: relative;
            animation: fadeIn 0.5s ease-out forwards;
            opacity: 0;
        }
        
        .form-group:nth-child(1) { animation-delay: 0.1s; }
        .form-group:nth-child(2) { animation-delay: 0.2s; }
        .form-group:nth-child(3) { animation-delay: 0.3s; }
        .form-group:nth-child(4) { animation-delay: 0.4s; }
        .form-group:nth-child(5) { animation-delay: 0.5s; }
        .form-group:nth-child(6) { animation-delay: 0.6s; }
        .form-group:nth-child(7) { animation-delay: 0.7s; }
        
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateX(-20px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }
        
        .form-group.full-width {
            grid-column: span 2;
        }
        
        label {
            display: block;
            margin-bottom: 8px;
            color: #a0b0d0;
            font-weight: 500;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            transition: all 0.3s;
        }
        
        input[type="text"],
        input[type="tel"],
        input[type="email"],
        input[type="date"],
        select,
        textarea {
            width: 100%;
            padding: 14px 18px;
            border: 2px solid #2a3a5a;
            border-radius: 15px;
            font-size: 1rem;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            background: #1a2540;
            color: #fff;
            font-family: 'Inter', sans-serif;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        
        input::placeholder,
        textarea::placeholder {
            color: #4a5a7a;
            opacity: 0.7;
        }
        
        input:hover,
        select:hover,
        textarea:hover {
            border-color: #4a6a9a;
            background: #1f2b4a;
            transform: translateY(-2px);
            box-shadow: 0 8px 12px rgba(0, 255, 255, 0.2);
        }
        
        input:focus,
        select:focus,
        textarea:focus {
            outline: none;
            border-color: #00ffff;
            background: #202d50;
            box-shadow: 0 0 20px rgba(0, 255, 255, 0.3),
                        0 4px 8px rgba(0, 0, 0, 0.2);
            transform: translateY(-2px) scale(1.01);
        }
        
        .radio-group {
            display: flex;
            gap: 25px;
            padding: 10px 0;
        }
        
        .radio-option {
            display: flex;
            align-items: center;
            gap: 10px;
            color: #fff;
            cursor: pointer;
            padding: 8px 16px;
            background: #1a2540;
            border-radius: 30px;
            border: 2px solid #2a3a5a;
            transition: all 0.3s;
        }
        
        .radio-option:hover {
            border-color: #00ffff;
            background: #202d50;
            transform: translateY(-2px);
            box-shadow: 0 0 15px rgba(0, 255, 255, 0.3);
        }
        
        .radio-option input[type="radio"] {
            width: 18px;
            height: 18px;
            accent-color: #00ffff;
            cursor: pointer;
        }
        
        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px 0;
            background: #1a2540;
            border-radius: 30px;
            padding: 12px 20px;
            border: 2px solid #2a3a5a;
            transition: all 0.3s;
        }
        
        .checkbox-group:hover {
            border-color: #ff00ff;
            box-shadow: 0 0 15px rgba(255, 0, 255, 0.3);
            transform: translateY(-2px);
        }
        
        .checkbox-group input[type="checkbox"] {
            width: 20px;
            height: 20px;
            cursor: pointer;
            accent-color: #ff00ff;
        }
        
        .checkbox-group label {
            margin: 0;
            color: #fff;
            cursor: pointer;
        }
        
        select[multiple] {
            height: 150px;
            background: #1a2540;
        }
        
        select[multiple] option {
            padding: 10px 15px;
            margin: 2px 0;
            border-radius: 8px;
            color: #fff;
            transition: all 0.2s;
        }
        
        select[multiple] option:hover {
            background: #2a3a6a;
        }
        
        select[multiple] option:checked {
            background: linear-gradient(45deg, #00ffff, #ff00ff);
            color: #fff;
        }
        
        textarea {
            resize: vertical;
            min-height: 120px;
        }
        
        .btn {
            background: linear-gradient(45deg, #00ffff, #ff00ff);
            color: #0a0f1f;
            border: none;
            padding: 16px 32px;
            font-size: 1.2rem;
            border-radius: 50px;
            cursor: pointer;
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            width: 100%;
            font-weight: 700;
            letter-spacing: 2px;
            text-transform: uppercase;
            margin-top: 30px;
            position: relative;
            overflow: hidden;
            box-shadow: 0 0 20px rgba(0, 255, 255, 0.5);
        }
        
        .btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
            transition: left 0.5s;
        }
        
        .btn:hover {
            transform: scale(1.05) translateY(-3px);
            box-shadow: 0 0 30px rgba(255, 0, 255, 0.7);
            background: linear-gradient(45deg, #ff00ff, #00ffff);
        }
        
        .btn:hover::before {
            left: 100%;
        }
        
        .btn:active {
            transform: scale(0.95) translateY(0);
        }
        
        .error-messages {
            background: rgba(255, 50, 50, 0.2);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            margin-bottom: 25px;
            border: 2px solid rgba(255, 50, 50, 0.3);
            animation: shake 0.5s;
        }
        
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            20%, 60% { transform: translateX(-5px); }
            40%, 80% { transform: translateX(5px); }
        }
        
        .error-message-item {
            background: rgba(255, 50, 50, 0.15);
            color: #ff8a8a;
            padding: 12px 20px;
            border-left: 4px solid #ff3333;
            margin: 5px 0;
        }
        
        .error-message-item:first-child {
            border-top-left-radius: 13px;
            border-top-right-radius: 13px;
        }
        
        .error-message-item:last-child {
            border-bottom-left-radius: 13px;
            border-bottom-right-radius: 13px;
        }
        
        .error-message-item strong {
            color: #ff6666;
        }
        
        .field-error {
            border-color: #ff4d4d !important;
            background: #2a1a1a !important;
            animation: errorPulse 1.5s infinite;
        }
        
        @keyframes errorPulse {
            0%, 100% { box-shadow: 0 0 5px rgba(255, 0, 0, 0.3); }
            50% { box-shadow: 0 0 15px rgba(255, 0, 0, 0.6); }
        }
        
        .field-error-message {
            color: #ff8a8a;
            font-size: 0.8rem;
            margin-top: 5px;
            display: block;
            font-weight: 500;
        }
        
        small {
            color: #7a8ab0 !important;
            display: block;
            margin-top: 8px;
            font-size: 0.8rem;
            letter-spacing: 0.5px;
        }
        
        .success-message {
            background: rgba(0, 255, 0, 0.1);
            backdrop-filter: blur(10px);
            color: #a0ffa0;
            padding: 15px;
            border-radius: 15px;
            margin-bottom: 25px;
            border-left: 4px solid #00ff00;
            border: 2px solid rgba(0, 255, 0, 0.3);
            text-align: center;
            font-weight: 500;
            animation: successPulse 2s infinite;
        }
        
        @keyframes successPulse {
            0%, 100% { box-shadow: 0 0 10px rgba(0, 255, 0, 0.3); }
            50% { box-shadow: 0 0 25px rgba(0, 255, 0, 0.6); }
        }
        
        .info-note {
            background: rgba(0, 255, 255, 0.1);
            border: 1px solid rgba(0, 255, 255, 0.3);
            border-radius: 10px;
            padding: 10px 15px;
            margin-bottom: 20px;
            font-size: 0.85rem;
            color: #7ab8d0;
        }
        
        .auth-info {
            background: rgba(255, 0, 255, 0.1);
            border-color: #ff00ff;
        }
        
        .auth-info a {
            color: #ff00ff;
            float: right;
            text-decoration: none;
        }
        
        .auth-info a:hover {
            text-decoration: underline;
        }
        
        .clearfix::after {
            content: '';
            clear: both;
            display: table;
        }
        
        @media (max-width: 768px) {
            .container {
                padding: 25px;
            }
            
            .form-grid {
                grid-template-columns: 1fr;
            }
            
            .form-group.full-width {
                grid-column: span 1;
            }
            
            h1 {
                font-size: 1.8rem;
            }
            
            .btn {
                padding: 14px 28px;
                font-size: 1rem;
            }
            
            .header-buttons {
                flex-direction: column;
            }
            
            .auth-btn {
                text-align: center;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header-buttons">
            <?php if ($isAdmin): ?>
                <a href="admin.php" class="auth-btn admin-btn">👑 Админ-панель</a>
            <?php endif; ?>
            
            <?php if (!isAuthenticated()): ?>
                <a href="login.php" class="auth-btn">🔑 Войти</a>
            <?php else: ?>
                <a href="logout.php" class="auth-btn">🚪 Выйти (<?= htmlspecialchars($_SESSION['user_login'] ?? '', ENT_QUOTES, 'UTF-8') ?>)</a>
            <?php endif; ?>
        </div>
        
        <h1>✨ Регистрация ✨</h1>
        
        <div class="info-note">
            💡 Подсказка: Ваши данные сохраняются в cookies на год. При следующем посещении поля будут автоматически заполнены.
        </div>
        
        <?php if (isAuthenticated()): ?>
            <div class="info-note auth-info clearfix">
                🔐 Вы авторизованы как: <strong><?= htmlspecialchars($_SESSION['user_login'] ?? '', ENT_QUOTES, 'UTF-8') ?></strong>
                <a href="logout.php">Выйти</a>
                <div style="clear: both;"></div>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($errors)): ?>
            <div class="error-messages">
                <?php foreach ($errors as $field => $error): ?>
                    <div class="error-message-item">
                        <strong>⚠️ <?= htmlspecialchars($field, ENT_QUOTES, 'UTF-8') ?>:</strong> 
                        <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_GET['success']) && $_GET['success'] == 1): ?>
            <div class="success-message">
                ✓ Данные успешно сохранены! ID записи: <?= htmlspecialchars($_GET['id'] ?? '', ENT_QUOTES, 'UTF-8') ?>
                <?php if (isset($_GET['login']) && isset($_GET['password'])): ?>
                    <br><br>
                    <strong>🔑 Ваши данные для входа:</strong><br>
                    Логин: <code style="background: #0a0f1f; padding: 5px 10px; border-radius: 5px; display: inline-block; margin: 5px 0;"><?= htmlspecialchars($_GET['login'], ENT_QUOTES, 'UTF-8') ?></code><br>
                    Пароль: <code style="background: #0a0f1f; padding: 5px 10px; border-radius: 5px; display: inline-block; margin: 5px 0;"><?= htmlspecialchars($_GET['password'], ENT_QUOTES, 'UTF-8') ?></code><br>
                    <small style="color: #ff00ff;">⚠️ Сохраните эти данные! Они понадобятся для входа.</small>
                <?php endif; ?>
                <?php if (isset($_GET['edited']) && $_GET['edited'] == 1): ?>
                    <br><br>
                    <strong>✏️ Данные успешно обновлены!</strong>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_GET['error'])): ?>
            <div class="error-messages">
                <div class="error-message-item">
                    <strong>⚠️ Ошибка:</strong> 
                    <?php 
                    $errorMsg = '';
                    switch($_GET['error']) {
                        case 'security':
                            $errorMsg = 'Ошибка безопасности. Пожалуйста, обновите страницу и попробуйте снова.';
                            break;
                        case 'db_error':
                            $errorMsg = 'Ошибка базы данных. Пожалуйста, попробуйте позже.';
                            break;
                        case 'update_failed':
                            $errorMsg = 'Ошибка при обновлении данных.';
                            break;
                        default:
                            $errorMsg = 'Произошла неизвестная ошибка.';
                    }
                    echo htmlspecialchars($errorMsg, ENT_QUOTES, 'UTF-8');
                    ?>
                </div>
            </div>
        <?php endif; ?>
        
        <form action="save.php" method="POST">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
            
            <?php if (isAuthenticated()): ?>
                <input type="hidden" name="edit_mode" value="1">
            <?php endif; ?>
            
            <div class="form-grid">
                <div class="form-group">
                    <label for="full_name">👤 ФИО *</label>
                    <input type="text" 
                           id="full_name" 
                           name="full_name" 
                           value="<?= htmlspecialchars($old['full_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                           placeholder="Иванов Иван Иванович"
                           class="<?= isset($errors['full_name']) ? 'field-error' : '' ?>"
                           required
                           maxlength="150">
                    <?php if (isset($errors['full_name'])): ?>
                        <span class="field-error-message"><?= htmlspecialchars($errors['full_name'], ENT_QUOTES, 'UTF-8') ?></span>
                    <?php endif; ?>
                    <small>Допустимы: русские и английские буквы, пробелы, дефисы (макс. 150 символов)</small>
                </div>
                
                <div class="form-group">
                    <label for="phone">📱 Телефон *</label>
                    <input type="tel" 
                           id="phone" 
                           name="phone" 
                           value="<?= htmlspecialchars($old['phone'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                           placeholder="+7 (999) 123-45-67"
                           class="<?= isset($errors['phone']) ? 'field-error' : '' ?>"
                           required
                           maxlength="20">
                    <?php if (isset($errors['phone'])): ?>
                        <span class="field-error-message"><?= htmlspecialchars($errors['phone'], ENT_QUOTES, 'UTF-8') ?></span>
                    <?php endif; ?>
                    <small>Допустимы: цифры, пробелы, +, -, (, ) (5-20 символов)</small>
                </div>
                
                <div class="form-group">
                    <label for="email">📧 E-mail *</label>
                    <input type="email" 
                           id="email" 
                           name="email" 
                           value="<?= htmlspecialchars($old['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                           placeholder="example@mail.com"
                           class="<?= isset($errors['email']) ? 'field-error' : '' ?>"
                           required
                           maxlength="100">
                    <?php if (isset($errors['email'])): ?>
                        <span class="field-error-message"><?= htmlspecialchars($errors['email'], ENT_QUOTES, 'UTF-8') ?></span>
                    <?php endif; ?>
                    <small>Формат: name@domain.com (до 100 символов)</small>
                </div>
                
                <div class="form-group">
                    <label for="birth_date">🎂 Дата рождения *</label>
                    <input type="date" 
                           id="birth_date" 
                           name="birth_date" 
                           value="<?= htmlspecialchars($old['birth_date'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                           class="<?= isset($errors['birth_date']) ? 'field-error' : '' ?>"
                           required>
                    <?php if (isset($errors['birth_date'])): ?>
                        <span class="field-error-message"><?= htmlspecialchars($errors['birth_date'], ENT_QUOTES, 'UTF-8') ?></span>
                    <?php endif; ?>
                    <small>Диапазон: от 1900-01-01 до сегодня</small>
                </div>
                
                <div class="form-group">
                    <label>⚥ Пол *</label>
                    <div class="radio-group">
                        <label class="radio-option">
                            <input type="radio" 
                                   name="gender" 
                                   value="male"
                                   <?= (isset($old['gender']) && $old['gender'] === 'male') ? 'checked' : '' ?>
                                   required> Мужской
                        </label>
                        <label class="radio-option">
                            <input type="radio" 
                                   name="gender" 
                                   value="female"
                                   <?= (isset($old['gender']) && $old['gender'] === 'female') ? 'checked' : '' ?>
                                   required> Женский
                        </label>
                    </div>
                    <?php if (isset($errors['gender'])): ?>
                        <span class="field-error-message"><?= htmlspecialchars($errors['gender'], ENT_QUOTES, 'UTF-8') ?></span>
                    <?php endif; ?>
                </div>
                
                <div class="form-group">
                    <label for="languages">💻 Любимый язык программирования *</label>
                    <select name="languages[]" 
                            id="languages" 
                            multiple 
                            size="6"
                            class="<?= isset($errors['languages']) ? 'field-error' : '' ?>"
                            required>
                        <?php
                        $selectedLanguages = $old['languages'] ?? [];
                        if (!is_array($selectedLanguages)) {
                            $selectedLanguages = [];
                        }
                        foreach ($languagesList as $lang):
                        ?>
                            <option value="<?= htmlspecialchars($lang, ENT_QUOTES, 'UTF-8') ?>" 
                                <?= in_array($lang, $selectedLanguages) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($lang, ENT_QUOTES, 'UTF-8') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (isset($errors['languages'])): ?>
                        <span class="field-error-message"><?= htmlspecialchars($errors['languages'], ENT_QUOTES, 'UTF-8') ?></span>
                    <?php endif; ?>
                    <small>Удерживайте Ctrl (Cmd) для выбора нескольких. Доступны только языки из списка</small>
                </div>
                
                <div class="form-group full-width">
                    <label for="biography">📝 Биография</label>
                    <textarea id="biography" 
                              name="biography" 
                              placeholder="Расскажите о себе..."
                              maxlength="65535"><?= htmlspecialchars($old['biography'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
                    <?php if (isset($errors['biography'])): ?>
                        <span class="field-error-message"><?= htmlspecialchars($errors['biography'], ENT_QUOTES, 'UTF-8') ?></span>
                    <?php endif; ?>
                    <small>Максимальная длина: 65535 символов</small>
                </div>
                
                <div class="form-group full-width">
                    <div class="checkbox-group">
                        <input type="checkbox" 
                               name="contract_accepted" 
                               id="contract" 
                               value="1"
                               <?= isset($old['contract_accepted']) && $old['contract_accepted'] === '1' ? 'checked' : '' ?>
                               required>
                        <label for="contract">✅ Я ознакомлен(а) с контрактом *</label>
                    </div>
                    <?php if (isset($errors['contract_accepted'])): ?>
                        <span class="field-error-message"><?= htmlspecialchars($errors['contract_accepted'], ENT_QUOTES, 'UTF-8') ?></span>
                    <?php endif; ?>
                </div>
            </div>
            
            <button type="submit" class="btn">🚀 Отправить</button>
        </form>
    </div>
    
    <!-- Защита от XSS через JavaScript -->
    <script>
        // Дополнительная защита от XSS - экранирование динамического контента
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
        
        // Проверка на наличие опасных символов в полях ввода
        document.querySelectorAll('input, textarea').forEach(function(element) {
            element.addEventListener('blur', function() {
                if (this.value && /[<>]/.test(this.value)) {
                    this.style.borderColor = '#ff4d4d';
                    if (!this.nextElementSibling || !this.nextElementSibling.classList.contains('xss-warning')) {
                        const warning = document.createElement('span');
                        warning.className = 'field-error-message xss-warning';
                        warning.style.color = '#ff8a8a';
                        warning.innerHTML = '⚠️ Обнаружены потенциально опасные символы (<, >)';
                        if (this.nextElementSibling) {
                            this.parentNode.insertBefore(warning, this.nextElementSibling);
                        } else {
                            this.parentNode.appendChild(warning);
                        }
                    }
                } else {
                    this.style.borderColor = '';
                    const warning = this.parentNode.querySelector('.xss-warning');
                    if (warning) warning.remove();
                }
            });
        });
    </script>
</body>
</html>