<?php

use PhpRepos\Web\Attributes\Method;

return
#[Method('PUT'), Method('PATCH')]
function ($user_id) {
    return "Edit user $user_id";
};
