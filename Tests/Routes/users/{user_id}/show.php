<?php

use PhpRepos\WebRouter\Business\Attributes\Method;

return
#[Method('GET')]
function ($user_id) {
    return "Showing user $user_id";
};
