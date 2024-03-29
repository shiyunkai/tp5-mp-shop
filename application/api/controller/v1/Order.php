<?php


namespace app\api\controller\v1;


use app\api\validate\IDMustBePostiveInt;
use app\api\validate\OrderPlace;

use app\api\service\Token as TokenService;
use app\api\service\Order as OrderService;
use app\api\model\Order as OrderModel;
use app\api\validate\PagingParameter;
use app\lib\exception\OrderException;

class Order extends BaseController
{
    // 用户在选择商品后，向API提交包含它所选择商品的相关信息
    // API在接收到信息后，需要检查订单相关商品的库存量
    // 有库存，把订单数据存入数据库中 = 下单成功了，返回客户端消息，告诉客户端可以支付了
    // 调用我们的支付接口，进行支付
    // 再次进行库存量检测(下单以后用户可能会等一段时间后再支付)
    // 服务器这边就可以调用微信的支付接口进行支付
    // 小程序根服务器返回的结果拉起微信支付(wx.requestPayment) (结果返回路径有两条：小程序和服务器)
    // 微信会返回给我们一个支付的结果（异步）
    // 成功：也需要进行库存量的检测
    // 成功：进行库存量的扣除（成功和失败都是微信返回给客户端，因为微信返回给我们的不是实时的）


    protected $beforeActionList = [
        'checkExclusiveScope' => ['only' => 'createOrUpdateAddress'],
        'checkPrimaryScope' => ['only' => 'getDetail, getSummaryByUser']
    ];


    /**
     *  下单接口
     */
    public function placeOrder(){
        (new OrderPlace())->goCheck();
        // 获取数组参数的形式
        $products = input('post.products/a');
        $uid = TokenService::getCurrentUid();
        $order = new OrderService();
        $status = $order->place($uid, $products);
        return $status;
    }

    /**
     *  用户历史订单
     * @param int $page 页码
     * @param int $size 每页数量
     */
    public function getSummaryByUser($page=1, $size=15){
        (new PagingParameter())->goCheck();
        $uid = TokenService::getCurrentUid();

        $pagingOrders = OrderModel::getSummaryByUser($uid, $page,$size);

        if($pagingOrders->isEmpty()){
            return [
                'data' => [],
                'current_page' => $pagingOrders->getCurrentPage()
            ];
        }

        $data = $pagingOrders->hidden(['snap_items','snap_address','prepay_id'])->toArray();
        return [
            'data' => $data,
            'current_page' => $pagingOrders->getCurrentPage()
        ];
    }

    public function getDetail($id){
        (new IDMustBePostiveInt())->goCheck();
        $orderDetail = OrderModel::get($id);
        if(!$orderDetail){
            throw new OrderException();
        }
        return $orderDetail
            ->hidden(['prepay_id']);
    }

}