<?php

use PhpRepos\Web\Attributes\Method;

return
    #[Method('GET')]
    function () {
        return 'Search on posts';
    };
