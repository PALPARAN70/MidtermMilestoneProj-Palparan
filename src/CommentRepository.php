<?php
declare(strict_types=1);

final class CommentRepository
{
    public const MAX_LENGTH = 1000;

    public function __construct(private PDO $pdo) {}

    public function forRecipe(int $recipeId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT c.id, c.user_id, c.body, c.created_at, c.updated_at, u.name AS author
               FROM comments c
               JOIN users u ON u.id = c.user_id
              WHERE c.recipe_id = ?
           ORDER BY c.created_at ASC, c.id ASC'
        );
        $stmt->execute([$recipeId]);
        return $stmt->fetchAll();
    }

    public function find(int $id): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT c.id, c.recipe_id, c.user_id, c.body, r.title AS recipe_title
               FROM comments c JOIN recipes r ON r.id = c.recipe_id
              WHERE c.id = ?'
        );
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public function create(int $recipeId, int $userId, string $body): void
    {
        $stmt = $this->pdo->prepare('INSERT INTO comments (recipe_id, user_id, body) VALUES (?, ?, ?)');
        $stmt->execute([$recipeId, $userId, $body]);
    }

    public function update(int $id, int $userId, string $body): bool
    {
        $stmt = $this->pdo->prepare(
            'UPDATE comments SET body = ?, updated_at = NOW() WHERE id = ? AND user_id = ?'
        );
        $stmt->execute([$body, $id, $userId]);
        return $stmt->rowCount() === 1;
    }

    public function delete(int $id, int $userId): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM comments WHERE id = ? AND user_id = ?');
        $stmt->execute([$id, $userId]);
        return $stmt->rowCount() === 1;
    }

    /** Server-side rule; returns an error message or null. */
    public static function validate(string $body): ?string
    {
        if ($body === '') {
            return 'Write something before posting your comment.';
        }
        if (mb_strlen($body) < 2 || mb_strlen($body) > self::MAX_LENGTH) {
            return 'Comments must be 2 to ' . self::MAX_LENGTH . ' characters.';
        }
        return null;
    }
}
