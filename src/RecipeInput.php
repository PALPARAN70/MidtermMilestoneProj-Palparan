<?php
declare(strict_types=1);

/**
 * A recipe as submitted by the form, cleaned and validated.
 * Validation rules here are mirrored on the browser side in app.js / HTML attributes.
 */
final class RecipeInput
{
    public const MAX_INGREDIENTS = 40;

    /** @param list<array{quantity:?string,name:string}> $ingredients */
    public function __construct(
        public string $title,
        public string $description,
        public int $categoryId,
        public int $servings,
        public int $cookMinutes,
        public string $steps,
        public array $ingredients,
    ) {}

    /** Builds the input from $_POST. Returns [RecipeInput, errors]. */
    public static function fromPost(array $post, CategoryRepository $categories): array
    {
        $title       = str_param($post, 'title');
        $description = str_param($post, 'description');
        $steps       = self::normaliseSteps(str_param($post, 'steps'));
        $categoryId  = int_param($post, 'category_id');
        $servingsRaw = str_param($post, 'servings');
        $minutesRaw  = str_param($post, 'cook_minutes');

        $quantities = is_array($post['ingredient_qty'] ?? null) ? $post['ingredient_qty'] : [];
        $names      = is_array($post['ingredient_name'] ?? null) ? $post['ingredient_name'] : [];

        $v = new Validator();
        $v->required('title', $title, 'Title')
          ->length('title', $title, 3, 120, 'Title')
          ->required('description', $description, 'Description')
          ->length('description', $description, 10, 500, 'Description')
          ->intBetween('servings', $servingsRaw, 1, 50, 'Servings')
          ->intBetween('cook_minutes', $minutesRaw, 1, 1440, 'Cooking time')
          ->required('steps', $steps, 'Cooking steps')
          ->length('steps', $steps, 20, 8000, 'Cooking steps');

        if ($categoryId === 0 || !$categories->exists($categoryId)) {
            $v->add('category_id', 'Choose a category from the list.');
        }

        // Ingredients: skip fully blank rows, validate the rest
        $ingredients = [];
        $count = max(count($quantities), count($names));
        for ($i = 0; $i < $count; $i++) {
            $qty  = is_string($quantities[$i] ?? null) ? trim($quantities[$i]) : '';
            $name = is_string($names[$i] ?? null) ? trim($names[$i]) : '';
            if ($qty === '' && $name === '') {
                continue;
            }
            $row = $i + 1;
            if ($name === '') {
                $v->add('ingredients', "Ingredient row {$row} has an amount but no ingredient name.");
            } elseif (mb_strlen($name) > 100) {
                $v->add('ingredients', "Ingredient row {$row}: the name must be 100 characters or fewer.");
            } elseif (mb_strlen($qty) > 40) {
                $v->add('ingredients', "Ingredient row {$row}: the amount must be 40 characters or fewer.");
            }
            $ingredients[] = ['quantity' => $qty === '' ? null : $qty, 'name' => $name];
        }
        if ($ingredients === []) {
            $v->add('ingredients', 'Add at least one ingredient.');
        } elseif (count($ingredients) > self::MAX_INGREDIENTS) {
            $v->add('ingredients', 'A recipe can have at most ' . self::MAX_INGREDIENTS . ' ingredients.');
        }

        $input = new self(
            $title, $description, $categoryId,
            (int) $servingsRaw, (int) $minutesRaw, $steps, $ingredients
        );
        return [$input, $v->errors()];
    }

    /** Values to put back in the form (from the DB when editing). */
    public static function fromRecipe(array $recipe, array $ingredients): self
    {
        return new self(
            $recipe['title'], $recipe['description'], (int) $recipe['category_id'],
            (int) $recipe['servings'], (int) $recipe['cook_minutes'], $recipe['steps'],
            array_map(static fn ($r) => ['quantity' => $r['quantity'], 'name' => $r['name']], $ingredients)
        );
    }

    public static function blank(): self
    {
        return new self('', '', 0, 4, 30, '', [['quantity' => null, 'name' => '']]);
    }

    public function equals(self $other): bool
    {
        return $this->title === $other->title
            && $this->description === $other->description
            && $this->categoryId === $other->categoryId
            && $this->servings === $other->servings
            && $this->cookMinutes === $other->cookMinutes
            && $this->steps === $other->steps
            && $this->ingredients == $other->ingredients;
    }

    /** One step per line, no blank lines, consistent \n line endings. */
    private static function normaliseSteps(string $steps): string
    {
        $lines = preg_split('/\R/u', $steps) ?: [];
        $lines = array_filter(array_map('trim', $lines), static fn ($l) => $l !== '');
        return implode("\n", $lines);
    }

    /** @return list<string> */
    public static function stepsToList(string $steps): array
    {
        return array_values(array_filter(array_map('trim', explode("\n", $steps)), static fn ($l) => $l !== ''));
    }
}
