<?php

require_once dirname(__FILE__) . '/includes/init.php';

if (isLoggedIn()) {
    redirect('/dashboard.php');
}

redirect('/login.php');
