<?php
/** @var string|null $pageTitle */
/** @var string|null $activeNav */
$activeNav = $activeNav ?? '';
$navLink = static function (string $href, string $label, string $key) use ($activeNav): string {
    $current = $activeNav === $key ? ' aria-current="page"' : '';
    return '<a href="' . e($href) . '"' . $current . '>' . e($label) . '</a>';
};
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="<?= e(Session::csrfToken()) ?>">
    <title><?= e($pageTitle ?? 'Recipes') ?> ·Kusina ni Rojan James Asjali</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,500;9..144,700;9..144,800&family=Work+Sans:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <script src="assets/js/app.js" defer></script>
</head>
<body>
<header class="site-header">
    <div class="wrap header-inner">
        <a class="brand" href="index.php">
            <span class="brand-mark" aria-hidden="true"></span>
            <span>Kusina ni Rojan James Asjali</span>
        </a>

        <?php if (Auth::check()): ?>
            <button class="nav-toggle" type="button" aria-expanded="false" aria-controls="site-nav">
                <span class="sr-only">Menu</span><span class="bars" aria-hidden="true"></span>
            </button>
            <nav class="site-nav" id="site-nav">
                <?= $navLink('index.php', 'Recipes', 'recipes') ?>
                <?= $navLink('index.php?mine=1', 'My recipes', 'mine') ?>
                <?= $navLink('favorites.php', 'Favorites', 'favorites') ?>
                <a class="btn btn-small" href="recipe_form.php">+ Share a recipe</a>
                <form action="logout.php" method="post" class="logout">
                    <?= csrf_field() ?>
                    <button type="submit" class="link">Log out (<?= e(Auth::name()) ?>)</button>
                </form>
            </nav>
        <?php else: ?>
            <nav class="site-nav site-nav-guest">
                <?= $navLink('login.php', 'Log in', 'login') ?>
                <a class="btn btn-small" href="register.php">Join</a>
            </nav>
        <?php endif; ?>
    </div>
</header>

<main class="wrap main">
<?php foreach (Session::pullFlashes() as $flash): ?>
    <div class="flash flash-<?= e($flash['type']) ?>" role="status"><?= e($flash['message']) ?></div>
<?php endforeach; ?>
