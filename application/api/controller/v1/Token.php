<?php


namespace app\api\controller\v1;


use app\api\service\UserToken;
use app\api\service\Token as TokenService;
use app\api\validate\TokenGet;
use app\lib\exception\ParameterException;
use app\lib\exception\TokenException;

class Token
{
    public function getToken($code = '')
    {
        (new TokenGet())->goCheck();
        $ut = new UserToken($code);
        $token = $ut->get();
        if (!$token) {
            throw new TokenException();
        }
        // string to object （框架会自动转换associative array为json对象）
        return [
            'token' => $token
        ];
    }

    public function verifyToken($token = ''){
        if(!$token){
            throw new ParameterException([
                'token不能为空'
            ]);
        }

        $valid = TokenService::verifyToken($token);
        return [
            'isValid' => $valid
        ];

    }
}