<?php

namespace CodeSteppers\Generated\Auth;

class RefreshController
{
    private $tokenVerifier;
    private $rawTokenGetter;
    private $tokenGetter;

    public function __construct(TokenVerifier $tokenVerifier, RawTokenGetter $rawTokenGetter, TokenGetter $tokenGetter)
    {
        $this->tokenVerifier = $tokenVerifier;
        $this->rawTokenGetter = $rawTokenGetter;
        $this->tokenGetter = $tokenGetter;
    }

    public function refresh(string $tokenValue): AccessToken
    {
        $token = $this->rawTokenGetter->getRawToken($tokenValue);

        if (!$token) {
            http_response_code(403);
            echo json_encode(["error" => "refreshFailed"]);
            exit;
        }

        return $this->tokenGetter->getAccessToken($token->getUserId());
    }
}
