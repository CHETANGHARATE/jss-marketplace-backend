<?php

namespace App\Search;

use App\Contracts\SearchDriverInterface;

class MeilisearchDriver implements SearchDriverInterface
{
    protected DatabaseSearchDriver $fallback;

    public function __construct(DatabaseSearchDriver $fallback)
    {
        $this->fallback = $fallback;
    }

    public function search(array $params): array
    {
        // Fallback to database driver if Meilisearch SDK is not configured
        return $this->fallback->search($params);
    }

    public function autocomplete(string $query): array
    {
        return $this->fallback->autocomplete($query);
    }
}
