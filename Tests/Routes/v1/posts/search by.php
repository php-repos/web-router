<?php

use PhpRepos\WebRouter\Business\Attributes\Method;

return
    #[Method('GET')]
    function () {
        return 'Search on posts';
    };
