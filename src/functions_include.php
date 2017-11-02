<?php

// Don't redefine the functions if included multiple times.
if (!function_exists('League\Uri\create')) {
    require __DIR__.'/functions.php';
    require __DIR__.'/Schemes/deprecated.php';
}
