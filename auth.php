<?php
// auth.php
session_start();
require_once 'config.php';

function generateLogin($fullName, $pdo) {
    // Генерация логина из ФИО (транслитерация)
    $translit = [
        'а' => 'a', 'б' => 'b', 'в' => 'v', 'г' => 'g', 'д' => 'd', 'е' => 'e', 'ё' => 'e',
        'ж' => 'zh', 'з' => 'z', 'и' => 'i', 'й' => 'y', 'к' => 'k', 'л' => 'l', 'м' => 'm',
        'н' => 'n', 'о' => 'o', 'п' => 'p', 'р' => 'r', 'с' => 's', 'т' => 't', 'у' => 'u',
        'ф' => 'f', 'х' => 'h', 'ц' => 'ts', 'ч' => 'ch', 'ш' => 'sh', 'щ' => 'sch', 'ъ' => '',
        'ы' => 'y', 'ь' => '', 'э' => 'e', 'ю' => 'yu', 'я' => 'ya'
    ];
    
    $name = mb_strtolower($fullName);
    $login = strtr($name, $translit);
    $login = preg_replace('/[^a-z0-9]/', '', $login);
    $login = substr($login, 0, 20);
    
    // Проверяем уникальность
    $originalLogin = $login;
    $counter = 1;
    
    $stmt = $pdo->prepare("SELECT id FROM users WHERE login = ?");
    while (true) {
        $stmt->execute([$login]);
        if (!$stmt->fetch()) {
            break;
        }
        $login = $originalLogin . $counter;
        $counter++;
    }
    
    return $login;
}

function generatePassword($length = 10) {
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*';
    return substr(str_shuffle($chars), 0, $length);
}

function login($login, $password, $pdo) {
    $stmt = $pdo->prepare("SELECT id, login, password_hash FROM users WHERE login = ?");
    $stmt->execute([$login]);
    $user = $stmt->fetch();
    
    if ($user && password_verify($password, $user['password_hash'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_login'] = $user['login'];
        $_SESSION['authenticated'] = true;
        return true;
    }
    return false;
}

function isAuthenticated() {
    return isset($_SESSION['authenticated']) && $_SESSION['authenticated'] === true;
}

function getCurrentUserId() {
    return $_SESSION['user_id'] ?? null;
}

function logout() {
    session_destroy();
}

function getUserData($userId, $pdo) {
    $stmt = $pdo->prepare("
        SELECT u.*, GROUP_CONCAT(pl.name) as languages_list
        FROM users u
        LEFT JOIN user_languages ul ON u.id = ul.user_id
        LEFT JOIN programming_languages pl ON ul.language_id = pl.id
        WHERE u.id = ?
        GROUP BY u.id
    ");
    $stmt->execute([$userId]);
    $userData = $stmt->fetch();
    
    if ($userData) {
        // Получаем языки как массив
        $userData['languages'] = $userData['languages_list'] ? explode(',', $userData['languages_list']) : [];
        unset($userData['languages_list']);
        unset($userData['password_hash']);
    }
    
    return $userData;
}

function updateUserData($userId, $data, $languages, $pdo) {
    try {
        $pdo->beginTransaction();
        
        // Обновляем данные пользователя
        $stmt = $pdo->prepare("
            UPDATE users 
            SET full_name = :full_name,
                phone = :phone,
                email = :email,
                birth_date = :birth_date,
                gender = :gender,
                biography = :biography,
                contract_accepted = :contract_accepted,
                is_edited = 1
            WHERE id = :id
        ");
        
        $stmt->execute([
            ':full_name' => $data['full_name'],
            ':phone' => $data['phone'],
            ':email' => $data['email'],
            ':birth_date' => $data['birth_date'],
            ':gender' => $data['gender'],
            ':biography' => $data['biography'] ?? '',
            ':contract_accepted' => isset($data['contract_accepted']) ? 1 : 0,
            ':id' => $userId
        ]);
        
        // Удаляем старые связи с языками
        $stmt = $pdo->prepare("DELETE FROM user_languages WHERE user_id = ?");
        $stmt->execute([$userId]);
        
        // Добавляем новые связи
        $placeholders = implode(',', array_fill(0, count($languages), '?'));
        $stmt = $pdo->prepare("SELECT id, name FROM programming_languages WHERE name IN ($placeholders)");
        $stmt->execute($languages);
        $langIds = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
        
        $stmt = $pdo->prepare("INSERT INTO user_languages (user_id, language_id) VALUES (?, ?)");
        foreach ($languages as $lang) {
            if (isset($langIds[$lang])) {
                $stmt->execute([$userId, $langIds[$lang]]);
            }
        }
        
        $pdo->commit();
        return true;
        
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
}
?>