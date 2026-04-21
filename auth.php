<?php
// auth.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'config.php';

// Функция для транслитерации без mbstring
function simpleTranslit($text) {
    $cyrillic = [
        'а','б','в','г','д','е','ё','ж','з','и','й','к','л','м','н','о','п',
        'р','с','т','у','ф','х','ц','ч','ш','щ','ъ','ы','ь','э','ю','я',
        'А','Б','В','Г','Д','Е','Ё','Ж','З','И','Й','К','Л','М','Н','О','П',
        'Р','С','Т','У','Ф','Х','Ц','Ч','Ш','Щ','Ъ','Ы','Ь','Э','Ю','Я'
    ];
    $latin = [
        'a','b','v','g','d','e','e','zh','z','i','y','k','l','m','n','o','p',
        'r','s','t','u','f','h','ts','ch','sh','sch','','y','','e','yu','ya',
        'A','B','V','G','D','E','E','Zh','Z','I','Y','K','L','M','N','O','P',
        'R','S','T','U','F','H','Ts','Ch','Sh','Sch','','Y','','E','Yu','Ya'
    ];
    
    // Используем str_replace вместо mb_strtolower
    $text = str_replace($cyrillic, $latin, $text);
    // Приводим к нижнему регистру стандартной функцией (работает с ASCII)
    $text = strtolower($text);
    // Удаляем все кроме букв и цифр
    $text = preg_replace('/[^a-z0-9]/', '', $text);
    
    return $text;
}

function generateLogin($fullName, $pdo) {
    // Транслитерация ФИО
    $login = simpleTranslit($fullName);
    $login = substr($login, 0, 20);
    
    // Если получилась пустая строка, генерируем случайный логин
    if (empty($login)) {
        $login = 'user_' . bin2hex(random_bytes(4));
    }
    
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
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_login'] = $user['login'];
        $_SESSION['authenticated'] = true;
        session_regenerate_id(true);
        return true;
    }
    return false;
}

function isAuthenticated() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    return isset($_SESSION['authenticated']) && $_SESSION['authenticated'] === true;
}

function getCurrentUserId() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    return $_SESSION['user_id'] ?? null;
}

function logout() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $_SESSION = array();
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
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
        $userData['languages'] = $userData['languages_list'] ? explode(',', $userData['languages_list']) : [];
        unset($userData['languages_list']);
        unset($userData['password_hash']);
    }
    
    return $userData;
}

function updateUserData($userId, $data, $languages, $pdo) {
    try {
        $pdo->beginTransaction();
        
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
        if (!empty($languages)) {
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
        }
        
        $pdo->commit();
        return true;
        
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Update user error: " . $e->getMessage());
        return false;
    }
}
?>