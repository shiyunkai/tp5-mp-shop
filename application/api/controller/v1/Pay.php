<?php


namespace app\api\controller\v1;


use app\api\validate\IDMustBePostiveInt;
use app\api\service\Pay as PayService;
use app\api\service\WxNotify;

class Pay extends BaseController
{
    protected $beforeActionList = [
        'checkExclusiveScope' => ['only' => 'getPreOrder']
    ];

    /**
     *  文档地址: https://pay.weixin.qq.com/wiki/doc/api/wxa/wxa_api.php?chapter=9_1#
     * 商户在小程序中先调用该接口在微信支付服务后台生成预支付交易单，返回正确的预支付交易后调起支付。
     * 接口链接
     * URL地址：https://api.mch.weixin.qq.com/pay/unifiedorder
     *  预订单
     * @param $id 订单id
     */
    public function getPreOrder($id = ''){
        (new IDMustBePostiveInt())->goCheck();
        $pay = new PayService($id);
        return $pay->pay();
    }

    /**
     *  微信支付回调
     *  特点： 请求方式: post
     *        参数格式: xml
     *        参数中不能以？号参数
     */
    public function receiveNotify(){
        // 微信会调用不止一次（我们没有在指定时间内返回结果的情况下）,通知频率为15/15/30/180/1800/1800/1800/1800/3600, 单位：秒

        //1. 检查库存量，超卖
        //2. 更新这个订单的状态
        //3. 减库存
        // 如果成功处理，返回给微信成功处理的信息。否则，需要返回没有成功处理
        $notify = new WxNotify();
        $notify->Handle();

    }

}