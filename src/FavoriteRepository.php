<?php
declare(strict_types=1);

final class FavoriteRepository
{
    public function __construct(private PDO $pdo) {}

    /**
     * Saves or un-saves a recipe. The check and the write happen inside one
     * transaction (row lock) so double-clicks can't leave the data inconsistent.
     *
     * @return array{favorited:bool,count:int}
     */
    public function toggle(int $userId, int $recipeId): array
    {
        $pdo = $this->pdo;

        try {
            $pdo->beginTransaction();

            // Step 1: is it already saved? (row lock stops double-click races)
            $check = $pdo->prepare(
                'SELECT 1 FROM favorites WHERE user_id = :user_id AND recipe_id = :recipe_id FOR UPDATE'
            );
            $check->execute(['user_id' => $userId, 'recipe_id' => $recipeId]);
            $alreadySaved = (bool) $check->fetchColumn();

            // Step 2: remove it if saved, otherwise save it
            if ($alreadySaved) {
                $write = $pdo->prepare(
                    'DELETE FROM favorites WHERE user_id = :user_id AND recipe_id = :recipe_id'
                );
            } else {
                $write = $pdo->prepare(
                    'INSERT INTO favorites (user_id, recipe_id) VALUES (:user_id, :recipe_id)'
                );
            }
            $write->execute(['user_id' => $userId, 'recipe_id' => $recipeId]);

            // Step 3: read the new total for the heart counter
            $total = $pdo->prepare('SELECT COUNT(*) FROM favorites WHERE recipe_id = :recipe_id');
            $total->execute(['recipe_id' => $recipeId]);
            $count = (int) $total->fetchColumn();

            $pdo->commit();
            return ['favorited' => !$alreadySaved, 'count' => $count];
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    /** A member's saved recipes, most recently saved first. */
    public function forUser(int $userId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT r.id, r.user_id, r.title, r.description, r.servings, r.cook_minutes,
                    r.created_at, r.updated_at, u.name AS author, c.name AS category,
                    (SELECT COUNT(*) FROM comments cm WHERE cm.recipe_id = r.id) AS comment_count,
                    (SELECT COUNT(*) FROM favorites f2 WHERE f2.recipe_id = r.id) AS favorite_count,
                    1 AS is_favorite, f.created_at AS saved_at
               FROM favorites f
               JOIN recipes r    ON r.id = f.recipe_id
               JOIN users u      ON u.id = r.user_id
               JOIN categories c ON c.id = r.category_id
              WHERE f.user_id = ?
           ORDER BY f.created_at DESC'
        );
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }
}
