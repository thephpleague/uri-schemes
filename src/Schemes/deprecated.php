<?php

namespace League\Uri\Schemes;

use League\Uri;

class_alias(Uri\AbstractUri::class, AbstractUri::class);
if (!class_exists(AbstractUri::class)) {
    /**
     * @deprecated use instead {@link Uri\AbstractUri}
     */
    class AbstractUri
    {
    }
}

class_alias(Uri\Data::class, Data::class);
if (!class_exists(Data::class)) {
    /**
     * @deprecated use instead {@link Uri\Data}
     */
    class Data
    {
    }
}

class_alias(Uri\File::class, File::class);
if (!class_exists(File::class)) {
    /**
     * @deprecated use instead {@link Uri\File}
     */
    class File
    {
    }
}

class_alias(Uri\Ftp::class, Ftp::class);
if (!class_exists(Ftp::class)) {
    /**
     * @deprecated use instead {@link Uri\Ftp}
     */
    class Ftp
    {
    }
}

class_alias(Uri\Http::class, Http::class);
if (!class_exists(Http::class)) {
    /**
     * @deprecated use instead {@link Uri\Http}
     */
    class Http
    {
    }
}

class_alias(Uri\UriException::class, UriException::class);
if (!class_exists(UriException::class)) {
    /**
     * @deprecated use instead {@link Uri\UriException}
     */
    class UriException
    {
    }
}

class_alias(Uri\Ws::class, Ws::class);
if (!class_exists(Ws::class)) {
    /**
     * @deprecated use instead {@link Uri\Ws}
     */
    class Ws
    {
    }
}
