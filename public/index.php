<?php

defined('APPLICATION_PATH') || define('APPLICATION_PATH',realpath(dirname(__FILE__) . '/..'));

const DS    = DIRECTORY_SEPARATOR;

require APPLICATION_PATH . DS . 'config' . DS . 'config.php';

$page = get('page', 'home');
$model = $config['MODEL_PATH'] . $page . '.php';
$view = $config['VIEW_PATH'] . $page . '.phtml';
$_404 = $config['VIEW_PATH'] . '404.phtml';

$main_content = $_404;

if(file_exists($view))
{
    $main_content = $view;
}

include $config['INC_PATH']. 'layout.phtml';

?>