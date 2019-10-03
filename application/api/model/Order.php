<?php


namespace app\api\model;


class Order extends BaseModel
{
    protected $hidden = [
        'user_id', 'delete_time', 'update_time'
    ];

    // 开启自动写入create_time, update_time, delete_time
    protected $autoWriteTimestamp = true;

    // 如果不数据库中不想使用create_time, update_time, delete_time命名
    // 可以重新定义
    //protected $createTime = 'create_timestamp';

    public function getSnapItemsAttr($value){
        if(empty($value)){
            return null;
        }
        // 将json字符串转换成json对象
        return json_decode($value);
    }

    public function getSnapAddressAttr($value){
        if(empty($value)){
            return null;
        }
        return json_decode($value);
    }

    public static function getSummaryByUser($uid, $page=1, $size){
        $pagingData = self::where('user_id','=',$uid)
            ->order('create_time desc')
            ->paginate($size, true, ['page' => $page]);
        return $pagingData;
    }

}