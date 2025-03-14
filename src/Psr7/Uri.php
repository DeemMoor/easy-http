<?php

namespace Custom\EasyHttp\Psr7;

use Psr\Http\Message\UriInterface;

class Uri implements UriInterface, \JsonSerializable
{

    private const string HTTP_DEFAULT_HOST = 'localhost';

    private const array DEFAULT_PORTS = [
        'http' => 80,
        'https' => 443,
        'ftp' => 21,
        'gopher' => 70,
        'nntp' => 119,
        'news' => 119,
        'telnet' => 23,
        'tn3270' => 23,
        'imap' => 143,
        'pop' => 110,
        'ldap' => 389,
    ];

    private const string CHAR_UNRESERVED = 'a-zA-Z0-9_\-\.~';

    private const string CHAR_SUB_DELIMS = '!\$&\'\(\)\*\+,;=';

    private string $scheme = '';

    private string $userInfo = '';

    private string $host = '';

    private ?int $port;

    private string $path = '';

    private string $query = '';

    private string $fragment = '';

    private ?string $composedComponents;

    public function __construct(string $uri = '')
    {
        if ($uri !== '') {
            $parts = self::parse($uri);
            if ($parts === false) {
                throw new \RuntimeException("Unable to parse URI: $uri");
            }
            $this->applyParts($parts);
        }
    }

    private static function parse(string $uri): false|array
    {
        $prefix = '';
        if (preg_match('%^(.*://\[[0-9:a-f]+\])(.*?)$%', $uri, $matches)) {
            /** @var array{0:string, 1:string, 2:string} $matches */
            $prefix = $matches[1];
            $uri = $matches[2];
        }

        $encodedUrl = preg_replace_callback(
            '%[^:/@?&=#]+%usD',
            static function ($matches) {
                return urlencode($matches[0]);
            },
            $uri
        );

        $result = parse_url($prefix.$encodedUrl);

        if ($result === false) {
            return false;
        }

        return array_map('urldecode', $result);
    }

    public function getScheme(): string
    {
        return $this->scheme;
    }

    public function getAuthority(): string
    {
        $authority = $this->host;
        if ($this->userInfo !== '') {
            $authority = $this->userInfo.'@'.$authority;
        }

        if ($this->port !== null) {
            $authority .= ':'.$this->port;
        }

        return $authority;
    }

    public function getUserInfo(): string
    {
        return $this->userInfo;
    }

    public function getHost(): string
    {
        return $this->host;
    }

    public function getPort(): ?int
    {
        return $this->port;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function getQuery(): string
    {
        return $this->query;
    }

    public function getFragment(): string
    {
        return $this->fragment;
    }

    public function withScheme(string $scheme): UriInterface
    {
        $scheme = $this->filterScheme($scheme);

        if ($this->scheme === $scheme) {
            return $this;
        }

        $new = clone $this;
        $new->scheme = $scheme;
        $new->composedComponents = null;
        $new->removeDefaultPort();
        $new->validateState();

        return $new;
    }

    public function withUserInfo(string $user, ?string $password = null): UriInterface
    {
        $info = $this->filterUserInfoComponent($user);
        if ($password !== null) {
            $info .= ':'.$this->filterUserInfoComponent($password);
        }

        if ($this->userInfo === $info) {
            return $this;
        }

        $new = clone $this;
        $new->userInfo = $info;
        $new->composedComponents = null;
        $new->validateState();

        return $new;
    }

    public function withHost(string $host): UriInterface
    {
        $host = $this->filterHost($host);

        if ($this->host === $host) {
            return $this;
        }

        $new = clone $this;
        $new->host = $host;
        $new->composedComponents = null;
        $new->validateState();

        return $new;
    }

    public function withPort(?int $port): UriInterface
    {
        $port = $this->filterPort($port);

        if ($this->port === $port) {
            return $this;
        }

        $new = clone $this;
        $new->port = $port;
        $new->composedComponents = null;
        $new->removeDefaultPort();
        $new->validateState();

        return $new;
    }

    public function withPath(string $path): UriInterface
    {
        $path = $this->filterPath($path);

        if ($this->path === $path) {
            return $this;
        }

        $new = clone $this;
        $new->path = $path;
        $new->composedComponents = null;
        $new->validateState();

        return $new;
    }

    public function withQuery(string $query): UriInterface
    {
        $query = $this->filterQueryAndFragment($query);

        if ($this->query === $query) {
            return $this;
        }

        $new = clone $this;
        $new->query = $query;
        $new->composedComponents = null;

        return $new;
    }

    public function withFragment(string $fragment): UriInterface
    {
        $fragment = $this->filterQueryAndFragment($fragment);

        if ($this->fragment === $fragment) {
            return $this;
        }

        $new = clone $this;
        $new->fragment = $fragment;
        $new->composedComponents = null;

        return $new;
    }

    public function __toString(): string
    {
        if ($this->composedComponents === null) {
            $this->composedComponents = self::composeComponents(
                $this->scheme,
                $this->getAuthority(),
                $this->path,
                $this->query,
                $this->fragment
            );
        }

        return $this->composedComponents;
    }

    public static function composeComponents(?string $scheme, ?string $authority, string $path, ?string $query, ?string $fragment): string
    {
        $uri = '';

        if ($scheme !== '') {
            $uri .= $scheme.':';
        }

        if ($authority !== '' || $scheme === 'file') {
            $uri .= '//'.$authority;
        }

        if ($authority !== '' && $path !== '' && $path[0] !== '/') {
            $path = '/'.$path;
        }

        $uri .= $path;

        if ($query !== '') {
            $uri .= '?'.$query;
        }

        if ($fragment !== '') {
            $uri .= '#'.$fragment;
        }

        return $uri;
    }

    public function jsonSerialize(): string
    {
        return $this->__toString();
    }

    private function applyParts(null $parts): void
    {
        $this->scheme = isset($parts['scheme'])
            ? $this->filterScheme($parts['scheme'])
            : '';
        $this->userInfo = isset($parts['user'])
            ? $this->filterUserInfoComponent($parts['user'])
            : '';
        $this->host = isset($parts['host'])
            ? $this->filterHost($parts['host'])
            : '';
        $this->port = isset($parts['port'])
            ? $this->filterPort($parts['port'])
            : null;
        $this->path = isset($parts['path'])
            ? $this->filterPath($parts['path'])
            : '';
        $this->query = isset($parts['query'])
            ? $this->filterQueryAndFragment($parts['query'])
            : '';
        $this->fragment = isset($parts['fragment'])
            ? $this->filterQueryAndFragment($parts['fragment'])
            : '';
        if (isset($parts['pass'])) {
            $this->userInfo .= ':'.$this->filterUserInfoComponent($parts['pass']);
        }

        $this->removeDefaultPort();
    }

    private function filterScheme($scheme): string
    {
        if (!is_string($scheme)) {
            throw new \InvalidArgumentException('Scheme must be a string');
        }

        return \strtr($scheme, 'ABCDEFGHIJKLMNOPQRSTUVWXYZ', 'abcdefghijklmnopqrstuvwxyz');
    }

    private function removeDefaultPort(): void
    {
        if ($this->port !== null && self::isDefaultPort($this)) {
            $this->port = null;
        }
    }

    public static function isDefaultPort(UriInterface $uri): bool
    {
        return $uri->getPort() === null
            || (isset(self::DEFAULT_PORTS[$uri->getScheme()]) && $uri->getPort() === self::DEFAULT_PORTS[$uri->getScheme()]);
    }

    private function validateState(): void
    {
        if ($this->host === '' && ($this->scheme === 'http' || $this->scheme === 'https')) {
            $this->host = self::HTTP_DEFAULT_HOST;
        }

        if ($this->getAuthority() === '') {
            if (0 === strpos($this->path, '//')) {
                throw new \RuntimeException('The path of a URI without an authority must not start with two slashes "//"');
            }
            if ($this->scheme === '' && false !== strpos(explode('/', $this->path, 2)[0], ':')) {
                throw new \RuntimeException('A relative URI must not have a path beginning with a segment containing a colon');
            }
        }
    }

    private function filterUserInfoComponent($user): string
    {
        if (!is_string($user)) {
            throw new \InvalidArgumentException('User info must be a string');
        }

        return preg_replace_callback(
            '/(?:[^%'.self::CHAR_UNRESERVED.self::CHAR_SUB_DELIMS.']+|%(?![A-Fa-f0-9]{2}))/',
            [$this, 'rawurlencodeMatchZero'],
            $user
        );
    }

    private function filterHost($host): string
    {
        if (!is_string($host)) {
            throw new \InvalidArgumentException('Host must be a string');
        }

        return \strtr($host, 'ABCDEFGHIJKLMNOPQRSTUVWXYZ', 'abcdefghijklmnopqrstuvwxyz');
    }

    private function filterPort(?int $port): ?int
    {
        if ($port === null) {
            return null;
        }

        $port = (int) $port;
        if (0 > $port || 0xFFFF < $port) {
            throw new \InvalidArgumentException(
                sprintf('Invalid port: %d. Must be between 0 and 65535', $port)
            );
        }

        return $port;
    }

    private function filterPath($path): string
    {
        if (!is_string($path)) {
            throw new \InvalidArgumentException('Path must be a string');
        }

        return preg_replace_callback(
            '/(?:[^'.self::CHAR_UNRESERVED.self::CHAR_SUB_DELIMS.'%:@\/]++|%(?![A-Fa-f0-9]{2}))/',
            [$this, 'rawurlencodeMatchZero'],
            $path
        );
    }

    private function filterQueryAndFragment($query): string
    {
        if (!is_string($query)) {
            throw new \InvalidArgumentException('Query and fragment must be a string');
        }

        return preg_replace_callback(
            '/(?:[^'.self::CHAR_UNRESERVED.self::CHAR_SUB_DELIMS.'%:@\/\?]++|%(?![A-Fa-f0-9]{2}))/',
            [$this, 'rawurlencodeMatchZero'],
            $query
        );
    }
}