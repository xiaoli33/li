<?php
/**
 * User: Hola
 * Date: 2018/12/11
 * Time: 11:18
 */
namespace Home\Controller;
use Think\Controller\RestController;
Class AiController extends RestController
{

    var $app;

    public function _initialize()
    {
//      if (in_array(strtolower(ACTION_NAME), [""])) {
//          return true;
//      }
//      $params = array_merge($_GET, $_POST);
//      $sign = $params['sign'];
//      if (empty($sign) || empty($params["appId"])) {
//          $s = array("error" => -3);
//          $this->response($s, 'json');
//      } else {
//          $app = D("App")->where(array("app_id" => $params["appId"]))->find();
//          if ($app["status"] != 1) {
//              $s = array("error" => -3);
//              $this->response($s, 'json');
//          }
//          $this->app = $app;
//          $key = $app["app_key"];//$key = "5y86ykj3";
//
//          unset($params['sign']);
//          ksort($params);
//          $tmp = array();
//          foreach ($params as $v) {
//              $tmp[] = rawurldecode($v);
//          }
//
//          $str = implode("&", $tmp) . "&" . $key;
//          $sign2 = md5($str);
//          if ($sign != $sign2) {
//              $s = array("error" => -3);
//              $this->response($s, 'json');
//          }
//      }
    }

    public function index()
    {

    }

    public function user()
    {
        $uid = I("uid","");

        $s = array("error"=>-1);

        $token = I("token","");
        if(!empty($token)){
            $uid = D("Customer")->uid($token);
            if(empty($uid)){
                $s["error"] = -2;
                $this->response($s, 'json');
            }
        }

        $user = D("Customer")->info($uid);
        if(!empty($user)){
            $s["error"] = 0;
            $s["body"] = $user;
        }
        $this->response($s, 'json');
    }

    public function user_edit()
    {
        $uid = I("uid","");
        $sex = I("sex",0,intval);
        $grade = I("grade",0,intval);

        $s = array("error"=>0);

        $token = I("token","");
        if(!empty($token)){
            $uid = D("Customer")->uid($token);
            if(empty($uid)){
                $s["error"] = -2;
                $this->response($s, 'json');
            }
        }

        $info = [];
        $sex && $info["sex"] = $sex;
        $grade && $info["grade"] = $grade;

        D("Customer")->editInfo($uid,$info);

        $this->response($s, 'json');
    }

     public function category()
    {
    	$appId = I("appId", "");
		$genre = I("genre", "");
    	$run = I("run", "");
    	$back = array("error"=> -1);
    	if($run != "debug"){
    		$message = array("status"=> 1);
    	}
		if(!empty($genre)){
			$message["genre"] = array("like", $genre."%");
		}
    	$message["app_id"] = $appId;
    	$r = D("AiCategory")->getlist($message);
    	if($r){
    		$back = array(
    			"error"=> 0,
    			"body"=>array("list"=>$r),
    		);
    	}
    	$this->response($back, "json");
    }
    
    public function course_list()
    {
    	$categoryId = I("categoryId", "");
    	$page = I("page", 1);
    	$count = I("count", 20);
    	$mine = I("mine", 0);
    	$uid = I("uid", "");
    	$appId = I("appId", "");
    	$back = array("error"=>-1);

        $token = I("token","");
        if(!empty($token)){
            $uid = D("Customer")->uid($token);
            if(empty($uid)){
                $s["error"] = -2;
                $this->response($s, 'json');
            }
        }

    	if($mine){
    		$courseList = D("AiCourseMyView")->getlist($categoryId, $page, $count, $uid,$appId);
    	}else{
    		$courseList = D("AiCourseView")->getlist($categoryId, $page, $count, $uid, $mine);    			
    	}
    	if($courseList){
    		$back = array("error"=>0, "body"=>$courseList);
    	}
	    $this->response($back, "json");
    }
    
    public function section()
    {
    	$uid = I("uid", "");
    	$courseId = I("courseId", 0);
    	$back = array("error"=>-1);
		$channel = I("channel", "");
        $token = I("token","");
        if(!empty($token)){
            $uid = D("Customer")->uid($token);
            if(empty($uid)){
                $s["error"] = -2;
                $this->response($s, 'json');
            }
        }

    	$r = D("AiCourse")->selectCourse($courseId, $uid);
    	$choose = D("AiCourseChose")->where(array("uid"=>$uid, "course_id"=>$courseId))->find();
    	if(empty($choose)){
    		$course = D("AiCourse")->where(array("id"=>$courseId))->find();
    		$datanumber = json_decode($course["section"], true);
			$x = D("AiCourseChose")->courseSelect($uid, $courseId, $datanumber[count($datanumber)-1]["optDNum"], $datanumber[count($datanumber)-1]["time"]["0"]["optTNum"],$channel);
    	}
    	if($r){
    		$back = array("error"=>0, "body"=>array("list"=>$r));
    	}
    	$this->response($back, "json");
    }
    
    public function section_commit()
    {
    	$uid = I("uid", "");
    	$courseId = I("courseId", 0);
    	$optDNum = I("optDNum", 1);
    	$optTNum = I("optTNum", 1);
    	$channel = I("channel", "");
    	$back = array("error"=>-1);

        $token = I("token","");
        if(!empty($token)){
            $uid = D("Customer")->uid($token);
            if(empty($uid)){
                $s["error"] = -2;
                $this->response($s, 'json');
            }
        }

    	$r = D("AiCourseChose")->courseSelect($uid, $courseId, $optDNum, $optTNum,$channel);
    	if($r){
    		$back = array("error"=>0);
    	}
    	$this->response($back, "json");
    }
    public function course(){
    	$uid = I("uid", "");
    	$courseId = I("courseId", 0);
    	$back = array("error"=>-1);
		$channel = I("channel", "");
        $token = I("token","");
        if(!empty($token)){
            $uid = D("Customer")->uid($token);
            if(empty($uid)){
                $s["error"] = -2;
                $this->response($s, 'json');
            }
        }

    	$r = D("AiCourse")->courseinfo($courseId, $uid, $channel);
    	if($r){
    		$back = array("error"=>0, "body"=>array("list"=>$r, "time"=>strtotime(date("Y-m-d H:i:s"))*1000));
    	}
    	$this->response($back, "json");
    }
    public function collection()
    {
    	$uid = 	I("uid", "");
    	$categoryId = I("categoryId", 0);
    	$back = array("error"=>-1);

        $token = I("token","");
        if(!empty($token)){
            $uid = D("Customer")->uid($token);
            if(empty($uid)){
                $s["error"] = -2;
                $this->response($s, 'json');
            }
        }

    	if(empty($uid) || empty($categoryId)){
    		$this->response($back, "json");
    	}else {
    		$hav = D("Collection")->where(array("uid"=>$uid, "category_id"=>$categoryId, "status"=>1))->find();
    		$message = array(
    			"uid"=> $uid,
    			"category_id"=>$categoryId,
    			"status"=>1
    		);
    		if(empty($hav)){
    			$r = D("Collection")->add($message);    			
    		}		
    		if($r){
    			$back = array("error"=>0);
    		}
    		$this->response($back, "json");
    	}
    	
    }
    public function collection_del()
    {
    	$id = I("id", 0);
    	$uid = I("uid", "");
    	$back = array("error"=>-1);

        $token = I("token","");
        if(!empty($token)){
            $uid = D("Customer")->uid($token);
            if(empty($uid)){
                $s["error"] = -2;
                $this->response($s, 'json');
            }
        }

    	$r = D("Collection")->where(array("category_id"=>$id, "uid"=>$uid))->save(array("status"=>0));
    	if($r){
    		$back = array("error"=>0);
    	}
    	$this->response($back, "json");
    }
    public function collection_list()
    {
    	$uid = I("uid", "");
    	$page = I("page", 1);
    	$count = I("count", 10);
    	$back = array("error"=>-1);

        $token = I("token","");
        if(!empty($token)){
            $uid = D("Customer")->uid($token);
            if(empty($uid)){
                $s["error"] = -2;
                $this->response($s, 'json');
            }
        }

    	if(!empty($uid)){
    		$r = D("CollectionView")->getlist($uid, $page, $count);
    		if($r){
    			$back = array(
    				"error"=>0,
    				"body"=>$r
    			);
    		}
    	}
    	$this->response($back, "json");
    }
    public function course_auth()
    {
        $appId = I("appId","");
        $channel = I("channel","");
        $uid = I("uid","");
        $courseId = I("courseId","");

        $s = ["error"=>-1];

        $token = I("token","");
        if(!empty($token)){
            $uid = D("Customer")->uid($token);
            if(empty($uid)){
                $s["error"] = -2;
                $this->response($s, 'json');
            }
        }

        if(empty($uid) || empty($courseId)){
            $s = array("error" => -1);
            $this->response($s, 'json');
        }

        $user = ["uid"=>$uid];

        $authResult = false;
        
        $contentId = $courseId."@course";
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
            $authResult = D("CustomerProduct")->productsAuth($user,$productList,$contentId);
        }
        if(!empty($authResult)){
            $s["error"] = 0;
        }else{
            $s["error"] = -1;
        }

        $this->response($s, 'json');
    }
    //返回时间差
	public function time_auth(){
    	$time = I("time", 0);
    	$date = I("date", "");
    	$back = array("error"=>-1);
    	if(!empty($time)){
    		$seconds = strtotime(date("Y-m-d H:i:s")) - $time/1000;
    	}
    	if(!empty($date)){
    		$seconds = strtotime(date("Y-m-d H:i:s")) - strtotime($date);
    	}
    	if(isset($seconds)){
    		$back = array(
    			"error"=>0,
    			"body"=>ceil($seconds)
    		);
    	}
    	$this->response($back, "json");
    }
	//
	public function get_course()
	{
		$categoryId = I("categoryId", "");
		$back = array("error"=>-1);
		$r = D("AiCourse")->getcourse($categoryId);
		if(!empty($r)){
			$back = array("error"=>0, "body"=>array("list"=>$r));
		}
		$this->response($back, "json");
	}
}