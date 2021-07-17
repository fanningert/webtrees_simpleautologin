<?php

declare(strict_types=1);

namespace at\fanninger\WebtreesModules\SimpleAutoLogin\Modules;

use Fisharebest\Webtrees\Http\RequestHandlers\Logout;
use Fisharebest\Webtrees\Exceptions\HttpServerErrorException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

class SimpleAutoLogout extends Logout
{
    // Options for fetching files using GuzzleHTTP
    private const GUZZLE_OPTIONS = [
        'connect_timeout' => 25,
        'read_timeout'    => 25,
        'timeout'         => 55,
    ];

    /**
     * @param ServerRequestInterface $request
     *
     * @return ResponseInterface
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        // Add support for Logout via Auth-Proxy
        $logout_url = $request->getAttribute('simpleautologin_auth_proxy_logout_url');

        if ($logout_url !== null && $logout_url !== '') {
            $httpclient = new Client();
            $httpclient_response = $httpclient->get($logout_url, self::GUZZLE_OPTIONS);
        }
        
        return parent::handle($request);
    }
}
