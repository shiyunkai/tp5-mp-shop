<?php


namespace app\api\controller\v1;


use app\api\model\User as UserModel;
use app\api\service\Token as TokenService;
use app\api\validate\AddressNew;
use app\lib\exception\SuccessMessage;
use app\lib\exception\UserException;

class Address extends BaseController
{

/*
    前置操作
    protected $beforeActionList = [
        // 只有second, third接口才需要执行前置方法first
        'first' => ['only' => 'second,third']
    ];

    protected function first(){
        echo 'first';
    }

    public function second(){
        echo 'second';
    }

    public function third(){
        echo "third";
    }*/

    protected $beforeActionList = [
        'checkPrimaryScope' => ['only' => 'createOrUpdateAddress']
    ];


    public function createOrUpdateAddress(){
        $validate = new AddressNew();
        $validate->goCheck();
        // 根据token获取Uid
        // 根据Uid来查询用户数据，判断用户是否存在， 如果不存在， 抛出异常
        // 获取用户从客户端提交来的地址信息
        // 根据用户地址信息是否存在，从而判断是添加还是更新地址

        $uid = TokenService::getCurrentUid();
        $user = UserModel::get($uid);
        if(!$user){
            throw new UserException();
        }

        $dataArray = $validate->getDataByRule(input('post.'));
        $userAddress = $user->address;
        if(!$userAddress){
            // 创建
            $user->address()->save($dataArray);
        }else{
            // 更新
            $user->address->save($dataArray);
        }

        return new SuccessMessage();
    }

}