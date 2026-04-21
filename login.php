<?php
// login.php

require_once 'config.php';
require_once 'auth.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login = $_POST['login'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if (login($login, $password, $pdo)) {
        header('Location: index.php');
        exit;
    } else {
        $error = 'Неверный логин или пароль';
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Вход в систему</title>
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
        }
        
        .login-container {
            max-width: 450px;
            width: 100%;
            background: rgba(18, 25, 45, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 30px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5),
                        0 0 0 1px rgba(0, 255, 255, 0.2) inset;
            padding: 40px;
            animation: slideUp 0.6s cubic-bezier(0.23, 1, 0.32, 1);
            border: 1px solid rgba(0, 255, 255, 0.3);
        }
        
        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        h1 {
            text-align: center;
            color: #fff;
            margin-bottom: 35px;
            font-size: 2rem;
            text-shadow: 0 0 10px rgba(0, 255, 255, 0.5);
        }
        
        .form-group {
            margin-bottom: 25px;
        }
        
        label {
            display: block;
            margin-bottom: 8px;
            color: #a0b0d0;
            font-weight: 500;
        }
        
        input {
            width: 100%;
            padding: 14px 18px;
            border: 2px solid #2a3a5a;
            border-radius: 15px;
            font-size: 1rem;
            background: #1a2540;
            color: #fff;
            transition: all 0.3s;
        }
        
        input:focus {
            outline: none;
            border-color: #00ffff;
            box-shadow: 0 0 20px rgba(0, 255, 255, 0.3);
        }
        
        .btn {
            background: linear-gradient(45deg, #00ffff, #ff00ff);
            color: #0a0f1f;
            border: none;
            padding: 16px 32px;
            font-size: 1.1rem;
            border-radius: 50px;
            cursor: pointer;
            width: 100%;
            font-weight: 700;
            text-transform: uppercase;
            transition: all 0.3s;
        }
        
        .btn:hover {
            transform: scale(1.02);
            box-shadow: 0 0 30px rgba(255, 0, 255, 0.5);
        }
        
        .error {
            background: rgba(255, 50, 50, 0.2);
            color: #ff8a8a;
            padding: 12px;
            border-radius: 10px;
            margin-bottom: 20px;
            text-align: center;
        }
        
        .register-link {
            text-align: center;
            margin-top: 20px;
            color: #a0b0d0;
        }
        
        .register-link a {
            color: #00ffff;
            text-decoration: none;
        }
        
        .register-link a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <h1>🔐 Вход в систему</h1>
        
        <?php if ($error): ?>
            <div class="error">⚠️ <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-group">
                <label>📝 Логин</label>
                <input type="text" name="login" required autocomplete="username">
            </div>
            
            <div class="form-group">
                <label>🔒 Пароль</label>
                <input type="password" name="password" required autocomplete="current-password">
            </div>
            
            <button type="submit" class="btn">Войти</button>
        </form>
        
        <div class="register-link">
            Нет аккаунта? <a href="index.php">Зарегистрируйтесь</a>
        </div>
    </div>
</body>
</html>