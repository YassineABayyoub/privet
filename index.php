<?php
declare(strict_types=1);

require_once __DIR__ . '/app/bootstrap.php';

$usersExist = (int) database()->query('SELECT COUNT(*) FROM users')->fetchColumn() > 0;
if (!$usersExist) {
    redirect('/pages/setup.php');
}

redirect(current_user() === null ? '/pages/login.php' : '/pages/dashboard.php');
