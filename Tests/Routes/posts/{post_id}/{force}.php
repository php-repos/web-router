<?php

return

function ($post_id, bool $force) {
    $force = $force ? 'true' : 'false';
    return "Delete post ID $post_id with force as $force";
};
