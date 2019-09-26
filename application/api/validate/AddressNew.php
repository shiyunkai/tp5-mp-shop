<?php


namespace app\api\validate;


class AddressNew extends BaseValidate
{

    protected $rule = [
        'name' => 'require|isNotEmpty',
        'mobile' => 'require|isMobile',
        'province' => 'require|isNotEmpty',
        'city' => 'require|isNotEmpty',
        'country' => 'require|isNotEmpty',
        'detail' => 'require|isNotEmpty',

        // uid是自动增长的，有安全性问题，会出现A用户修改B用户的数据
        //'uid' => 're'
    ];
}