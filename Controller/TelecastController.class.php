<?php
namespace Home\Controller;
use Think\Controller\RestController;
//use Overtrue\Pinyin\Pinyin;
Class TelecastController extends RestController
{
	public function _initialize()
    {
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
	public function getquestion(){
		$program_id = I("id",0);
		$back = array("error"=>1);
		$r = D("ProgramQuestion")->where(array("program_id"=>$program_id))->order("start_time")->select();
		$img = D("AiCourseLesson")->where(array("program_id"=>$program_id))->find();
		$list = array();
		foreach($r as $val){
			$list[] = array(
				"id"=>$val["id"],
				"programId"=>$val["program_id"],
				"startTime"=>$val["start_time"],
				"lastTime"=>$val["last_time"],
				"answer"=>$val["answer"],
				"right"=>$val["right"],
				"img"=>domain_img($val["img_path"])
			);
		}
		if($r){
			$back = array(
				"error"=>0,
				"body"=>array("list"=>$list,"qrcode"=>$img["img_path"] ? domain_img($img["img_path"]) : null)
			);
		}
		$this->response($back,"json");
	}
	
	//获取答题历史
	public function answer_list()
	{
		$uid = I("uid", "");
		$program_id = I("programId", 0);
		$back = array("error"=>1);
		$r = D("AnswerHis")->where(array("uid"=>$uid,"program_id"=>$program_id))->select();
		if($r){
			$back = array("error"=>0,"body"=>array("list"=>$r));
		}
		$this->response($back,"json");
	}
	//获取直播课列表
	public function telecast_list()
	{
		$back = array("error"=>1);
		$r = D("Telecast")->order("start_time")->select();
		$list = array();
		foreach($r as $index=>$v){
			$list[] = array(
					"programId"=>$v["program_id"],
					"startTime"=>strtotime($v["start_time"])*1000,
					"length"=>$v["length"],
					"title"=>$v["title"]
				);
		}
		if($r){
			$back = array(
				"error"=>0,
				"body"=>array("list"=>$list),
			);
		}
		$this->response($back,"json");
	}
	
	//存记录
	public function user_answer()
	{
		$uid = I("uid", "");
		$program_id = I("programId", 0);
		$question_id = I("questionId",0);
		$user_answer = I("answer",0);
		$back = array("error"=>1);
		$right = 0;
		if(!empty($question_id) && !empty($user_answer) && !empty($uid)){
			$answer = D("ProgramQuestion")->where(array("id"=>$question_id))->find();
			if($answer["right"] == $user_answer){
				$right = 1;
			}
			$message = array(
				"uid"=>$uid,
				"program_id"=>$program_id,
				"question_id"=>$question_id,
				"user_answer"=>$user_answer,
				"right"=>$right
			);
			$r = D("AnswerHis")->add($message);
		}
		if($r){
			$back = array(
				"error"=>0,
				"body"=>array("right"=>$right)
			);
		}
		$this->response($back,"json");
	}
	public function telecast_number(){
		$value = S('programuser');
		if(!$value){
			S('programuser',50,3000);
		}else{
			S('programuser',$value+1,3000);
		}
		$back = array("error"=>0,"body"=>S('programuser'));
		$this->response($back,"json");
	}
	//计数
	public function enroll(){
		$programId = I("programId", "");
		$tag = I("tag", 1);
		$back = array("error"=>1);
		if(!empty($programId)){
			$redis = new \Redis();
	        $redis->connect(C("REDIS.HOST"),intval(C("REDIS.PORT")));
	        $key = "enroll".$programId;
	        $num = $redis->get($key);
	        if(empty($num)){
	        	$num = 1000;
	        	$redis->setTimeout($key,18000);
	        }
	        if($tag == 1){
	        	if($num < 1000){
	        		$num = 1000;
	        	}
	        	$redis->set($key,$num+rand(1,5));
	        }
	        $redis->close();
		}
		if($num){
			$back = array("error"=>0,"body"=>array("customer"=>$num));
		}
		$this->response($back,"json");
	}
}
?>