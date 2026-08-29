<?php
/**
 * Конфигурация базы данных и приложения
 * GameMods Platform - FastPanel Optimized
 */

// Настройки базы данных (измените под вашу конфигурацию FastPanel)
define('DB_HOST', 'localhost');
define('DB_NAME', 'game_mods_db');
define('DB_USER', 'gamemods_user');
define('DB_PASS', 'your_secure_password_here');
define('DB_CHARSET', 'utf8mb4');

// Настройки приложения
define('APP_NAME', 'GameMods');
define('APP_URL', 'https://yourdomain.com');
define('APP_DEBUG', false); // Включить только для разработки

// Настройки сессий
define('SESSION_LIFETIME', 86400); // 24 часа в секундах
define('SESSION_NAME', 'gamemods_session');

// Пути
define('BASE_PATH', dirname(__DIR__));
define('UPLOAD_PATH', BASE_PATH . '/uploads');
define('AVATAR_PATH', UPLOAD_PATH . '/avatars');
define('MODS_PATH', UPLOAD_PATH . '/mods');

// Ограничения загрузки
define('MAX_FILE_SIZE', 100 * 1024 * 1024); // 100 MB
define('ALLOWED_EXTENSIONS', ['zip', 'rar', '7z', 'tar.gz']);

// Роли пользователей
define('ROLE_USER', 'user');
define('ROLE_MODERATOR', 'moderator');
define('ROLE_ADMIN', 'admin');

// Функция подключения к БД
function getDbConnection() {
    static $pdo = null;
    
    if ($pdo === null) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];
            
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            if (APP_DEBUG) {
                die("Ошибка подключения к БД: " . $e->getMessage());
            } else {
                die("Ошибка подключения к базе данных");
            }
        }
    }
    
    return $pdo;
}

// Функция хеширования пароля
function hashPassword($password) {
    return password_hash($password, PASSWORD_BCRYPT, ['cost' => 10]);
}

// Функция проверки пароля
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

// Генерация токена сессии
function generateSessionToken() {
    return bin2hex(random_bytes(32));
}

// Безопасный вывод данных
function e($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

// Перенаправление
function redirect($url) {
    header("Location: " . $url);
    exit;
}

// Проверка авторизации
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

// Получение текущего пользователя
function getCurrentUser() {
    if (!isLoggedIn()) {
        return null;
    }
    
    $pdo = getDbConnection();
    $stmt = $pdo->prepare("SELECT id, username, email, avatar_url, role, created_at FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch();
}

// Проверка роли администратора
function isAdmin() {
    if (!isLoggedIn()) {
        return false;
    }
    
    $user = getCurrentUser();
    return $user && $user['role'] === ROLE_ADMIN;
}

// Проверка роли модератора или админа
function isModerator() {
    if (!isLoggedIn()) {
        return false;
    }
    
    $user = getCurrentUser();
    return $user && in_array($user['role'], [ROLE_MODERATOR, ROLE_ADMIN]);
}

// Логирование активности пользователя
function logUserActivity($userId, $actionType, $description = null) {
    $pdo = getDbConnection();
    $ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;
    
    $stmt = $pdo->prepare("INSERT INTO user_activity (user_id, action_type, description, ip_address) VALUES (?, ?, ?, ?)");
    $stmt->execute([$userId, $actionType, $description, $ipAddress]);
}

// Создание сессии пользователя
function createSession($userId) {
    $pdo = getDbConnection();
    $sessionToken = generateSessionToken();
    $ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;
    $expiresAt = date('Y-m-d H:i:s', time() + SESSION_LIFETIME);
    
    $stmt = $pdo->prepare("INSERT INTO user_sessions (user_id, session_token, ip_address, user_agent, expires_at) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$userId, $sessionToken, $ipAddress, $userAgent, $expiresAt]);
    
    $_SESSION['user_id'] = $userId;
    $_SESSION['session_token'] = $sessionToken;
    
    logUserActivity($userId, 'login', 'Пользователь вошел в систему');
    
    return $sessionToken;
}

// Уничтожение сессии
function destroySession() {
    if (isset($_SESSION['user_id'])) {
        logUserActivity($_SESSION['user_id'], 'logout', 'Пользователь вышел из системы');
        
        $pdo = getDbConnection();
        $stmt = $pdo->prepare("UPDATE user_sessions SET is_active = FALSE WHERE user_id = ? AND session_token = ?");
        $stmt->execute([$_SESSION['user_id'], $_SESSION['session_token'] ?? null]);
    }
    
    session_destroy();
    $_SESSION = [];
}

// Инициализация сессии
if (session_status() === PHP_SESSION_NONE) {
    session_name(SESSION_NAME);
    session_start();
}

// Проверка CSRF токена
function generateCsrfToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCsrfToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// Валидация email
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

// Валидация username
function isValidUsername($username) {
    return preg_match('/^[a-zA-Z0-9_]{3,50}$/', $username);
}

// Загрузка аватара
function uploadAvatar($file, $userId) {
    if (!isset($file['error']) || !is_int($file['error'])) {
        return ['success' => false, 'message' => 'Ошибка загрузки файла'];
    }
    
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'message' => 'Ошибка при загрузке файла'];
    }
    
    if (!is_dir(AVATAR_PATH)) {
        mkdir(AVATAR_PATH, 0755, true);
    }
    
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    
    if (!in_array($extension, $allowedExtensions)) {
        return ['success' => false, 'message' => 'Недопустимый формат файла'];
    }
    
    if ($file['size'] > 5 * 1024 * 1024) {
        return ['success' => false, 'message' => 'Файл слишком большой (макс. 5MB)'];
    }
    
    $filename = 'avatar_' . $userId . '_' . time() . '.' . $extension;
    $destination = AVATAR_PATH . '/' . $filename;
    
    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        return ['success' => false, 'message' => 'Не удалось сохранить файл'];
    }
    
    return ['success' => true, 'filename' => $filename];
}

// Получение URL аватара
function getAvatarUrl($user) {
    if (!empty($user['avatar_url'])) {
        return '/uploads/avatars/' . $user['avatar_url'];
    }
    
    // Генерация аватара по умолчанию на основе email
    $hash = md5(strtolower(trim($user['email'])));
    return 'https://www.gravatar.com/avatar/' . $hash . '?d=mp&s=100';
}
