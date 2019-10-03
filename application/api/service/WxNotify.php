<?php


namespace app\api\service;

use app\api\model\Product;
use app\lib\enum\OrderStatusEnum;
use think\Db;
use think\Exception;
use think\Loader;

use app\api\model\Order as OrderModel;
use app\api\service\Order as OrderService;
use think\Log;

Loader::import('WxPay.WxPay',EXTEND_PATH,'.Api.php');

/**
 *  处理微信回调业务
 * @package app\api\service
 */
class WxNotify extends \WxPayNotify
{
    /*
     * <xml>
           <return_code><![CDATA[SUCCESS]]></return_code>
           <return_msg><![CDATA[OK]]></return_msg>
           <appid><![CDATA[wx2421b1c4370ec43b]]></appid>
           <mch_id><![CDATA[10000100]]></mch_id>
           <nonce_str><![CDATA[IITRi8Iabbblz1Jc]]></nonce_str>
           <openid><![CDATA[oUpF8uMuAJO_M2pxb1Q9zNjWeS6o]]></openid>
           <sign><![CDATA[7921E432F65EB8ED0CE9755F0E86D72F]]></sign>
           <result_code><![CDATA[SUCCESS]]></result_code>
           <prepay_id><![CDATA[wx201411101639507cbf6ffd8b0779950874]]></prepay_id>
           <trade_type><![CDATA[JSAPI]]></trade_type>
    </xml>
     * */
    // 重写父类回调处理方法
    public function NotifyProcess($data, $config, &$msg)
    {
        if($data['result_code'] == 'SUCCESS'){
            // 微信返回支付成功
            // 订单号
            $orderNo = $data['out_trade_no'];
            Db::startTrans(); // 加事务锁防止多次减库存
            try{
                $order = OrderModel::where('order_no','=',$orderNo)
                    ->lock(true)
                    ->find();
                if($order->status == OrderStatusEnum::UNPAID){
                   $service = new OrderService();
                   $stockStatus = $service->checkOrderStock($order->id);
                   if($stockStatus['pass']){
                       // 库存量检测通过
                       $this->updateOrderStatus($order->id, true);
                       $this->reduceStock($stockStatus);
                   }else{
                       // 库存量检测未通过
                       $this->updateOrderStatus($order->id, false);
                   }
                }

                Db::commit();
                // 通知微信我们已经正确处理，微信不需要向我们发送持续异步消息了
                return true;
            }catch (Exception $ex){
                Db::rollback();
                Log::error($ex);
                return false;
            }
        }else{
            // 微信返回支付失败，我们也通知微信已正确处理，不需要向我们发送持续异步消息了
            return true;
        }
    }

    private function updateOrderStatus($orderID, $success)
    {
        // 订单支付状态
        $status = $success ? OrderStatusEnum::PAID : OrderStatusEnum::PAID_BUT_OUT_OF;
        OrderModel::where('id','=',$orderID)
            ->update(['status'=>$status]);

    }

    private function reduceStock($stockStatus)
    {
        foreach ($stockStatus['pStatusArray'] as $singlePStatus){
            Product::where('id','=',$singlePStatus['id'])
                ->setDec('stock',$singlePStatus['count']);
        }
    }
}