<?php
declare(strict_types=1);
require __DIR__ . '/bootstrap.php';
Auth::requireLogin();

$pdo        = Database::connection();
$recipes    = new RecipeRepository($pdo);
$categoryRepo = new CategoryRepository($pdo);
$categories = $categoryRepo->all();

$id      = int_param($_SERVER['REQUEST_METHOD'] === 'POST' ? $_POST : $_GET, 'id');
$editing = $id > 0;
$current = null;

if ($editing) {
    $recipe = $recipes->find($id, Auth::id());
    if ($recipe === null) {
        abort(404, 'That recipe could not be found.');
    }
    if ((int) $recipe['user_id'] !== Auth::id()) {
        abort(403, 'You can only edit recipes you shared.');
    }
    $current = RecipeInput::fromRecipe($recipe, $recipes->ingredients($id));
}

$input  = $current ?? RecipeInput::blank();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_post_with_csrf();
    [$input, $errors] = RecipeInput::fromPost($_POST, $categoryRepo);

    if (!$errors) {
        if ($editing) {
            if ($current !== null && $input->equals($current)) {
                Session::flash('info', 'Nothing changed, so the recipe was left as is.');
            } elseif ($recipes->update($id, Auth::id(), $input)) {
                Session::flash('success', 'Recipe updated.');
            } else {
                abort(403, 'You can only edit recipes you shared.');
            }
        } else {
            $id = $recipes->create(Auth::id(), $input);
            Session::flash('success', 'Your recipe is now in the barangay recipe box!');
        }
        redirect("recipe.php?id={$id}");
    }
}

$rows = $input->ingredients ?: [['quantity' => null, 'name' => '']];

$pageTitle = $editing ? 'Edit recipe' : 'Share a recipe';
require __DIR__ . '/partials/header.php';
?>
<section class="form-head">
    <p class="eyebrow"><?= $editing ? 'Ayusin ang recipe' : 'Ibahagi ang luto mo' ?></p>
    <h1><?= $editing ? 'Edit your recipe' : 'Share a recipe' ?></h1>
    <?php if ($errors): ?>
        <div class="flash flash-error">Please fix the highlighted fields below.</div>
    <?php endif; ?>
</section>

<form class="recipe-form" method="post" action="recipe_form.php" data-validate novalidate>
    <?= csrf_field() ?>
    <?php if ($editing): ?><input type="hidden" name="id" value="<?= $id ?>"><?php endif; ?>

    <div class="panel">
        <div class="field">
            <label for="title">Recipe title</label>
            <input id="title" name="title" type="text" value="<?= e($input->title) ?>"
                   required minlength="3" maxlength="120" placeholder="e.g. Lola Nena's Chicken Tinola">
            <p class="error"><?= e($errors['title'] ?? '') ?></p>
        </div>

        <div class="field">
            <label for="description">Short description</label>
            <textarea id="description" name="description" rows="3" required minlength="10" maxlength="500"
                      data-counter placeholder="What makes this dish special?"><?= e($input->description) ?></textarea>
            <p class="error"><?= e($errors['description'] ?? '') ?></p>
        </div>

        <div class="field-row">
            <div class="field">
                <label for="category_id">Category</label>
                <select id="category_id" name="category_id" required>
                    <option value="">Choose one…</option>
                    <?php foreach ($categories as $c): ?>
                        <option value="<?= (int) $c['id'] ?>" <?= (int) $c['id'] === $input->categoryId ? 'selected' : '' ?>>
                            <?= e($c['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <p class="error"><?= e($errors['category_id'] ?? '') ?></p>
            </div>
            <div class="field">
                <label for="servings">Servings</label>
                <input id="servings" name="servings" type="number" inputmode="numeric"
                       min="1" max="50" step="1" required value="<?= (int) $input->servings ?: '' ?>">
                <p class="error"><?= e($errors['servings'] ?? '') ?></p>
            </div>
            <div class="field">
                <label for="cook_minutes">Cooking time (minutes)</label>
                <input id="cook_minutes" name="cook_minutes" type="number" inputmode="numeric"
                       min="1" max="1440" step="1" required value="<?= (int) $input->cookMinutes ?: '' ?>">
                <p class="error"><?= e($errors['cook_minutes'] ?? '') ?></p>
            </div>
        </div>
    </div>

    <div class="panel">
        <h2>Sangkap <small>(ingredients)</small></h2>
        <p class="hint">One ingredient per row. Amount is optional (e.g. “2 cups”, “1 thumb-sized”).</p>

        <div id="ingredient-rows" class="ingredient-rows" data-max="<?= RecipeInput::MAX_INGREDIENTS ?>">
            <?php foreach ($rows as $row): ?>
                <div class="ingredient-row">
                    <input name="ingredient_qty[]" type="text" maxlength="40" placeholder="Amount"
                           aria-label="Amount" value="<?= e($row['quantity'] ?? '') ?>">
                    <input name="ingredient_name[]" type="text" maxlength="100" placeholder="Ingredient"
                           aria-label="Ingredient" value="<?= e($row['name']) ?>">
                    <button type="button" class="icon-btn" data-remove-row aria-label="Remove ingredient">×</button>
                </div>
            <?php endforeach; ?>
        </div>
        <template id="ingredient-template">
            <div class="ingredient-row">
                <input name="ingredient_qty[]" type="text" maxlength="40" placeholder="Amount" aria-label="Amount">
                <input name="ingredient_name[]" type="text" maxlength="100" placeholder="Ingredient" aria-label="Ingredient">
                <button type="button" class="icon-btn" data-remove-row aria-label="Remove ingredient">×</button>
            </div>
        </template>
        <p class="error" id="ingredients-error"><?= e($errors['ingredients'] ?? '') ?></p>
        <button type="button" class="btn btn-ghost btn-small" id="add-ingredient">+ Add ingredient</button>
    </div>

    <div class="panel">
        <div class="field">
            <label for="steps">Cooking steps</label>
            <p class="hint">Write one step per line — each line becomes a numbered step.</p>
            <textarea id="steps" name="steps" rows="8" required minlength="20" maxlength="8000"
                      placeholder="Sauté garlic, onion and ginger until fragrant.&#10;Add the chicken and cook until lightly browned.&#10;…"><?= e($input->steps) ?></textarea>
            <p class="error"><?= e($errors['steps'] ?? '') ?></p>
        </div>
    </div>

    <div class="form-actions">
        <button class="btn" type="submit"><?= $editing ? 'Save changes' : 'Publish recipe' ?></button>
        <a class="btn btn-ghost" href="<?= $editing ? 'recipe.php?id=' . $id : 'index.php' ?>">Cancel</a>
    </div>
</form>
<?php require __DIR__ . '/partials/footer.php'; ?>
