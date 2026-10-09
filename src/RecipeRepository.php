<?php
declare(strict_types=1);

final class RecipeRepository
{
    /** Columns shared by list and detail queries. :viewer = logged-in member. */
    private const SELECT = '
        SELECT r.id, r.user_id, r.category_id, r.title, r.description, r.steps,
               r.servings, r.cook_minutes, r.created_at, r.updated_at,
               u.name AS author, c.name AS category,
               (SELECT COUNT(*) FROM comments cm WHERE cm.recipe_id = r.id)  AS comment_count,
               (SELECT COUNT(*) FROM favorites f  WHERE f.recipe_id = r.id)  AS favorite_count,
               EXISTS (SELECT 1 FROM favorites f2
                        WHERE f2.recipe_id = r.id AND f2.user_id = :viewer) AS is_favorite
          FROM recipes r
          JOIN users u      ON u.id = r.user_id
          JOIN categories c ON c.id = r.category_id';

    public function __construct(private PDO $pdo) {}

    /**
     * Latest-first listing with optional keyword, category and author filters.
     * The keyword matches the title, description, or any ingredient name.
     */
    public function search(int $viewerId, RecipeFilter $filter, int $limit, int $offset): array
    {
        [$where, $params] = $this->buildWhere($filter);
        $sql = self::SELECT . $where . ' ORDER BY r.created_at DESC, r.id DESC LIMIT :limit OFFSET :offset';

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':viewer', $viewerId, PDO::PARAM_INT);
        foreach ($params as $name => [$value, $type]) {
            $stmt->bindValue($name, $value, $type);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function count(RecipeFilter $filter): int
    {
        [$where, $params] = $this->buildWhere($filter);
        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM recipes r' . $where);
        foreach ($params as $name => [$value, $type]) {
            $stmt->bindValue($name, $value, $type);
        }
        $stmt->execute();
        return (int) $stmt->fetchColumn();
    }

    /** @return array{0:string,1:array<string,array{0:mixed,1:int}>} */
    private function buildWhere(RecipeFilter $filter): array
    {
        $clauses = [];
        $params  = [];

        if ($filter->keyword !== '') {
            // Escape LIKE wildcards so "%" or "_" typed by a member is treated literally
            $like = '%' . addcslashes($filter->keyword, '%_\\') . '%';
            $clauses[] = '(r.title LIKE :kw1 OR r.description LIKE :kw2
                           OR EXISTS (SELECT 1 FROM ingredients i
                                       WHERE i.recipe_id = r.id AND i.name LIKE :kw3))';
            foreach ([':kw1', ':kw2', ':kw3'] as $name) {
                $params[$name] = [$like, PDO::PARAM_STR];
            }
        }
        if ($filter->categoryId > 0) {
            $clauses[] = 'r.category_id = :category';
            $params[':category'] = [$filter->categoryId, PDO::PARAM_INT];
        }
        if ($filter->authorId > 0) {
            $clauses[] = 'r.user_id = :author';
            $params[':author'] = [$filter->authorId, PDO::PARAM_INT];
        }

        $where = $clauses ? ' WHERE ' . implode(' AND ', $clauses) : '';
        return [$where, $params];
    }

    public function find(int $id, int $viewerId): ?array
    {
        $stmt = $this->pdo->prepare(self::SELECT . ' WHERE r.id = :id');
        $stmt->bindValue(':viewer', $viewerId, PDO::PARAM_INT);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch() ?: null;
    }

    /** @return list<array{quantity:?string,name:string}> */
    public function ingredients(int $recipeId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT quantity, name FROM ingredients WHERE recipe_id = ? ORDER BY position'
        );
        $stmt->execute([$recipeId]);
        return $stmt->fetchAll();
    }

    /** Recipe + all its ingredients are saved together, or not at all. */
    public function create(int $userId, RecipeInput $in): int
    {
        $pdo = $this->pdo;

        try {
            $pdo->beginTransaction();

            // Step 1: insert the recipe
            $recipe = $pdo->prepare(
                'INSERT INTO recipes (user_id, category_id, title, description, steps, servings, cook_minutes)
                 VALUES (:user_id, :category_id, :title, :description, :steps, :servings, :cook_minutes)'
            );
            $recipe->execute([
                'user_id'      => $userId,
                'category_id'  => $in->categoryId,
                'title'        => $in->title,
                'description'  => $in->description,
                'steps'        => $in->steps,
                'servings'     => $in->servings,
                'cook_minutes' => $in->cookMinutes,
            ]);

            // Grab the new recipe's id for the foreign key
            $recipeId = (int) $pdo->lastInsertId();

            // Step 2: insert the ingredients that belong to that recipe
            $this->insertIngredients($recipeId, $in->ingredients);

            $pdo->commit();
            return $recipeId;
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    /** Replaces the recipe fields and its ingredient list in one transaction. */
    public function update(int $recipeId, int $userId, RecipeInput $in): bool
    {
        $pdo = $this->pdo;

        try {
            $pdo->beginTransaction();

            // Step 1: lock the row and confirm the recipe belongs to this member
            $owner = $pdo->prepare(
                'SELECT id FROM recipes WHERE id = :id AND user_id = :user_id FOR UPDATE'
            );
            $owner->execute(['id' => $recipeId, 'user_id' => $userId]);
            if ($owner->fetchColumn() === false) {
                $pdo->rollBack();
                return false;
            }

            // Step 2: update the recipe (updated_at is what shows the "Edited" tag)
            $recipe = $pdo->prepare(
                'UPDATE recipes
                    SET category_id = :category_id, title = :title, description = :description,
                        steps = :steps, servings = :servings, cook_minutes = :cook_minutes,
                        updated_at = NOW()
                  WHERE id = :id AND user_id = :user_id'
            );
            $recipe->execute([
                'category_id'  => $in->categoryId,
                'title'        => $in->title,
                'description'  => $in->description,
                'steps'        => $in->steps,
                'servings'     => $in->servings,
                'cook_minutes' => $in->cookMinutes,
                'id'           => $recipeId,
                'user_id'      => $userId,
            ]);

            // Step 3: replace the ingredient list
            $remove = $pdo->prepare('DELETE FROM ingredients WHERE recipe_id = :recipe_id');
            $remove->execute(['recipe_id' => $recipeId]);
            $this->insertIngredients($recipeId, $in->ingredients);

            $pdo->commit();
            return true;
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    /** Ingredients, comments and favorites go with it (ON DELETE CASCADE). */
    public function delete(int $recipeId, int $userId): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM recipes WHERE id = ? AND user_id = ?');
        $stmt->execute([$recipeId, $userId]);
        return $stmt->rowCount() === 1;
    }

    /** "Ano'ng ulam?" — a random recipe id, optionally within one category. */
    public function randomId(int $categoryId = 0): ?int
    {
        if ($categoryId > 0) {
            $stmt = $this->pdo->prepare('SELECT id FROM recipes WHERE category_id = ? ORDER BY RAND() LIMIT 1');
            $stmt->execute([$categoryId]);
        } else {
            $stmt = $this->pdo->query('SELECT id FROM recipes ORDER BY RAND() LIMIT 1');
        }
        $id = $stmt->fetchColumn();
        return $id === false ? null : (int) $id;
    }

    /** @param list<array{quantity:?string,name:string}> $ingredients */
    private function insertIngredients(int $recipeId, array $ingredients): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO ingredients (recipe_id, position, quantity, name)
             VALUES (:recipe_id, :position, :quantity, :name)'
        );
        foreach (array_values($ingredients) as $i => $row) {
            $stmt->execute([
                'recipe_id' => $recipeId,
                'position'  => $i + 1,
                'quantity'  => $row['quantity'],
                'name'      => $row['name'],
            ]);
        }
    }
}
