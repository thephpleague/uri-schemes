<?php

/**
 * League.Uri (http://uri.thephpleague.com).
 *
 * @package    League\Uri
 * @subpackage League\Uri\Schemes
 * @author     Ignace Nyamagana Butera <nyamsprod@gmail.com>
 * @license    https://github.com/thephpleague/uri-components/blob/master/LICENSE (MIT License)
 * @version    2.0.0
 * @link       https://github.com/thephpleague/uri-schemes
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace League\Uri;

use finfo;
use League\Uri\Exception\InvalidUri;
use League\Uri\Exception\MalformedUri;

final class Data extends Uri
{
    /**
     * {@inheritdoc}
     */
    protected static $supported_schemes = [
        'data' => null,
    ];

    /**
     * Create a new instance from a file path.
     *
     * @param string $path the file path
     *
     * @return self
     */
    public static function createFromPath(string $path)
    {
        $raw = @file_get_contents($path);
        if (false === $raw) {
            throw new InvalidUri(sprintf('The file `%s` does not exist or is not readable', $path));
        }

        $mimetype = str_replace(' ', '', (new finfo(FILEINFO_MIME))->file($path));

        return new self(
            'data',
            null,
            null,
            null,
            null,
            $mimetype.';base64,'.base64_encode($raw)
        );
    }

    /**
     * Tell whether the Data URI is in valid state.
     *
     * A valid Data URI:
     *
     * <ul>
     * <li>scheme is 'data'
     * <li>only contains a scheme and a path component
     * </ul>
     *
     * @see https://tools.ietf.org/html/rfc2397#section-3
     */
    protected function isValidUri(): bool
    {
        return 'data' === $this->scheme
            && null === $this->authority
            && null === $this->query
            && null === $this->fragment;
    }

    /**
     * Filter the Path component.
     *
     * @see https://tools.ietf.org/html/rfc2397
     *
     * @throws InvalidUri If the path is not compliant with RFC2397
     */
    protected function formatPath(string $path): string
    {
        if ('' == $path) {
            return 'text/plain;charset=us-ascii,';
        }

        if (false === mb_detect_encoding($path, 'US-ASCII', true) || false === strpos($path, ',')) {
            throw new MalformedUri(sprintf('The path `%s` is invalid according to RFC2937', $path));
        }

        $parts = explode(',', $path, 2) + [1 => null];
        $mediatype = explode(';', (string) $parts[0], 2) + [1 => null];
        $data = (string) $parts[1];
        $mimetype = $mediatype[0];
        if (null === $mimetype || '' === $mimetype) {
            $mimetype = 'text/plain';
        }

        $parameters = $mediatype[1];
        if (null === $parameters || '' === $parameters) {
            $parameters = 'charset=us-ascii';
        }

        $this->assertValidPath($mimetype, $parameters, $data);

        return parent::formatPath($mimetype.';'.$parameters.','.$data);
    }

    /**
     * Assert the path is a compliant with RFC2397.
     *
     * @see https://tools.ietf.org/html/rfc2397
     *
     * @throws MalformedUri If the mediatype or the data are not compliant with the RFC2397
     */
    private function assertValidPath(string $mimetype, string $parameters, string $data): void
    {
        if (1 !== preg_match(',^\w+/[-.\w]+(?:\+[-.\w]+)?$,', $mimetype)) {
            throw new MalformedUri(sprintf('The path mimetype `%s` is invalid', $mimetype));
        }

        $is_binary = 1 === preg_match(',(;|^)base64$,', $parameters, $matches);
        if ($is_binary) {
            $parameters = substr($parameters, 0, - strlen($matches[0]));
        }

        $res = array_filter(array_filter(explode(';', $parameters), [$this, 'validateParameter']));
        if ([] !== $res) {
            throw new MalformedUri(sprintf('The path paremeters `%s` is invalid', $parameters));
        }

        if (!$is_binary) {
            return;
        }

        $res = base64_decode($data, true);
        if (false === $res || $data !== base64_encode($res)) {
            throw new MalformedUri(sprintf('The path data `%s` is invalid', $data));
        }
    }

    /**
     * Validate mediatype parameter.
     */
    private function validateParameter(string $parameter): bool
    {
        $properties = explode('=', $parameter);

        return 2 != count($properties) || strtolower($properties[0]) === 'base64';
    }
}
