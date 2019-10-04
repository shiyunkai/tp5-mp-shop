<?php


namespace app\api\service;

use app\api\service\Token as TokenService;
use app\lib\enum\ScopeEnum;
use app\lib\exception\ForbiddenException;
use app\lib\exception\TokenException;
use think\Cache;
use think\Exception;
use think\Request;

class Token
{

    public static function generateToken(){
        // 32个字符组成一组随机字符串
        $randChars = getRandChar(32);
        // 用三组字符串， 进行md5加密
        $timestamp = $_SERVER['REQUEST_TIME_FLOAT'];
        // salt
        $salt = config('secure.token_salt');
        return md5($randChars.$timestamp.$salt);
    }

    public static function getCurrentTokenVar($key){
        // 约定token只在header中携带
        $token = Request::instance()
            ->header('token');
        $vars = Cache::get($token);
        if(!$vars){
            throw new TokenException();
        }
        else{
            if(!is_array($vars)){
                // 字符串转数组,
                $vars = json_decode($vars, true);
            }
            if(array_key_exists($key, $vars)){
                return $vars[$key];
            }
            else{
                throw new Exception('尝试获取的Token变量并不存在');
            }

        }
    }

    public static function getCurrentUid(){
        $uid = self::getCurrentTokenVar('uid');
        return $uid;
    }

    // 需要用户和CMS管理员都可以访问
    public static function needPrimaryScope(){
        $scope = self::getCurrentTokenVar('scope');
        if($scope){
            if($scope == ScopeEnum::User){
                return true;
            }else{
                throw new ForbiddenException();
            }
        }
        else{
            throw new TokenException();
        }
    }

    // 只有用户才能访问的接口权限
    public static function needExclusiveScope(){
        $scope = self::getCurrentTokenVar('scope');
        if($scope){
            if($scope == ScopeEnum::User){
                return true;
            }else{
                throw new ForbiddenException();
            }
        }
        else{
            throw new TokenException();
        }
    }

    // 是否是一个合法操作
    public static function isValidOperate($checkedUID){
        if(!$checkedUID){
            throw new Exception('检查UID时必须传入一个被检查的UID');
        }
        $currentOperateUID = self::getCurrentUid();
        if($currentOperateUID == $checkedUID){
            return true;
        }else{
            return false;
        }
    }

    public static function verifyToken($token){
        $exists = Cache::get($token);
        if($exists){
            return true;
        }else{
            return false;
        }
    }


}