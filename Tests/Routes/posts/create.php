<?php

use PhpRepos\WebRouter\Business\Attributes\Method;

return
#[Method('POST')]
function (string $title, ?string $description, array $image)
{
    $description = $description ?: 'null';
    $image = implode('-', $image);

    return "Post with title $title and description $description and image $image has been created.";
};
