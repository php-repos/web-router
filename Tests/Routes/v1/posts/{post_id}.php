<?php

use PhpRepos\WebRouter\Business\Attributes\Method;

return
    #[Method('GET')]
    function ($post_id) {
        return "Showing post $post_id";
    };
