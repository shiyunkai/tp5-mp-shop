<?php


namespace app\api\validate;


class IDMustBePostiveInt extends BaseValidate
{
    /*
     * rule名称是固定的
     */
    protected $rule = [
        'id' => 'require|isPositiveInteger'
    ];

    protected $message = [
        'id' => 'id必须是一个正整数'
    ];

}