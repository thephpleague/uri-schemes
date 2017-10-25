<?php

namespace League\Uri\Schemes;

use League\Uri as Base;

class_alias(Base\AbstractUri::class, AbstractUri::class);
if (!class_exists(AbstractUri::class)) {
    /**
     * @deprecated use instead {@link Base\AbstractUri}
     */
    class AbstractUri
    {
    }
}

class_alias(Base\Data::class, Data::class);
if (!class_exists(Data::class)) {
    /**
     * @deprecated use instead {@link Base\Data}
     */
    class Data
    {
    }
}

class_alias(Base\File::class, File::class);
if (!class_exists(File::class)) {
    /**
     * @deprecated use instead {@link Base\File}
     */
    class File
    {
    }
}

class_alias(Base\Ftp::class, Ftp::class);
if (!class_exists(Ftp::class)) {
    /**
     * @deprecated use instead {@link Base\Ftp}
     */
    class Ftp
    {
    }
}

class_alias(Base\Http::class, Http::class);
if (!class_exists(Http::class)) {
    /**
     * @deprecated use instead {@link Base\Http}
     */
    class Http
    {
    }
}

class_alias(Base\Uri::class, Uri::class);
if (!class_exists(Uri::class)) {
    /**
     * @deprecated use instead {@link Base\Ws}
     */
    class Uri
    {
    }
}

class_alias(Base\UriException::class, UriException::class);
if (!class_exists(UriException::class)) {
    /**
     * @deprecated use instead {@link Base\UriException}
     */
    class UriException
    {
    }
}

class_alias(Base\Ws::class, Ws::class);
if (!class_exists(Ws::class)) {
    /**
     * @deprecated use instead {@link Base\Ws}
     */
    class Ws
    {
    }
}
