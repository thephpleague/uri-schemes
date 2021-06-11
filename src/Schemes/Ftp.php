<?php

/**
 * League.Uri (http://uri.thephpleague.com)
 *
 * @package    League\Uri
 * @subpackage League\Uri\Schemes
 * @author     Ignace Nyamagana Butera <nyamsprod@gmail.com>
 * @license    https://github.com/thephpleague/uri-schemes/blob/master/LICENSE (MIT License)
 * @version    1.2.1
 * @link       https://github.com/thephpleague/uri-schemes
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace League\Uri\Schemes;

use function class_exists;

class_exists(\League\Uri\Ftp::class);
if (!class_exists(Ftp::class)) {
    /**
     * @deprecated use instead {@link League\Uri\Ftp}
     */
    class Ftp
    {
    }
}
