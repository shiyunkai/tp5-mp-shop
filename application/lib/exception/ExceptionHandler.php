<?php


namespace app\lib\exception;

use Exception;
use think\exception\Handle;
use think\Log;

/**
 *  全局异常处理类
 * Class ExceptionHandler
 * @package app\lib\exception
 */
class ExceptionHandler extends Handle
{

    /*
     *  http状态码
     */
    private $code;

    private $msg;

    private $errorCode;



    /**
     *  覆盖render方法, 所有代码中抛出的异常都会被render方法渲染
     * @param Exception $e
     * @return \think\Response|void
     */
    public function render(Exception $e)
    {
        if($e instanceof  BaseException){
            // 如果是自定义异常
            $this->code = $e->code;
            $this->msg = $e->msg;
            $this->errorCode = $e->errorCode;
        }else{
            // 判断是否处于调试模式
            if(config('app_debug')){
                // return default error page
                return parent::render($e);
            }else{
                $this->code = 500;
                $this->msg = '服务器内部错误';
                $this->errorCode = 999;
                $this->recordError($e);
            }
        }

        $result = [
            'msg'=> $this->msg,
            'error_code'=> $this->errorCode,
            'request_url'=>request()->url()
        ];

        return json($result,$this->code);
    }

    private function recordError(Exception $e){
        Log::record($e->getMessage(),'error');
    }

}