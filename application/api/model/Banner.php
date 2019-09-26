<?php


namespace app\api\model;

class Banner extends BaseModel
{
    // 隐藏属性
    protected $hidden = ['update_time','delete_time'];

    //protected $visible =

    public function items(){
        // 第三个参数为当前模型id,关联模型是BannerItem
        return $this->hasMany('BannerItem','banner_id','id');
    }

    public static function getBannerById($id){

        $banner = self::with(['items','items.img'])
            ->find($id);
        return $banner;

        /*//1. 原生方式操作数据库
        $result = Db::query('select * from banner_item where banner_id=?',[$id]);*/

        //2. 查询构造器,只有select()、find()、update()、delete()、insert()才会生成sql语句执行查询
        // $result = Db::table('banner_item')->where('banner_id','=',$id)->select();

        // 闭包写法
        /*$result = Db::table('banner_item')
            ->where(function($query) use ($id){
                $query->where('banner_id','=',$id);
            })->select();

        return $result;*/
    }

}