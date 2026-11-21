<?php

function get($name, $def='')
{
    return isset($_REQUEST[$name]) ? $_REQUEST[$name] : $def;
}

function error_class(array $errors, string $field): string
{
    return isset($errors[$field]) ? 'error' : '';
}

function render(string $view, array $data = []): void
{
    $viewPath = __DIR__ . '/../view/' . $view . '.phtml';
    $layoutPath = __DIR__ . '/../inc/layout.phtml';

    // Only allow valid view filenames.
    if (!preg_match('/^[a-zA-Z0-9_-]+$/', $view)) {
        http_response_code(400);
        exit('Invalid view name.');
    }

    // Check that the view exists.
    if (!is_file($viewPath)) {
        http_response_code(404);
        exit('View not found.');
    }

    if (!is_file($layoutPath)) {
        http_response_code(500);
        exit('Layout not found.');
    }

    // Make controller data available to the view.
    extract($data, EXTR_SKIP);

    // The layout needs to know which view to include.
    $content = $viewPath;

    require $layoutPath;
}
