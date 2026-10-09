<?php
declare(strict_types=1);
require __DIR__ . '/bootstrap.php';
require_post_with_csrf();

Auth::logout();
Session::start();   // fresh, empty session just to carry the goodbye message
Session::flash('success', 'You have logged out. Salamat sa pagbisita!');
redirect('login.php');
