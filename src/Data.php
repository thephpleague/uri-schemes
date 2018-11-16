<?php

/**
 * League.Uri (http://uri.thephpleague.com).
 *
 * @package    League\Uri
 * @subpackage League\Uri\Schemes
 * @author     Ignace Nyamagana Butera <nyamsprod@gmail.com>
 * @license    https://github.com/thephpleague/uri-schemes/blob/master/LICENSE (MIT License)
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
use function array_filter;
use function base64_decode;
use function base64_encode;
use function count;
use function explode;
use function file_get_contents;
use function mb_detect_encoding;
use function preg_match;
use function sprintf;
use function str_replace;
use function strlen;
use function strpos;
use function substr;
use const FILEINFO_MIME;

final class Data extends Uri
{
    private const REGEXP_MIMETYPE = ',^\w+/[-.\w]+(?:\+[-.\w]+)?$,';

    private const REGEXP_BINARY = ',(;|^)base64$,';

    /**
     * {@inheritdoc}
     */
    protected static $supported_schemes = [
        'data' => null,
    ];

    /**
     * Create a new instance from a file path.
     *
     * @param resource|null $context
     *
     * @throws InvalidUri If the file does not exist or is not readable
     * @throws InvalidUri If the file mimetype can not be detected
     */
    public static function createFromPath(string $path, $context = null): self
    {
        $file_args = [$path, false];
        $mime_args = [$path, FILEINFO_MIME];
        if (null !== $context) {
            $file_args[] = $context;
            $mime_args[] = $context;
        }

        $raw = @file_get_contents(...$file_args);
        if (false === $raw) {
            throw new InvalidUri(sprintf('The file `%s` does not exist or is not readable', $path));
        }

        return new self(
            'data',
            null,
            null,
            null,
            null,
            str_replace(' ', '', (new finfo(FILEINFO_MIME))->file(...$mime_args)).';base64,'.base64_encode($raw)
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
        if (1 !== preg_match(self::REGEXP_MIMETYPE, $mimetype)) {
            throw new MalformedUri(sprintf('The path mimetype `%s` is invalid', $mimetype));
        }

        $is_binary = 1 === preg_match(self::REGEXP_BINARY, $parameters, $matches);
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
