<?php
declare(strict_types=1);
require __DIR__ . '/bootstrap.php';
Auth::requireLogin();

$repo    = new CommentRepository(Database::connection());
$id      = int_param($_SERVER['REQUEST_METHOD'] === 'POST' ? $_POST : $_GET, 'id');
$comment = $id ? $repo->find($id) : null;

if ($comment === null) {
    abort(404, 'That comment could not be found.');
}
if ((int) $comment['user_id'] !== Auth::id()) {
    abort(403, 'You can only edit your own comments.');
}

$body  = $comment['body'];
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_post_with_csrf();
    $body  = str_param($_POST, 'body');
    $error = CommentRepository::validate($body);

    if ($error === null) {
        if ($body === $comment['body']) {
            Session::flash('info', 'Nothing changed.');
        } else {
            $repo->update($id, Auth::id(), $body);
            Session::flash('success', 'Comment updated.');
        }
        redirect('recipe.php?id=' . (int) $comment['recipe_id'] . '#comment-' . $id);
    }
}

$pageTitle = 'Edit comment';
require __DIR__ . '/partials/header.php';
?>
<section class="narrow">
    <p class="eyebrow">Edit comment</p>
    <h1>On “<?= e($comment['recipe_title']) ?>”</h1>
    <form class="panel" method="post" action="comment_edit.php" data-validate novalidate>
        <?= csrf_field() ?>
        <input type="hidden" name="id" value="<?= $id ?>">
        <div class="field">
            <label for="body">Your comment</label>
            <textarea id="body" name="body" rows="5" required minlength="2"
                      maxlength="<?= CommentRepository::MAX_LENGTH ?>" data-counter><?= e($body) ?></textarea>
            <p class="error"><?= e($error ?? '') ?></p>
        </div>
        <div class="form-actions">
            <button class="btn" type="submit">Save changes</button>
            <a class="btn btn-ghost" href="recipe.php?id=<?= (int) $comment['recipe_id'] ?>#comment-<?= $id ?>">Cancel</a>
        </div>
    </form>
</section>
<?php require __DIR__ . '/partials/footer.php'; ?>
