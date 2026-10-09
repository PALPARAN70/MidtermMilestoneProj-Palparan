<?php
declare(strict_types=1);
require __DIR__ . '/bootstrap.php';
Auth::requireLogin();

$pdo     = Database::connection();
$repo    = new RecipeRepository($pdo);
$id      = int_param($_GET, 'id');
$recipe  = $id ? $repo->find($id, Auth::id()) : null;

if ($recipe === null) {
    abort(404, 'That recipe could not be found.');
}

$ingredients = $repo->ingredients($id);
$steps       = RecipeInput::stepsToList($recipe['steps']);
$comments    = (new CommentRepository($pdo))->forRecipe($id);
$isOwner     = (int) $recipe['user_id'] === Auth::id();

// Comment text kept after a failed submit
$oldComment   = (string) Session::get('_old_comment', '');
$commentError = (string) Session::get('_comment_error', '');
unset($_SESSION['_old_comment'], $_SESSION['_comment_error']);

$r = $recipe;   // for the favorite button partial
$pageTitle = $recipe['title'];
require __DIR__ . '/partials/header.php';
?>
<?php if (isset($_GET['picked'])): ?>
    <div class="flash flash-info">🎲 Ito ang ulam mo ngayon! Not feeling it?
        <a href="random.php?category=<?= (int) $recipe['category_id'] ?>">Pick another <?= e($recipe['category']) ?></a>
        or <a href="random.php">anything</a>.</div>
<?php endif; ?>

<article class="recipe">
    <header class="recipe-head">
        <a class="back" href="index.php">← All recipes</a>
        <span class="chip"><?= e($recipe['category']) ?></span>
        <h1><?= e($recipe['title']) ?></h1>
        <p class="lede"><?= e_multiline($recipe['description']) ?></p>

        <div class="recipe-meta">
            <span>Shared by <strong><?= e($recipe['author']) ?></strong></span>
            <span><?= e(format_date($recipe['created_at'])) ?></span>
            <?php if ($recipe['updated_at']): ?>
                <span class="edited" title="Last edited <?= e(format_date($recipe['updated_at'])) ?>">
                    Edited <?= e(format_date($recipe['updated_at'])) ?>
                </span>
            <?php endif; ?>
        </div>

        <div class="recipe-actions">
            <?php require __DIR__ . '/partials/favorite_button.php'; ?>
            <?php if ($isOwner): ?>
                <a class="btn btn-ghost btn-small" href="recipe_form.php?id=<?= $id ?>">Edit recipe</a>
                <form method="post" action="recipe_delete.php" data-confirm="Delete this recipe? Its ingredients, comments and favorites will be removed too.">
                    <?= csrf_field() ?>
                    <input type="hidden" name="id" value="<?= $id ?>">
                    <button class="btn btn-danger btn-small" type="submit">Delete</button>
                </form>
            <?php endif; ?>
        </div>
    </header>

    <div class="recipe-body">
        <aside class="ingredients panel">
            <div class="stat-row">
                <div><strong><?= e(time_label((int) $recipe['cook_minutes'])) ?></strong><span>cooking time</span></div>
                <div><strong><?= (int) $recipe['servings'] ?></strong><span>servings</span></div>
            </div>
            <h2>Sangkap</h2>
            <ul class="ingredient-list">
                <?php foreach ($ingredients as $ing): ?>
                    <li>
                        <label>
                            <input type="checkbox">
                            <span><?php if ($ing['quantity'] !== null): ?><b><?= e($ing['quantity']) ?></b> <?php endif; ?><?= e($ing['name']) ?></span>
                        </label>
                    </li>
                <?php endforeach; ?>
            </ul>
            <p class="hint">Tick off ingredients as you prep.</p>
        </aside>

        <section class="steps">
            <h2>Paraan ng pagluluto</h2>
            <ol class="step-list">
                <?php foreach ($steps as $step): ?>
                    <li><?= e($step) ?></li>
                <?php endforeach; ?>
            </ol>
        </section>
    </div>
</article>

<section class="comments" id="comments">
    <h2>Mga komento <span class="count"><?= count($comments) ?></span></h2>

    <?php if (!$comments): ?>
        <p class="muted">No comments yet. Tried this dish? Tell the cook how it went.</p>
    <?php endif; ?>

    <?php foreach ($comments as $c): ?>
        <div class="comment" id="comment-<?= (int) $c['id'] ?>">
            <p class="comment-meta">
                <strong><?= e($c['author']) ?></strong>
                <?php if ((int) $c['user_id'] === (int) $recipe['user_id']): ?><span class="chip chip-small">Cook</span><?php endif; ?>
                · <?= e(format_date($c['created_at'])) ?>
                <?php if ($c['updated_at']): ?>
                    <span class="edited" title="Edited <?= e(format_date($c['updated_at'])) ?>">edited</span>
                <?php endif; ?>
            </p>
            <p><?= e_multiline($c['body']) ?></p>
            <?php if ((int) $c['user_id'] === Auth::id()): ?>
                <div class="comment-actions">
                    <a class="link" href="comment_edit.php?id=<?= (int) $c['id'] ?>">Edit</a>
                    <form method="post" action="comment_delete.php" data-confirm="Delete your comment?">
                        <?= csrf_field() ?>
                        <input type="hidden" name="id" value="<?= (int) $c['id'] ?>">
                        <button class="link link-danger" type="submit">Delete</button>
                    </form>
                </div>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>

    <form class="panel comment-form" method="post" action="comment_store.php" data-validate novalidate>
        <?= csrf_field() ?>
        <input type="hidden" name="recipe_id" value="<?= $id ?>">
        <div class="field">
            <label for="body">Add a comment</label>
            <textarea id="body" name="body" rows="3" required minlength="2"
                      maxlength="<?= CommentRepository::MAX_LENGTH ?>" data-counter
                      placeholder="Masarap ba? Any tips or substitutions?"><?= e($oldComment) ?></textarea>
            <p class="error"><?= e($commentError) ?></p>
        </div>
        <button class="btn" type="submit">Post comment</button>
    </form>
</section>
<?php require __DIR__ . '/partials/footer.php'; ?>
