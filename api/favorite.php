<?php
declare(strict_types=1);
require __DIR__ . '/../bootstrap.php';

/* AJAX endpoint: toggles a favorite and answers in JSON (no page reload). */
header('Content-Type: application/json; charset=utf-8');

function respond(int $status, array $payload): void
{
    http_response_code($status);
    echo json_encode($payload);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(405, ['ok' => false, 'message' => 'Method not allowed.']);
}
if (!Auth::check()) {
    respond(401, ['ok' => false, 'message' => 'Please log in again.']);
}
if (!Session::verifyCsrf($_SERVER['HTTP_X_CSRF_TOKEN'] ?? null)) {
    respond(419, ['ok' => false, 'message' => 'Session expired. Refresh the page.']);
}

$pdo      = Database::connection();
$recipeId = int_param($_POST, 'recipe_id');

if ($recipeId === 0 || (new RecipeRepository($pdo))->find($recipeId, Auth::id()) === null) {
    respond(404, ['ok' => false, 'message' => 'Recipe not found.']);
}

try {
    $result = (new FavoriteRepository($pdo))->toggle(Auth::id(), $recipeId);
    respond(200, ['ok' => true] + $result);
} catch (Throwable $e) {
    error_log((string) $e);
    respond(500, ['ok' => false, 'message' => 'Could not update favorites. Try again.']);
}
