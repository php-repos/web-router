<?php

use PhpRepos\Web\Attributes\Method;

return
    #[Method('GET')]
    function ($post_id) {
        return "Showing post $post_id";
    };
