<?php
/**
 * User: Hola
 * Date: 2018/8/17
 * Time: 15:54
 */
namespace Home\Controller;
use Think\Controller\RestController;
Class DmController extends RestController
{
	const RN = "\r\n";
	const ZONE_NO = "SP_BJXRS_01";
	const DS = "|,|";
	const FILE_PATH = "/home/xueersi_data/data/xueersi/";

    public function play()
    {
        set_time_limit(0);
        $appId = I("appId","");
        $channel = I("channel","");
        $uid = I("uid","");
        $tag = I("tag",""); //start end
        $mac = I("mac", "");
        $programId = I("programId",0,intval);

        $s = ["error"=>0];
        $this->response($s, 'json',200,false);
        if(function_exists("fastcgi_finish_request")){
            fastcgi_finish_request();
        }

        $info = array(
            "uid"=>$uid,
            "programId"=>$programId,
            "appId"=>$appId,
            "channel"=>$channel,
            "tag"=>$tag,
            "mac"=>$mac,
            "time"=>date("Y-m-d H:i:s")
        );
        if($tag == "start"){
            D("Dm")->playStart($info);
        }elseif($tag == "end"){
            D("Dm")->playEnd($info);
        }
    }



    public function link()
    {
        $appId = I("appId","");
        $channel = I("channel","");
        $uid = I("uid","");
        $query = I("query","");

        $s = ["error"=>0];
        $this->response($s, 'json',200,false);
        if(function_exists("fastcgi_finish_request")){
            fastcgi_finish_request();
        }
        if(!empty($query)){
            $info = array(
                "appId"=>$appId,
                "channel"=>$channel,
                "uid"=>$uid,
                "query"=>html_entity_decode($query)
            );
            D("Dm")->link($info);
        }
    }

    public function promotion()
    {
        $appId = I("appId","");
        $channel = I("channel","");
        $uid = I("uid","");
        $tag = I("tag","");
        $title = I("title","");

        $s = ["error"=>0];
        $this->response($s, 'json',200,false);
        if(function_exists("fastcgi_finish_request")){
            fastcgi_finish_request();
        }
        if(!empty($tag)){
            $info = array(
                "appId"=>$appId,
                "channel"=>$channel,
                "uid"=>$uid,
                "tag"=>$tag,
                "title"=>urldecode($title)
            );
            D("Dm")->promotion($info);
        }
        if($channel == "sichuan"){
        	$user_ip = empty($_SERVER["HTTP_X_REAL_IP"])?$_SERVER["REMOTE_ADDR"]:$_SERVER["HTTP_X_REAL_IP"];
        	D("ReportUser")->panelClick($user_ip, $tag, $uid);
        }
    }
    
    public function exposure()
	{
		$start = I("start", "");
		$start = $start*1;
		$start = date("Y-m-d H:i:s", $start);
		$end = I("end", "");
		$end = $end*1;
		$end = date("Y-m-d H:i:s", $end);
		$uid = I("uid", "");
		$page = I("page", "");
		$tagurl = I("tagurl", "http://112.18.251.151:7008/app/portal.html");
		$lt = time();
		$s = ["error"=>0];
		$this->response($s, 'json',200,false);
		$pageType = "详情页";
		
		if(strstr($tm, "portal")){
			$pageType = "首页";
		}
		$r = D("Customer")->where(array("uid"=>$uid))->find();
		$log_raw = array(
			date("Ymd", mktime(0,0,0,date("m",$lt),date("d",$lt),date("Y",$lt))),
			$r["username"],
			self::ZONE_NO,
			$page,
			$pageType,
			$tagurl,
			"链接",
			$start,
			$end
		);
		$raws = implode(self::DS,$log_raw);
		error_log($raws.self::RN,3,self::FILE_PATH."exposure/"."exposure_".date("Ymd",mktime(0,0,0,date("m",$lt),date("d",$lt),date("Y",$lt))).".txt");
	}

}