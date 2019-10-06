<?php


namespace app\lib\exception;


class PayException extends BaseException
{
    public $code = 400;

    public $msg = '支付错误';

    public $errorCode = 10000;
}