<?php

/**
 * League.Uri (https://uri.thephpleague.com)
 *
 * (c) Ignace Nyamagana Butera <nyamsprod@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

require __DIR__.'/../src/UriString.php';

$components = ['scheme' => 'http', 'host' => 'uri.thephpleague.com', 'path' => '/5.0'];

for ($i = 0; $i < 100000; $i++) {
    League\Uri\UriString::build($components);
}
