<?php
declare(strict_types=1);

/** The search/filter options a member picked on the listing page. */
final class RecipeFilter
{
    public function __construct(
        public string $keyword = '',
        public int $categoryId = 0,
        public int $authorId = 0,
    ) {}

    public static function fromQuery(array $query, int $viewerId): self
    {
        $keyword = mb_substr(str_param($query, 'q'), 0, 80);
        $mine    = ($query['mine'] ?? '') === '1';
        return new self($keyword, int_param($query, 'category'), $mine ? $viewerId : 0);
    }
}
