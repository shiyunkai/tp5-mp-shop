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

    protected function isMobile($value, $rule='', $data='',$field=''){
        if (!is_numeric($value)) {
            return false;
        }
        $mobile_preg_expresion = '#^13[\d]{9}$|^14[5,7]{1}\d{8}$|^15[^4]{1}\d{8}$|^17[0,6,7,8]{1}\d{8}$|^18[\d]{9}$#';
        if(!preg_match($mobile_preg_expresion, $value)){
            return false;
        }
        return true;
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
            $newArray[$key] = $arrays[$key];
        }
        return $newArray;
    }


}