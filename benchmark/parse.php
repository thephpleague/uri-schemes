<?php

require __DIR__ . '/../vendor/autoload.php';

for ($i = 0; $i < 100000; $i++) {
    League\Uri\Http::createFromString('http://amphp.org/amp');
}
