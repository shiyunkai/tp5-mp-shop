<?php

namespace app\api\model;

use think\Model;

class BaseModel extends Model
{

    protected function prefixUrlAttr($value, $data){
        if($data['from'] == 1){
            // 如果图片是来自本地
            // 给图片url加上前缀，图片配置,setting: extra/setting.php
            return config('setting.img_prefix').$value;
        }
        return $value;
    }
}
