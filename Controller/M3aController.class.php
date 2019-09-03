<?php
/**
 * User: 浪子
 * Date: 2018-07-02
 * Time: 15:57
 */
namespace Home\Controller;
use Think\Controller\RestController;
Class M3aController extends RestController
{

    public function _initialize()
    {
        $actionNSign = array("util_log","util_implode","util_implode_status","util_do_implode","util_explode","util_explode_blacklist","blacklist_search","blacklist_add","blacklist_cls","whitelist_search","whitelist_add","whitelist_cls","whitelist_all", "customer_product_import","util_explode_whitelist");
        if(in_array(strtolower(ACTION_NAME),$actionNSign)){
           return true;
        }
        $params = array_merge($_GET, $_POST);
        $sign = $params['sign'];
        if (empty($sign)) {
            $s = array("error" => -2);
            $this->response($s, 'json');
        } else {
            unset($params['sign']);
            ksort($params);
            $tmp = array();
            foreach ($params as $v) {
                if($v != ""){
                    $tmp[] = rawurldecode($v);
                }
            }
            $key = "duf5bzsu";
            $str = implode("&", $tmp) . "&" . $key;
            $sign2 = md5($str);
            if ($sign != $sign2) {
                $s = array("error" => -2);
                $this->response($s, 'json');
            }
        }
    }

    public function index()
    {

    }

    public function util_implode()
    {
        set_time_limit(0);
        $type = I("type","program");//category|program|media|blacklist|image
        $s = array("error"=>-1);
        if(IS_POST)
        {
            $saveName = ($type == "image")?"":array('date','YmdHis');
            $config = array(
                'rootPath'=>C("SRC_PATH"),
                'savePath'=>'',
                'maxSize'=>0,
                'exts'=>array("csv","zip"),
                'saveName'=>$saveName,
                'autoSub'=>false,
                'subName'=>false
            );
            $upload = new \Think\Upload($config);
            $info = $upload->upload();
            if(!$info)
            {
                $s["info"] = $upload->getError();
            }else{
                $nonce = mt_rand(10000,99999);
                $origin_file  = C("SRC_PATH").str_replace(array("../","./"),"",$info['file']['savepath']).$info['file']['savename'];
                try{
                    $redis = new \Redis();
                    $redis->connect(C("REDIS.HOST"),intval(C("REDIS.PORT")));
                    while ($redis->exists("program_implode_".$nonce)){
                        $nonce = mt_rand(10000,99999);
                    }
                    $redis->set("program_implode_".$nonce,json_encode(["m"=>$type,"s"=>$origin_file]));
                    $redis->setTimeout("program_implode_".$nonce,86400);
                    $redis->close();
                }catch (\RedisException $e){

                }
                $s["error"] = 0;
                $s["body"] = array("nonce"=>$nonce);
                $this->response($s, 'json',200,false);
                if(function_exists("fastcgi_finish_request")){
                    fastcgi_finish_request();
                }
                $url = C("API_LOCAL")."/m3a/util_do_implode?nonce=".$nonce;
                curl($url,null,4000);
                exit();
            }
        }
        $this->response($s, 'json');
    }

    public function util_do_implode()
    {
        set_time_limit(0);
        $nonce = I("nonce","");
        $type = "";
        $origin_file = "";
        try{
            $redis = new \Redis();
            $redis->connect(C("REDIS.HOST"),intval(C("REDIS.PORT")));
            $val = $redis->get("program_implode_".$nonce);
            if(!empty($val)){
                $r = json_decode($val,true);
                $type = isset($r["m"])?$r["m"]:"";
                $origin_file = isset($r["s"])?$r["s"]:"";
            }
            $redis->close();
        }catch (\RedisException $e){

        }
        echo "Done";
        if(function_exists("fastcgi_finish_request")){
            fastcgi_finish_request();
        }
        if(empty($type) || empty($origin_file))exit();
        $logs = "";
        switch ($type){
            case "category":
                $logs = D("Category")->categoryImplode($origin_file);
                break;
            case "program":
                $logs = D("Program")->programImplode($origin_file);
                break;
            case "media":
                $logs = D("Movie")->mediaImplode($origin_file);
                break;
            case "blacklist":
                $logs = D("Customer")->blacklistImplode($origin_file);
                break;
            case "image":
                $logs = $this->archive($origin_file);
                break;
        }
        try{
            $redis = new \Redis();
            $redis->connect(C("REDIS.HOST"),intval(C("REDIS.PORT")));

            $redis->set("implode_log_".$nonce,$logs);
            $redis->setTimeout("implode_log_".$nonce,60);

            $redis->set("program_implode_".$nonce,json_encode(["m"=>$type,"s"=>$origin_file,"e"=>"done"]));
            $redis->setTimeout("program_implode_".$nonce,60);

            $redis->close();
        }catch (\RedisException $e){

        }
        @unlink($origin_file);
        exit();
    }

    private function archive($zipFile)
    {
        Vendor("pclzip");
        $logs = "";
        $zip = new \PclZip($zipFile);
        $result = $zip->extract(PCLZIP_OPT_PATH,C("SRC_PATH"));
        if ($result == 0) {
            $logs .= "Error : " . $zip->errorInfo(true);
        }else{
            $i = 0;
            foreach ($result as $v){
                $logs .= $v["stored_filename"]." : ".$v["status"]."\r\n";
                $i++;
            }
            $logs .= "Total: ".$i."\r\n";
        }
        return $logs;
    }

    public function util_log()
    {
        $nonce = I("nonce","");
        $stream = "";
        try{
            $redis = new \Redis();
            $redis->connect(C("REDIS.HOST"),intval(C("REDIS.PORT")));
            $stream = $redis->get("implode_log_".$nonce);
            $redis->close();
        }catch (\RedisException $e){

        }
        header("Content-type:application/octet-stream");
        header("Accept-Ranges:bytes");
        header("Accept-Length:".strlen($stream));
        header("Content-Disposition: attachment; filename=implodeLog".date("YmdHis").".txt");
        echo $stream;
    }

    public function util_implode_status()
    {
        $nonce = I("nonce","");
        $s = array("error"=>0);
        try{
            $redis = new \Redis();
            $redis->connect(C("REDIS.HOST"),intval(C("REDIS.PORT")));
            if($redis->exists("implode_log_".$nonce)){
                $s["error"] = 0;
                $s["body"]["log"] = C("API_DOMAIN")."m3a/util_log?nonce=".$nonce;
            }else{
                $s["error"] = -1;
                $s["body"]["log"] = "NotFound";
            }
            $redis->close();
        }catch (\RedisException $e){

        }
        $this->response($s, 'json');
    }

    public function util_explode()
    {
        $start_time = I("start_time","");
        $end_time = I("end_time","");

        $stream = D("Program")->programExplode($start_time,$end_time);

        header("Content-type:application/octet-stream");
        header("Accept-Ranges:bytes");
        header("Accept-Length:".strlen($stream));
        header("Content-Disposition: attachment; filename=".date("YmdHis")."-explode.csv");
        echo $stream;
    }

    public function util_explode_blacklist()
    {
        set_time_limit(0);
        $stream = D("Customer")->blacklistExplode();

        header("Content-type:application/octet-stream");
        header("Accept-Ranges:bytes");
        header("Accept-Length:".strlen($stream));
        header("Content-Disposition: attachment; filename=".date("YmdHis")."-blacklist-explode.csv");
        echo $stream;
    }

    public function blacklist_search()
    {
        $userId = I("userId","");
        $s = array("error"=>-1);

        $r = D("Customer")->blacklistSearch($userId);
        if($r){
            $s["error"] = 0;
        }

        $this->response($s, 'json');
    }

    public function blacklist_add()
    {
        $userId = I("userId","");
        $s = array("error"=>-1);

        $r = D("Customer")->blacklistAdd($userId);
        if($r){
            $s["error"] = 0;
        }

        $this->response($s, 'json');
    }

    public function blacklist_cls()
    {
        $userId = I("userId","");
        $s = array("error"=>-1);

        $r = D("Customer")->blacklistCls($userId);
        if($r){
            $s["error"] = 0;
        }

        $this->response($s, 'json');
    }

    public function whitelist_search()
    {
        $userId = I("userId","");
        $s = array("error"=>-1);

        $r = D("Customer")->whiteSearch($userId);
        if($r){
            $s["error"] = 0;
            $s["body"] = $r;
        }

        $this->response($s, 'json');
    }

    public function whitelist_add()
    {
        $time = I("time","");
        $userId = I("userId","");
        $s = array("error"=>-1);

        $r = D("Customer")->whiteAdd($userId,$time);
        if($r){
            $s["error"] = 0;
        }

        $this->response($s, 'json');
    }

    public function whitelist_cls()
    {
        $type = I("type","order");
        $userId = I("userId","");
        $s = array("error"=>-1);

        $r = D("Customer")->whiteDel($userId);
        if($r){
            $s["error"] = 0;
        }

        $this->response($s, 'json');
    }

    public function whitelist_all()
    {
        $s = array("error"=>-1);
		$page = I("page", 1);
		$acount = I("acount", 20);
        $list = D("Customer")->whiteExplode(true);
        $r = array_slice($list, ($page-1)*$acount,$acount, true);
        if(!empty($r)){
            $s["error"] = 0;
            $s["body"]["list"] = $r;
            $s["body"]["total"] = count($list);
        }

        $this->response($s, 'json');
    }
    
    public function util_explode_whitelist()
    {
        set_time_limit(0);
        $stream = D("Customer")->whiteExplode();

        header("Content-type:application/octet-stream");
        header("Accept-Ranges:bytes");
        header("Accept-Length:".strlen($stream));
        header("Content-Disposition: attachment; filename=".date("YmdHis")."-blacklist-explode.csv");
        echo $stream;
    }

    public function app_list()
    {
        $page = I("page",1);
        $count = I("count",20);

        $s = array("error"=>-1);

        $r = D("App")->getList($page,$count);
        if(!empty($r["list"])){
            $s = array(
                "error"=>0,
                "body"=>array(
                    "list"=>$r["list"],
                    "total"=>$r["total"]
                )
            );
        }

        $this->response($s, 'json');
    }

    public function app_detail()
    {
        $id = I("id",0);

        $s = array("error"=>-1);

        if($id >0){
            $app = D("App")->where(array("id"=>$id))->find();
            if(!empty($app["id"])){
                $s = array(
                    "error"=>0,
                    "body"=>$app
                );
            }
        }

        $this->response($s, 'json');
    }

    public function app_edit()
    {
        $id = I("id",0);

        $s = array("error"=>-1);

        $data = array();
        if($id >0){
            $data["id"] = $id;
        }
        if(isset($_REQUEST["description"])){
            $data["description"] = $_REQUEST["description"];
        }
        if(isset($_REQUEST["status"])){
            $data["status"] = $_REQUEST["status"];
        }
        $r = D("App")->edit($data);
        if($r){
            $s = array(
                "error"=>0,
                "body"=>array(
                    "id"=>$r
                )
            );
        }

        $this->response($s, 'json');
    }

    public function app_delete()
    {
        $id = I("id",0);

        $s = array("error"=>-1);

        if($id >0){
            $r = D("App")->cls($id);
            if($r){
                $s["error"] = 0;
            }
        }

        $this->response($s, 'json');
    }

    public function product_list()
    {
        $page = I("page",1);
        $count = I("count",20);
		$appId = I("appId", "");
		$product = I("product","");
		$channel = I("channel","");
        $s = array("error"=>-1);

        $map = [];
		if(!empty($appId)){
			$map["app_id"] = $appId;
		}
        if(!empty($product)){
            $map["product"] = $product;
        }
        if(!empty($channel)){
            $map["channel"] = $channel;
        }
        $r = D("Product")->getList($map,$page,$count);
        if(!empty($r["list"])){
            $s = array(
                "error"=>0,
                "body"=>array(
                    "list"=>$r["list"],
                    "total"=>$r["total"]
                )
            );
        }

        $this->response($s, 'json');
    }
    public function product_img()
    {
    	$id = I("id", 0);
		$back = array("error"=> -1);
		if($id > 0){
			$config = array(
	                'rootPath'=>C("SRC_PATH"),
	                'savePath'=>'xueersi/',
	                'maxSize'=>0,
	                'exts'=>array("jpg","png","gif","jpeg"),
	                'saveName'=>array('date','YmdHis'),
	                'subName'=>""
	            );
	        $upload = new \Think\Upload($config);
	        $info = $upload->upload();
	        if($info){
	        	$img_file  = str_replace(array("../","./"),"",$info['file']['savepath']).$info['file']['savename'];
	        	$r = D("Product")->where(array("id"=>$id))->save(array("img_path"=>$img_file));
	        	if($r){
	        		$back = array("error"=> 0,"flile"=>domain_img($img_file)); 	        		
	        	}
	        }
		}
        $this->response($back, "json");
    }

    public function product_detail()
    {
        $id = I("id",0);

        $s = array("error"=>-1);

        if($id >0){
            $app = D("Product")->where(array("id"=>$id))->find();
            if(!empty($app["id"])){
                $s = array(
                    "error"=>0,
                    "body"=>$app
                );
            }
        }

        $this->response($s, 'json');
    }

    public function product_edit()
    {
        $id = I("id",0);

        $s = array("error"=>-1);

        $data = array();

        if($id >0){
            $data["id"] = $id;
        }
        isset($_REQUEST["product"]) && $data["product"] = $_REQUEST["product"];
        isset($_REQUEST["pay_code"]) && $data["pay_code"] = $_REQUEST["pay_code"];
        isset($_REQUEST["channel"]) && $data["channel"] = $_REQUEST["channel"];
        isset($_REQUEST["app_id"]) && $data["app_id"] = $_REQUEST["app_id"];
        isset($_REQUEST["mode"]) && $data["mode"] = intval($_REQUEST["mode"]);
        isset($_REQUEST["type"]) && $data["type"] = intval($_REQUEST["type"]);
        isset($_REQUEST["genre"]) && $data["genre"] = $_REQUEST["genre"];
        isset($_REQUEST["price"]) && $data["price"] = intval($_REQUEST["price"]);
        isset($_REQUEST["sales"]) && $data["sales"] = intval($_REQUEST["sales"]);
        isset($_REQUEST["expire_days"]) && $data["expire_days"] = $_REQUEST["expire_days"];
        isset($_REQUEST["description"]) && $data["description"] = $_REQUEST["description"];
        isset($_REQUEST["status"]) && $data["status"] = intval($_REQUEST["status"]);
		isset($_REQUEST["sort_num"]) && $data["sort_num"] = intval($_REQUEST["sort_num"]);

        $r = D("Product")->edit($data);
        if($r){
            $s = array(
                "error"=>0,
                "body"=>array(
                    "id"=>$r
                )
            );
        }

        $this->response($s, 'json');
    }

    public function product_delete()
    {
        $id = I("id",0);

        $s = array("error"=>-1);

        if($id >0){
            $r = D("Product")->cls($id);
            if($r){
                $s["error"] = 0;
            }
        }

        $this->response($s, 'json');
    }

    public function content_list()
    {
        $product_id = I("product_id",0);
        $page = I("page",1);
        $count = I("count",20);

        $s = array("error"=>-1);

        $r = D("ProductContent")->getList($product_id,$page,$count);
        if(!empty($r["list"])){
            $s = array(
                "error"=>0,
                "body"=>array(
                    "list"=>$r["list"],
                    "total"=>$r["total"]
                )
            );
        }

        $this->response($s, 'json');
    }

    public function content_edit()
    {
        $product_id = I("product_id",0);
        $content_id = I("content_id","");
        $title = I("title","",htmlspecialchars_decode);

        $s = array("error"=>-1);

        if($product_id>0 && !empty($content_id)){
            $ids = explode(",",$content_id);
            $ts = json_decode($title,true);
            $data = array();
            foreach ($ids as $id){
                if(empty($id))continue;
                $data[] = array(
                    "product_id"=>$product_id,
                    "content_id"=>$id,
                    "title"=>isset($ts[$id])?$ts[$id]:null
                );
            }
            if(!empty($data)){
                $r = D("ProductContent")->addAll($data);
                if($r){
                    $cnt = D("ProductContent")->where(array("product_id"=>$product_id))->count();
                    $cnt = $cnt?$cnt:0;
                    D("Product")->where(array("id"=>$product_id))->save(array("content_cnt"=>$cnt));
                    $s = array(
                        "error"=>0
                    );
                }
            }
        }

        $this->response($s, 'json');
    }

    public function content_delete()
    {
        $product_id = I("product_id",0);
        $content_id = I("content_id","");

        $s = array("error"=>-1);

        if($product_id>0 && !empty($content_id)){
            $r = D("ProductContent")->cls($product_id,$content_id);
            if($r){
                $s = array(
                    "error"=>0
                );
            }
        }

        $this->response($s, 'json');
    }

    public function customer_list()
    {
        $appId = I("appId", "");
        $uid = I("uid","");
        $username = I("username","");
        $page = I("page",1);
        $count = I("count",20);

        $s = array("error"=>-1);

        $map = array();
        if(!empty($appId)){
            $map["app_id"] = $appId;
        }
        if(!empty($username)){
            $map["username"] = $username;
        }
        if(!empty($uid)){
            $map["uid"] = $uid;
        }
        $r = D("Customer")->getList($map,$page,$count);
        if(!empty($r["list"])){
            $s = array(
                "error"=>0,
                "body"=>array(
                    "list"=>$r["list"],
                    "total"=>$r["total"]
                )
            );
        }

        $this->response($s, 'json');
    }

    public function customer_order_list()
    {
        $appId = I("appId", "");
        $start_time = I("start_time","");
        $end_time = I("end_time","");
        $order_no = I("order_no","");
        $uid = I("uid","");
        $out_trade_no = I("out_trade_no","");
        $page = I("page",1);
        $count = I("count",20);

        $s = array("error"=>-1);

        $map = array();
        !empty($appId) && $map["app_id"] = $appId;
        !empty($order_no) && $map["order_no"] = $order_no;
        !empty($uid) && $map["uid"] = $uid;
        !empty($out_trade_no) && $map["out_trade_no"] = $out_trade_no;
        isset($_REQUEST["status"]) && $map["status"] = $_REQUEST["status"];
        if(!empty($start_time)){
            $end_time = empty($end_time)?date("Y-m-d"):$end_time;
            $end_time = $end_time." 23:59:59";
            $map["create_time"] = array(array("egt",$start_time),array("elt",$end_time));
        }

        $r = D("Order")->getList($map,$page,$count);
        if(!empty($r["list"])){
            $s = array(
                "error"=>0,
                "body"=>array(
                    "list"=>$r["list"],
                    "total"=>$r["total"],
                    "count"=>$r["count"]
                )
            );
        }

        $this->response($s, 'json');
    }

    public function customer_product_list()
    {
        $uid = I("uid","");
        $page = I("page",1);
        $count = I("count",20);

        $s = array("error"=>-1);

        $map = array();
        !empty($uid) && $map["uid"] = $uid;

        $r = D("CustomerProduct")->getList($map,$page,$count);
        if(!empty($r["list"])){
            $s = array(
                "error"=>0,
                "body"=>array(
                    "list"=>$r["list"],
                    "total"=>$r["total"]
                )
            );
        }

        $this->response($s, 'json');
    }

    public function customer_product_edit()
    {
        $id = I("id",0,intval);
        $uid = I("uid","");
        $product = I("product","");
        $content_id = I("content_id","");
        $expire_time = I("expire_time","");

        $s = array("error"=>-1);

        if(empty($uid) || empty($product)){
            $this->response($s, 'json');
        }

        $data = array();

        if($id >0){
            $data["id"] = $id;
        }
        !empty($uid) && $data["uid"] = $uid;
        !empty($product) && $data["product"] = $product;
        !empty($content_id) && $data["content_id"] = htmlspecialchars_decode($content_id);
        !empty($expire_time) && $data["expire_time"] = $expire_time;

        $r = D("CustomerProduct")->edit($data);
        if($r){
            $s = array(
                "error"=>0
            );
        }

        $this->response($s, 'json');
    }

    public function customer_product_delete()
    {
        $id = I("id",0);

        $s = array("error"=>-1);

        if($id>0){
            $r = D("CustomerProduct")->cls($id);
            if($r){
                $s = array(
                    "error"=>0
                );
            }
        }

        $this->response($s, 'json');
    }
    
    public function customer_product_import()
    {
    	set_time_limit(0);
    	$saveName = ($type == "image")?"":array('date','YmdHis');
    	$r = array("error"=>-1);
        $config = array(
            'rootPath'=>C("SRC_PATH"),
            'savePath'=>'',
            'maxSize'=>0,
            'exts'=>array("csv","zip"),
            'saveName'=>$saveName,
            'autoSub'=>false,
            'subName'=>false
        );
        $upload = new \Think\Upload($config);
        $info = $upload->upload();
        if(!$info){
            $r["info"] = $upload->getError();
        	$this->response($r, "json");
        }else{
        	$origin_file  = C("SRC_PATH").str_replace(array("../","./"),"",$info['file']['savepath']).$info['file']['savename'];
        	$r["error"] = 0;
        	$save = D("Customer")->whiteImplode($origin_file);
        	$r["body"] = $save;
        	@unlink($origin_file);
        	$this->response($r, "json");
        }
    }
}