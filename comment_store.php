<?php
declare(strict_types=1);
require __DIR__ . '/bootstrap.php';
Auth::requireLogin();
require_post_with_csrf();

$pdo      = Database::connection();
$recipeId = int_param($_POST, 'recipe_id');
$body     = str_param($_POST, 'body');

if ((new RecipeRepository($pdo))->find($recipeId, Auth::id()) === null) {
    abort(404, 'That recipe could not be found.');
}

$error = CommentRepository::validate($body);
if ($error !== null) {
    Session::set('_old_comment', $body);
    Session::set('_comment_error', $error);
    redirect("recipe.php?id={$recipeId}#comments");
}

(new CommentRepository($pdo))->create($recipeId, Auth::id(), $body);
Session::flash('success', 'Comment posted. Salamat!');
redirect("recipe.php?id={$recipeId}#comments");
