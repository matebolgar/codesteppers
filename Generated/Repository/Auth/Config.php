<?php

namespace CodeSteppers\Generated\Repository\Auth;

class Config
{
    /**
     * @var int
     */
    private $appId;
    /**
     * @var string
     */
    private $authKey;
    /**
     * @var string
     */
    private $authSecret;
    /**
     * @var string
     */
    private $apiUrl;

    public function __construct(int $appId, string $authKey, string $authSecret, string $apiUrl)
    {
        $this->appId = $appId;
        $this->authKey = $authKey;
        $this->authSecret = $authSecret;
        $this->apiUrl = $apiUrl;
    }

    /**
     * @return int
     */
    public function getAppId(): int
    {
        return $this->appId;
    }

    /**
     * @return string
     */
    public function getAuthKey(): string
    {
        return $this->authKey;
    }

    /**
     * @return string
     */
    public function getAuthSecret(): string
    {
        return $this->authSecret;
    }

    /**
     * @return string
     */
    public function getApiUrl(): string
    {
        return $this->apiUrl;
    }
}