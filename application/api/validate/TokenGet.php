<?php


namespace app\api\validate;


class TokenGet extends BaseValidate
{
    // 如果只传code不传code的值，require还会通过，所以需要isNotEmpty
    protected $rule = [
        'code' => 'require|isNotEmpty'
    ];

    protected $message = [
        'code' => 'code不能为空'
    ];
}