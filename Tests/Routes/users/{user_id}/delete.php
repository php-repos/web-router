<?php

use PhpRepos\WebRouter\Business\Attributes\Method;

return
#[Method('DELETE')]
function (int $user_id) {
    return "Delete user $user_id";
};
