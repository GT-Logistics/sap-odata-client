<?php

namespace Gtlogistics\Sap\Odata\Exception;

class SapException extends \RuntimeException
{
    public function __construct(string $message = "", string $code = '', ?\Throwable $previous = null)
    {
        parent::__construct($message, 0, $previous);

        $this->code = $code;
    }
}
