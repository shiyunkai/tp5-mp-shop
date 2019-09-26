<?php

namespace app\api\model;

class BannerItem extends BaseModel
{

    // 隐藏属性
    protected $hidden = ['id','img_id','banner_id','update_time','delete_time'];

    public function img(){
        // 一对一关联, 拥有外键的一方定义关系用belognsTo, 没有外键的一方定义关系用hasOne
        return $this->belongsTo('Image','img_id','id');
    }
}
