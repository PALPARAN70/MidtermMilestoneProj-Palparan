<?php /** @var array $r */ ?>
<article class="recipe-card" data-card="<?= (int) $r['id'] ?>">
    <div class="card-top">
        <span class="chip"><?= e($r['category']) ?></span>
        <?php require __DIR__ . '/favorite_button.php'; ?>
    </div>
    <h3><a href="recipe.php?id=<?= (int) $r['id'] ?>"><?= e($r['title']) ?></a></h3>
    <p class="card-desc"><?= e(mb_strimwidth($r['description'], 0, 140, '…')) ?></p>
    <ul class="card-meta">
        <li><?= e(time_label((int) $r['cook_minutes'])) ?></li>
        <li><?= (int) $r['servings'] ?> servings</li>
        <li><?= (int) $r['comment_count'] ?> comment<?= (int) $r['comment_count'] === 1 ? '' : 's' ?></li>
    </ul>
    <p class="byline">
        by <?= e($r['author']) ?> · <?= e(date('M j, Y', strtotime($r['created_at']))) ?>
        <?php if ($r['updated_at']): ?><span class="edited">edited</span><?php endif; ?>
    </p>
</article>
