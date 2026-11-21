<?php

function session_flash(...$keys): array
{
    $data = [];
    foreach ($keys as $key) {
        if (isset($_SESSION[$key])) {
            $data[] = $_SESSION[$key];
            unset($_SESSION[$key]);
        } else {
            $data[] = [];
        }
    }
    return $data;
}

// <div class="alert alert-error" role="alert">
//     Invalid username or password.
// </div>

// <div class="alert alert-success" role="status">
//     Your profile has been updated.
// </div>

// <div class="alert alert-info" role="status">
//     Please check your email to verify your account.
// </div>