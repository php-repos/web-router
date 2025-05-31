<?php

use PhpRepos\Web\Attributes\Method;

return
#[Method('DELETE')]
function (int $user_id) {
    return "Delete user $user_id";
};
