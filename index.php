<?php
declare(strict_types=1);
require __DIR__ . '/bootstrap.php';
Auth::requireLogin();

$pdo        = Database::connection();
$recipes    = new RecipeRepository($pdo);
$filter     = RecipeFilter::fromQuery($_GET, Auth::id());
// On "My recipes" the tab counts only include the member's own recipes
$categories = (new CategoryRepository($pdo))->all($filter->authorId);

$perPage = 9;
$total   = $recipes->count($filter);
$pages   = max(1, (int) ceil($total / $perPage));
$page    = min(max(1, int_param($_GET, 'page')), $pages);
$list    = $recipes->search(Auth::id(), $filter, $perPage, ($page - 1) * $perPage);

$isMine     = $filter->authorId > 0;
$isFiltered = $filter->keyword !== '' || $filter->categoryId > 0 || $isMine;

$activeCategory = '';
foreach ($categories as $c) {
    if ((int) $c['id'] === $filter->categoryId) {
        $activeCategory = $c['name'];
    }
}

$pageTitle = $isMine ? 'My recipes' : 'Recipes';
$activeNav = $isMine ? 'mine' : 'recipes';
require __DIR__ . '/partials/header.php';
?>
<section class="hero">
    <div>
        <p class="eyebrow"><?= $isMine ? 'Your recipe box' : 'Fresh from the barangay' ?></p>
        <h1><?= $isMine ? 'Recipes you shared' : 'Ano\'ng <em>ulam</em> natin today?' ?></h1>
        <p class="lede">Browse what your neighbors are cooking, newest first. Search by dish or by what's in your kitchen — try “kangkong” or “gata”.</p>
    </div>

    <!-- Own feature: random recipe picker -->
    <form class="surprise" action="random.php" method="get">
        <input type="hidden" name="category" value="<?= (int) $filter->categoryId ?>">
        <button class="btn btn-gold" type="submit">
            <span aria-hidden="true">🎲</span> Ano'ng ulam? Pick for me
        </button>
        <small><?= $activeCategory !== '' ? 'From ' . e($activeCategory) : 'From any category' ?></small>
    </form>
</section>

<form class="search-bar" method="get" action="index.php" data-validate novalidate role="search">
    <?php if ($isMine): ?><input type="hidden" name="mine" value="1"><?php endif; ?>
    <div class="field grow">
        <label for="q" class="sr-only">Search recipes</label>
        <input id="q" name="q" type="search" placeholder="Search a dish or ingredient…"
               value="<?= e($filter->keyword) ?>" maxlength="80">
        <p class="error"></p>
    </div>
    <div class="field">
        <label for="category" class="sr-only">Category</label>
        <select id="category" name="category">
            <option value="">All categories</option>
            <?php foreach ($categories as $c): ?>
                <option value="<?= (int) $c['id'] ?>" <?= (int) $c['id'] === $filter->categoryId ? 'selected' : '' ?>>
                    <?= e($c['name']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    <button class="btn" type="submit">Search</button>
    <?php if ($isFiltered): ?>
        <a class="btn btn-ghost" href="index.php<?= $isMine ? '?mine=1' : '' ?>">Clear</a>
    <?php endif; ?>
</form>

<nav class="chips" aria-label="Categories">
    <a class="chip-link <?= $filter->categoryId === 0 ? 'is-active' : '' ?>"
       href="index.php<?= e(query_with(['category' => null, 'page' => null])) ?>">All</a>
    <?php foreach ($categories as $c): ?>
        <a class="chip-link <?= (int) $c['id'] === $filter->categoryId ? 'is-active' : '' ?>"
           href="index.php<?= e(query_with(['category' => (int) $c['id'], 'page' => null])) ?>">
            <?= e($c['name']) ?> <span><?= (int) $c['recipe_count'] ?></span>
        </a>
    <?php endforeach; ?>
</nav>

<p class="results-note">
    <?= $total ?> recipe<?= $total === 1 ? '' : 's' ?>
    <?php if ($filter->keyword !== ''): ?> matching “<?= e($filter->keyword) ?>”<?php endif; ?>
    <?php if ($activeCategory !== ''): ?> in <?= e($activeCategory) ?><?php endif; ?>
</p>

<?php if (!$list): ?>
    <div class="empty">
        <p><?= $isFiltered ? 'Walang nahanap. No recipes match that search yet.' : 'The kusina is quiet. Be the first to share a recipe!' ?></p>
        <a class="btn" href="recipe_form.php">Share a recipe</a>
    </div>
<?php else: ?>
    <div class="recipe-grid">
        <?php foreach ($list as $r): require __DIR__ . '/partials/recipe_card.php'; endforeach; ?>
    </div>
<?php endif; ?>

<?php if ($pages > 1): ?>
    <nav class="pager" aria-label="Pages">
        <?php if ($page > 1): ?>
            <a href="index.php<?= e(query_with(['page' => $page - 1])) ?>">← Newer</a>
        <?php endif; ?>
        <span>Page <?= $page ?> of <?= $pages ?></span>
        <?php if ($page < $pages): ?>
            <a href="index.php<?= e(query_with(['page' => $page + 1])) ?>">Older →</a>
        <?php endif; ?>
    </nav>
<?php endif; ?>

<?php require __DIR__ . '/partials/footer.php'; ?>
