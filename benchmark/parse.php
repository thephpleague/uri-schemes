<?php

require __DIR__.'/../vendor/autoload.php';

$components = [
    'scheme' => 'https',
    'host' => 'uri.thephpleague.com',
    'path' => '/5.0',
];
for ($i = 0; $i < 100000; $i++) {
    League\Uri\Http::createFromComponents($components);
}
