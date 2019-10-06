<?php


namespace app\api\service;


use app\api\model\OrderProduct;
use app\api\model\Product;
use app\api\model\UserAddress;
use app\lib\exception\UserException;
use think\Db;
use think\Exception;

class Order
{

    // 订单的列表，也就是客户端传递过来的products参数
    protected $oProducts;

    // 真实的商品信息（包括库存量）
    protected $products;

    protected $uid;

    // 下单
    public function place($uid, $oProducts)
    {
        // oProducts和products对比, 检测库存量
        // products从数据库中查询出来
        $this->oProducts = $oProducts;
        $this->products = $this->getProductByOrder($oProducts);
        $this->uid = $uid;
        $status = $this->getOrderStatus();
        if(!$status['pass']){
            // 库存量检测未通过
            $status['order_id'] = -1;
            return $status;
        }else{
            // 检测通过,开始创建订单
            $orderSnap = $this->snapOrder($status);
            $order = $this->createOrder($orderSnap);
            $order['pass'] = true;
            return $order;
        }
    }

    private function getOrderStatus()
    {
        $status = [
            'pass' => true, //商品库存量检测是否通过，只要有一个商品的库存量检测不通过则为false
            'orderPrice' => 0, //订单总价格
            'totalCount' => 0, //订单商品总数量
            'pStatusArray' => [], //订单里面所有商品的详细信息
        ];

        foreach ($this->oProducts as $oProduct) {
            $pStatus = $this->getProductStatus(
                $oProduct['product_id'],$oProduct['count'],$this->products);
            if(!$pStatus['haveStock']){
                // 只要有一个商品检测不通过就为false
                $status['pass'] = false;
            }
            $status['orderPrice'] += $pStatus['totalPrice'];
            $status['totalCount'] += $pStatus['counts'];
            array_push($status['pStatusArray'], $status);
        }
        return $status;
    }

    private function getProductStatus($oPID, $oCount, $products)
    {
        // $oPID在$products中的位置
        $pIndex = -1;

        // 单个商品的详细信息
        $pStatus = [
            'id' => null,
            'haveStock' => false,
            'counts' => 0,
            'price' => 0, // 商品单价
            'name' => '',
            'totalPrice' => 0,// 商品数量*商品单价
            'main_img_url' => null
        ];

        for($i = 0; $i < count($products); $i++) {
            if($oPID == $products[$i]['id']) {
                $pIndex = $i;
            }
        }

        if($pIndex == -1) {
            throw new OrderException([
                'msg' => 'id为'.$oPID.'的商品不存在，创建订单失败'
                ]
            );
        }else{
            $product = $products[$pIndex];
            $pStatus['id'] = $product['id'];
            $pStatus['name'] = $product['name'];
            $pStatus['counts'] = $oCount;
            $pStatus['price'] = $product['price'];
            $pStatus['main_img_url'] = $product['main_img_url'];
            $pStatus['totalPrice'] = $product['price'] * $oCount;
            if($product['stock'] - $oCount >= 0){
                $pStatus['haveStock'] = true;
            }

            return $pStatus;
        }

    }

    // 根据订单信息查找真实的商品信息
    private function getProductByOrder($oProducts)
    {
        /*foreach ($oProducts as $oProduct){
            // 循环的查询数据库, 不可取
        }*/

        // 获取商品id
        $oPIDs = [];
        foreach ($oProducts as $item){
            array_push($oPIDs, $item['product_id']);
        }
        $products = Product::all($oPIDs)
        ->visible(['id','price','stock','name','main_img_url'])
        ->toArray();

        return $products;
    }

    // 生成订单快照
    private function snapOrder($status)
    {
        $snap = [
          'orderPrice' => 0,// 订单价格
          'totalCount' => 0, // 订单商品总数量
            'snapAddress' => null,
          'pStatus' => [], //订单商品状态
            'snapName' => '',
            'snapImg' => ''
        ];

        $snap['orderPrice'] = $status['orderPrice'];
        $snap['totalCount'] = $status['totalCount'];
        $snap['pStatus'] = $status['pStatusArray'];
        $snap['snapAddress'] = json_encode($this->getUserAddress());
        // 取订单快照商品名称和图片为订单中的第一个商品展示
        $snap['snapName'] = $this->products[0]['name'];
        $snap['snapImg'] = $this->products[0]['main_img_url'];
        if(count($this->products)>1){
            $snap['snapName'] .= '等';
        }

        return $snap;

    }

    private function getUserAddress()
    {
        $userAddress = UserAddress::where('user_id','=',$this->uid)
            ->find();
        if(!$userAddress){
            throw new UserException([
                'msg' => '用户收货地址不存在，下单失败',
                'errorCode' => 60001
            ]);
        }
        return $userAddress->toArray();
    }
    // 创建订单
    private function createOrder($snap)
    {
        Db::startTrans();
        try{
            $orderNo = $this->makeOrderNo();
            $order = new \app\api\model\Order();
            $order->user_id = $this->uid;
            $order->order_no = $orderNo;
            $order->total_count = $snap['orderPrice'];
            $order->snap_img = $snap['snapImg'];
            $order->snap_name = $snap['snapName'];
            $order->snap_address = $snap['snapAddress'];
            $order->snap_items = json_encode($snap['pStatus']);
            $order->save();

            $orderID = $order->id;
            $create_time = $order->create_time;
            // 添加订单商品关联
            foreach ($this->oProducts as &$p){
                $p['order_id'] = $orderID;
            }

            $orderProduct = new OrderProduct();
            $orderProduct->saveAll($this->oProducts);
            Db::commit();
            return [
                'order_no' =>$orderNo,
                'order_id' => $orderID,
                'create_tiem' => $create_time
            ];

        }catch (\Exception $e){
            Db::rollback();
            throw $e;
        }
    }

    // 生成订单号
    public static function makeOrderNo(){
        $yCode = array('A','B','C','D','E','F','G','H','I','J');
        $orderSn =
            $yCode[intval(date('Y')) - 2017]
            . strtoupper(dechex(date('m')))
            . date('d')
            . substr(time() , -5)
            . substr(microtime(),2,5)
            .sprintf('%02d',rand(0, 99));
        return $orderSn;
    }

    // 提供一个对外访问的检测订单库存量函数
    public function checkOrderStock($orderID){
        // 订单商品
        $oProducts = OrderProduct::where('order_id', '=', $orderID)
            ->select();
        $this->oProducts = $oProducts;
        // 真实商品
        $this->products = $this->getProductByOrder($oProducts);

        $status = $this->getOrderStatus();
        return $status;
    }
}