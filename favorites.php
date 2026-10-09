<?php
declare(strict_types=1);
require __DIR__ . '/bootstrap.php';
Auth::requireLogin();

$list = (new FavoriteRepository(Database::connection()))->forUser(Auth::id());

$pageTitle = 'Favorites';
$activeNav = 'favorites';
require __DIR__ . '/partials/header.php';
?>
<section class="hero hero-small">
    <div>
        <p class="eyebrow">Paborito</p>
        <h1>Your saved recipes</h1>
        <p class="lede">Tap the heart on any recipe to save it here. Tap it again to remove it.</p>
    </div>
</section>

<div class="empty" id="favorites-empty" <?= $list ? 'hidden' : '' ?>>
    <p>No favorites yet. Browse the recipe box and tap a heart.</p>
    <a class="btn" href="index.php">Browse recipes</a>
</div>

<div class="recipe-grid" data-remove-unfavorited>
    <?php foreach ($list as $r): require __DIR__ . '/partials/recipe_card.php'; endforeach; ?>
</div>
<?php require __DIR__ . '/partials/footer.php'; ?>
