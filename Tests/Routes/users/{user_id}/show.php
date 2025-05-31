<?php

use PhpRepos\Web\Attributes\Method;

return
#[Method('GET')]
function ($user_id) {
    return "Showing user $user_id";
};
