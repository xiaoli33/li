<?php
namespace Home\Controller;
use Think\Controller\RestController;
//use Overtrue\Pinyin\Pinyin;
Class BannerController extends RestController
{
	public function _initialize()
    {
        $params = array_merge($_GET, $_POST);
        $sign = $params['sign'];
        if (empty($sign) || empty($params["appId"])) {
            $s = array("error" => -1);
            $this->response($s, 'json');
        } else {
            $app = D("App")->where(array("app_id"=>$params["appId"]))->find();
            if($app["status"] != 1){
                $s = array("error" => -1);
                $this->response($s, 'json');
            }
            $this->app = $app;
            $key = $app["app_key"];//$key = "5y86ykj3";

            unset($params['sign']);
            ksort($params);
            $tmp = array();
            foreach ($params as $v) {
                $tmp[] = rawurldecode($v);
            }
            $str = implode("&", $tmp) . "&" . $key;
            $sign2 = md5($str);
            if ($sign != $sign2) {
                $s = array("error" => -1);
                $this->response($s, 'json');
            }
        }
    }
	public function getbanner()
	{
		$userId = I("uid");
		$back = array(
			"error"=> 0,
			"body"=>array()
		);
		$state = 1;
		$user = D("Customer")->where(array("uid"=>$userId, "status"=>1))->find();
		if(!empty($user)){
			$state = D("RaffleHis")->getState($user);
			$back["error"] = 0;
		}
		$r = D("Raffle")->getList();
        if(empty($r)){
            $back["error"] = -1;
        }else{
        	$back["body"] = array(
                    "prize"=>$r,
                    "state"=>$state
                );
        }
        $this->response($back, 'json');
	}
	public function lottery_draw()
    {
        $ac = I("channel","",strval);
        $userId = I("uid","",strval);

        $s = array("error"=>-1);

        $user = D("Customer")->where(array("uid"=>$userId, "status"=>1))->find();
        if(empty($user)){
            $s["error"] = -1;
            $s["info"] = "error token";
        }
        $r = D("Raffle")->draw($user,$ac);
        if(!empty($r)){
            $s = array(
                "error"=>0,
                "body"=>array(
                    "prize"=>$r
                )
            );
        }

        $this->response($s, 'json');
    }
    
    public function lottery_contact()
    {
        $ac = I("channel","",strval);
        $userId = I("uid","",strval);
        $mobile = safe_input(I("mobile","",strval));

        $s = array("error"=>-1);

        $user = D("Customer")->where(array("uid"=>$userId, "status"=>1))->find();
        if(empty($user) || empty($mobile)){
            $s["error"] = -1;
            $s["info"] = "error param";
        }

        $r = D("RaffleHis")->updateContact($user,$ac,$mobile);
        if($r){
            $s["error"] = 0;
        }

        $this->response($s, 'json');
    }
    
    public function lottery_mine()
    {
        $ac = I("channel","",strval);
        $userId = I("uid","",strval);

        $s = array("error"=>-1);

        $user = D("Customer")->where(array("uid"=>$userId, "status"=>1))->find();
        if(empty($user)){
            $s["error"] = -1;
            $s["info"] = "error param";
        }

        $r = D("RaffleHis")->mine($user,$ac);
        if(!empty($r)){
            $s = array(
                "error"=>0,
                "body"=>array(
                    "mine"=>$r
                )
            );
        }

        $this->response($s, 'json');
    }
    
    public function lottery_his()
    {
        $ac = I("channel","",strval);

        $s = array("error"=>-1);

        $r = D("RaffleHis")->his($ac);
        if(!empty($r)){
            $s = array(
                "error"=>0,
                "body"=>array(
                    "his"=>$r
                )
            );
        }

        $this->response($s, 'json');
    }
}
?>