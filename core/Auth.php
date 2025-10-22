<?php
/**
 * LightBlog CMS - Authentication System
 * Handles user authentication and authorization
 */

class Auth {
    private $db;
    private static $currentUser = null;

    /**
     * Constructor
     */
    public function __construct() {
        $this->db = Database::getInstance();

        // Start session if not already started
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    /**
     * Login user
     * @param string $username Username or email
     * @param string $password Password
     * @return bool Success
     */
    public function login($username, $password) {
        $user = $this->db->queryOne(
            "SELECT * FROM users WHERE username = ? OR email = ? LIMIT 1",
            [$username, $username]
        );

        if (!$user) {
            return false;
        }

        if (!password_verify($password, $user->password)) {
            return false;
        }

        // Set session
        $_SESSION['user_id'] = $user->id;
        $_SESSION['username'] = $user->username;
        $_SESSION['role'] = $user->role;
        $_SESSION['login_time'] = time();

        // Regenerate session ID for security
        session_regenerate_id(true);

        self::$currentUser = $user;

        return true;
    }

    /**
     * Logout user
     */
    public function logout() {
        $_SESSION = [];

        if (isset($_COOKIE[session_name()])) {
            setcookie(session_name(), '', time() - 3600, '/');
        }

        session_destroy();
        self::$currentUser = null;
    }

    /**
     * Check if user is logged in
     * @return bool
     */
    public function isLoggedIn() {
        return isset($_SESSION['user_id']);
    }

    /**
     * Get current user
     * @return object|null User object
     */
    public function getCurrentUser() {
        if (self::$currentUser !== null) {
            return self::$currentUser;
        }

        if (!$this->isLoggedIn()) {
            return null;
        }

        $user = $this->db->queryOne(
            "SELECT * FROM users WHERE id = ?",
            [$_SESSION['user_id']]
        );

        self::$currentUser = $user;

        return $user;
    }

    /**
     * Get current user ID
     * @return int|null
     */
    public function getCurrentUserId() {
        return $_SESSION['user_id'] ?? null;
    }

    /**
     * Check if current user has a specific role
     * @param string $role Role to check
     * @return bool
     */
    public function hasRole($role) {
        if (!$this->isLoggedIn()) {
            return false;
        }

        return $_SESSION['role'] === $role;
    }

    /**
     * Check if current user is admin
     * @return bool
     */
    public function isAdmin() {
        return $this->hasRole('admin');
    }

    /**
     * Require login - redirect if not logged in
     * @param string $redirect_to URL to redirect to after login
     */
    public function requireLogin($redirect_to = null) {
        if (!$this->isLoggedIn()) {
            $redirect = $redirect_to ?? $_SERVER['REQUEST_URI'];
            $basePath = defined('BASE_PATH') ? BASE_PATH : '/';
            header('Location: ' . $basePath . 'admin/login.php?redirect=' . urlencode($redirect));
            exit;
        }
    }

    /**
     * Require admin - redirect if not admin
     */
    public function requireAdmin() {
        $this->requireLogin();

        if (!$this->isAdmin()) {
            http_response_code(403);
            die('Access denied. Admin privileges required.');
        }
    }

    /**
     * Register new user
     * @param array $data User data (username, email, password, role)
     * @return int|false User ID or false on failure
     */
    public function register($data) {
        // Validate username
        if (empty($data['username']) || strlen($data['username']) < 3) {
            throw new Exception('Username must be at least 3 characters');
        }

        // Validate email
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            throw new Exception('Invalid email address');
        }

        // Validate password
        if (empty($data['password']) || strlen($data['password']) < 6) {
            throw new Exception('Password must be at least 6 characters');
        }

        // Check if username exists
        if ($this->db->exists('users', 'username = ?', [$data['username']])) {
            throw new Exception('Username already exists');
        }

        // Check if email exists
        if ($this->db->exists('users', 'email = ?', [$data['email']])) {
            throw new Exception('Email already exists');
        }

        // Hash password
        $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);

        // Insert user
        $userId = $this->db->insert('users', [
            'username' => $data['username'],
            'email' => $data['email'],
            'password' => $hashedPassword,
            'role' => $data['role'] ?? 'editor',
            'created_at' => date('Y-m-d H:i:s')
        ]);

        return $userId;
    }

    /**
     * Update user password
     * @param int $user_id User ID
     * @param string $old_password Old password
     * @param string $new_password New password
     * @return bool Success
     */
    public function changePassword($user_id, $old_password, $new_password) {
        $user = $this->db->queryOne("SELECT * FROM users WHERE id = ?", [$user_id]);

        if (!$user) {
            return false;
        }

        if (!password_verify($old_password, $user->password)) {
            throw new Exception('Old password is incorrect');
        }

        if (strlen($new_password) < 6) {
            throw new Exception('New password must be at least 6 characters');
        }

        $hashedPassword = password_hash($new_password, PASSWORD_DEFAULT);

        $this->db->update(
            'users',
            ['password' => $hashedPassword],
            'id = :id',
            ['id' => $user_id]
        );

        return true;
    }

    /**
     * Generate CSRF token
     * @return string Token
     */
    public static function generateCSRFToken() {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    /**
     * Verify CSRF token
     * @param string $token Token to verify
     * @return bool Valid
     */
    public static function verifyCSRFToken($token) {
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }

    /**
     * Get CSRF token input field HTML
     * @return string HTML input field
     */
    public static function csrfField() {
        $token = self::generateCSRFToken();
        return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token) . '">';
    }

    /**
     * Check if session is expired (optional timeout feature)
     * @param int $timeout Timeout in seconds (default 2 hours)
     * @return bool Expired
     */
    public function isSessionExpired($timeout = 7200) {
        if (!isset($_SESSION['login_time'])) {
            return true;
        }

        return (time() - $_SESSION['login_time']) > $timeout;
    }

    /**
     * Refresh session timeout
     */
    public function refreshSession() {
        $_SESSION['login_time'] = time();
    }
}
