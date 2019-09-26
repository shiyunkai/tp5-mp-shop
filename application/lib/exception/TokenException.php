<?php


namespace app\lib\exception;


class TokenException extends BaseException
{
    public $code = 404;
    public $msg = 'Token已过期或无效Token';
    public $errorCode = 10001;
}