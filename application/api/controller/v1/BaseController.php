<?php


namespace app\api\controller\v1;


use think\Controller;

use app\api\service\Token as TokenService;

class BaseController extends Controller
{

    /**
     *  验证初级权限
     */
    protected function checkPrimaryScope(){
        TokenService::needPrimaryScope();
    }

    protected function checkExclusiveScope(){
        TokenService::needExclusiveScope();
    }
}