<?php

defined('APPLICATION_PATH') || define('APPLICATION_PATH',realpath(dirname(__FILE__) . '/..'));

const DS    = DIRECTORY_SEPARATOR;

require APPLICATION_PATH . DS . 'config' . DS . 'config.php';
require_once APPLICATION_PATH . DS . 'src' . DS . 'controllers' . DS . 'authController.php';

$auth = new AuthController($pdo);

$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'];

switch ($path) {
    case '/':
        render('home');
        break;

    case '/register':
        $auth->register();
        break;

    case '/login':
        $auth->login();
        break;

    case '/logout':
        $auth->logout();
        break;

    case '/verify':
        $auth->verify();
        break;

    case '/forgot-password':
        $auth->forgotPassword();
        break;

    case '/reset-password':
        $auth->resetPassword();
        break;

    case '/profile':
        $auth->updateProfile();
        break;

    case '/change-password':
        $auth->changePassword();
        break;

    default:
        http_response_code(404);
        render('404');
        break;
}
