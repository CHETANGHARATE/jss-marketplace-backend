<?php

namespace App\Contracts;

interface SearchDriverInterface
{
    /**
     * Perform full-text search with dynamic facets and filters.
     */
    public function search(array $params): array;

    /**
     * Perform fast autocomplete query suggestions.
     */
    public function autocomplete(string $query): array;
}
