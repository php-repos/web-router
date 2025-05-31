<?php

namespace PhpRepos\Web;

use PhpRepos\Datatype\Map;

class Router extends Map
{
    public function handle(string $url_pattern, callable $handler): static
    {
        $this->put($url_pattern, $handler);

        return $this;
    }

    public function sort_key(callable $callback): static
    {
        $routes = $this->items;
        usort($routes, fn (array $pair1, array $pair2) => $callback($pair1['key'], $pair2['key']));
        $this->items = array_values($routes);

        return $this;
    }
}
