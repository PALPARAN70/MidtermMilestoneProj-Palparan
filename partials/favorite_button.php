<?php
/** @var array $r  a recipe row with id, is_favorite, favorite_count */
$saved = (bool) $r['is_favorite'];
?>
<button type="button" class="fav-btn" data-recipe="<?= (int) $r['id'] ?>"
        aria-pressed="<?= $saved ? 'true' : 'false' ?>"
        title="<?= $saved ? 'Remove from favorites' : 'Save to favorites' ?>">
    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 21s-7.5-4.6-10-9.3C.3 8.5 2.2 4.5 6 4.5c2.2 0 3.6 1.2 4.4 2.4.5.7 1 .7 1.5 0 .9-1.2 2.3-2.4 4.5-2.4 3.8 0 5.7 4 4 7.2C19.5 16.4 12 21 12 21z"/></svg>
    <span class="fav-count"><?= (int) $r['favorite_count'] ?></span>
    <span class="sr-only fav-label"><?= $saved ? 'Saved' : 'Save' ?></span>
</button>
