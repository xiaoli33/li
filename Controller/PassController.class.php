<?php
/**
 * User: 浪子
 * Date: 2018-05-23
 * Time: 18:03
 */
namespace Home\Controller;
use Think\Controller\RestController;
Class PassController extends RestController
{
    public function index()
    {
        $this->response(null,null,404);
    }

    public function notify()
    {
        set_time_limit(0);
        if(LOG_TRACK){
            $log = date("Y-m-d H:i:s ")."notify# ";
            $log .= "method:".REQUEST_METHOD."  ";
            $log .= "get:".json_encode($_GET)."  ";
            $log .= "put:".file_get_contents("php://input")."  ";
            $log .= "post:".json_encode($_POST)."  \r\n";
            error_log($log,3,RUNTIME_PATH."notify.log");
        }

        $channel = $_GET["channel"];

        switch($channel)
        {
            case "tianjin":
                $this->tj();
                break;
            case "jiangxi":
                $this->jx();
                break;
            case "liaoning":
            	$this->ln();
            	break;
        }

    }

    /*
     * 江西移动
     */
    private function jx()
    {
        $outTradeNo = I("orderId","");
        $orderNo = I("extStr","");
        $product = I("billingIndex","");
        $money = I("billingFee","");

        echo "ok";
        if(function_exists("fastcgi_finish_request")){
            fastcgi_finish_request();
        }
        D("Order")->orderOK($orderNo,1,$money,$outTradeNo,"bill",$product);
        exit("ok");
    }


    /*
     * 天津联通
     */
    private function tj()
    {
        $Act = I("Act","",strval);
        $AppId = I("AppId","",strval);
        $ThirdAppId = I("ThirdAppId","",strval);
        $Uin = I("Uin","",strval);
        $ConsumeStreamId = I("ConsumeStreamId","",strval);
        $TradeNo = I("TradeNo","",strval);
        $Subject = I("Subject","",strval);
        $Amount = sprintf("%.2f",I("Amount",0,intval));
        $ChargeAmount = sprintf("%.2f",I("ChargeAmount",0,intval));
        $ChargeAmountIncVAT = sprintf("%.2f",I("ChargeAmountIncVAT",0,intval));
        $ChargeAmountExclVAT = sprintf("%.2f",I("ChargeAmountExclVAT",0,intval));
        $Country = I("Country","",strval);
        $Currency = I("Currency","",strval);
        $Note = I("Note","",strval);
        $Note = empty($Note)?"null":$Note;
        $TradeStatus = I("TradeStatus","",strval);
        $CreateTime = I("CreateTime","",strval);
        $Share = sprintf("%.2f",I("Share",0,intval));
        $IsTest = I("IsTest","",strval);
        $PayChannel = I("PayChannel","",strval);
        $Sign = I("Sign","",strval);

        $s = array("ErrorCode"=>1,"ErrorDesc"=>"Success");

        if(empty($TradeNo)) {
            $this->response($s,'json');
        }
        $order = D("Order")->where(array("order_no"=>$TradeNo))->find();
        if(empty($order)){
            $this->response($s,'json');
        }
        $appInfo = array(
            "xesqk"=>"xesjf"
        );
        $AppKey = "xesjf";

        $str = "{{$Act}}{{$AppId}}{{$ThirdAppId}}{{$Uin}}{{$ConsumeStreamId}}{{$TradeNo}}{{$Subject}}{{$Amount}}{{$ChargeAmount}}{{$ChargeAmountIncVAT}}{{$ChargeAmountExclVAT}}{{$Country}}{{$Currency}}{{$Note}}{{$TradeStatus}}{{$CreateTime}}{{$Share}}{{$IsTest}}{{$AppKey}}";

        if(md5($str) != $Sign){
            $s["ErrorCode"] = 5;
            $s["ErrorDesc"] = "sign error";
            $this->response($s,'json');
        }
        $this->response($s,'json',200,false);
        if(function_exists("fastcgi_finish_request")){
            fastcgi_finish_request();
        }
        if($TradeStatus == "completed"){
            D("Order")->orderOK($order["order_no"],1,0,$ConsumeStreamId);
        }elseif($TradeStatus == "failed"){
            D("Order")->orderFail($order["order_no"],$ConsumeStreamId);
        }

        $this->response($s,'json');
    }

    
    
    function pkcs5Unpad($text) {
	    $pad = ord ( $text {strlen ( $text ) - 1} );
	    if ($pad > strlen ( $text ))
	        return false;
	    if (strspn ( $text, chr ( $pad ), strlen ( $text ) - $pad ) != $pad)
	        return false;
	    return substr ( $text, 0, - 1 * $pad );
	}
    public function ln(){
    	set_time_limit(0);
        $raw = file_get_contents("php://input");
        $req = json_decode($raw,true);
        
        $serviceid = isset($req["serviceid"])?$req["serviceid"]:"";
		$data = isset($req["data"])?$req["data"]:"";
    	$list = array(
    		"ALN3991612"=>array(
    			"key"=>"335b0ffe917a4fb7f7c66f7c6f6bb514",
    			"iv"=>"05b84cab5dc43a12942c8c1a70fbaee8"
    		),
    		"ALN3991611"=>array(
    			"key"=>"938d486bcdf7a64885e67c42e19da13a",
    			"iv"=>"caf646bb43915ba4171ebb48944094df"
    		),
    		"ALN3991610"=>array(
    			"key"=>"e6d40d3608f292eb337b6ed7bf148af3",
    			"iv"=>"eb214c8039791a56230e5a9242be714e"
    		)
    	);
    	if(empty($data)){
    		return;
    	}
//  	$serviceid = "ALN3991610";
//  	$data = "pjyiY12X1NwhxNi3zs-PBhdTZrB0pJV2INd1SzJR0RGBvI42PoYtZ5aBGrFtd9zyelTRsqahjim88FY5LjeaqC2i3BnWf-tnx2gr7bbzfS_DuFvuRXPzFI-YTLUO3EIcOidg7F4t1FrGIu9RO_HyN_HhXNzniUMa4HTuWLxLkPOmya_zIvYqYAwoQUImjUrJuVb7IdqbevuOKEM-ie3R29fX6z_6zpJQvu33hZGXAU8_MSokYvQw1lxi_A5GlvRVI6NM_TTLH5BMYToNVs18zhqRvEzzSmAzcQWnG-OyTSk.";
		$data = str_replace(["-","_","."],["+","/","="],$data);
		$key = $list[$serviceid]["key"];
		$iv = $list[$serviceid]["iv"];
		$data = base64_decode($data);
		$key = pack ( 'H*', $key );
		$iv = pack ( 'H*', $iv );
		$str = mcrypt_decrypt ( MCRYPT_RIJNDAEL_128, $key, $data, MCRYPT_MODE_CBC, $iv );
		$r = $this->pkcs5Unpad($str);
		$message = json_decode($r,true);
		$this->response(array("sid"=>$message["SID"], "status"=>1), "json", 200, false);
//		$this->response(array("data"=>$message), "json", 200, false);
		$username = $message["UID"];
		$product = $message["SERVICEID"];
		if(empty($username) || empty($product)){
			return;
		}
		$type = $message["OPTYPE"];
		if($type == 1 || $type == 3 || $type == 4){
			$status = 1;
		}else {
			$status = 0;
		}
		$order_no = D("Order")->produceOrderNo();
		$oderMessage = array(
			"app_id"=>"a_b0lethuc0vgrwvxj",
			"order_no"=>$order_no,
			"uid"=>$username,
			"product"=>$product,
			"description"=> $message["COSTTYPE"],
			"money"=>$message["COST"],
			"create_time"=>date("Y-m-d H:i:s"),
			"status"=>$status
		);
		$addOrder = D("CustomerOrder")->add($oderMessage);
		$lt = time() + 2592000;
		if($status == 0){
			$lt = time();
		}
		$start = date("Y-m-d H:i:s", $lt);
		$productMessage = array(
			"uid"=>$username,
    		"product"=>$product,
			"expire_time"=>$start
		);
		$rp = D("CustomerProduct")->where($productMessage)->find();
		if(!empty($rp)){
			$productMessage["id"] = $rp["id"];
		}
		$addProduct = D("CustomerProduct")->edit($data);
    }
}