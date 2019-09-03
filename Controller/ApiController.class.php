<?php
namespace Home\Controller;
use Think\Controller\RestController;
use Overtrue\Pinyin\Pinyin;
Class ApiController extends RestController
{
	public function _initialize()
	{
		$headers = array();
		if (!function_exists('getallheaders')) {
		    foreach ($_SERVER as $name => $value) {
		        if (substr($name, 0, 5) == 'HTTP_') {
		            $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
		        }
		    }
		}else{
		    $headers = getallheaders();
		}
		$Authorization = $headers["Authorization"];
		header("Authorization: ".$Authorization);
		if(in_array(strtolower(ACTION_NAME),["sys_menu"])){
			$auth = explode(",", $Authorization);
			if($auth[0] != $this->userToken() || time() > $auth[1]){
				$this->response(array("err"=>-2), "json");
			}
		}
	}
	private function userToken()
	{
		return hash_hmac("sha256",$_SERVER["REMOTE_ADDR"].",".$_SERVER["HTTP_USER_AGENT"],"123");
	}
	public function verify()  
   	{  
    	$Verify = new \Think\Verify();  
    	$Verify->fontSize = 30;
       	$Verify->entry();
//     	error_log(date("Y-m-d H:i:s ")."verify:".json_encode($_SESSION)."\r\n",3,"debug.log");  
   	}
   	
	public function login()
	{
		$user = I("post.account", "");
		$pwd = I("post.pwd", "");
		$code = I("post.code", "");
		$r = D("SysUser")->where(array("account"=>$user))->find();
		if(!empty($r)){
			if($pwd == $r["pwd"]){
				$Verify = new \Think\Verify();
//				error_log(date("Y-m-d H:i:s ")."verify:".json_encode($_SESSION)."\r\n",3,"debug.log");
				if($Verify->check($code)){
					$token = $this->userToken();
					$back = array(
						"err"=>0,
					    "body"=>array(
					        "uid"=>$r["id"],
					        "name"=>$r["account"]
					    )
					);					
					header("Authorization: ".$token.",".(time()+60*60*24));
					session(array('name'=>'syslogin'));
				}else {
					$back = array("err"=>3);
				}
			} else {
				$back = array("err"=>1);
			}
		}else {
			$back = array("err"=>1);
		}
		$this->response($back, "json");
	}
	
	public function pwd()
	{
		$id = I("id", "");
		$pwd1 = I("p1","");
		$pwd2 = I("p2", "");
		$user = D("SysUser")->where(array("id"=>$id,"pwd"=>$pwd1))->find();
		if(!empty($user)){
			$r = D("SysUser")->where(array("id"=>$id))->save(array("pwd"=>$pwd2));
		}
		if($r){
			$back = array("err"=>0);
		}else{
			$back = array("err"=>1);
		}
		$this->response($back, "json");
	}
	
	public function sys_menu()
	{
		$user = I("id", "");
		if(empty($user)){
			$menu = D("SysMenu")->getmenuall();
		}else{
			$groupid = D("SysUser")->where(array("id"=>$user))->field("group_id")->find();
			if($groupid["group_id"] == 1){
				$menu = D("SysMenu")->getmenus();
			}else {
				$menu = D("SysGroupMenu")->where(array("group_id"=>$groupid["group_id"]))->getmenu();			
			}
		}
		$this->response($menu, "json");
	}
	
	public function menu_detail()
	{
		$id = I("id", "");
		$menu = D("SysMenu")->where(array("id"=>$id))->field("id,title,param,sort_num,status,parent_id")->find();
		if($menu){
			$back = array("err"=>0,"body"=>$menu);
		}else{
			$back = array("err"=>1);
		}
		$this->response($back,"json");
	}
	
	public function group_menu()
	{
		$id = I("id", "");
		$r = D("SysGroupMenu")->where(array("group_id"=>$id))->getlist();
		if($r){
			$back = array("err"=>0,"body"=>$r);
		}else{
			$back = array("err"=>2);
		}
		$this->response($back,"json");
	}
	public function set_menu()
	{
		$id = I("id", "");
		$ids = I("ids", array());
		if(!empty($id) && !empty($ids)){
			$del = D("SysGroupMenu")->where(array("group_id"=>$id))->delete();
			$r = D("SysGroupMenu")->setmenu($id,$ids);
		}
		if($r){
			$back = array("err"=>0);
		}else{
			$back = array("err"=>1);
		}
		$this->response($back, "json");
	}
	
	public function menu_status()
	{
		$id = I("id", "");
		$status = I("status", 0);
		$r = D("SysMenu")->where(array("id"=>$id))->save(array("status"=>$status));
		if($r){
			$back = array("err"=>0);
		}else{
			$back = array("err"=>1);
		}
		$this->response($back, "json");	
	}
	
	public function menu_dele()
	{
		$id = I("id", "");
		$r = D("SysMenu")->where(array("id"=>$id))->delete();
		if($r){
			$back = array("err"=>0);
		}else{
			$back = array("err"=>1);
		}
		$this->response($back, "json");	
	}
	
	public function menu_sort()
	{
		$ids = I("ids", array());
		$back = array("lis"=>$ids);
		if(!empty($ids)){
			$r = D("SysMenu")->sortmenu($ids);
		}
		if($r){
			$back = array("err"=>0);
		}else{
			$back = array("err"=>1);
		}
		$this->response($back, "json");	
	}
	
	public function menu_edit()
	{
		$id = I("id", "");
		$param = I("param", "");
		$message = array(
			"title"=>I("title", ""),
			"parent_id"=>I("parent", 0),
			"status"=>I("status", 0),
			"controller"=>I("controller", ""),
			"action"=>I("action", "")
		);
		if(!empty($param)){
			$message["param"] = $param;
		}
		if(!empty($message["title"])){
			if(empty($id)){
				$r = D("SysMenu")->add($message);
			}else {
				$r = D("SysMenu")->where(array("id"=>$id))->save($message);
			}
		}
		if($r){
			$back = array("err"=>0);
		}else{
			$back = array("err"=>1);
		}
		$this->response($back, "json");	
	}
	
	public function user_list()
	{
		$cl = I("post.count", "20");
		$page = I("post.page", "1");
		$status = I("status", "");
		$group = I("group", "");
		$account = I("account", "");
		$map = array();
		$map["_logic"] = "AND";
		if($status != ""){
			$map["sys_user.status"] = array("EQ", $status);
		}
		if(!empty($group)){
			$map["group_id"] = array("EQ", $group);
		}
		if(!empty($account)){
			$map["account"] = array("like", "%".$account."%");
		}
		$r = D("SysUser")->where($map)->join("sys_group on  sys_user.group_id = sys_group.id", "LEFT")->limit(($page-1)*$cl, $cl)->userlist();
		$total = D("SysUser")->where($map)->join("sys_group ON sys_group.id = sys_user.group_id")->count();
		if($r){
			$back = array(
				"err"=> 0,
				"body"=>array(
					"total"=>ceil($total/$cl),
					"list"=>$r
				)
			);
		}else {
			$back = array("err"=> 2);
		}
		$this->response($back, "json");
	}
	
	public function user_edit()
	{
		$id = I("id", "");
		$account = I("account", "");
		$status = I("status", "");
		$group = I("group","");
		$pwd = I("pwd","");
		$message = array(
			"account"=>$account,
			"group_id"=>$group,
			"status"=>$status,
			"utime"=>time()
		);
		if(!empty($pwd)){
			$message["pwd"] = md5($pwd);
		}
		if(empty($id)){
			$message["stime"] = time();
			$h = D("SysUser")->where(array("account"=>$account))->find();
			if(empty($h)){
				$r = D("SysUser")->add($message);
			}else{
				$back = array("err"=>5);
				$this->response($back, "json");
			}
		}else {
			$r = D("SysUser")->where(array("id"=>$id))->save($message);
		}
		if($r){
			$back = array("err"=>0);
		}else{
			$back = array("err"=>1);
		}
		$this->response($back, "json");
	}
	
	public function user_dele()
	{
		$id = I("id", "");
		$r = D("SysUser")->where(array("id"=>$id))->delete();
		if($r){
			$back = array("err"=>0);
		}else{
			$back = array("err"=>1);
		}
		$this->response($back, "json");
	}
	public function user_status()
	{
		$id = I("id", "");
		$status = I("status", 0);
		$r = D("SysUser")->where(array("id"=>$id))->save(array("status"=>$status,"utime"=>time()));
		if($r){
			$back = array("err"=>0);
		}else{
			$back = array("err"=>1);
		}
		$this->response($back, "json");
	}
	
	public function user_detail()
	{
		$id = I("id","");
		$r = D("SysUser")->where(array("id"=>$id))->userdetail();
		$group = D("SysGroup")->select();
		if($r){
			$back = array(
				"err"=> 0,
				"body"=> $r
			);
		}else {
			$back = array(
				"err"=> 1
			);
		}
		$this->response($back, "json");
	}
	
	public function group_list()
	{
		$r = D("SysGroup")->select();
		if($r){
			$back = array(
				"err"=>0,
				"list"=>$r
			);
		}else {
			$back = array(
				"err"=>2
			);
		}
		$this->response($back, "json");
	}
	
	public function group_status()
	{
		$id = I("id", "");
		$status = I("status", "");
		$r = D("SysGroup")->where(array("id"=>$id))->save(array("status"=>$status));
		if($r){
			$back = array("err"=>0);
		}else {
			$back = array("err"=>1);
		}
		$this->response($back, "json");
	}
	public function group_dele()
	{
		$id = I("id", "");
		$r = D("SysGroup")->where(array("id"=>$id))->delete();
		if($r){
			$back = array("err"=>0);
		}else{
			$back = array("err"=>1);
		}
		$this->response($back, "json");
	}
	
	public function group_detail()
	{
		$id = I("id", "");
		$r = D("SysGroup")->where(array("id"=>$id))->find();
		if($r){
			$back = array(
				"err"=>0,
				"body"=>$r
			);
		}else {
			$back = array("err"=>1);
		}
		$this->response($back, "json");
	}
	
	public function group_edit()
	{
		$id = I("id", "");
		$name = I("name", "");
		$status = I("status", "");
		$message = array(
			"status"=>$status,
			"name"=>$name
		);
		if(empty($id)){
			$r = D("SysGroup")->add($message);
		}else{
			$r = D("SysGroup")->where(array("id"=>$id))->save($message);
		}
		if($r){
			$back = array("err"=>0);
		}else {
			$back = array("err"=>1);
		}
		$this->response($back, "json");
	}
	public function program_list()
	{
		$page = I("page", 1);
		$acount = I("acount", 20);
		$type = I("type", "");
		$title = I("title", "");
		$status = I("status", 2);
		$code = I("code","");
		$id = I("programId",0);
		$map = array("_logic"=>"AND");
		$back = array("err"=> 2);
		if($id != 0){
			$map["id"] = $id;
		}
		if(!empty($code)){
			$map["code"] = array('like','%'.$code.'%');
		}
		if(!empty($type)){
			$map["type"] = $type;
		}
		if($status != 2){
			$map["status"] = $status;
		}
		if(!empty($title)){
			$map["title"] = array('like','%'.$title.'%');
		}
		$r = D("Program")->where($map)->order("id desc")->limit(($page-1)*$acount, $acount)->select();
		$total = D("Program")->where($map)->count();
		if($r){
			$list = array();
			foreach($r as $v){
				$list[] = array(
					"id"=>$v["id"],
					"title"=>$v["title"],
					"code"=>$v["code"],
					"type"=>$v["type"],
					"img_path"=>domain_img($v["img_path"]),
					"poster_path"=>$v["poster_path"],
					"description"=>$v["description"],
					"video_url"=>$v["video_url"],
					"create_time"=>$v["create_time"],
					"update_time"=>$v["update_time"],
					"status"=>$v["status"],
					"is_free"=>$v["is_free"]
				);
			}
			$back = array(
					"err"=> 0,
					"total"=> ceil($total/$acount),
					"list"=> $list
				);
		}
		$this->response($back, "json");
	}
	
	public function program_video()
	{
		$program_id = I("id", "");
		$back = array("err"=> 2);
		if(!empty($program_id)){
			$r = D("Movie")->where(array("program_id"=>$program_id))->select();			
		}
		if($r){
			$back = array(
					"err"=> 0,
					"body"=>$r
				);
		}
		$this->response($back, "json");
	}
	public function video_edit(){
		$id = I("id","");
		$program_id = I("program_id", "");
		$platform = I("platform", "default");
		$media = I("media", "",html_entity_decode);
		$back = array("err"=> 1);
		$message = array(
			"platform"=>$platform,
			"media"=>$media,
			"program_id"=>$program_id
		);
		if(empty($id)){
			$r = D("Movie")->add($message);
		}else{
			$r = D("Movie")->where(array("id"=>$id))->save($message);
		}
		if($r){
		    D("Movie")->clsCache($program_id);
			$back = array("err"=> 0);
		}
		$this->response($back, "json");
	}
	
	public function video_del()
	{
		$id = I("id", "");
		$back = array("err"=> 1);
		if(!empty($id)){
            $program_id = D("Movie")->where(array("id"=>$id))->getField("program_id");
            D("Movie")->clsCache($program_id);
			$r = D("Movie")->where(array("id"=>$id))->delete();
		}
		if($r){
			$back = array("err"=> 0);
		}
		$this->response($back, "json");
	}
	
	public function program_detail(){
		$id = I("id", "");
		$back = array("err"=> 1);
		$r = D("Program")->where(array("id"=>$id))->find();
		if($r){
			$back = array("err"=> 0,"body"=>$r);
		}
		$this->response($back, "json");
	}
	
	public function program_dele(){
		$id = I("id", "");
		$back = array("err"=> 1);
		$r = D("Program")->where(array("id"=>$id))->delete();
		if($r){
			$back = array("err"=> 0);
		}
		$this->response($back, "json");
	}
	
	
	public function program_status(){
		$id = I("id", "");
		$status = I("status", 1);
		$back = array("err"=> 1);
		$r = D("Program")->where(array("id"=>$id))->save(array("status"=>$status));
		if($r){
			$back = array("err"=> 0);
		}
		$this->response($back, "json");
	}
	
	public function program_info()
	{
		$id = I("id", "1");
		$r = D("Program")->where(array("id"=>$id))->find();
		if($r){
			$back = array("err"=>0,"body"=>$r);
		}else {
			$back = array("err"=>2);
		}
		$this->response($back, "json");
	}
	public function program_edit(){
		$id = I("id", "");
		$title = I("title", "");
		$sub_title = I("sub_title", "");
		$free = I("is_free", 0);
		$despription = I("despription", "");
		$status = I("status", "");
		$type = I("type", 0);
		$video_url = I("video_url", "");
		$code = I("code", "");
		$back = array("err"=> 1);
		$message = array(
				"title"=> $title,
				"sub_title"=> $sub_title,
				"is_free"=> $free,
				"despription"=>$despription,
				"status"=>$status,
				"type"=>$type,
				"code"=>$code,
				"video_url"=>$video_url,
				"update_time"=>date("Y-m-d H:i:s")
			);
        $pinyin = new Pinyin();
        $message["search_title"] = $pinyin->abbr($message["title"]);
		if(empty($id) && !empty($code)){
			$r = D("Program")->add($message);
		}else {
			if(!empty($code)){
				$r = D("Program")->where(array("id"=>$id))->save($message);				
			}
		}
		if($r){
			$back = array("err"=> 0);
		}
		$this->response($back, "json");
	}
	
	public function image()
	{
		$id = I("pid", "", intval);
		$from = $_GET["lab"];
		$file_upload = [
		    "err"=>-1,
		    "url"=>""
		];
		if($_GET["tag"] == "file_upload"){
		    if ((($_FILES["pic"]["type"] == "image/gif") || ($_FILES["pic"]["type"] == "image/jpeg") || ($_FILES["pic"]["type"] == "image/pjpeg"))) {
		        $file_name = date("YmdHis").".jpg";
		        if ($_FILES["pic"]["error"] > 0)
		        {
		            $file_upload["url"] = "Return Code: " . $_FILES["pic"]["error"] . "<br />";
		        } else {
		            if (file_exists("img/" . $file_name))
		            {
		                $file_upload["url"] = $_FILES["pic"]["name"] . " already exists. ";
		            } else {
		                move_uploaded_file($_FILES["pic"]["tmp_name"], "../src/img/" . $file_name);
		                $file_upload["err"] = 0;
		                $file_upload["url"] = "img/" . $file_name;
		                $x = 1;
		                $r = D("Program")->where(array("id"=>$id))->save(array("img_path"=>$file_upload["url"],"update_time"=>date("Y-m-d H:i:s")));
					    error_log(date("Y-m-d H:i:s ")."图片:".$r."\r\n",3,"debug.log");
		            	
		            }
		        }
		    } else {
		        $file_upload["url"] = "Invalid file";
		    }
		    echo "<script language=\"JavaScript\">";
		    echo "window.parent.postMessage('".json_encode($file_upload)."','*');";
		    echo "</script>";
		}else{
		    header("content-type:application/json;charset=utf8");
		    header("Authorization: ".$Authorization);
//		    $s = "return $".$_GET["tag"].";";
//		    exit(json_encode(eval($s)));
		}
	}
	
	public function lesson_list()
	{
		$status = I("status", "x");
		$capability = I("capability", "");
		$key = I("key", "");
		$map = array("type"=>2);
		if($status != "x"){
			$map["status"] = $status;
		}
		if($capability != ""){
			$map["capability"] = $capability;
		}
		if(!empty($key)){
			$map["title"] = array('like','%'.$key.'%');
			$map["_logic"] = "and";
		}
		$cl = I("count", "20");
		$page = I("page", "1");
		$r = D("Program")->where($map)->order("id desc")->limit(($page-1)*$cl, $cl)->select();
		$totla = D("Program")->where($map)->count();
		if($r){
			$back = array(
				"err"=> 0,
				"body"=>array(
					"total"=>ceil($totla/$cl),
					"list"=>$r
				)
			);
		}else {
			$back = array(
				"err"=> 2
			);
		}
		$this->response($back, "json");
	}
	
	public function lesson_info()
	{
		$id = I("id", "");
		$r = D("Program")->where(array("id"=>$id))->find();
		if($r){
			$back = array("err"=>0,"body"=>$r);
		}else {
			$back = array("err"=>2);
		}
		$this->response($back, "json");
	}
	
	public function lesson_related()
	{
		$id = I("id", "");
		$r = D("Lesson")->where(array("id"=>$id))->field("related")->find();
		if(!empty($r)){
			$back = array("err"=>0,"body"=>json_decode($r["related"]));
		}else {
			$back = array("err"=>2);
		}
		$this->response($back, "json");
	}
	public function lesson_comments()
	{
		$id = I("id", "");
		$r = D("Lesson")->where(array("id"=>$id))->field("dialogs")->find();
		if(!empty($r["dialogs"])){
			$back = array("err"=>0,"body"=>json_decode($r["dialogs"]));
		}else {
			$back = array("err"=>2);
		}
		$this->response($back, "json");
	}
	
	public function lesson_edit()
	{
		$id = I("id", "",intval);
		$title = I("post.title", "",trim);
		$capability = I("post.capability", "",intval);
		$is_free = I("post.is_free", "",intval);
		$code = I("post.code", "");
		$brief = safe_input(urldecode(I("post.brief", "",trim)));
		$dialogs = I("post.dialogs", "");
		$video_url = safe_input(urldecode(I("post.video_url", "",trim)));
		$video_id = I("post.video_id", "");
		$map = array(
			"title"=>$title,
			"capability"=> $capability,
			"is_free"=> $is_free,
			"code"=> $code,
			"brief"=> $brief,
			"video_url"=> $video_url,
			"video_id"=> $video_id
		);
		if(!empty($dialogs)){
			$map["dialogs"] = json_encode($dialogs);
		}
		if(empty($id)){
			$map["update_time"] = date("Y-m-d H:i:s");
			$r = D("Lesson")->add($map);
		}else{
			$r = D("Lesson")->where(array("id"=>$id))->save($map);
		}
		if($r){
			$back = array("err"=>0);
		}else {
			$back = array("err"=>1);
		}
		$this->response($back, "json");
	}
	
	public function lesson_del()
	{
		$id = I("id", "");
		$r = D("Lesson")->where(array("id"=>$id))->delete();
		if($r){
			$back = array("err"=>0);
		}else {
			$back = array("err"=>2);
		}
		$this->response($back, "json");
	}
	
	public function lesson_status()
	{
		$id = I("id", "");
		$status = I("status", "");
		$r = D("Lesson")->where(array("id"=>$id))->save(array("status"=>$status));
		if($r){
			$back = array("err"=>0);
		}else {
			$back = array("err"=>2);
		}
		$this->response($back, "json");
	}
	
	public function promotion()
	{
		$id = I("post.id", "", intval);
		$cl = I("count", "20");
		$page = I("page", "1");
		$tag = I("tag","");
		$grade = I("grade", "");
		$map = array();
		$app_id = I("appId", "");
		if($tag != ""){
			$map["tag"] = array("like", $tag."%");
		}
		if(!empty($grade)){
			$map["grade"] = array('in',"-1,".$grade);
			$map["_logic"] = "and";
		}
		if(!empty($app_id)){
			$map["app_id"] = $app_id;
			$map["_logic"] = "and";
		}
		
		if(empty($id)){
			$r = D("Promotion")->where($map)->limit(($page-1)*$cl, $cl)->order("tag,id")->select();
			$total = D("Promotion")->where($map)->count();
			if($r){
				$list = array();
				foreach($r as $v){
					$list[] = array(
						"id"=>$v["id"],
						"tag"=>$v["tag"],
						"type"=>$v["type"],
						"value"=>$v["value"],
						"img_path"=>domain_img($v["img_path"]),
						"title"=>$v["title"],
						"trailer_id"=>$v["trailer_id"],
						"extra"=>$v["extra"],
						"appId"=>$v["app_id"],
						"create_time"=>$v["create_time"],
						"update_time"=>$v["update_time"],
						"status"=>$v["status"]
					);
				}
				$back = array(
					"err"=>0,
					"body"=>array(
						"total"=>ceil($total/$cl),
						"list"=>$list,
					)
				);
			}
		} else {
			$r = D("Promotion")->where(array("id"=>$id))->find();
			$back = array(
					"err"=>0,
					"body"=>$r
				);
		}
		$this->response($back, "json");
	}
	
	public function promotion_status()
	{
		$id = I("id", "");
		$status = I("status", "");
		$r = D("Promotion")->where(array("id"=>$id))->save(array("status"=>$status));
		if($r){
			$back = array("err"=>0);
            if($status == 0){
                $tag = D("Promotion")->where(array("id"=>$id))->getField("tag");
                D("Promotion")->clsCache($tag);
            }
		}else {
			$back = array("err"=>1);
		}
		$this->response($back, "json");
	}
	public function promotion_del()
	{
		$id = I("id", "");
        $tag = D("Promotion")->where(array("id"=>$id))->getField("tag");
		$r = D("Promotion")->where(array("id"=>$id))->delete();
		if($r){
			$back = array("err"=>0);
            D("Promotion")->clsCache($tag);
		}else {
			$back = array("err"=>1);
		}
		$this->response($back, "json");
	}
	
	public function promotion_edit()
	{
		$id = I("id", "");
		$tag = I("post.tag", "");
		$value = I("post.value", "");
		$title = I("post.title", "");
		$type = I("post.type", "");
		$status = I("post.status", "");
		$extra = I("post.extra", "");
		$trailer_id = I("post.trailer_id", "");
		$app_id = I("appId", "");
		$message = array(
			"tag"=> $tag,
			"value"=>$value,
			"type"=>$type,
			"status"=>$status,
			"title"=>$title,
			"extra"=> $extra,
			"app_id"=>$app_id,
			"trailer_id"=>$trailer_id,
			"update_time"=>date("Y-m-d H:i:s")
		);
		if(empty($id)){
			$r = D("Promotion")->add($message);
		} else {
			$r = D("Promotion")->where(array("id"=>$id))->save($message);
		}
		if($r){
			$back = array("err"=>0);
            D("Promotion")->clsCache($tag);
		}else{
			$back = array("err"=>1);
		}
		$this->response($back, "json");
	}
	
	
	public function category(){
		$id = I("id", "");
		$back = array("err"=> 2);
		$app_id = I("appId", "");
		if(!empty($app_id)){
			$message["app_id"] = $app_id;
		}
		if(empty($id)){
			$r = D("Category")->getmenus();			
		}else {
			$r = D("Category")->where(array("id"=>$id))->find();
			$r = array($r);
		}
		if($r){
			$back = array("err"=> 0,"body"=>$r);
		}
		$this->response($back, "json");
	}
	public function category_dele(){
		$id = I("id", "");
		$back = array("err"=> 1);
		if(!empty($id)){
			$r = D("Category")->where(array("id"=>$id))->delete();
		}
		if($r){
			$back = array("err"=> 0,"body"=>$r);
		}
		$this->response($back, "json");
	}
	
	public function category_list()
	{
		$id = I("id", "");
		if(empty($id)){
			$r = D("Category")->getmenu();
		} else {
			$r = D("Category")->where(array("id"=>$id))->find();
		}
		if(!empty($r)){
			$list = array();
			foreach($r as $v){
				$list[] = array(
					"id"=>$v["id"],
					"title"=>$v["title"],
					"sub_title"=>$v["sub_title"],
					"genre"=>$v["genre"],
					"img_path"=>domain_img($v["img_path"]),
					"mount"=>$v["mount"],
					"description"=>$v["description"],
					"focus_path"=>$v["focus_path"],
					"parent"=>$v["parent"],
					"app_id"=>$v["app_id"],
					"create_time"=>$v["create_time"],
					"sort_num"=>$v["sort_num"],
					"status"=>$v["status"]
				);
			}
			$back = array(
				"err"=>0,
				"body"=>$list
			);
		}else {
			$back = array("err"=>2);
		}
		$this->response($back, "json");
	}
	
	public function category_status(){
		$id = I("id", "");
		$status = I("status", 0);
		$back = array("err"=> 1);
		if(!empty($id)){
			$r = D("Category")->where(array("id"=>$id))->save(array("status"=>$status));
		}
		if($r){
			$back = array("err"=> 0,"body"=>$r);
		}
		$this->response($back, "json");
	}
	
	public function category_edit()
	{
		$id = I("id", "");
		$genre = I("genre", "");
		$sort_num = I("sort", "");
		$status = I("status", "");
		$name = I("name", "");
		$mount = I("mount", "");
		$description = I("description","");
		$sub_title = I("sub_title","");
		$app_id = I("app_id","");
		$title = I("title", "");
		$parent = I("parent", array());
		$code = i("code", "");
		$message = array(
			"name"=>$name,
			"genre"=>$genre,
			"status"=>$status,
			"sort_num"=>$sort_num,
			"description"=>$description,
			"sub_title"=>$sub_title,
			"app_id"=>$app_id,
			"title"=>$title,
			"code"=>$code,
			"mount"=>$mount,
			"update_time"=>date("Y-m-d H:i:s")
		);
		if(!empty($parent)){
			$message["parent"] =json_encode($parent);
		}
		if(empty($id)){
			$r = D("Category")->add($message);
		}else{
			$r = D("Category")->where(array("id"=>$id))->save($message);
		}
		if($r){
			$back = array("err"=>0);
		}else {
			$back = array("err"=>1);
		}
		$this->response($back, "json");
	}
	
	public function category_del()
	{
		$id = I("id", "");
		$r = D("Category")->where(array("id"=>$id))->delete();
		if($r){
			$back = array("err"=>0);			
		}else {
			$back = array("err"=>2);	
		}
		$this->response($back, "json");
	}
	
	public function category_sort(){
		$sort = I("sort",array());
        if(!empty($sort))
        {              
        	$r = D("Category")->sort($sort);
        }
        error_log(date("Y-m-d H:i:s ")."$sort:".json_encode($sort)."返回".$r."\r\n",3,"debug.log");
		if($r){
			$back = array("err"=>0);
		}else {
			$back = array("err"=>1);
		}
		$this->response($back, "json");
	}
	
	public function category_add(){
		$title = I("title", "");
		$genre = I("genre", "");
		$mount = I("mount", 1);
		$despription = I("despription", "");
		$sort = I("sort", 0);
		$status = I("status", 1);
		$back = array("err"=> 1);
		$message = array(
					"title"=>$title,
					"genre"=>$genre,
					"mount"=>$mount,
					"despription"=>$despription,
					"sort"=>$sort,
					"status"=>$status
				);
		$r = D("Category")->add($message);
		if($r){
			$back = array("err"=> 0);
		}
		$this->response($back, "json");
	}
	
	public function category_child(){
		$id = I("id","");
		$title = I("title", "");
		$back = array("err"=> 2);
		$map = array();
		if(!empty($title)){
			$map["title"] = array('like','%'.$title.'%');
		}
		$r = D("Category")->where($map)->getchildlist($id);
		if($r){
			$back = array("err"=> 0,"body"=>array("total"=>ceil($total/$acount),"list"=>$r));
		}
		$this->response($back, "json");
	}
	
	public function category_relative(){
		$id = I("id", 0);
		$parent = I("list", array());
		$back = array("err"=> 1);
		if(!empty($parent)){
			$r = D("Category")->where(array("id"=>$id))->save(array("parent"=>json_encode($parent)));
		}else{
			$r = D("Category")->where(array("id"=>$id))->save(array("parent"=> null));
		}
		if($r){
			$back = array("err"=> 0);
		}
		$this->response($back, "json");
	}
	
	public function program_extra(){
		$id = I("id", 0);
		$back = array("err"=> 2);
		$map = array();
		if(!empty($id)){
			$map["parent_id"] = $id;
		}
		$r = D("Episode")->where($map)
			->join("program ON episode.program_id = program.id")
			->field("title,episode.id as id,sub_title,program_id,sort_num")
			->order("sort_num")
			->select();
		$total = D("Episode")->where($map)->count();
		if($r){
			$back = array("err"=> 0,"body"=>$r);
		}
		$this->response($back, "json");
	}
	public function program_extrasort()
	{
		$arr = I("list", array());
		$back = array("err"=> 1);
		if(!empty($arr)){
			$r = D("Episode")->program_sort($arr);
		}
		if($r){
			$back = array("err"=> 0);
		}
		$this->response($back, "json");
	}
	public function program_addextra(){
		$id = I("id",0);
		$arr = I("list", array());
		$back = array("err"=> 1);
		if(is_array($arr)){
			foreach($arr as $v){
				$r = D("Episode")->add(array("parent_id"=>$id,"program_id"=>$v));
			}
		}
		$episode_total = D("Episode")->where(array("parent_id"=>$id))->count();
		D("Program")->where(array("id"=>$id))->save(array("episode_total"=>$episode_total));
		
		if($r){
			$back = array("err"=> 0);
		}
		$this->response($back, "json");
	}
	public function program_delextra(){
		$id = I("id",0);
		$back = array("err"=> 1);
		if(!empty($id)){
            $parent_id = D("Episode")->where(array("id"=>$id))->getField("parent_id");
			$r = D("Episode")->where(array("id"=>$id))->delete();

            $episode_total = D("Episode")->where(array("parent_id"=>$parent_id))->count();
            D("Program")->where(array("id"=>$parent_id))->save(array("episode_total"=>$episode_total));
		}
		if($r){
			$back = array("err"=> 0);
		}
		$this->response($back, "json");
	}
	
	public function sort()
	{
		$sort = I("sort",array());
        if(!empty($sort))
        {              
        	$r = D("Category")->sort($sort);
        }
		if($r){
			$back = array("err"=>1);
		}else {
			$back = array("err"=>0);
		}
		$this->response($back, "json");
	}
	public function category_program(){
		$id = I("id","");
		$page = I("page", 1);
		$acount = I("acount", 20);
		$title = I("title", "");
		$back = array("err"=> 2);
		$map = array("_logic"=>"AND");
		if(!empty($id)){
			$map = array("category_id"=>$id);
		}
		if(!empty($title)){
			$map["program.title"] = array('like','%'.$title.'%');
		}
		$r = D("CategoryProgram")
			->join("program ON program.id = category_program.program_id")
			->join("category ON category.id = category_program.category_id")
			->field("category_id,category_program.id as id,category_program.sort_num as sort_num,category.title as parent,category_program.id,program.title,category.status as cstatus,program.status as pstatus,program_id")
			->order("pstatus desc")
			->limit(($page-1)*$acount, $acount)
			->where($map)
			->select();
		$total = D("CategoryProgram")->where($map)
			->join("program ON program.id = category_program.program_id")
			->join("category ON category.id = category_program.category_id")
			->count();
		if($r){
			$back = array("err"=> 0,"body"=>array("total"=>ceil($total/$acount),"list"=>$r));
		}
		$this->response($back, "json");
	}
	public function category_programdel()
	{
		$id = I("id","");
		$back = array("err"=>1);
		$r = D("CategoryProgram")->where(array("id"=>$id))->delete();
		if($r){
			$back = array("err"=>0);
		}
		$this->response($back, "json");
	}
	
	public function program_sort()
	{
		$sort = I("list",array());
        if(!empty($sort))
        {   
        	$list = array();
        	foreach($sort as $id => $v){
        		$r = D("CategoryProgram")->where(array("id"=>$id))->save(array("sort_num"=>$v));
        		if(!$r){
        			$list[] = $id;
        		}
        	}           
        }
		$back = array("err"=>0,"list"=>$list);
		$this->response($back, "json");
	}
	public function category_program_del()
	{
		$id = I("id", "");
		$r = D("CategoryProgram")->where(array("id"=>$id))->delete();
		if($r){
			$back = array("err"=>0);			
		}else {
			$back = array("err"=>2);	
		}
		$this->response($back, "json");
	}
	public function comic_add()
	{
		$ids = I("ids",array());
		$pid = I("pid", "");
		$cid = I("cid", "");
		if(empty($cid)){
			$r = D("CategoryProgram")->addprogram($pid, $ids);
		}else{
			$r = D("CategoryProgram")->addprogram($cid, $ids);
		}
		if($r){
			$back = array(
				"err"=>0,
			);
		}else {
			$back = array(
				"err"=>1,
			);
		}
		$this->response($back, "json");
	}
	public function category_lesson_del()
	{
		$id = I("id", "");
		$r = D("CategoryLesson")->where(array("id"=>$id))->delete();
		if($r){
			$back = array("err"=>0);		
		}else {
			$back = array("err"=>2);
		}
		$this->response($back, "json");
	}
	public function lesson_sort()
	{
		$sort = I("sort",array());
        if(!empty($sort))
        {              
        	$r = D("CategoryLesson")->sort($sort);
        }
		if($r){
			$back = array("err"=>1);
		}else {
			$back = array("err"=>0);
		}
		$this->response($back, "json");
	}
	
	public function parent_category()
	{
		$type = I("type","");
		$x = D("Category")->getmenu($type);
		if(empty($x)){
			$back = array(
				"err"=> 2
			);
		}else {
			$back = array(
				"err"=> 0,
				"body"=>$x
			);
		}
		$this->response($back, "json");
	}
	public function child_category()
	{
		$id = I("pid","");
		$x = D("Category")->getchildlist($id);
		if(empty($x)){
			$back = array(
				"err"=> 2
			);
		}else {
			$back = array(
				"err"=> 0,
				"body"=>$x
			);
		}
		$this->response($back, "json");
	}
	public function category_lesson_add()
	{
		$ids = I("ids",array());
		$pid = I("pid", "");
		$cid = I("cid", "");
		if(empty($cid)){
			$r = D("CategoryLesson")->addlesson($pid, $ids);			
		} else {
			$r = D("CategoryLesson")->addlesson($cid, $ids);
		}
		if($r){
			$back = array(
				"err"=>0
			);
		}else {
			$back = array(
				"err"=>1
			);
		}
		$this->response($back, "json");
	}
	
	public function related_add()
	{
		$y = I("idd",array());
		$id = I("id", "");
		$related = D("Lesson")->where(array("id"=>$id))->relatedadd($y);
		$r = D("Lesson")->where(array("id"=>$id))->save(array("related"=>json_encode($related)));
		if($r){
			$back = array("err"=> 0);			
		}else{
			$back = array("err"=> 1);
		}
		$this->response($back,"json");
	}
	public function related_sort()
	{
		$related = I("m",array());
		$id = I("id","");
		$r = D("Lesson")->where(array("id"=>$id))->save(array("related"=>json_encode($related)));
		if($r){
			$back = array("err"=>0);
		}else{
			$back = array("err"=>1);
		}
		$this->response($back, "json");
	}
	public function comments_sort()
	{
		$related = I("m",array());
		$id = I("id","");
		$r = D("Lesson")->where(array("id"=>$id))->save(array("dialogs"=>json_encode($related)));
		if($r){
			$back = array("err"=>0);
		}else{
			$back = array("err"=>1);
		}
		$this->response($back, "json");
	}
	public function comments_add()
	{
		$id = I("id", "");
		$message = array(
			"transcript"=>I("transcript",""),
			"translation"=>I("translation", ""),
			"cue_start"=>I("cue_start", ""),
			"cue_end"=>I("cue_end", ""),
			"ec_dialog_line_id"=>I("myid","")
		);
		$related = D("Lesson")->where(array("id"=>$id))->field("dialogs")->find();
		$b =json_decode($related["dialogs"]);
		array_push($b,$message);
		$r = D("Lesson")->where(array("id"=>$id))->save(array("dialogs"=>json_encode($b)));
		if($r){
			$back = array("err"=> 0);			
		}else{
			$back = array("err"=> 1);
		}
		$this->response($back,"json");
	}
	public function category_programadd(){
		$id = I("id", 0);
		$list = I("list", array());
		$back = array("err"=> 0);
		$x = array();
		if(is_array($list) && !empty($list)){
			foreach($list as $v){
				$r = D("CategoryProgram")->add(array("category_id"=>$id,"program_id"=>$v));
				if(!$r){
					$x[] = $v;
				}
			}
		}
		$back = array("err"=> 0,"list"=>$x);
		$this->response($back, "json");
	}
	
	
	public function program_image(){
		$id = I("id","");
		$back = array("err"=> 99);
		if(!empty($id)){
			$back = array("err"=> 88);
			$config = array(
	                'rootPath'=>C("SRC_PATH"),
	                'savePath'=>'img/',
	                'maxSize'=>0,
	                'exts'=>array("jpg","png","gif","jpeg"),
	                'saveName'=>array('date','YmdHis'),
	                'subName'=>$id
	            );
	        $upload = new \Think\Upload($config);
	        $info = $upload->upload();
	        if($info){
	        	$img_file = str_replace(array("../","./"),"",$info['file']['savepath']).$info['file']['savename'];
	        	$his = D("Program")->where(array("id"=>$id))->find();
	        	if(!empty($his["img_path"])){
	        		@unlink(C("SRC_PATH").$his["img_path"]);
	        	}
	        	$r = D("Program")->where(array("id"=>$id))->save(array("img_path"=>$img_file));
	        	if($r){
	        		$back = array("err"=> 0,"flile"=>domain_img($img_file)); 	        		
	        	}
	        }else{
	        	$back = array("err"=> $upload->getError());
	        }
		}
        $this->response($back, "json");
	}
	public function promotion_image(){
		$id = I("id",0);
		$back = array("err"=> 1);
		if(!empty($id)){
			$config = array(
	                'rootPath'=>C("SRC_PATH"),
	                'savePath'=>'promotion/',
	                'maxSize'=>0,
	                'exts'=>array("jpg","png","gif","jpeg"),
	                'saveName'=>array('date','YmdHis'),
	                'subName'=>$id
	            );
	        $upload = new \Think\Upload($config);
	        $info = $upload->upload();
	        if($info){
	        	$img_file  = str_replace(array("../","./"),"",$info['file']['savepath']).$info['file']['savename'];
	        	$his = D("Promotion")->where(array("id"=>$id))->find();
	        	if(!empty($his["img_path"])){
	        		@unlink(C("SRC_PATH").$his["img_path"]);
	        	}
	        	$r = D("Promotion")->where(array("id"=>$id))->save(array("img_path"=>$img_file));
	        	if($r){
	        		$back = array("err"=> 0,"flile"=>domain_img($img_file));
	        	}
	        }else {
	        	$back = array("err"=> $upload->getError());
	        }
		}
//		error_log(date("Y-m-d H:i:s ")."图片:".$upload->getError()."----".$id."\r\n",3,"debug.log");	        		
        $this->response($back, "json");
	}
	
	public function category_image(){
		$id = I("id","");
		$back = array("err"=> 1);
		if(!empty($id)){
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
	        	$his = D("Category")->where(array("id"=>$id))->find();
	        	if(!empty($his["img_path"])){
	        		@unlink(C("SRC_PATH").$his["img_path"]);
	        	}
	        	$r = D("Category")->where(array("id"=>$id))->save(array("img_path"=>$img_file));
	        	if($r){
	        		$back = array("err"=> 0,"flile"=>domain_img($img_file)); 	        		
	        	}
	        }else {
	        	$back = array("err"=> $upload->getError());
	        }
		}
        $this->response($back, "json");
	}
	public function category_focusimage(){
		$id = I("id","");
		$back = array("err"=> 1);
		if(!empty($id)){
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
	        	$his = D("Category")->where(array("id"=>$id))->find();
	        	if(!empty($his["focus_path"])){
	        		@unlink(C("SRC_PATH").$his["focus_path"]);
	        	}
	        	$r = D("Category")->where(array("id"=>$id))->save(array("focus_path"=>$img_file));
	        	if($r){
	        		$back = array("err"=> 0,"flile"=>domain_img($img_file)); 	        		
	        	}
	        }
		}
        $this->response($back, "json");
	}
	
	public function raffle_list()
	{
		$status = I("status", 2);
		$message = array();
		$back = array("err"=>1);
		if($status != 2){
			$message = array("status"=>$status);			
		}
		$list = D("Raffle")->where($message)->select();
		$hash = array();
        try {
            $redis = new \Redis();
            $redis->connect(C('REDIS.HOST'), intval(C('REDIS.PORT')));
            $hash = $redis->hGetAll("my_raffle_hash");
            $redis->close();
        }catch (\RedisException $e){

        }
        foreach($list as $k=>$v)
        {
//          $list[$k]["status_name"] = $status[$v["status"]];
			$list[$k]["img_path"] = domain_img($v["img_path"]);
            if(isset($hash[$v["id"]])){
                $j = json_decode($hash[$v["id"]],true);
                $list[$k]["luck_no"] = $j["no"];
            }
        }
        if(!empty($list)){
        	$back = array("err"=>0,"list"=>$list);
        }
        $this->response($back, "json");
	}
	
	public function raffle_edit()
	{
		$id = I("id", 0);
		$back = array("err"=>1);
		$data = array(
            "name"=>I("post.name","",strval),
            "info"=>I("post.info","",strval),
            "per"=>I("post.per",1,intval),
            "need_phone"=>I("post.need_phone",0),
            "update_time"=>date("Y-m-d H:i:s")
        );
        if(empty($id)){
        	$r = D("Raffle")->add($data);
        }else{
        	$r = D("Raffle")->where(array("id"=>$id))->save($data);
        }
        if($r){
        	$back["err"] = 0;
        }
        $this->response($back, "json");
	}
	
	public function raffle_status()
    {
        $id = I("id",0,intval);
        $status = I("status",0);
		$back = array("err"=>1);
        if($id)
        {
            $r = D("Raffle")->where(array("id"=>$id))->save(array("status"=>$status,"update_time"=>date("Y-m-d H:i:s")));
            if($r){
            	$back = array("err"=>0);
            }
        }
        $this->response($back, "json");
    }
	
	public function raffle_cls()
    {
        $back = array("err"=>1);
        try {
            $redis = new \Redis();
            $redis->connect(C('REDIS.HOST'), intval(C('REDIS.PORT')));
            $r = $redis->del("my_raffle_hash");
            if($r >0){
                $back = array("err"=>0);
            }
            $redis->close();
        }catch (\RedisException $e){
        }
        $this->response($back, "json");
    }
    
    public function raffle_dele()
    {
        $id = I("id",0,intval);
        $back = array("err"=>1);
        if($id)
        {
            $raffle = D("Raffle")->where(array("id"=>$id))->find();
            if(!empty($raffle["img_path"])){
                @unlink(C("SRC_PATH").$raffle["img_path"]);
            }
            $r = D("Raffle")->where(array("id"=>$id))->delete();
            $back["err"] = $r?0:1;
        }
        $this->response($back, "json");
    }
    
    public function raffle_image(){
		$id = I("id","");
		$back = array("err"=> 1);
		if(!empty($id)){
			$config = array(
	                'rootPath'=>C("SRC_PATH"),
	                'savePath'=>'raffle/',
	                'maxSize'=>0,
	                'exts'=>array("jpg","png","gif","jpeg"),
	                'saveName'=>array('date','YmdHis'),
	                'subName'=>""
	            );
	        $upload = new \Think\Upload($config);
	        $info = $upload->upload();
	        if($info){
	        	$img_file  = str_replace(array("../","./"),"",$info['file']['savepath']).$info['file']['savename'];
	        	$his = D("Raffle")->where(array("id"=>$id))->find();
	        	if(!empty($his["img_path"])){
	        		@unlink(C("SRC_PATH").$his["img_path"]);
	        	}
	        	$r = D("Raffle")->where(array("id"=>$id))->save(array("img_path"=>$img_file));
	        	if($r){
	        		$back = array("err"=> 0,"flile"=>domain_img($img_file)); 	        		
	        	}
	        }else {
	        	$back = array("err"=> $upload->getError());
	        }
		}
        $this->response($back, "json");
	}
    
    public function raffle_key_list()
    {
    	$id = I("id", 0);
    	$acount = I("count", 20);
    	$page = I("page", 1);
    	$status = I("status", 9);
    	$back = array("err"=>1);
    	$message = array();
    	if($status != 9){
    		$message["status"] = $status;
    	}
    	if(!empty($id)){
    		$message["gift_id"] = $id;
    	}
    	$r = D("RaffleGiftKey")->where($message)->limit(($page-1)*$acount, $acount)->select();
    	$total = D("RaffleGiftKey")->where($message)->count();
    	if($r){
    		$back = array(
    			"err"=>0,
    			"body"=>array("total"=>ceil($total/$acount),"list"=>$r)
    		);
    	}
    	$this->response($back, "json");
    }
    
    public function raffle_key_edit()
    {
    	$id = I("id", 0);
    	$gitftId = I("giftId", 0);
    	$status = I("status", 1);
    	$key = I("key", "");
    	$message = array();
    	if(!empty($gitftId) && !empty($key)){
    		$message = array("gift_id"=>$gitftId, "key"=>$key, "status"=>$status);
    		if($id){
    			$r = D("RaffleGiftKey")->where(array("id"=>$id))->save($message);
    		}else {
    			$r = D("RaffleGiftKey")->add($message);
    		}
    		if($r){
    			$back = array("err"=>0);
    		}
    	}else{
    		$back = array("err"=>1);
    	}
    	$this->response($back, "json");
    }
    public function raffle_key_status()
    {
    	$id = I("id", 0);
    	$status = I("status", 1);
    	$back = array("err"=>1);
    	$r = D("RaffleGiftKey")->where(array("id"=>$id))->save(array("status"=>$status));
    	if($r){
    		$back = array("err"=>0);
    	}
    	$this->response($back, "json");
    }
    
    public function raffle_key_del()
    {
    	$id = I("id", 0);
    	$back = array("err"=>1);
    	$r = D("RaffleGiftKey")->where(array("id"=>$id))->delete();
    	if($r){
    		$back = array("err"=>0);
    	}
    	$this->response($back, "json");
    }
    
    public function raffleHis_list()
    {
    	$acount = I("count", 20);
    	$page = I("page", 1);
    	$bingo = I("bingo", 9);
    	$dispatch = I("dispatch", 9);
    	$back = array("err"=>1);
    	$message = array();
    	if($bingo != 9){
    		$message["is_bingo"] = $bingo;
    	}
    	if($dispatch != 9){
    		$message["dispatch_info"] = $dispatch;
    	}
    	$r = D("RaffleHis")->where($message)->limit(($page-1)*$acount, $acount)->select();
    	$total = D("RaffleHis")->where($message)->count();
    	if($r){
    		$body = array(
    			"total"=> ceil($total / $acount),
    			"list"=>$r
    		);
    		$back = array("err"=>0,"body"=>$body);
    	}
    	$this->response($back, "json");
    }
    
    public function raffleHis_edit()
    {
    	$id = I("id", 0);
    	$dispatch_info = I("info", "");		//派送信息
    	$dispatch_odd = I("odd", "");		//派送单号
    	$is_dispatch = I("ispatch", 0);		//是否派送
    	$back = array("err"=> 1);
    	$message = array(
    		"dispatch_info"=>$dispatch_info,
    		"dispatch_odd"=>$dispatch_odd,
    		"is_dispatch"=>$is_dispatch
    	);
    	if(!empty($id)){
    		$r = D("RaffleHis")->where(array("id"=>$id))->save($message);
    	}
    	if(!empty($r)){
    		$back = array("err"=> 0);
    	}
    	$this->response($back, "json");
    }
    
    public function program_product()
	{
		$id = I("id", 0);
		$x = array();
		$back = array("err"=> 1);
		$r1 = D("ProductContent")->where(array("content_id"=>$id."@program"))->select();
		$x = array_merge($x,$r1);
		$r2 = D("CategoryProgram")->where(array("program_id"=>$id))->select();
		foreach($r2 as $v){
			$x = array_merge($x,D("ProductContent")->where(array("content_id"=>$v["category_id"]."@category"))->select());
		}
		$list = array();
		foreach($x as $val){
			$list[] = $val["product_id"];
		}
		$list = array_unique($list);
		$product = array();
		foreach($list as $val){
			$rr = D("Product")->where(array("id"=>$val))->field("id,description")->find();
			if(!empty($rr)){
				$product[] = $rr;				
			}
		}
		if(!empty($product)){
			$back["err"] = 0;
			$back["list"] = $product;			
		}
		$this->response($back, "json");
	}
	
	public function program_category()
	{
		$id = I("id", 0);
		$back = array("err"=> 1);
		$category = array();
		$r1 = D("CategoryProgram")->where(array("program_id"=>$id))->select();
		foreach($r1 as $val){
			$category[] = $val["category_id"];
		}
		$r2 = D("Episode")->where(array("program_id"=>$id))->select();
		$pid = array();
		foreach($r2 as $val){
			$parent_program = D("CategoryProgram")->where(array("program_id"=>$val["parent_id"]))->select();
			foreach($parent_program as $vals){
				$category[] = $vals["category_id"];
			}
		}
		$catelist = array();
		foreach($category as $val){
			$rr = D("Category")->where(array("id"=>$val))->find();
			if(!empty($rr)){
				$catelist[] = array("id"=>$rr["id"], "title"=>$rr["title"].$rr["sub_title"]);				
			}
		}
		
		if(!empty($catelist)){
			$back = array(
				"err"=>0,
				"list"=>$catelist
			);			
		}
		$this->response($back, "json");
	}
	
	public function category_product()
	{
		$id = I("id", "");
		$back = array("err"=> 1);
		$r = D("ProductContent")->where(array("content_id"=>$id."@category"))->select();
		$list = array();
		foreach($r as $val){
			$list[] = $val["product_id"];
		}
		$product = array();
		foreach($list as $val){
			$rr = D("Product")->where(array("id"=>$val))->find();
			if(!empty($rr)){
				$product[] = array("id"=>$rr["id"],"description"=>$rr["description"],"product"=>$rr["product"]);				
			}
		}
		if(!empty($product)){
			$back = array(
				"err"=>0,
				"list"=>$product
			);
		}
		$this->response($back, "json");
	}
	
	//数据统计
	public function user_day()
	{
		$start = I("start", date("Y-m-d"));
		$end = I("end", date("Y-m-d"));
		$back = array("err"=>1);
		$channel = I("channel","");
		$page = I("page", 1);
		$acount = I("acount",10);
		$where = "day >= '".$start."' and day <= '".$end."' order by day desc limit ".($page-1)*$acount.",".$acount;
		$channel && $where = "channel='".$channel."' and ".$where;
		$sql = "select day,new_customer,today_user,today_order,customer,play,customer_play,pm from report_user where ".$where;
		$r = D("ReportUser")->query($sql);
		$sql = "select count(1) as cnt from report_user where day >= '".$start."' and day <= '".$end."'";
		$total = D("ReportUser")->query($sql);
		if($r){
			$back = array("err"=>0, "body"=>array("list"=>$r,"total"=>ceil($total[0]["cnt"]/$acount)));
		}
		$this->response($back, "json");
	}
	public function user_back()
	{
		$start = I("start", date("Y-m-d"));
		$end = I("end", date("Y-m-d"));
        $channel = I("channel","");
		$page = I("page", 1);
		$acount = I("acount",10);
		$back = array("err"=>1);
		$where = "day >= '".$start."' and day <= '".$end."' order by day desc limit ".($page-1)*$acount.",".$acount;
        $channel && $where = "channel='".$channel."' and ".$where;
		$sql = "select day,newuser,r1,r2,r3,r4,r5,r6,rm from report_day where ".$where;
		$r = D("ReportDay")->query($sql);
		$where = "day >= '".$start."' and day <= '".$end."'";
        $channel && $where = "channel='".$channel."' and ".$where;
		$sql = "select count(1) as cnt from report_day where ".$where;
		$total = D("ReportDay")->query($sql);
		if($r){
			$back = array("err"=>0, "body"=>array("list"=>$r,"total"=>ceil($total[0]["cnt"]/$acount)));
		}
		$this->response($back, "json");
	}
	
	public function user_play()
	{
		$start = I("start", date("Y-m-d"));
		$end = I("end", date("Y-m-d"));
		$start = date("Y-m-d 00:00:00", strtotime($start));
		$end = date("Y-m-d 00:00:00", strtotime("+1 day",strtotime($end)));
        $channel = I("channel","");
		$page = I("page", 1);
		$acount = I("acount", 10);
		$back = array("err"=>1);
		$channel && $channel = " channel='".$channel."' and ";
		$sql = "select program.id,program.title,program.code,t.cnt,t.length from program, (select program_id,count(1) as cnt,sum(length) as length from dm_play where ".$channel." start_time >= '".$start."' and start_time <= '".$end."' group by program_id ) t where program.id = t.program_id limit ".($page-1)*$acount.",".$acount;
		$r = D("DmPlay")->query($sql);
		$sql = "select count(1) as cnt from program, (select program_id,count(1) as cnt from dm_play where ".$channel." start_time >= '".$start."' and start_time <= '".$end."' group by program_id ) t where program.id = t.program_id";
		$total = D("DmPlay")->query($sql);
		if($r){
			$back = array("err"=>0,"body"=>array("list"=>$r,"total"=>ceil($total[0]["cnt"]/$acount)));			
		}
		$this->response($back, "json");
	}
	//用户记录
	public function login_list()
	{
		$start = I("start", date("Y-m-d"));
		$end = I("end", date("Y-m-d"));
		$uid = I("uid", "");
		$page = I("page", 1);
		$acount = I("acount", 10);
		$username = I("username","");
		$start = date("Y-m-d 00:00:00", strtotime($start));
		$end = date("Y-m-d 00:00:00", strtotime("+1 day",strtotime($end)));
		$back = array("err"=>1);
		$where = array("create_time"=>array("between",$start.",".$end));
		if(!empty($uid)){
			$where["uid"] = $uid;
		}
		if(!empty($username)){
			$uuu = D("Customer")->where(array("username"=>$username))->find();
			if($uuu){
				$where["uid"] = $uuu["uid"];
			}
		}
		$r = D("DmLaunch")->where($where)->order("create_time desc")->limit(($page-1)*$acount,$acount)->select();
		$total = D("DmLaunch")->where($where)->count();
		if($r){
			$back = array(
				"err"=>0,
				"body"=>array(
					"list"=>$r,
					"total"=>ceil($total/$acount)
				)
			);
		}
		$this->response($back, "json");
	}
	public function play_list()
	{
		$start = I("start", date("Y-m-d"));
		$end = I("end", date("Y-m-d"));
		$uid = I("uid", "");
		$username = I("username","");
		$page = I("page", 1);
		$acount = I("acount", 10);
		$start = date("Y-m-d 00:00:00", strtotime($start));
		$end = date("Y-m-d 00:00:00", strtotime("+1 day",strtotime($end)));
		$back = array("err"=>1);
		$where = array("start_time"=>array("between",$start.",".$end));
		if(!empty($uid)){
			$where["uid"] = $uid;
		}
		if(!empty($username)){
			$where["username"] = array("in",$username);
		}
		$hh = array();
		$category = D("CategoryProgram")->where(array("category_id"=>I("category","")))->field("program_id")->select();
		foreach($category as $v){
			$hh[] = $v["program_id"];
		}
		if(!empty($hh)){
			$where["program_id"] = array("in",$hh);
		}
		$r = D("DmPlay")->where($where)->order("start_time desc")->limit(($page-1)*$acount,$acount)->select();
		$total = D("DmPlay")->where($where)->count();
		if($r){
			$back = array(
				"err"=>0,
				"body"=>array(
					"list"=>$r,
					"total"=>ceil($total/$acount)
				)
			);
		}
		$this->response($back, "json");
	}
	public function link_list()
	{
		$start = I("start", date("Y-m-d"));
		$end = I("end", date("Y-m-d"));
		$uid = I("uid", "");
		$page = I("page", 1);
		$acount = I("acount", 10);
		$username = I("username","");
		$tag = I("tag", "search");
		$start = date("Y-m-d 00:00:00", strtotime($start));
		$end = date("Y-m-d 00:00:00", strtotime("+1 day",strtotime($end)));
		$back = array("err"=>1);
		$where = array("create_time"=>array("between",$start.",".$end),"tag"=>$tag);
		if(!empty($uid)){
			$where["uid"] = $uid;
		}
		if(!empty($username)){
			$uuu = D("Customer")->where(array("username"=>$username))->find();
			if($uuu){
				$where["uid"] = $uuu["uid"];
			}
		}
		$r = D("DmLink")->where($where)->order("create_time desc")->limit(($page-1)*$acount,$acount)->select();
		$total = D("DmLink")->where($where)->count();
		if($r){
			$back = array(
				"err"=>0,
				"body"=>array(
					"list"=>$r,
					"total"=>ceil($total/$acount)
				)
			);
		}
		$this->response($back, "json");
	}
	public function dmprogram_list()
	{
		$start = I("start", date("Y-m-d"));
		$end = I("end", date("Y-m-d"));
		$uid = I("uid", "");
		$page = I("page", 1);
		$acount = I("acount", 10);
		$username = I("username","");
		$start = date("Y-m-d 00:00:00", strtotime($start));
		$end = date("Y-m-d 00:00:00", strtotime("+1 day",strtotime($end)));
		$back = array("err"=>1);
		$where = array("create_time"=>array("between",$start.",".$end));
		if(!empty($uid)){
			$where["uid"] = $uid;
		}
		if(!empty($username)){
			$uuu = D("Customer")->where(array("username"=>$username))->find();
			if($uuu){
				$where["uid"] = $uuu["uid"];
			}
		}
		$r = D("DmPromotion")->where($where)->order("create_time desc")->limit(($page-1)*$acount,$acount)->select();
		$total = D("DmPromotion")->where($where)->count();
		if($r){
			$back = array(
				"err"=>0,
				"body"=>array(
					"list"=>$r,
					"total"=>ceil($total/$acount)
				)
			);
		}
		$this->response($back, "json");
	}
	//数据记录
	public function report(){
		set_time_limit(0);
		$start = I("time", date("Y-m-d"));
		$end = date("Y-m-d 00:00:00", strtotime($start));
		$start = date("Y-m-d 00:00:00", strtotime("-1 day",strtotime($start)));
		$channel = I("channel", "jiangsu");
		$r = D("ReportUser")->todayreport($start,$end,$channel);
		$back = array("start"=>$start, "end"=>$end, "r"=>$r);
		$this->response($back, "json",200,false);
		$r = $this->mmm();
	}
	
	
	//历史导出
	public function loginlist_dc()
	{
		$start = I("start", date("Y-m-d"));
		$end = I("end", date("Y-m-d"));
		$uid = I("uid", "");
		$page = I("page", 1);
		$acount = I("acount", 10);
		$username = I("username","");
		$start = date("Y-m-d 00:00:00", strtotime($start));
		$end = date("Y-m-d 00:00:00", strtotime("+1 day",strtotime($end)));
		$back = array("err"=>1);
		$where = array("create_time"=>array("between",$start.",".$end));
		if(!empty($uid)){
			$where["uid"] = $uid;
		}
		if(!empty($username)){
			$uuu = D("Customer")->where(array("username"=>$username))->find();
			if($uuu){
				$where["uid"] = $uuu["uid"];
			}
		}
		$r = D("DmLaunch")->where($where)->order("create_time desc")->select();
		
		$mm = "序号,用户id,时间"."\r\n";
		foreach($r as $index => $v){
			$mm .= $index.",".$v["uid"].",".$v["create_time"]."\r\n";
		}
		
		set_time_limit(0);
        $stream = $mm;

        header("Content-type:application/octet-stream;");
        header("Accept-Ranges:bytes");
        header("Accept-Length:".strlen($stream));
        header("Content-Disposition: attachment; filename=".date("YmdHis")."user-login.csv");
        echo $stream;
	}
	public function playlist_dc()
	{
		set_time_limit(0);
		$start = I("start", date("Y-m-d"));
		$end = I("end", date("Y-m-d"));
		$uid = I("uid", "");
		$username = I("username","");
		$page = I("page", 1);
		$acount = I("acount", 10);
		$start = date("Y-m-d 00:00:00", strtotime($start));
		$end = date("Y-m-d 00:00:00", strtotime("+1 day",strtotime($end)));
		$back = array("err"=>1);
		$where = array("start_time"=>array("between",$start.",".$end));
		if(!empty($uid)){
			$where["uid"] = $uid;
		}
		if(!empty($username)){
			$where["username"] = array("in",$username);
		}
		$hh = array();
		$category = D("CategoryProgram")->where(array("category_id"=>I("category","")))->field("program_id")->select();
		foreach($category as $v){
			$hh[] = $v["program_id"];
		}
		if(!empty($hh)){
			$where["program_id"] = array("in",$hh);
		}
		$r = D("DmPlay")->where($where)->order("start_time desc")->select();
		$mm = "序号,用户id,用户名称,节目id,节目名称,节目code,播放时长,播放时间,地区"."\r\n";
		foreach($r as $index => $v){
			$mm .= $index.",".$v["uid"].",".$v["username"].",".$v["program_id"].",".$v["program_title"].",".$v["program_code"].",".$v["length"].",".$v["start_time"].",".$v["province"].$v["city"]."\r\n";
		}
		
		set_time_limit(0);
        $stream = $mm;
		
		header("Content-type:application/octet-stream");
        header("Accept-Ranges:bytes");
        header("Accept-Length:".strlen($stream));
        header("Content-Disposition: attachment; filename=".date("YmdHis")."user-login.csv");
        echo $stream;
	}
	
	public function linklist_dc()
	{
		$start = I("start", date("Y-m-d"));
		$end = I("end", date("Y-m-d"));
		$uid = I("uid", "");
		$page = I("page", 1);
		$acount = I("acount", 10);
		$username = I("username","");
		$tag = I("tag", "search");
		$start = date("Y-m-d 00:00:00", strtotime($start));
		$end = date("Y-m-d 00:00:00", strtotime("+1 day",strtotime($end)));
		$back = array("err"=>1);
		$where = array("create_time"=>array("between",$start.",".$end),"tag"=>$tag);
		if(!empty($uid)){
			$where["uid"] = $uid;
		}
		if(!empty($username)){
			$uuu = D("Customer")->where(array("username"=>$username))->find();
			if($uuu){
				$where["uid"] = $uuu["uid"];
			}
		}
		$r = D("DmLink")->where($where)->order("create_time desc")->select();
		
		$mm = "序号,用户id,节目id,节目名称,节目code,时间"."\r\n";
		foreach($r as $index => $v){
			$mm .= $index.",".$v["uid"].",".$v["program_id"].",".$v["program_title"].",".$v["program_code"].",".$v["create_time"]."\r\n";
		}
		
		set_time_limit(0);
        $stream = $mm;
		
		header("Content-type:application/octet-stream");
        header("Accept-Ranges:bytes");
        header("Accept-Length:".strlen($stream));
        header("Content-Disposition: attachment; filename=".date("YmdHis")."link-list.csv");
        echo $stream;
	}
	
	public function pmlist_dc()
	{
		$start = I("start", date("Y-m-d"));
		$end = I("end", date("Y-m-d"));
		$uid = I("uid", "");
		$page = I("page", 1);
		$acount = I("acount", 10);
		$username = I("username","");
		$start = date("Y-m-d 00:00:00", strtotime($start));
		$end = date("Y-m-d 00:00:00", strtotime("+1 day",strtotime($end)));
		$back = array("err"=>1);
		$where = array("create_time"=>array("between",$start.",".$end));
		if(!empty($uid)){
			$where["uid"] = $uid;
		}
		if(!empty($username)){
			$uuu = D("Customer")->where(array("username"=>$username))->find();
			if($uuu){
				$where["uid"] = $uuu["uid"];
			}
		}
		$r = D("DmPromotion")->where($where)->order("create_time desc")->select();
		
		$mm = "序号,用户id,推荐位,标题,时间"."\r\n";
		foreach($r as $index => $v){
			$mm .= $index.",".$v["uid"].",".$v["tag"].",".$v["title"].",".$v["create_time"]."\r\n";
		}
		
		set_time_limit(0);
        $stream = $mm;
		
		header("Content-type:application/octet-stream");
        header("Accept-Ranges:bytes");
        header("Accept-Length:".strlen($stream));
        header("Content-Disposition: attachment; filename=".date("YmdHis")."pm-list.csv");
        echo $stream;
	}
	
	//统计导出
	public function userday_dc()
	{
		$start = I("start", date("Y-m-d"));
		$end = I("end", date("Y-m-d"));
		$back = array("err"=>1);
		$page = I("page", 1);
		$acount = I("acount",10);
		$sql = "select day,new_customer,today_user,today_order,customer,play,customer_play,pm from report_user where day >= '".$start."' and day <= '".$end."'";
		$r = D("ReportUser")->query($sql);
		
		$mm = "序号,日期,付费转化率,新增订购用户,日活跃用户,下单数量,进入的已经订购过的用户,播放次数,今天前的订购用户播放次数,pm0001,pm0002,pm0003,pm0004,pm0005,pm0006"."\r\n";
		foreach($r as $index => $v){
			$pm = json_decode($v["pm"],true);
			$mm .= $index.",".$v["day"].",".($v["new_customer"]/$v["today_user"]).",".$v["new_customer"].",".$v["today_user"].",".$v["today_order"].",".$v["customer"].",".$v["play"].",".$v["customer_play"].
			",".$pm[0]["cnt"].",".$pm[1]["cnt"].",".$pm[2]["cnt"].",".$pm[3]["cnt"].",".$pm[4]["cnt"].",".$pm[5]["cnt"]."\r\n";
		}
		set_time_limit(0);
        $stream = $mm;
		
		header("Content-type:application/octet-stream");
        header("Accept-Ranges:bytes");
        header("Accept-Length:".strlen($stream));
        header("Content-Disposition: attachment; filename=".date("YmdHis")."user-day.csv");
        echo $stream;
	}
	
	public function userback_dc()
	{
		$start = I("start", date("Y-m-d"));
		$end = I("end", date("Y-m-d"));
		$page = I("page", 1);
		$acount = I("acount",10);
		$back = array("err"=>1);
		$sql = "select day,newuser,r1,r2,r3,r4,r5,r6,rm from report_day where day >= '".$start."' and day <= '".$end."'";
		$r = D("ReportDay")->query($sql);
		
		$mm = "序号,日期,新用户,r1,r2,r3,r4,r5,r6,大于6天"."\r\n";
		foreach($r as $index => $v){
			$mm .= $index.",".$v["day"].",".$v["newuser"].",".$v["r1"].",".$v["r2"].",".$v["r3"].",".$v["r4"].",".$v["r5"].",".$v["r6"].",".$v["rm"]."\r\n";
		}
		
		set_time_limit(0);
        $stream = $mm;
		
		header("Content-type:application/octet-stream");
        header("Accept-Ranges:bytes");
        header("Accept-Length:".strlen($stream));
        header("Content-Disposition: attachment; filename=".date("YmdHis")."user-back.csv");
        echo $stream;
	}
	
	public function userplay_dc()
	{
		$start = I("start", date("Y-m-d"));
		$end = I("end", date("Y-m-d"));
		$start = date("Y-m-d 00:00:00", strtotime($start));
		$end = date("Y-m-d 00:00:00", strtotime("+1 day",strtotime($end)));
		$page = I("page", 1);
		$acount = I("acount", 10);
		$back = array("err"=>1);
		$sql = "select program.id,program.title,program.code,t.cnt,t.length from program, (select program_id,count(1) as cnt,sum(length) as length from dm_play where start_time >= '".$start."' and start_time <= '".$end."' group by program_id ) t where program.id = t.program_id";
		$r = D("DmPlay")->query($sql);
		
		$mm = "序号,节目id,节目标题,节目code,播放次数,播放总时长"."\r\n";
		foreach($r as $index => $v){
			$mm .= $index.",".$v["id"].",".$v["title"].",".$v["code"].",".$v["cnt"].",".$v["length"]."\r\n";
		}
		
		set_time_limit(0);
        $stream = $mm;
		
		header("Content-type:application/octet-stream");
        header("Accept-Ranges:bytes");
        header("Accept-Length:".strlen($stream));
        header("Content-Disposition: attachment; filename=".date("YmdHis")."user-back.csv");
        echo $stream;
	}
	public function product_count(){
	    $channel = I("channel","");
		$start = I("start", date("Y-m-d",strtotime("2000-01-01")));
		$end = I("end", date("Y-m-d"));
		$start = date("Y-m-d 00:00:00", strtotime($start));
		$end = date("Y-m-d 00:00:00", strtotime("+1 day",strtotime($end)));
		$back = array("err"=>1);
        $channel && $channel = " channel='".$channel."' and ";
		$sql = "select t.product,product.description,product.price,t.cnt from "."(select product,count(1) as cnt from customer_product where ".$channel." expire_time is not null and create_time >= '".$start."' and create_time <= '". $end ."' group by product) t,product where product.product = t.product";
		$r = D("CustomerProduct")->query($sql);
		if($r){
			$back = array(
				"err"=>0,
				"body"=>array("list"=>$r)
			);
		}
		$this->response($back,"json");
	}
	public function mount()
	{
		$back = array("err"=>1);
		$r = D("Category")->where(array("mount"=>1))->select();
		if($r){
			$back = array(
				"err"=>0,
				"body"=>array("list"=>$r)
			);
		}
		$this->response($back,"json");
	}
	
	public function seter()
	{
		$message = array(
			"app_id"=> I("appId", ""),
			"uid"=> I("uid",0),
			"channel"=>I("channel", "jiangsu"),
			"tag"=>"error",
			"program_id"=>I("programId",0),
			"query_info"=>I("plat", "").I("playurl",""),
			"create_time"=>date("Y-m-d H:i:s")
		);
		$r = D("DmLink")->add($message);
		$this->response(array("error"=>0),"json");
	}
	
	public function mmm()
    {
    	$lt = I("time", "");
    	if(empty($lt)){
    		$lt = time();
    	}else {
    		$lt = strtotime($lt);
    	}
    	$list = array(
    		"jiangsu"=> "jsyd",
    		"tianjin"=> "tjlt",
    		"jiangxi"=> "jxyd",
    		"henan"=> "hnyd",
    		"sichuan"=>"scyd"
    	);
    	$map["day"] = date("Y-m-d", strtotime("-1 day",$lt));
    	$r = D("ReportUser")->where($map)->find();
    	$user = D("ReportDay")->where($map)->find();
    	$message = array(
    		"day"=>date("Y-m-d", strtotime("-1 day",$lt)),
    		"add_user_num"=>$user["newuser"],
    		"new_pipeline"=>"",
    		"user_num"=>$r["today_user"],
    		"active_change_rate"=>"",//转化率
    		"start_apk_num"=>"",//apk开启次数
    		"access_num"=>"",//访问次数
    		"return_num"=>$r["today_user"]-$user["newuser"]."",//回访
    		"new_user_rate"=>ceil($user["newuser"]/$r["today_user"]*100)."%",//新用户活跃占比
    		"old_user_rate"=>(100-ceil($user["newuser"]/$r["today_user"]*100))."%",//老用户活跃占比
    		"user_paly_num"=>"",//播放人数
    		"user_play_rate"=>"",//播放人数占比
    		"paly_num"=>$r["play"],//播放次数
    		"play_num_rate"=>"",//播放次数占比
    		"user_pay_num"=>"",//订购次数
    		"featured_pay_num"=>$r["new_customer"],//订购用户数
    		"pay_num"=>$r["today_order"],
    		"vest_id"=> "xueersi",
			"channel_id"=> "iptv",
			"platform_id"=> $list[$r["channel"]]
    	);
    	if(!empty($list[$r["channel"]])){
    		$r = curl("http://223.111.206.251:7010/SciGraphica/Xueersi/xueersiDailyInfo",array("data"=>$message));    		
    	}
		return 1;
    }
	public function yy()
	{
		$channel = I("channel", "jiangsu");
		$appId = I("appId", "");
		$uid = I("uid", "");
		$token = I("token", "");
		$snNo = I("snNo", "");
		$backUrl = I("backUrl", "");
		$product = I("product","");
        $payMode = "bill";
        $programId = 0;
		$p = D("Product")->where(array("product"=>$product))->find();
		if(!empty($p["id"])){
			$productList = array(
				array(
					"productCode"=> $p["product"],
					"productPrice"=> $p["price"]/100,
					"productUnit"=> "个",
					"productCount"=> 1
				)
			);
		}
        $orderNo = D("Order")->preOrder($uid,$channel,$appId,$product,$programId,$payMode);
		$not = C("API_DOMAIN")."pass/notify/channel/".$channel."/od/".$orderNo;
		$r = D("Auth")->tvPay($orderNo,$productList,$phoneNum,$not,$backUrl,$token,$snNo);
		$this->response($r,"json");
	}
	
	public function sjb()
	{
		$i = I("i", 1);
		set_time_limit(0);
		$c = [];
		$x = "用户,观看时长,使用次数,节目数量"."\r\n";
		$y = [];
		
		$handle = fopen(C("SRC_PATH")."program.csv","r");
        while($data = fgetcsv($handle,1024,",")){
        	$program_id = D("Program")->where(array("code"=>$data[0]))->find();
        	$category_id = D("Category")->where(array("code"=>$data[1]))->find();
        	$r = D("CategoryProgram")->where(array("category_id"=>$category_id["id"], "program_id"=>$program_id["id"]))->delete();
        	$y[] = array($category_id["id"],$program_id["id"],$r);
        }
		$this->response($y,"json");
	}
	
	//河南的上传
	public function impload()
	{
		set_time_limit(0);
    	$saveName = ($type == "image")?"":array('date','YmdHis');
    	$r = array("error"=>-1);
        $config = array(
            'rootPath'=>C("SRC_PATH"),
            'savePath'=>'',
            'maxSize'=>0,
            'exts'=>array("zip"),
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
			$this->archive($origin_file);
        }
	}
	
	private function archive($origin_file)
    {
    	if(empty($origin_file)){
    		return;
    	}
        Vendor("pclzip");
        $zipFile = $origin_file;
        $logs = "";
        $zip = new \PclZip($zipFile);
        $result = $zip->extract(PCLZIP_OPT_PATH, "/home/www/xueersi/activity/");
        if ($result == 0) {
            $logs .= "Error : " . $zip->errorInfo(true);
            $error = -1;
        }else{
            $i = 0;
            foreach ($result as $v){
                $logs .= $v["stored_filename"]." : ".$v["status"]."\r\n";
                $i++;
            }
            $logs .= "Total: ".$i."\r\n";
            $error = 0;
        }
        @unlink($zipFile);
        $this->response(array("log"=>$logs, "err"=>$error),"json");
    }
    public function dm_play_report()
    {
    	set_time_limit(0);
    	$time = I("time", date("Y-m-d"));
    	$needtime = date("Y-m-d H:i:s", strtotime("-1 day",strtotime($time)));
    	$r = D("ReportUser")->henanreport($needtime);
    	$this->response(array($r,$needtime),"json");
    }
    
    public function sichuan_report()
    {
    	D("ReportUser")->contentInfo();
    	D("ReportUser")->userVod();
    	D("ReportUser")->setChk();
    	$this->response(1,"json");
    }
    
    public function new_activecode()
    {
    	$number = I("number", 1);
    	$product = I("product", "");
    	$time = I("time", date("Y-m-d H:i:s "));
		$list = array();
		$back = array("err"=>-1);
		if(empty($product)){
			$this->response($back, "json");
		}
    	for($i = 0;$i < $number;$i++){
    		$message = array(
    			"code"=>strtoupper(random_string(16)),
    			"product"=>$product,
    			"active_time"=>$time
    		);
    		$r = D("ActiveCode")->add($message);
    		if($r){
    			$list[] = $r;
    		}else{
    			$i--;
    		}
    	}
 		$back = array("err"=>0,"body"=>$list);
    	$this->response($back, "json");
    }
    public function activecode_list()
    {
    	$status = I("status", 0);
    	$code = I("code", "");
    	$page = I("page", 1);
    	$acount = I("acount", 1);
    	$back = array("err"=>-1);
    	if($status != 0){
    		$map["status"] = $status;
    	}
    	if(!empty($code)){
    		$map["code"] = $code;
    	}
    	$r = D("ActiveCode")->where($map)->limit(($page-1)*$acount, $acount)->select();
    	$total = D("ActiveCode")->where($map)->count();
    	if($r){
    		$back = array("err"=>0, "body"=>array("list"=>$r, "total"=>ceil($total/$acount)));
    	}
    	$this->response($back, "json");
    }
    public function activecode_edit()
    {
    	$code = I("code", "");
    	$product = I("product", "");
    	$time = I("time", "");
    	$back = array("err"=>-1);
    	if(empty($code)){
    		$this->response($back, "json");
    	}
    	if(!empty($product)){
    		$message["product"] = $product;
    	}
    	if(!empty($time)){
    		$message["active_time"] = $time;
    	}
    	$r = D("ActiveCode")->where(array("code"=>$code))->save($message);
    	if($r){
    		$back = array("err"=>0);
    	}
    	$this->response($back, "json");
    }
    public function activecode_export()
    {
    	$status = I("status", 0);
    	$code = I("code", "");
    	$back = array("err"=>-1);
    	if($status != 0){
    		$map["status"] = $status;
    	}
    	if(!empty($code)){
    		$map["code"] = $code;
    	}
    	$r = D("ActiveCode")->where($map)->select();
		$mm = "序号,激活码,产品编码,有效时间,激活时间,状态"."\r\n";
		foreach($r as $index => $v){
			$mm .= $index.",".$v["code"].",".$v["code"].",".$v["active_time"].",".$v["use_time"].",".$v["status"]."\r\n";
		}
		
		set_time_limit(0);
        $stream = $mm;
		
		header("Content-type:application/octet-stream");
        header("Accept-Ranges:bytes");
        header("Accept-Length:".strlen($stream));
        header("Content-Disposition: attachment; filename=".date("YmdHis")."activecode.csv");
        echo $stream;
    }
	public function haha()
	{
		$baseTime = time();
		$authUsers = [];
		$baseTime = strtotime("-1 hour",$baseTime);
		$map = array(
		    "expire_time"=>array("exp","is not null"),
		    "create_time"=>array("like","%".date("-d H:",$baseTime)."%")
		);
		$currentDay = date("j",$baseTime);
		if($currentDay == date("t",$baseTime) && $currentDay != "31"){
		    $mapOrderTime = array();
		    for($i=$currentDay;$i<=31;$i++){
		        $mapOrderTime[] = "create_time like '%".date("-".$i." H:",$baseTime)."%'";
		    }
		    unset($map["create_time"]);
		    $map["_string"] = implode(" or ",$mapOrderTime);
		}
		$cp = D("CustomerProduct")->where($map)->select();
		foreach ($cp as $v){
		    $tel = D("Customer")->where(array("uid"=>$v["uid"]))->getField("username");
		    if(empty($tel))continue;
		    $rAuth = D("Auth")->jsAuthOnly($tel,$v["product"]);
		    if($rAuth){
		        $authUsers[] = [
		            "tel"=>$tel,
		            "product"=>$v["product"]
		        ];
		    }
		}
		$this->response(array("cp"=>$cp, "map"=>$map, "authUsers"=>$authUsers), "json");
		// return $authUsers;
	}
}
?>