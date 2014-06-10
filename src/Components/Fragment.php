<?php
/**
* This file is part of the League.url library
*
* @license http://opensource.org/licenses/MIT
* @link https://github.com/thephpleague/url/
* @version 3.0.0
* @package League.url
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/
namespace League\Url\Components;

/**
 *  A class to manipulate URL Fragment component
 *
 *  @package League.url
 */
class Fragment extends AbstractComponent
{
    /**
     * {@inheritdoc}
     */
    public function __toString()
    {
        $value = parent::__toString();

        return rawurlencode($value);
    }

    /**
     * {@inheritdoc}
     */
    public function getUriComponent()
    {
        $value = $this->__toString();
        if ('' != $value) {
            $value = '#'.$value;
        }

        return $value;
    }
}