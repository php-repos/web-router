<?php


use PhpRepos\Web\Attributes\Method;

return
#[Method('GET')]
function () {
    return 'List of users';
};
