<?php
/**
 * User: Hola
 * Date: 2018/8/10
 * Time: 16:13
 */
namespace Home\Controller;
use Think\Controller;
Class HeController extends Controller
{
    public function index()
    {
        header('HTTP/1.1 404 Not Found');
        // 确保FastCGI模式下正常
        header('Status:404 Not Found');
    }
    /*
     * 重庆和教育APP入口
     */
    public function cq()
    {
        set_time_limit(0);
        $openid = I("openid","");
        if(LOG_TRACK){
            $log = date("Y-m-d H:i:s ")."He-cq# ";
            $log .= "method:".REQUEST_METHOD."  ";
            $log .= "openid:".$openid."  ";
            $log .= "post:".json_encode($_POST)."  \r\n";
            error_log($log,3,RUNTIME_PATH."auth.log");
        }
        $token = D("HeCq")->enter($openid);

        $url = "http://".$_SERVER["HTTP_HOST"]."/cqh5/index.html?channel=sxhe&token=".$token;
        header("Location:".$url);
        exit();
    }

}