<?php


namespace app\api\service;


use app\lib\enum\OrderStatusEnum;
use app\lib\exception\OrderException;
use app\lib\exception\PayException;
use app\lib\exception\TokenException;
use think\Config;
use think\Exception;
use app\api\model\Order as OrderModel;
use think\Loader;
use think\Log;

// TP遵循了PSR-4,PSR-0才能使用use 自动载入，才不用使用require导入
// 导入微信SDK，微信SDK没有命名空间，不能使用use
// extend/WxPay/WxPay.Api.php
Loader::import('WxPay.WxPay',EXTEND_PATH,'.Api.php');

class Pay
{
    private $orderID;

    private $orderNO;

    public function __construct($orderID)
    {
        if(!$orderID){
            throw new Exception('订单号不能为NULL');
        }
        $this->orderID = $orderID;
    }

    public function pay(){
        // 1. 检测客户端传过来的订单id是否存在
        // 2. 检测订单号和当前用户是否匹配
        // 3. 检测订单是否已经被支付
        $this->checkOrderValid();
        // 2. 进行库存量检测
        $orderService = new Order();
        $status = $orderService->checkOrderStock($this->orderID);
        if(!$status['pass']){
            return $status;
        }

        return $this->makeWxPreOrder($status['orderPrice']);
    }


    // 生成微信预订单
    private function makeWxPreOrder($totalPrice){

        $openid = Token::getCurrentTokenVar('openid');
        if(!$openid){
            throw new TokenException();
        }
        // 统一下单对象, 微信预支付接口参数赋值
        $wxOrderData = new \WxPayUnifiedOrder();
        $wxOrderData->SetOut_trade_no($this->orderNO);
        // 小程序为JSAPI
        $wxOrderData->SetTrade_type('JSAPI');
        $wxOrderData->SetTotal_fee($totalPrice);
        $wxOrderData->SetBody('零食商贩');
        $wxOrderData->SetOpenid($openid);
        $wxOrderData->SetNotify_url(config('secure.pay_back_url'));
        return $this->getPaySignature($wxOrderData);
    }

    private function getPaySignature($wxOrderData){
        // 调用微信预支付接口
        $wxOrder = \WxPayApi::unifiedOrder($wxOrderData);
        if($wxOrder['return_code'] != 'SUCCESS' || $wxOrder['result_code'] != 'SUCCESS'){
            Log::record($wxOrder, 'error');
            Log::record('获取预支付订单失败','error');
            throw new PayException([
                'msg'=>'获取预支付订单失败'
            ]);
        }
        // 保存prepay_id，用于向用户发发送模板消息
        $this->recordPreOrder($wxOrder);
        $signature = $this->sign($wxOrder);
        return $signature;
    }

    private function sign($wxOrder){
        $jsApiPayData = new \WxPayJsApiPay();
        $jsApiPayData->SetAppid(config('wx.app_id'));
        $jsApiPayData->SetTimeStamp((string)time());

        $rand = md5(time().mt_rand(0,1000));

        $jsApiPayData->SetNonceStr($rand);
        $jsApiPayData->SetPackage('prepay_id='.$wxOrder['prepay_id']);
        $jsApiPayData->SetSignType('md5');

        $sign = $jsApiPayData->MakeSign();
        $rawValues = $jsApiPayData->GetValues();
        $rawValues['paySign'] = $sign;

        unset($rawValues['appId']);

        return $rawValues;
    }

    private function recordPreOrder($wxOrder){
        OrderModel::where('id','=',$this->orderID)
            ->update(['prepay_id' => $wxOrder['prepay_id']]);
    }

    private function checkOrderValid(){
        $order = OrderModel::where('id','=', $this->orderID)
            ->find();
        if(!$order){
            throw new OrderException();
        }

        if(!Token::isValidOperate($order->user_id)){
            throw new TokenException([
                'msg' => '订单与用户不匹配',
                'errorCode' => 10003
            ]);
        }

        if($order->status != OrderStatusEnum::UNPAID){
            throw new OrderException([
                'msg' => '订单已支付过了',
                'errorCode' => 80003,
                'code' => 400
            ]);
        }
        $this->orderNO = $order->order_no;

        return true;
    }
}