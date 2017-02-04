<?php

/*
 * This file is part of the GLAVWEB.cms Rest Client package.
 *
 * (c) Andrey Nilov <nilov@glavweb.ru>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Glavweb\CmsRestClient;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;

/**
 * Class CmsRestClient
 *
 * @package Glavweb\CmsRestClient
 * @author Andrey Nilov <nilov@glavweb.ru>
 */
class CmsRestClient
{
    /**
     * @var array
     */
    private static $validateTokenResult = [];

    /**
     * @var Client
     */
    private $guzzle;

    /**
     * @var string
     */
    private $apiBaseUrl;

    /**
     * @var string
     */
    private $apiUserName;

    /**
     * @var string
     */
    private $apiPassword;

    /**
     * @var string
     */
    private static $token = false;

    /**
     * ContentBlockService constructor.
     *
     * @param Client $guzzle
     * @param string $apiBaseUrl
     * @param string $apiUserName
     * @param string $apiPassword
     */
    public function __construct(Client $guzzle, $apiBaseUrl, $apiUserName, $apiPassword)
    {
        $this->guzzle      = $guzzle;
        $this->apiBaseUrl  = $this->prepareApiBaseUrl($apiBaseUrl);
        $this->apiUserName = $apiUserName;
        $this->apiPassword = $apiPassword;
    }

    /**
     * @param string $method
     * @param string $uri
     * @param array $options
     * @param bool|false $auth
     * @return mixed|\Psr\Http\Message\ResponseInterface
     * @throws \Exception
     */
    public function request($method, $uri, array $options = [], $auth = false)
    {
        if ($auth) {
            if (!isset($options['headers'])) {
                $options['headers'] = [];
            }

            $options['headers'] = array_merge($options['headers'], $this->singIn());
        }

        $response = $this->guzzle->request($method, $this->url($uri), $options);

        return $response;
    }

    /**
     * @param string $uri
     * @param array $options
     * @param bool|true $auth
     * @return mixed|\Psr\Http\Message\ResponseInterface
     * @throws \Exception
     */
    public function get($uri, array $options = [], $auth = false)
    {
        return $this->request('GET', $uri, $options, $auth);
    }

    /**
     * @param string $uri
     * @param array $options
     * @param bool|true $auth
     * @return mixed|\Psr\Http\Message\ResponseInterface
     * @throws \Exception
     */
    public function post($uri, array $options = [], $auth = false)
    {
        return $this->request('POST', $uri, $options, $auth);
    }

    /**
     * @param string $uri
     * @param array $options
     * @param bool|true $auth
     * @return mixed|\Psr\Http\Message\ResponseInterface
     * @throws \Exception
     */
    public function put($uri, array $options = [], $auth = false)
    {
        return $this->request('PUT', $uri, $options, $auth);
    }

    /**
     * @param string $uri
     * @param array $options
     * @param bool|true $auth
     * @return mixed|\Psr\Http\Message\ResponseInterface
     * @throws \Exception
     */
    public function patch($uri, array $options = [], $auth = false)
    {
        return $this->request('PATCH', $uri, $options, $auth);
    }

    /**
     * @param string $uri
     * @param array $options
     * @param bool|true $auth
     * @return mixed|\Psr\Http\Message\ResponseInterface
     * @throws \Exception
     */
    public function delete($uri, array $options = [], $auth = false)
    {
        return $this->request('DELETE', $uri, $options, $auth);
    }

    /**
     * @param string $uri
     * @param array $options
     * @param bool|true $auth
     * @return mixed|\Psr\Http\Message\ResponseInterface
     * @throws \Exception
     */
    public function link($uri, array $options = [], $auth = false)
    {
        return $this->request('LINK', $uri, $options, $auth);
    }

    /**
     * @param string $uri
     * @param array $options
     * @param bool|true $auth
     * @return mixed|\Psr\Http\Message\ResponseInterface
     * @throws \Exception
     */
    public function unlink($uri, array $options = [], $auth = false)
    {
        return $this->request('UNLINK', $uri, $options, $auth);
    }

    /**
     * @param string $token
     * @param bool $cash
     * @return bool
     */
    public function validateToken($token, $cash = true)
    {
        if (!$cash) {
            return $this->doValidateToken($token);
        }

        if (!isset(self::$validateTokenResult[$token])) {
            self::$validateTokenResult[$token] = $this->doValidateToken($token);
        }

        return self::$validateTokenResult[$token];
    }

    /**
     * @param string $token
     * @return bool
     */
    public function doValidateToken($token)
    {
        try {
            $response = $this->guzzle->request('GET', $this->url('validate-token'), [
                'headers' => [
                    'Token' => $token
                ],
            ]);

            return $response->getStatusCode() == 200;

        } catch (ClientException $e) {
            return false;
        }
    }

    /**
     * @param string $apiBaseUrl
     * @return string
     */
    private function prepareApiBaseUrl($apiBaseUrl)
    {
        return rtrim($apiBaseUrl, '/') . '/';
    }

    /**
     * @param string $uri
     * @return string
     */
    private function url($uri)
    {
        $scheme = parse_url($uri, PHP_URL_SCHEME);

        if ($scheme !== null) {
            return $uri;
        }

        return $this->apiBaseUrl . $uri;
    }

    /**
     * @return array
     * @throws \Exception
     */
    private function singIn()
    {
        $needSingId =
            self::$token === false ||
            (self::$token && !$this->validateToken(self::$token))
        ;

        if ($needSingId) {
            self::$token = $this->singInRequest();
        }

        if (!self::$token) {
            throw new \Exception('Sing in is failed.');
        }

        return [
            'Token' => self::$token
        ];
    }

    /**
     * @return array
     */
    private function singInRequest()
    {
        $response = $this->guzzle->request('POST', $this->url('sign-in'), [
            'form_params' => [
                'username' => $this->apiUserName,
                'password' => $this->apiPassword,
            ]
        ]);

        $token = null;
        if ($response->getStatusCode() == 200) {
            $body = json_decode($response->getBody(), true);
            $token = isset($body['Token']) ? $body['Token'] : null;
        }

        return $token;
    }
}