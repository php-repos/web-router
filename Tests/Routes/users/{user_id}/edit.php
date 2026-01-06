<?php

use PhpRepos\WebRouter\Business\Attributes\Method;

return
#[Method('PUT'), Method('PATCH')]
function ($user_id) {
    return "Edit user $user_id";
};
