<?php
declare(strict_types=1);
require __DIR__ . '/bootstrap.php';
Auth::requireLogin();
require_post_with_csrf();

$id = int_param($_POST, 'id');
$deleted = (new RecipeRepository(Database::connection()))->delete($id, Auth::id());

if ($deleted) {
    Session::flash('success', 'Recipe deleted.');
    redirect('index.php?mine=1');
}
Session::flash('error', 'You can only delete recipes you shared.');
redirect('index.php');
