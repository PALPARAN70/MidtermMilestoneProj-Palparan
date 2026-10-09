<?php
declare(strict_types=1);
require __DIR__ . '/bootstrap.php';
Auth::requireLogin();
require_post_with_csrf();

$repo    = new CommentRepository(Database::connection());
$id      = int_param($_POST, 'id');
$comment = $id ? $repo->find($id) : null;

if ($comment !== null && $repo->delete($id, Auth::id())) {
    Session::flash('success', 'Comment deleted.');
    redirect('recipe.php?id=' . (int) $comment['recipe_id'] . '#comments');
}
Session::flash('error', 'You can only delete your own comments.');
redirect($comment ? 'recipe.php?id=' . (int) $comment['recipe_id'] : 'index.php');
