<?php
declare(strict_types=1);

final class CategoryRepository
{
    public function __construct(private PDO $pdo) {}

    /**
     * Categories with how many recipes each one has.
     * Pass a member's id to count only that member's recipes ("My recipes" page).
     */
    public function all(int $authorId = 0): array
    {
        $sql = 'SELECT c.id, c.name, COUNT(r.id) AS recipe_count
                  FROM categories c
             LEFT JOIN recipes r ON r.category_id = c.id';
        if ($authorId > 0) {
            $sql .= ' AND r.user_id = :author';
        }
        $sql .= ' GROUP BY c.id, c.name ORDER BY c.id';

        $stmt = $this->pdo->prepare($sql);
        if ($authorId > 0) {
            $stmt->bindValue(':author', $authorId, PDO::PARAM_INT);
        }
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function exists(int $id): bool
    {
        $stmt = $this->pdo->prepare('SELECT 1 FROM categories WHERE id = ?');
        $stmt->execute([$id]);
        return (bool) $stmt->fetchColumn();
    }
}
