<?php
/**
 * User: Hola
 * Date: 2018/12/12
 * Time: 14:18
 */
namespace Home\Controller;
use Think\Controller\RestController;
Class ReportController extends RestController
{

    public function index()
    {
        $apiList = [
            ["江苏移动","http://223.111.206.234:8001/qingke/"],
            ["江西移动","http://223.111.206.250:7010/qingke/"],
            ["天津联通","http://202.99.114.74:7010/qingke/"],
            ["河南移动","http://117.158.46.25:7010/qingke/"],
        ];
        $reportString = "渠道,日期,新增订购用户,日活跃用户,下单数,进入的已经订购过的用户,播放次数,今天前的订购用户播放次数,退订用户\r\n";
        try {
            $redis = new \Redis();
            $redis->connect(C("REDIS.HOST"), intval(C("REDIS.PORT")));
            $key = "report_xes";
            $keyRefund = "report_refund_";
            if($redis->exists($key)){
                $reportString = $redis->get($key);
            }else{
                foreach ($apiList as $api){
                    $reportString .= $api[0]."\r\n";
                    $r = curl($api[1]."report/user");
                    $userList = json_decode($r,true);
                    foreach ($userList as $v){
                        $reportString .=  implode(",",$v);
                        if($api[0] == "江苏移动"){
                            if($redis->exists($keyRefund.$v["day"])){
                                $refundCnt = $redis->get($keyRefund.$v["day"]);
                            }else{
                                $refundCnt = curl($api[1]."report/refund?day=".$v["day"]);
                                $redis->set($keyRefund.$v["day"],$refundCnt);
                                $expireTime = mktime(0,0,0,date("m"),date("d")+3,date("Y")) - time();
                                $redis->setTimeout($keyRefund.$v["day"],$expireTime);
                            }
                            $reportString .= ",".$refundCnt;
                        }
                        $reportString .=  "\r\n";
                    }
                }
                if(!empty($reportString)){
                    $redis->set($key,$reportString);
                    $expireTime = mktime(0,0,0,date("m"),date("d")+1,date("Y")) - time();
                    $redis->setTimeout($key,$expireTime);
                }
            }
            $redis->close();
        }catch ( \RedisException $e){

        }
        $reportString = iconv('utf-8', 'gb2312', $reportString);
        header("Content-type:application/octet-stream");
        header("Accept-Ranges:bytes");
        header("Accept-Length:".strlen($reportString));
        header("Content-Disposition: attachment; filename=report(".date("Y-m-d_H").").csv");
        echo $reportString;
    }


    public function user()
    {
        $u = D("ReportUser")->order("id desc")->limit(3)->select();
        $s = array();
        foreach ($u as $v){
            $s[] = array(
                "channel"=>$v["channel"],
                "day"=>$v["day"],
                "new_customer"=>$v["new_customer"],
                "today_user"=>$v["today_user"],
                "today_order"=>$v["today_order"],
                "customer"=>$v["customer"],
                "play"=>$v["play"],
                "customer_play"=>$v["customer_play"]
            );
        }
        $this->response($s, 'json');
    }

    public function refund()
    {
        $day = I("day","");
        if(empty($day))exit("0");

        $sql = "select count(1) as cnt from (select distinct(uid) from customer_order where create_time like'".$day." %' and status = 3) t";
        $r = D("Order")->query($sql);
        echo $r[0]["cnt"]>0?$r[0]["cnt"]:0;
    }
}