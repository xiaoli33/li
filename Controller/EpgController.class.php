<?php
/**
 * User: 浪子
 * Date: 2018-07-12
 * Time: 9:22
 */
namespace Home\Controller;
use Think\Controller\RestController;
Class EpgController extends RestController
{

    var $app;
    public function _initialize()
    {
        if(in_array(strtolower(ACTION_NAME),["transmit"])){
            return true;
        }
        $params = array_merge($_GET, $_POST);
        $sign = $params['sign'];
        if (empty($sign) || empty($params["appId"])) {
            $s = array("error" => -3);
            $this->response($s, 'json');
        } else {
            $app = D("App")->where(array("app_id"=>$params["appId"]))->find();
            if($app["status"] != 1){
                $s = array("error" => -3);
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
                $s = array("error" => -3);
                $this->response($s, 'json');
            }
        }
    }

    public function index()
    {

    }

    public function login()
    {
        $appId = I("appId","");
        $channel = I("channel","");
        $userId = I("userId","");
        $platform = I("platform","");
        $terminalId = I("terminalId","");
        $token = I("token","");
        $copyRightId = I("copyRightId","");
        $province = I("province","");


        $s = array("error" => -1);
        if(empty($userId)){
            $this->response($s, 'json');
        }
        $miguInfo = array();
        $platform && $miguInfo["platform"] = $platform;
        $terminalId && $miguInfo["terminalId"] = $terminalId;
        $token && $miguInfo["token"] = $token;
        $copyRightId && $miguInfo["copyRightId"] = $copyRightId;
        $province && $miguInfo["province"] = $province;

        $uid = D("User")->login($userId,$appId,$channel,$miguInfo);
        if(!empty($uid)){
            D("Dm")->login(["appId"=>$appId,"channel"=>$channel,"uid"=>$uid]);
            $s = array(
                "error"=>0,
                "body"=>array(
                    "token"=>D("Customer")->accessToken($uid,true),
                    "uid"=>$uid,
                    "isHellion"=>0
                )
            );
            $isHellion = D("Customer")->blacklistSearch($userId);
            if($isHellion){
                $s["body"]["isHellion"] = 1;
            }
        }

        $this->response($s, 'json');
    }

    public function promotion()
    {
        $tag = I("tag","");
        $grade = I("grade",0,intval);

        $s = array("error" => -1);

        if(empty($tag)){
            $this->response($s, 'json');
        }

        $list = D("Promotion")->getList($tag,$grade);
        if(!empty($list)){
            $s = array(
                "error"=>0,
                "body"=>$list
            );
        }

        $this->response($s, 'json');
    }

    public function category()
    {
        $appId = I("appId","");
        $genre = I("genre","");//subject|grade
        $categoryId = I("categoryId",0,intval);
        $run = I("run","");

        $s = array("error" => -1);

        $list = D("Category")->getList($genre,$categoryId,$appId,$run);
        if(!empty($list)){
            $s = array(
                "error"=>0,
                "body"=>array(
                    "list"=>$list
                )
            );
            if($categoryId >0){
                $category = D("Category")->where(array("id"=>$categoryId))->find();
                $s["body"]["category"] = array(
                    "title"=>$category["title"],
                    "subTitle"=>empty($category["sub_title"])?"":$category["sub_title"],
                    "genre"=>empty($category["genre"])?"":$category["genre"],
                    "description"=>empty($category["description"])?"":$category["description"]
                );
            }
        }

        $this->response($s, 'json');
    }

    public function program_list()
    {
        $categoryId = I("categoryId",0,intval);
        $page = I("page",1,intval);
        $count = I("count",20,intval);
        $uid = I("uid", "");
        $searchId = I("searchId",0,intval);

        $s = array("error" => -1);

        $token = I("token","");
        if(!empty($token)){
            $uid = D("Customer")->uid($token);
            if(empty($uid)){
                $s["error"] = -2;
                $this->response($s, 'json');
            }
        }

        if(empty($categoryId)){
            $s = array("error" => -1);
            $this->response($s, 'json');
        }

        if($searchId >0){
            $map = array("category_id"=>$categoryId,"program_id"=>$searchId);
            $cl = D("CategoryProgram")->where($map)->find();
            if(!empty($cl)){
                unset($map["program_id"]);
                $map["status"] = 1;
                $map["sort_num"] = array("lt",$cl["sort_num"]);
                $preCnt = D("ProgramView")->where($map)->order("sort_num asc,id asc")->count();
                $map["sort_num"] = $cl["sort_num"];
                $diff = D("ProgramView")->where($map)->field("id,program_id")->order("sort_num asc,id asc")->select();
                foreach($diff as $v){
                    $preCnt += 1;
                    if($v["program_id"] == $searchId){
                        break;
                    }
                }
                $page = ceil($preCnt/$count);
            }
        }
        $categoryInfox = D("Category")->where(array("id"=>$categoryId))->find();
		if($categoryInfox["mount"] == 2){
			$listc = D("Category")->getList("",$categoryInfox["id"],$categoryInfox["app_id"],"");
			$categoryId = $listc[0]["categoryId"];
		}
        $body = D("ProgramView")->getList($categoryId,$page,$count);
        if(!empty($body["list"])){
            $categoryInfo = D("Category")->where(array("id"=>$categoryId))->find();
            $isTable = M()->query('SHOW TABLES LIKE "collection"');
            if($isTable){
            	$collection = D("Collection")->where(array("uid"=>$uid, "category_id"=>I("categoryId",0,intval), "status"=>1))->find();            	
            }
            $mycollection = $collection?1:0;
            $body["category"] = array(
                "title"=>$categoryInfo["title"]?$categoryInfo["title"]:"",
                "genre"=>$categoryInfo["genre"]?$categoryInfo["genre"]:"",
                "description"=>$categoryInfo["description"]?$categoryInfo["description"]:"",
                "collection"=>$mycollection
            );
            if(!empty($listc)){
            	$body["categoryList"] = $listc;
            }
            $body["currentPage"] = $page;
            $s = array(
                "error"=>0,
                "body"=>$body
            );
        }

        $this->response($s, 'json');
    }

    public function episode_list()
    {
        $programId = I("programId",0,intval);
        $page = I("page",1,intval);
        $count = I("count",20,intval);

        $s = array("error" => -1);

        if(empty($programId)){
            $s = array("error" => -1);
            $this->response($s, 'json');
        }

        $body = D("EpisodeView")->getList($programId,$page,$count);
        if(!empty($body["list"])){
            $s = array(
                "error"=>0,
                "body"=>$body
            );
        }

        $this->response($s, 'json');
    }

    public function program()
    {
        $programId = I("programId",0,intval);

        $s = array("error" => -1);

        if(empty($programId)){
            $s = array("error" => -1);
            $this->response($s, 'json');
        }

        $program = D("Program")->getDetail($programId);
        if(!empty($program)){
            $s = array(
                "error"=>0,
                "body"=>$program
            );
        }

        $this->response($s, 'json');
    }

    public function movie()
    {
        $uid = I("uid","");
        $programId = I("programId",0,intval);

        $s = array("error" => -1);

        $token = I("token","");
        if(!empty($token)){
            $uid = D("Customer")->uid($token);
            if(empty($uid)){
                $s["error"] = -2;
                $this->response($s, 'json');
            }
        }

        if(empty($programId)){
            $s = array("error" => -1);
            $this->response($s, 'json');
        }

        $body = D("Movie")->getList($programId);
        if(!empty($body["list"])){
            $s = array(
                "error"=>0,
                "body"=>$body
            );
        }

        $this->response($s, 'json',200,false);
        if(function_exists("fastcgi_finish_request")){
            fastcgi_finish_request();
        }
        if(!empty($uid)){
            D("Movie")->playHis($uid,$programId);
        }
    }

    public function play_history()
    {
        $uid = I("uid","");

        $s = array("error" => -1);

        $token = I("token","");
        if(!empty($token)){
            $uid = D("Customer")->uid($token);
            if(empty($uid)){
                $s["error"] = -2;
                $this->response($s, 'json');
            }
        }

        if(empty($uid) || D("User")->check($uid) == false){
            $s = array("error" => -2);
            $this->response($s, 'json');
        }

        $list = D("Movie")->getHisList($uid);
        if(!empty($list)){
            $s = array(
                "error"=>0,
                "body"=>array("list"=>$list)
            );
        }

        $this->response($s, 'json');
    }

    public function program_auth()
    {
        $appId = I("appId","");
        $channel = I("channel","");
        $uid = I("uid","");
        $programId = I("programId",0,intval);

        $s = array("error" => -1);

        $token = I("token","");
        if(!empty($token)){
            $uid = D("Customer")->uid($token);
            if(empty($uid)){
                $s["error"] = -2;
                $this->response($s, 'json');
            }
        }

        if(empty($uid) || empty($programId)){
            $s = array("error" => -1);
            $this->response($s, 'json');
        }
        $user = D("User")->getInfo($uid);
        if(empty($user)){
            $s = array("error" => -2);
            $this->response($s, 'json');
        }

        //->>检查白名单
        $whiteTag = D("Customer")->whiteSearch($user["username"]);
        if($whiteTag){
            $s["error"] = 0;
            $this->response($s, 'json');
        }
        //<<-end

        $authResult = false;
        $contentId = [
            $programId."@program"
        ];
        $cp = D("CategoryProgram")->where(array("program_id"=>$programId))->select();
        foreach($cp as $v){
            $contentId[] = $v["category_id"]."@category";
        }
        $productList = D("ProductView")->productList($contentId,null,$appId,true);
        if(empty($productList)){
            $authResult = true;
        }else{
            $pIds = array();
            $ppvIds = array();
            foreach ($productList as $v){
                if($v["authType"] == 1){
                    $ppvIds[] = $v["product"];
                }else{
                    $pIds[] = $v["payCode"];
                }
                if($v["status"] == 1){
                    $s["body"]["list"][] = $v;
                }
            }
            $authResult = D("CustomerProduct")->productsAuth($user,$productList,$programId);
        }
        if(!empty($authResult)){
            $s["error"] = 0;
        }else{
            $s["error"] = -1;
        }

        $s["body"]["transactionID"] = D("Dm")->transactionID();

        $this->response($s, 'json');
    }

    public function order()
    {
        $appId = I("appId","");
        $channel = I("channel","");
        $uid = I("uid","");
        $product = I("product","");
        $programId = I("programId",0,intval);
        $backUrl = I("backUrl", "");

        $s = array("error" => -1);

        $token = I("token","");
        if(!empty($token)){
            $uid = D("Customer")->uid($token);
            if(empty($uid)){
                $s["error"] = -2;
                $this->response($s, 'json');
            }
        }

        if(empty($uid)){
            $s = array("error" => -1);
            $this->response($s, 'json');
        }
        $user = D("User")->getInfo($uid);
        if(empty($user)){
            $s = array("error" => -2);
            $this->response($s, 'json');
        }
        $payJump = D("Promotion")->payJump();
        $payMode = ($payJump==1)?"jump":"bill";
        if(empty($product)){
            $orderNo = D("Order")->hnOrder($uid,$channel,$appId);
        }else{
            $orderNo = D("Order")->preOrder($uid,$channel,$appId,$product,$programId,$payMode);
        }
        if(!empty($orderNo)){
            $s["error"] = 0;
            $s["body"] = array(
                "payJump"=>$payJump,
                "transactionID"=>D("Dm")->transactionID(),
                "orderNo"=>$orderNo,
                "notifyUrl"=>C("API_DOMAIN")."pass/notify/channel/".$channel."/od/".$orderNo
            );
            if(!empty($backUrl)){
            	$s["body"]["backUrl"] = C("API_DOMAIN")."back/notify/channel/".$channel."/od/".$orderNo."?backUrl=".$backUrl;
            }
        }

        $this->response($s, 'json');
    }

    public function order_status()
    {
        $appId = I("appId","");
        $channel = I("channel","");
        $orderNo = I("orderNo","");

        $s = array("error" => -1);

        if(empty($orderNo)){
            $this->response($s, 'json');
        }

        $order = D("Order")->where(["order_no"=>$orderNo])->field("id,create_time,status")->find();
        if(!empty($order["id"])){
            if(($order["status"] == 0) && ((time() - strtotime($order["create_time"])) > 1800)){
                $order["status"] = -1;//timeout
            }
            $s["error"] = 0;
            $s["body"] = array(
                "status"=>$order["status"]
            );
        }

        $this->response($s, 'json');
    }

    public function paid()
    {
        $channel = I("channel","");
        $orderNo = I("orderNo");
        $status = I("status");
        $fee = I("fee",0);
        $outTradeNo = I("outTradeNo","");

        $s = ["error"=>-1];

        if(empty($orderNo) || empty($status)){
            $this->response($s,"json");
        }
//        0待支付1正常2失败3退费4支付中
        $statusList = ["NOT_FOUND"=>0,"TRADE_PAYING"=>4,"TRADE_TIMEOUT"=>0,"TRADE_SUCCESS"=>1,"TRADE_FAIL"=>2];
        $statusInt = isset($statusList[$status])?$statusList[$status]:0;

        $s["error"] = 0;
        $this->response($s,"json",200,false);
        if(function_exists("fastcgi_finish_request")){
            fastcgi_finish_request();
        }
        if(strpos($orderNo,"M") === 0) {
            D("Order")->hnOrderOk($orderNo,$statusInt,$fee,$outTradeNo);
        }else{
            D("Order")->orderOK($orderNo,$statusInt,$fee,$outTradeNo);
        }
    }

    public function product_list()
    {
        $appId = I("appId","");
        $programId = I("programId",0,intval);
        $courseId = I("courseId",0,intval);
        $platform = I("platform","");
        if(empty($platform)){
            $platform = I("channel","");
        }

        $s = array("error" => -1);

        if(empty($programId) && empty($courseId)){
            $s = array("error" => -1);
            $this->response($s, 'json');
        }
        $cp = [];
        if($programId >0){
            $program = D("Program")->where(array("id"=>$programId))->field("id,parent_code,type")->find();
            if($program["type"] == 1 && !empty($program["parent_code"])){
                $programId = D("Program")->where(array("code"=>$program["parent_code"]))->getField("id");
            }
            $contentId = [
                $programId."@program"
            ];
            $cp = D("CategoryProgram")->where(array("program_id"=>$programId))->select();
        }
        if($courseId >0){
            $contentId = [
                $courseId."@course"
            ];
            $cp = D("AiCategoryCourse")->where(array("course_id"=>$courseId))->select();
        }

        foreach($cp as $v){
            $contentId[] = $v["category_id"]."@category";
        }
        $list = D("ProductView")->productList($contentId,$platform,$appId);
        if(!empty($list)){
            $s = array(
                "error"=>0,
                "body"=>array(
                    "list"=>$list,
                    "transactionID" => D("Dm")->transactionID()
                )
            );
        }

        $this->response($s, 'json');
    }

    public function product_info()
    {
        $product = I("product","");

        $s = array("error" => -1);

        if(empty($product)){
            $s = array("error" => -1);
            $this->response($s, 'json');
        }

        $info = D("Product")->info($product);
        if(!empty($info)){
            $s = array(
                "error"=>0,
                "body"=>$info
            );
        }

        $this->response($s, 'json');
    }

    public function product_auth()
    {
        $uid = I("uid","");
        $product = I("product","");
        $programId = I("programId",0,intval);
        $channel = I("channel","");

        $s = array("error" => -1);

        $token = I("token","");
        if(!empty($token)){
            $uid = D("Customer")->uid($token);
            if(empty($uid)){
                $s["error"] = -2;
                $this->response($s, 'json');
            }
        }
		
        if(empty($uid) || empty($product)){
            $s = array("error" => -1);
            $this->response($s, 'json');
        }
        
        $user = D("User")->getInfo($uid);
        if(empty($user)){
            $s = array("error" => -2);
            $this->response($s, 'json');
        }

        //->>检查白名单
        $whiteTag = D("Customer")->whiteSearch($user["username"]);
        if($whiteTag){
            $s["error"] = 0;
            $this->response($s, 'json');
        }
        
        $contentId = 0;
        $p = D("Product")->where(array("product"=>$product))->find();
        if(empty($p)){
            $s["error"] = 0;
            $s["body"] = array(
                "expire"=>""
            );
            $this->response($s, 'json');
        }
        if($p["mode"] ==1){ //ppv
            if(empty($programId)){
                $this->response($s, 'json');
            }
            $contentId = $programId."@program";
        }elseif($p["mode"] ==2){ //pvod
            $contentId = 0;
        }

        $expire_time = D("CustomerProduct")->auth($uid,$product,$contentId);
        if(!empty($expire_time)){
            $s["error"] = 0;
            $s["body"] = array(
                "expire"=>$expire_time
            );
        }

        $this->response($s, 'json');
    }

    public function my_product_list()
    {
        $appId = I("appId","");
        $uid = I("uid","");

        $s = array("error" => -1);

        $token = I("token","");
        if(!empty($token)){
            $uid = D("Customer")->uid($token);
            if(empty($uid)){
                $s["error"] = -2;
                $this->response($s, 'json');
            }
        }

        $list = D("CustomerProduct")->myList($uid,$appId);
        if(!empty($list)){
            $s = array(
                "error"=>0,
                "body"=>array(
                    "list"=>$list
                )
            );
        }

        $this->response($s, 'json');
    }

    public function transmit()
    {
        $url = urldecode($_POST["url"]);
        $head = urldecode($_POST["head"]);
        $body = urldecode($_POST["body"]);
        $contentType = urldecode($_POST["contentType"]);

        $header = array();
        $h = json_decode($head,true);
        foreach ($h as $k=>$v){
            $header[] = $k.":".$v;
        }
        if(!empty($contentType)){
            $header[] = "Content-Type:".$contentType;
        }

        $result = curl($url,$body,0,true,$header);
        if(!empty($result["header"])){
            $respHeader = explode("\n",$result["header"]);
            foreach ($respHeader as $v){
                header($v);
            }
        }
        if(!empty($result["body"])){
            echo $result["body"];
        }else{
            echo "";
        }
    }
    //四川搜索
	public function searchProgram()
	{
		$search = I("search", "");
		$page = I("page",1,intval);
        $count = I("count",12,intval);
        $back = array("error"=> -1);
		$map["search_title"] = array('like','%'.$search.'%');
		$map["status"] = 1;
		$r = D("Program")->where($map)->limit(($page-1)*$count, $count)->select();
		$total = D("Program")->where($map)->count();
		if($r){
			$list = array();
			foreach($r as $val){
				$list[] = array("title"=>$val["title"],"img"=>domain_img($val["img_path"]),"programId"=>$val["id"]);
			}
			$back = array(
				"error"=> 0,
				"body"=>array(
					"list"=>$list,
					"total"=>ceil($total/$count)
				)
			);
		}
		$this->response($back, 'json');
	}
	//四川我的收藏
	public function myCollection()
	{
		$uid = I("uid", "");
    	$page = I("page", 1, intval);
    	$count = I("count", 12, intval);
    	$back = array("error"=>-1);
    	$sql = "select b.id as programId,b.img_path as img,b.title as title from collection as a, program as b where a.status=1 and a.category_id=b.id and a.uid=".$uid." limit ".($page-1)*$count.",".$count.";";
    	$sqltotal = "select count(1) as cnt from collection as a, program as b where a.status=1 and a.category_id=b.id and a.uid=".$uid.";";
    	$r = D("Collection")->query($sql);
    	$total = D("Collection")->query($sqltotal);
    	if($r){
    		$list = array();
    		foreach($r as $val){
				$list[] = array("title"=>$val["title"],"img"=>domain_img($val["img"]),"programId"=>$val["programid"]);
			}
    		$back = array(
				"error"=> 0,
				"body"=>array(
					"list"=>$list,
					"total"=>ceil($total[0]["cnt"]/$count)
				)
			);
    	}
    	$this->response($back, 'json');
	}
	//四川清空收藏
	public function cleanCollection()
	{
		$uid = I("uid", "");
		$r = D("Collection")->where(array("uid"=>$uid))->save(array("status"=>0));
		$back = array("error"=> 0);
		$this->response($back, 'json');
	}
	public function ordered_product()
	{
		$uid = I("uid","");
        $s = array("error" => -1);
        $token = I("token","");
        if(!empty($token)){
            $uid = D("Customer")->uid($token);
        }
        if(empty($uid)){
            $s["error"] = -2;
            $this->response($s, 'json');
        }
        $r = D("CustomerProduct")->getMy($uid);
        if($r){
        	$s["error"] = 0;
        	$s["body"]["list"] = $r;
        }
        $this->response($s, 'json');
	}

}