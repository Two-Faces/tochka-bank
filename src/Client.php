<?php

namespace TochkaBank;

use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\GuzzleException;
use TochkaBank\Api\Account;
use TochkaBank\Api\Statement;
use TochkaBank\Exceptions\InvalidArgumentException;
use GuzzleHttp\Client as HttpClient;
use TochkaBank\Exceptions\InvalidJWTToken;

class Client
{

    const HOST = 'https://enter.tochka.com';

    protected $jwt = null;

    /**
     * @var Account
     */
    public $account;

    /**
     * @var Statement
     */
    public $statement;


    /**
     * @var HttpClient
     */
    protected $guzzle;

    /**
     * Client constructor.
     * @param null $jwt
     * @param array $guzzleOptions
     * @throws InvalidArgumentException
     */
    public function __construct($jwt = null, $guzzleOptions = [])
    {
        $this->setJwt($jwt);
        $this->configureGuzzle($guzzleOptions);
        $this->account = new Account($this);
        $this->statement = new Statement($this);
    }

    /**
     * @param $method
     * @param $uri
     * @param array $json
     *
     * @return array|mixed
     * @throws InvalidJWTToken|GuzzleException
     */
    public function request($method, $uri, $json = [])
    {
        $options = [];
        if (!empty($json)) {
            $options = [
                'json' => $json,
            ];
        }
        try {
            $response = $this->guzzle->request($method, '/' . $this->getPrefixApiPath() . $uri, $options);
            $jsonRaw = $response->getBody()->getContents();

            return json_decode($jsonRaw, true);
        } catch (ClientException $exception) {
            if ($exception->getCode() == 403) {
                throw new InvalidJWTToken();
            }
            throw $exception;
        }
    }

    /**
     * Возвращает префикс апи
     *
     * @return string
     */
    protected function getPrefixApiPath()
    {
        return 'api/v1';
    }

    protected function configureGuzzle($options = [])
    {
        $resultOptions = array_merge([
            'base_uri' => static::HOST,
            'headers' => [
                'Authorization' => 'Bearer ' . $this->getJwt(),
                'Accept' => 'application/json',
            ]
        ], $options);

        $this->guzzle = new HttpClient($resultOptions);
    }

    /**
     * @return null
     */
    public function getJwt()
    {
        return $this->jwt;
    }

    /**
     * @param null $jwt
     * @return null|string
     * @throws InvalidArgumentException
     */
    public function setJwt($jwt)
    {
        if (empty($jwt)) {
            throw new InvalidArgumentException('JWT empty');
        }
        $oldValue = $this->jwt;
        $this->jwt = $jwt;

        return $oldValue;
    }
}
