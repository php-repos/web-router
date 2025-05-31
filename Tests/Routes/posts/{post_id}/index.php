<?php

return

function (int $post_id, ?string $title, ?bool $force = false)
{
    $title = $title ?: 'null';
    if ($force) {
        return "Force post $post_id with title $title";
    }

    return "Optional post $post_id with title $title";
};
