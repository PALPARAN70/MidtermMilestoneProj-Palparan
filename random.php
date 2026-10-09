<?php
declare(strict_types=1);
require __DIR__ . '/bootstrap.php';
Auth::requireLogin();

// Own feature: "Ano'ng ulam?" picks a random recipe (optionally within a category)
$categoryId = int_param($_GET, 'category');
$id = (new RecipeRepository(Database::connection()))->randomId($categoryId);

if ($id === null) {
    Session::flash('info', 'No recipes to pick from yet — share one first!');
    redirect('index.php');
}
redirect("recipe.php?id={$id}&picked=1");
