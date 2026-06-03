<?php

declare(strict_types=1);

// Clear session
$_SESSION = [];
session_destroy();

// Also delete session cookie
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params['path'], $params['domain'],
        $params['secure'], $params['httponly']
    );
}

set_flash('success', 'Vous avez ete deconnecte.');
redirect_to('connexion');