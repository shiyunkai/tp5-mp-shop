<?php

namespace app\api\model;

class Product extends BaseModel
{
    // pivot是默认的中间表属性
    protected $hidden = ['create_time','update_time','delete_time','pivot','from','category_id'];

    // 读取器
    public function getMainImgUrlAttr($value, $data){
        return $this->prefixUrlAttr($value,$data);
    }

    public function imgs(){
        return $this->hasMany('ProductImage','product_id','id');
    }

    public function properties(){
        return $this->hasMany('ProductProperty','product_id','id');
    }

    public static function getMostRecent($count){
        $products = self::limit($count)
            ->order('create_time desc')
            ->select();
        return $products;
    }

    public static function getProductsByCategoryID($categoryID){
        $products = self::where('category_id','=',$categoryID)
            ->select();
        return $products;
    }

    public static function getProductDetail($id){

/*        $product = self::with(['imgs.imgUrl'])
            ->with(['properties'])
            ->find($id);*/
        // 闭包函数构建查询器，对关联模型的关联字段进行排序
        $product = self::with([
            'imgs' => function($query){
                $query->with(['imgUrl'])
                    ->order('order', 'asc');
            }
        ])
            ->with(['properties'])
            ->find($id);

        return $product;
    }
}
