<?php
// save.php

require_once 'config.php';
require_once 'auth.php';

// Функции валидации (остаются те же, что и были)
function validateFullName($name) {
    if (empty($name)) {
        return "ФИО обязательно для заполнения";
    }
    if (strlen($name) > 150) {
        return "ФИО не должно превышать 150 символов (текущая длина: " . strlen($name) . ")";
    }
    if (!preg_match('/^[а-яА-ЯёЁa-zA-Z\s\-]+$/u', $name)) {
        return "ФИО должно содержать только буквы (русские или английские), пробелы и дефисы. Недопустимые символы: цифры, знаки препинания, спецсимволы";
    }
    return null;
}

function validatePhone($phone) {
    if (empty($phone)) {
        return "Телефон обязателен для заполнения";
    }
    if (!preg_match('/^[\d\s\+\-\(\)]{5,20}$/', $phone)) {
        return "Телефон должен содержать от 5 до 20 символов. Допустимые символы: цифры (0-9), пробелы, +, -, (, ). Пример: +7 (999) 123-45-67";
    }
    return null;
}

function validateEmail($email) {
    if (empty($email)) {
        return "Email обязателен для заполнения";
    }
    if (strlen($email) > 100) {
        return "Email не должен превышать 100 символов (текущая длина: " . strlen($email) . ")";
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return "Некорректный формат email. Допустимый формат: username@domain.tld. Допустимы: латинские буквы, цифры, точки, дефисы, знак @";
    }
    return null;
}

function validateBirthDate($date) {
    if (empty($date)) {
        return "Дата рождения обязательна для заполнения";
    }
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
        return "Дата рождения должна быть в формате ГГГГ-ММ-ДД (год-месяц-день). Пример: 1990-05-15";
    }
    
    $d = DateTime::createFromFormat('Y-m-d', $date);
    if (!$d || $d->format('Y-m-d') !== $date) {
        return "Некорректная дата. Проверьте правильность указания года, месяца и дня";
    }
    
    $minDate = new DateTime('1900-01-01');
    $maxDate = new DateTime('today');
    
    if ($d < $minDate) {
        return "Дата рождения не может быть ранее 1900-01-01. Указанная дата: " . $date;
    }
    if ($d > $maxDate) {
        return "Дата рождения не может быть в будущем. Указанная дата: " . $date;
    }
    return null;
}

function validateGender($gender) {
    if (empty($gender)) {
        return "Выберите пол (Мужской или Женский)";
    }
    $allowed = ['male', 'female'];
    if (!in_array($gender, $allowed)) {
        return "Недопустимое значение пола. Допустимые значения: 'male' (Мужской) или 'female' (Женский)";
    }
    return null;
}

function validateLanguages($languages) {
    if (empty($languages) || !is_array($languages)) {
        return "Выберите хотя бы один язык программирования из списка";
    }
    
    $allowed = ['Pascal', 'C', 'C++', 'JavaScript', 'PHP', 'Python',
                'Java', 'Haskell', 'Clojure', 'Prolog', 'Scala', 'Go'];
    
    $invalidLanguages = [];
    foreach ($languages as $lang) {
        if (!in_array($lang, $allowed)) {
            $invalidLanguages[] = $lang;
        }
    }
    
    if (!empty($invalidLanguages)) {
        return "Обнаружены недопустимые языки программирования: " . implode(', ', $invalidLanguages) . 
               ". Допустимые языки: " . implode(', ', $allowed);
    }
    return null;
}

function validateBiography($bio) {
    if (strlen($bio) > 65535) {
        return "Биография слишком длинная. Максимальная длина: 65535 символов (текущая длина: " . strlen($bio) . ")";
    }
    return null;
}

function validateContract($contract) {
    if ($contract !== '1') {
        return "Необходимо подтвердить ознакомление с контрактом, поставив галочку";
    }
    return null;
}

// Сохранение данных в БД (новая версия с логином и паролем)
function saveFormData($pdo, $data, $languages) {
    try {
        $pdo->beginTransaction();
        
        // Генерируем логин и пароль
        $login = generateLogin($data['full_name'], $pdo);
        $password = generatePassword();
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        
        // Вставка в таблицу users
        $stmt = $pdo->prepare("
            INSERT INTO users (full_name, phone, email, birth_date, gender, biography, contract_accepted, login, password_hash)
            VALUES (:full_name, :phone, :email, :birth_date, :gender, :biography, :contract_accepted, :login, :password_hash)
        ");
        
        $stmt->execute([
            ':full_name' => $data['full_name'],
            ':phone' => $data['phone'],
            ':email' => $data['email'],
            ':birth_date' => $data['birth_date'],
            ':gender' => $data['gender'],
            ':biography' => $data['biography'] ?? '',
            ':contract_accepted' => isset($data['contract_accepted']) ? 1 : 0,
            ':login' => $login,
            ':password_hash' => $passwordHash
        ]);
        
        $userId = $pdo->lastInsertId();
        
        // Получаем ID языков из справочника
        $placeholders = implode(',', array_fill(0, count($languages), '?'));
        $stmt = $pdo->prepare("SELECT id, name FROM programming_languages WHERE name IN ($placeholders)");
        $stmt->execute($languages);
        $langIds = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
        
        // Вставка связей
        $stmt = $pdo->prepare("INSERT INTO user_languages (user_id, language_id) VALUES (?, ?)");
        foreach ($languages as $lang) {
            if (isset($langIds[$lang])) {
                $stmt->execute([$userId, $langIds[$lang]]);
            }
        }
        
        $pdo->commit();
        return ['userId' => $userId, 'login' => $login, 'password' => $password];
        
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
}

// Сохранение данных в Cookies на год
function saveToCookies($data, $languages) {
    $saveData = [
        'full_name' => $data['full_name'] ?? '',
        'phone' => $data['phone'] ?? '',
        'email' => $data['email'] ?? '',
        'birth_date' => $data['birth_date'] ?? '',
        'gender' => $data['gender'] ?? '',
        'languages' => $languages ?? [],
        'biography' => $data['biography'] ?? '',
        'contract_accepted' => isset($data['contract_accepted']) ? '1' : ''
    ];
    
    setcookie('saved_form_data', json_encode($saveData), time() + 365 * 24 * 60 * 60, '/');
}

// Основная логика обработки
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$errors = [];

// Валидация всех полей
if ($error = validateFullName($_POST['full_name'] ?? '')) {
    $errors['full_name'] = $error;
}

if ($error = validatePhone($_POST['phone'] ?? '')) {
    $errors['phone'] = $error;
}

if ($error = validateEmail($_POST['email'] ?? '')) {
    $errors['email'] = $error;
}

if ($error = validateBirthDate($_POST['birth_date'] ?? '')) {
    $errors['birth_date'] = $error;
}

if ($error = validateGender($_POST['gender'] ?? '')) {
    $errors['gender'] = $error;
}

$languages = $_POST['languages'] ?? [];
if ($error = validateLanguages($languages)) {
    $errors['languages'] = $error;
}

if ($error = validateBiography($_POST['biography'] ?? '')) {
    $errors['biography'] = $error;
}

if ($error = validateContract($_POST['contract_accepted'] ?? '')) {
    $errors['contract_accepted'] = $error;
}

// Если есть ошибки, сохраняем в cookies и возвращаемся
if (!empty($errors)) {
    setcookie('form_errors', json_encode($errors), time() + 300, '/');
    
    $oldData = [
        'full_name' => $_POST['full_name'] ?? '',
        'phone' => $_POST['phone'] ?? '',
        'email' => $_POST['email'] ?? '',
        'birth_date' => $_POST['birth_date'] ?? '',
        'gender' => $_POST['gender'] ?? '',
        'languages' => $languages,
        'biography' => $_POST['biography'] ?? '',
        'contract_accepted' => isset($_POST['contract_accepted']) ? '1' : ''
    ];
    setcookie('form_old', json_encode($oldData), time() + 300, '/');
    
    header('Location: index.php');
    exit;
}

// Проверяем, авторизован ли пользователь и есть ли параметр edit
$isEdit = isset($_POST['edit_mode']) && $_POST['edit_mode'] === '1' && isAuthenticated();

if ($isEdit) {
    // Режим редактирования
    $userId = getCurrentUserId();
    try {
        if (updateUserData($userId, $_POST, $languages, $pdo)) {
            // Обновляем данные в cookies
            saveToCookies($_POST, $languages);
            header('Location: index.php?success=1&edited=1&id=' . $userId);
        } else {
            header('Location: index.php?error=update_failed');
        }
    } catch (Exception $e) {
        header('Location: index.php?error=db_error');
    }
} else {
    // Режим новой регистрации
    try {
        $result = saveFormData($pdo, $_POST, $languages);
        saveToCookies($_POST, $languages);
        
        // Автоматически авторизуем пользователя после регистрации
        login($result['login'], $result['password'], $pdo);
        
        header('Location: index.php?success=1&id=' . $result['userId'] . '&login=' . urlencode($result['login']) . '&password=' . urlencode($result['password']));
    } catch (Exception $e) {
        header('Location: index.php?error=db_error');
    }
}
exit;
?>