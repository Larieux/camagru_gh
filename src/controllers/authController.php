<?php

require_once __DIR__ . '/../models/User.php';

class AuthController
{
    private User $user;

    public function __construct(PDO $db)
    {
        $this->user = new User($db);
    }

    public function register(): void
    {
        require_guest();

        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            render('register', [
                'title' => 'Create an accountbite',
                'testMessage' => 'Hello from the controller!'###tmp
            ]);
            return;
        }

        if (!csrf_valid($_POST['csrf_token'] ?? null)) {
            http_response_code(403);
            render('register', ['errors' => ['Invalid request.']]);
            return;
        }

        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        $errors = [];

        if (!preg_match('/^[a-zA-Z0-9_-]{3,50}$/', $username)) {
            $errors[] = 'Username must be 3-50 characters and use only letters, numbers, underscores or hyphens.';
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Invalid email address.';
        }

        if (strlen($password) < 12) {
            $errors[] = 'Password must contain at least 12 characters.';
        }

        if ($this->user->findByUsername($username)) {
            $errors[] = 'Username is already taken.';
        }

        if ($this->user->findByEmail($email)) {
            $errors[] = 'Email is already registered.';
        }

        if (!empty($errors)) {
            render('register', [
                'errors' => $errors,
                'username' => $username,
                'email' => $email
            ]);
            return;
        }

        $passwordHash = password_hash($password, PASSWORD_DEFAULT);

        $userId = $this->user->create(
            $username,
            $email,
            $passwordHash
        );

        $token = bin2hex(random_bytes(32));
        $tokenHash = hash('sha256', $token);

        $expiresAt = date('Y-m-d H:i:s', time() + 3600);

        $this->user->createToken(
            $userId,
            $tokenHash,
            'verification',
            $expiresAt
        );

        $verificationUrl = $this->buildTokenUrl(
            '/verify',
            $token
        );

        $this->sendMail(
            $email,
            'Confirm your Camagru account',
            "Please confirm your account using this link:\n\n"
            . $verificationUrl
        );

        render('verify_notice');
    }

    public function login(): void
    {
        require_guest();

        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            render('login');
            return;
        }

        if (!csrf_valid($_POST['csrf_token'] ?? null)) {
            http_response_code(403);
            render('login', ['errors' => ['Invalid request.']]);
            return;
        }

        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        $user = $this->user->findByUsername($username);

        if (!$user || !password_verify($password, $user['password'])) {
            render('login', [
                'errors' => ['Invalid username or password.']
            ]);
            return;
        }

        if (!$user['is_verified']) {
            render('login', [
                'errors' => ['Please verify your email before logging in.']
            ]);
            return;
        }

        session_regenerate_id(true);

        $_SESSION['user_id'] = (int)$user['id'];

        header('Location: /');
        exit;
    }

    public function logout(): void
    {
        require_auth();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            exit;
        }

        if (!csrf_valid($_POST['csrf_token'] ?? null)) {
            http_response_code(403);
            exit('Invalid request.');
        }

        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();

            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }

        session_destroy();

        header('Location: /');
        exit;
    }

    public function verify(): void
    {
        $token = $_GET['token'] ?? '';

        if (!is_string($token) || !preg_match('/^[a-f0-9]{64}$/', $token)) {
            render('verification_result', [
                'message' => 'Invalid verification link.'
            ]);
            return;
        }

        $tokenHash = hash('sha256', $token);

        $record = $this->user->findValidToken(
            $tokenHash,
            'verification'
        );

        if (!$record) {
            render('verification_result', [
                'message' => 'This verification link is invalid or expired.'
            ]);
            return;
        }

        $this->user->verifyUser((int)$record['user_id']);
        $this->user->deleteToken((int)$record['id']);

        render('verification_result', [
            'message' => 'Your account is verified. You can now log in.'
        ]);
    }

    public function forgotPassword(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            render('forgot_password');
            return;
        }

        if (!csrf_valid($_POST['csrf_token'] ?? null)) {
            http_response_code(403);
            exit('Invalid request.');
        }

        $email = trim($_POST['email'] ?? '');

        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $user = $this->user->findByEmail($email);

            if ($user && $user['is_verified']) {
                $token = bin2hex(random_bytes(32));
                $tokenHash = hash('sha256', $token);

                $expiresAt = date('Y-m-d H:i:s', time() + 3600);

                $this->user->createToken(
                    (int)$user['id'],
                    $tokenHash,
                    'password_reset',
                    $expiresAt
                );

                $url = $this->buildTokenUrl(
                    '/reset-password',
                    $token
                );

                $this->sendMail(
                    $email,
                    'Reset your Camagru password',
                    "Reset your password using this link:\n\n" . $url
                );
            }
        }

        // Same response whether or not the email exists.
        render('forgot_password_notice');
    }

    public function resetPassword(): void
    {
        $token = $_GET['token'] ?? '';

        if (!is_string($token) || !preg_match('/^[a-f0-9]{64}$/', $token)) {
            render('reset_password', [
                'errors' => ['Invalid reset link.']
            ]);
            return;
        }

        $record = $this->user->findValidToken(
            hash('sha256', $token),
            'password_reset'
        );

        if (!$record) {
            render('reset_password', [
                'errors' => ['This link is invalid or expired.']
            ]);
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            render('reset_password', ['token' => $token]);
            return;
        }

        if (!csrf_valid($_POST['csrf_token'] ?? null)) {
            http_response_code(403);
            exit('Invalid request.');
        }

        $password = $_POST['password'] ?? '';
        $confirmation = $_POST['password_confirmation'] ?? '';

        if (!$this->validatePassword($password, $confirmation)) {
            render('reset_password', [
                'token' => $token,
                'errors' => ['Password does not meet requirements.']
            ]);
            return;
        }

        $this->user->updatePassword(
            (int)$record['user_id'],
            password_hash($password, PASSWORD_DEFAULT)
        );

        $this->user->deleteToken((int)$record['id']);

        render('reset_password_notice');
    }

    public function updateProfile(): void
    {
        require_auth();

        $userId = current_user_id();

        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            render('profile', [
                'user' => $this->user->findById($userId)
            ]);
            return;
        }

        if (!csrf_valid($_POST['csrf_token'] ?? null)) {
            http_response_code(403);
            exit('Invalid request.');
        }

        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');

        $errors = [];

        if (!preg_match('/^[a-zA-Z0-9_-]{3,50}$/', $username)) {
            $errors[] = 'Invalid username.';
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Invalid email.';
        }

        $existingUsername = $this->user->findByUsername($username);
        $existingEmail = $this->user->findByEmail($email);

        if ($existingUsername && (int)$existingUsername['id'] !== $userId) {
            $errors[] = 'Username already taken.';
        }

        if ($existingEmail && (int)$existingEmail['id'] !== $userId) {
            $errors[] = 'Email already taken.';
        }

        if ($errors) {
            render('profile', [
                'errors' => $errors,
                'username' => $username,
                'email' => $email
            ]);
            return;
        }

        $this->user->updateUsername($userId, $username);

        // If the email changes, it should be re-verified.
        $currentUser = $this->user->findById($userId);

        if ($currentUser['email'] !== $email) {
            $this->user->updateEmail($userId, $email);

            // Generate and send a new verification token here.
            // Consider disabling access until re-verification.
        }

        render('profile', [
            'user' => $this->user->findById($userId),
            'success' => 'Profile updated.'
        ]);
    }

    public function changePassword(): void
    {
        require_auth();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            exit;
        }

        if (!csrf_valid($_POST['csrf_token'] ?? null)) {
            http_response_code(403);
            exit('Invalid request.');
        }

        $userId = current_user_id();

        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['password'] ?? '';
        $confirmation = $_POST['password_confirmation'] ?? '';

        $hash = $this->user->findPasswordHashById($userId);

        if (!$hash || !password_verify($currentPassword, $hash)) {
            render('profile', [
                'errors' => ['Current password is incorrect.']
            ]);
            return;
        }

        if (!$this->validatePassword($newPassword, $confirmation)) {
            render('profile', [
                'errors' => ['New password does not meet requirements.']
            ]);
            return;
        }

        $this->user->updatePassword(
            $userId,
            password_hash($newPassword, PASSWORD_DEFAULT)
        );

        session_regenerate_id(true);

        render('profile', [
            'success' => 'Password updated.'
        ]);
    }

    private function validatePassword(
        string $password,
        string $confirmation
    ): bool {
        return strlen($password) >= 12
            && strlen($password) <= 4096
            && $password === $confirmation;
    }

    private function buildTokenUrl(
        string $path,
        string $token
    ): string {
        $baseUrl = rtrim(
            getenv('APP_URL') ?: 'http://localhost:8080',
            '/'
        );

        return $baseUrl . $path . '?token=' . urlencode($token);
    }

    private function sendMail(
        string $to,
        string $subject,
        string $message
    ): bool {
        $from = getenv('MAIL_FROM');

        if (!$from) {
            error_log('MAIL_FROM is not configured.');
            return false;
        }

        $headers = [
            'From: ' . $from,
            'Content-Type: text/plain; charset=UTF-8'
        ];

        return mail($to, $subject, $message, implode("\r\n", $headers));
    }
}
