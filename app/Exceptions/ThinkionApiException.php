<?php

namespace App\Exceptions;

use RuntimeException;

class ThinkionApiException extends RuntimeException
{
    protected int $httpStatus;
    protected string $responseBody;

    public function __construct(string $message, int $httpStatus = 0, string $responseBody = '', ?\Throwable $previous = null)
    {
        parent::__construct($message, $httpStatus, $previous);
        $this->httpStatus = $httpStatus;
        $this->responseBody = $responseBody;
    }

    public function getHttpStatus(): int
    {
        return $this->httpStatus;
    }

    public function getResponseBody(): string
    {
        return $this->responseBody;
    }
}
