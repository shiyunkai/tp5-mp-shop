<?php


namespace app\api\service;

use app\api\model\User as UserModel;
use app\lib\enum\ScopeEnum;
use app\lib\exception\TokenException;
use app\lib\exception\WeChatException;
use Exception;


class UserToken extends Token
{
    protected $code;
    protected $wxAppID;
    protected $wxAppSecret;
    protected $wxLoginUrl;

    public function __construct($code)
    {
        $this->code = $code;
        $this->wxAppID = config('wx.app_id');
        $this->wxAppSecret = config('wx.app_secret');
        // 拼接wxLoginUrl
        $this->wxLoginUrl = sprintf(config('wx.login_url'),
            $this->wxAppID, $this->wxAppSecret, $this->code);
    }

    public function get()
    {
        // send request, get weichat info
        $result = curl_get($this->wxLoginUrl);
        // json object to associative object
        $wxResult = json_decode($result,true);
        if(empty($wxResult)){
            throw new Exception('获取session_key及openID异常，微信内部错误');
        }else{
            $loginFail = array_key_exists('errcode',$wxResult);
            if($loginFail){
                // error
                $this->processLoginError($wxResult);
            }else{
                // success,颁发令牌
                return $this->grantToken($wxResult);
            }
        }
    }

    private function processLoginError($wxResult)
    {
        throw new WeChatException([
           'msg'=> $wxResult['errmsg'],
           'errorCode' => $wxResult['errcode']
        ]);
    }

    private function grantToken($wxResult)
    {
        // 获取openid
        // 到数据库中查看，这个openid是否已经存在（用户已经存在）
        // 如果不存在，新增用户
        // 准备缓存数据，写入缓存，作用：用户可以通过携带的令牌找到对应的数据
        // key: 令牌
        // value: wxResult, uid, scope(权限级别)
        // 把令牌返回到客户端去
        $openid = $wxResult['openid'];
        $user = UserModel::getByOpenID($openid);
        if($user){
            $uid = $user->id;
        }else{
            $uid = $this->newUser($openid);
        }
        $cachedValue = $this->prepareCachedValue($wxResult, $uid);
        $token = $this->saveToCache($cachedValue);
        return $token;

    }

    private function saveToCache($cachedValue){
        $key = self::generateToken();
        // array to string
        $value = json_encode($cachedValue);
        $expire_in = config('setting.token_expire_in');
        $request = cache($key, $value, $expire_in);
        if(!$request){
            throw new TokenException([
                'msg'=> '服务器缓存异常',
                'errorCode'=>10005
            ]);
        }
        return $key;
    }

    private function prepareCachedValue($wxResult, $uid){
        $cachedValue = $wxResult;
        $cachedValue['uid'] = $uid;
        // scope越大，能访问的接口越多
        $cachedValue['scope'] = ScopeEnum::User;
        return $cachedValue;
    }

    private function newUser($openid){
        $user = UserModel::create([
            'openid'=> $openid
        ]);
        return $user->id;
    }
}