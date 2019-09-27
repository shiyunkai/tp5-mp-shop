<?php


namespace app\lib\exception;


class OrderException
{

    public $code = 404;
    public $msg = '订单不存在，请检查ID';
    public $errorCode = 80000;
}