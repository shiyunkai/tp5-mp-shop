<?php


namespace app\api\validate;


use app\lib\exception\ParameterException;
use think\Exception;
use think\Validate;
use think\Request;

class BaseValidate extends Validate
{

    public function goCheck(){
        $request = Request::instance();
        $params = $request->param();
        $result = $this->batch()->check($params);
        if(!$result){
            throw new ParameterException([
                'msg'=>$this->getError()
            ]);

        }else{
            return true;
        }
    }

    protected function isPositiveInteger($value, $rule='', $data='',$field=''){
        if(is_numeric($value) && is_int($value+0) && ($value+0)>0){
            return true;
        }else{
            return false;
        }
    }

    protected function isNotEmpty($value, $rule='', $data='',$field=''){
        if(!empty($value)){
            return true;
        }else{
            return false;
        }
    }

    public function getDataByRule($arrays){
        if (array_key_exists('user_id', $arrays) |
        array_key_exists('uid', $arrays)){
            // 不允许包含user_id或者uid,防止恶意覆盖user_id覆盖
            throw new ParameterException(
                [
                    'msg' => '参数中包含有非法的参数名user_id或者uid'
                ]
            );
        }

        $newArray = [];
        foreach ($this->rule as $key => $value){
            $newArray = $arrays[$key];
        }
        return $newArray;
    }
}