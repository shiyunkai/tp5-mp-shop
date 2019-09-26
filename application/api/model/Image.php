<?php

namespace app\api\model;


class Image extends BaseModel
{
    // 隐藏属性
    protected $hidden = ['id','from','update_time','delete_time'];

//    /*
//     * 模型读取器，
//     * 以get开头,Attr结尾
//     * $value是属性的值, 框架自动传入
//     * $data 是所有的属性及其属性的值
//     */
//    public function getUrlAttr($value, $data){
//        if($data['from'] == 1){
//            // 如果图片是来自本地
//            // 给图片url加上前缀，图片配置,setting: extra/setting.php
//            return config('setting.img_prefix').$value;
//        }
//        return $value;
//    }

    public function getUrlAttr($value, $data){
        return $this->prefixUrlAttr($value,$data);
    }

}
