<?php
namespace Home\Controller;
use Think\Controller\RestController;
use Overtrue\Pinyin\Pinyin;
Class AicmsController extends RestController
{
//栏目编辑
	public function aicategory_edit()
	{
		$id = I("id", "");
		$title = I("title", "");
		$status = I("status", 2);
		$back = array("err"=>1);
		$app_id = I("appId", "");
		if($status != 2){
			$message = array("title"=>$title,"status"=>$status);
		}else {
			$message = array("title"=>$title);
		}
		$message["app_id"] = $app_id; 
		if(empty($id)){
			$r = D("AiCategory")->add($message);
		}else {
			$r = D("AiCategory")->where(array("id"=>$id))->save($message);
		}
		if($r){
			$back = array("err"=>0);
		}
		$this->response($back, "json");
	}
	//栏目列表
	public function aicategory_list(){
		$id = I("id", "");
		$back = array("err"=>1);
		$status = I("status", 2);
		$acount = I("acount", 20);
		$page = I("page", 1);
		$app_id = I("appId", "");
		$message = array();
		if(!empty($id)){
			$message['id'] = $id;
		}
		if($status != 2){
			$message['status'] = $status;
		}
		if(!empty($app_id)){
			$message['app_id'] = $app_id;
		}
		$r = D("AiCategory")->where($message)->limit(($page-1)*$acount,$acount)->getlist();
		$all = D("AiCategory")->where($message)->count();
		if($r){
			$back = array(
				"err"=> 0,
				"body"=>array("list"=>$r,"total"=>ceil($all/$acount))
			);
		}
		$this->response($back, "json");
	}
	//课程
	public function course_edit(){
		$id = I("id", "");
		$back = array("err"=>1);
		$message = array(
			"title"=>I("title", ""),
			"sub_title"=> I("sub_title", ""),
			"description"=> I("description", ""),
			"teacher_id"=> I("teacher_id", ""),
			"price"=> I("price", 0),
			"sale"=> I("sale", 0),
			"status"=> I("status", 0)
		);
		if(empty($id)){
			$r = D("AiCourse")->add($message);
		}else {
			$r = D("AiCourse")->where(array("id"=>$id))->save($message);
		}
		if($r){
		}
		$back = array("err"=> 0);
		$this->response($back, "json");
	}
	//课程列表
	public function course_list(){
		$id = I("id", "");
		$status = I("status", 2);
		$back = array("err"=>1);
		$acount = I("acount", 20);
		$page = I("page", 1);
		$message = array();
		if(!empty($id)){
			$message['id'] = $id;
		}
		if($status != 2){
			$message['status'] = $status;
		}
		$r = D("AiCourse")->where($message)->limit(($page-1)*$acount, $acount)->select();
		$total = D("AiCourse")->where($message)->count();
		if($r){
			$list = array();
			foreach($r as $val){
				$list[] = array(
					"id"=>$val["id"],
					"title"=>$val["title"],
					"sub_title"=>$val["sub_title"],
					"image"=>domain_img($val["img_path"]),
					"infoImg"=>domain_img($val["detail_img"]),
					"detail_img"=>domain_img($val["detail_img"]),
					"description"=>$val["description"],
					"teacher_id"=>$val["teacher_id"],
					"section"=> json_decode($val["section"]),
					"price"=>$val["price"],
					"sale"=>$val["sale"],
					"status"=>$val["status"]
				);
			}
			$back = array(
				"err"=> 0,
				"body"=>array("list"=>$list),
				"total"=>ceil($total/$acount)
			);
		}
		$this->response($back, "json");
	}
	//课程的时间
	public function courseDate_edit(){
		$id = I("id", "");
		$section = I("section", array());
		$back = array("err"=>1);
		$r = D("AiCourse")->where(array("id"=>$id))->save(array("section"=>json_encode($section)));
		if($r){
			$back = array("err"=> 0);
		}
		$this->response($back, "json");
	}
	public function categoryCourse_list(){
		$acount = I("acount", 10);
		$page = I("page", 1);
		$categoryId = I("id", 0);
		$back = array("err"=>1);
		$map = array();
		if(!empty($categoryId)){
			$map["category_id"] = $categoryId;
		}
		$r = D("AiCategoryCourse")->where($map)->limit(($page-1)*$acount,$acount)->select();
		$total = D("AiCategoryCourse")->where($map)->count();
		if($r){
			$back = array(
				"err"=>0,
				"body"=>array("list"=>$r),
				"total"=>ceil($total/$acount)
			);
		}
		$this->response($back, "json");
	}
	//栏目课程排序
	public function categoryCourse_sort(){
		$sort = I("sort", array());
		$back = array("err"=>1);
		if(empty($sort)){
			$this->response($back, "json");
		}
		$when = "";
        $ids = array();
        foreach ($sort as $id => $v)
        {
            $when .= " when ".$id." then ".$v;
            $ids[] = $id;
        }
        $sql = "update ai_category_course set sort_num = case id ".$when." end where id in (".implode(",",$ids).")";
        $r = D("AiCategoryCourse")->execute($sql);
        if($r){
        	$back = array("err"=>0);
        }
        $this->response($back, "json");
	}
	//课程节目排序
	public function courseLesson_sort(){
		$sort = I("sort", array());
		$back = array("err"=>1);
		if(empty($sort)){
			$this->response($back, "json");
		}
		$when = "";
        $ids = array();
        foreach ($sort as $id => $v)
        {
            $when .= " when ".$id." then ".$v;
            $ids[] = $id;
        }
        $sql = "update ai_course_lesson set sort_num = case id ".$when." end where id in (".implode(",",$ids).")";
        $r = D("AiCourseLesson")->execute($sql);
        if($r){
        	$back = array("err"=>0);
        }
        $this->response($back, "json");
	}
	//栏目课程编辑
	public function categoryCourse_edit(){
		$id = I("id", "");
		$back = array("err"=>1);
		$message = array(
			"course_id"=> I("courseId", ""),
			"category_id"=> I("categoryId", ""),
			"sort_num"=> I("sort", "")
		);
		if(empty($id)){
			$r = D("AiCategoryCourse")->add($message);
		}else {
			$r = D("AiCategoryCourse")->where(array("id"=>$id))->save($message);
		}
		if($r){
			$back = array("err"=> 0);
		}
		$this->response($back, "json");
	}
	//课程_节目
	public function courseLesson_edit(){
		$id = I("id", "");
		$back = array("err"=>1);
		$message = array(
			"course_id"=> I("courseId", ""),
			"program_id"=> I("programId", ""),
			"title"=> I("title", ""),
			"length"=>I("length", 0)
		);
		if(empty($id)){
			$r = D("AiCourseLesson")->add($message);
		}else {
			$r = D("AiCourseLesson")->where(array("id"=>$id))->save($message);
		}
		if($r){
			$back = array("err"=> 0);
		}
		$this->response($back, "json");
	}
	//课程_节目_时间
	public function lesson_time(){
		$id = I("id", "");
		$opts_date = I("optsDate", array());
		$back = array("err"=>1);
		$r = D("AiCourseLesson")->where(array("id"=>$id))->save(array("opts_date"=>json_encode($opts_date)));
		if($r){
			$back = array("err"=> 0);
		}
		$this->response($back, "json");
	}
	//课程_节目列表
	public function courseLesson_list(){
		$id = I("id", "");
		$status = I("status", 2);
		$courseId = I("courseId", 0);
		$back = array("err"=>1);
		$message = array();
		if(!empty($id)){
			$message['id'] = $id;
		}
		if(!empty($courseId)){
			$message['course_id'] = $courseId;
		}
		if($status != 2){
			$message['status'] = $status;
		}
		$r = D("AiCourseLesson")->where($message)->order("sort_num")->select();
		if($r){
			$list = array();
			foreach($r as $val){
				$list[] = array(
					"id"=>$val["id"],
					"courseId"=>$val["course_id"],
					"programId"=>$val["program_id"],
					"title"=>$val["title"],
					"optsDate"=> json_decode($val["opts_date"]),
					"sort"=>$val["sort_num"],
					"img"=>domain_img($val["img_path"]),
					"length"=>$val["length"]
				);
			}
			$back = array(
				"err"=> 0,
				"body"=>array("list"=>$list)
			);
		}
		$this->response($back, "json");
	}
	//教师
	public function teacher_edit(){
		$id = I("id", 0);
		$back = array("err"=>1);
		$message = array(
			"name"=> I("name", ""),
			"description"=> I("description", "")
		);
		if($id == 0){
			$r = D("AiTeacher")->add($message);
		}else {
			$r = D("AiTeacher")->where(array("id"=>$id))->save($message);
		}
		if($r){
			$back = array("err"=> 0);
		}
		$this->response($back, "json");
	}
	//老师列表
	public function teacher_list(){
		$id = I("id", "");
		$back = array("err"=>1);
		$message = array();
		if(!empty($id)){
			$message['id'] = $id;
		}
		$r = D("AiTeacher")->where($message)->select();
		if($r){
			$list = array();
			foreach($r as $val){
				$list[] = array(
					"id"=>$val["id"],
					"name"=>$val["name"],
					"image"=>domain_img($val["img_path"]),
					"description"=>$val["description"],
				);
			}
			$back = array(
				"err"=> 0,
				"body"=>array("list"=>$list)
			);
		}
		$this->response($back, "json");
	}
	
	public function question_edit(){
		$id = I("id", "");
		$back = array("err"=>1);
		$message = array(
			"program_id"=> I("programId", ""),
			"start_time"=> I("startTime", ""),
			"last_time"=> I("lastTime", ""),
			"answer"=> I("answer", 1),
			"right"=> I("right", 1)
		);
		if(empty($id)){
			$r = D("ProgramQuestion")->add($message);
		}else {
			$r = D("ProgramQuestion")->where(array("id"=>$id))->save($message);
		}
		if($r){
			$back = array(
				"err"=> 0,
				"body"=>array("list"=>$r)
			);
		}
		$this->response($back, "json");
	}
	
	public function question_list(){
		$id = I("id", "");
		$programId = I("programId", "");
		$acount = I("acount", 10);
		$page = I("page", 1);
		$back = array("err"=>1);
		$message = array();
		if(!empty($id)){
			$message["id"] = $id;
		}
		if(!empty($programId)){
			$message["program_id"] = $programId;
		}
		$r = D("ProgramQuestion")->where($message)->limit(($page-1)*$acount, $acount)->select();
		if($r){
			$list = array();
			foreach($r as $val){
				$list[] = array(
					"id"=>$val["id"],
					"programId"=>$val["program_id"],
					"image"=>domain_img($val["img_path"]),
					"startTime"=>$val["start_time"],
					"lastTime"=>$val["last_time"],
					"answer"=>$val["answer"],
					"right"=>$val["right"]
				);
			}
			$back = array(
				"err"=> 0,
				"body"=>array("list"=>$list)
			);
		}
		$this->response($back, "json");
	}
	
//图片
	public function ai_image(){
		$id = I("id","");
		$type = I("type", "");	//0栏目，1课程，2教师，3问题，4节目二维码
		$table = array("AiCategory", "AiCourse", "AiTeacher","ProgramQuestion","AiCourseLesson");
		$file = array("category", "course", "teacher","question","qrcode");
		$back = array("err"=> 1);
		if(!empty($id)){
			$config = array(
	                'rootPath'=>C("SRC_PATH"),
	                'savePath'=>'ai/',
	                'maxSize'=>0,
	                'exts'=>array("jpg","png","gif","jpeg"),
	                'saveName'=>array('date','YmdHis'),
	                'subName'=>$file[$type]."/".$id
	            );
	        $upload = new \Think\Upload($config);
	        $info = $upload->upload();
	        if($info){
	        	$img_file  = str_replace(array("../","./"),"",$info['file']['savepath']).$info['file']['savename'];
	        	$his = D($table[$type])->where(array("id"=>$id))->find();
	        	if(!empty($his["img_path"])){
	        		@unlink(C("SRC_PATH").$his["img_path"]);
	        	}
	        	$r = D($table[$type])->where(array("id"=>$id))->save(array("img_path"=>$img_file));
	        	if($r){
	        		$back = array("err"=> 0,"flile"=>domain_img($img_file)); 	        		
	        	}
	        }else {
	        	$back = array("err"=> $upload->getError());
	        }
		}
        $this->response($back, "json");
	}
	public function courseinfo_image(){
		$id = I("id","");
		$type = I("type", "");	//0栏目，1课程，2教师，3问题，
		$table = array("AiCategory", "AiCourse", "AiTeacher","ProgramQuestion");
		$back = array("err"=> 1);
		if(!empty($id)){
			$config = array(
	                'rootPath'=>C("SRC_PATH"),
	                'savePath'=>'ai/course/',
	                'maxSize'=>0,
	                'exts'=>array("jpg","png","gif","jpeg"),
	                'saveName'=>array('date','YmdHis'),
	                'subName'=>$id
	            );
	        $upload = new \Think\Upload($config);
	        $info = $upload->upload();
	        if($info){
	        	$img_file  = str_replace(array("../","./"),"",$info['file']['savepath']).$info['file']['savename'];
	        	$his = D("AiCourse")->where(array("id"=>$id))->find();
	        	if(!empty($his["detail_img"])){
	        		@unlink(C("SRC_PATH").$his["detail_img"]);
	        	}
	        	$r = D("AiCourse")->where(array("id"=>$id))->save(array("detail_img"=>$img_file));
	        	if($r){
	        		$back = array("err"=> 0,"flile"=>domain_img($img_file)); 	        		
	        	}
	        }else {
	        	$back = array("err"=> $upload->getError());
	        }
		}
        $this->response($back, "json");
	}
	//删除
	public function del()
	{
		$id = I("id", 0);
		$type = I("type", 0);	//0:栏目，1：栏目课程，2：课程，3：课程节目，4：教师，5：问题
		$table = array("AiCategory", "AiCategoryCourse", "AiCourse", "AiCourseLesson", "AiTeacher", "ProgramQuestion");
		$back = array("err"=> 1);
		if(!empty($id)){
			$r = D($table[$type])->where(array("id"=>$id))->delete();
		}
		if($r){
			$back = array("err"=> 0);
		}
		 $this->response($back, "json");
	}
	
	//导入
	public function question_import()
	{
		set_time_limit(0);
		$id = I("id", "");
		$question = I("question", array());
		$success = array("err"=>1);
		if(!empty($question)){
			foreach($question as $val){
				$message = array(
					"program_id"=> $val["programId"],
					"start_time"=>$val["sTime"],
					"last_time"=>$val["lastTime"],
					"answer"=>$val["answer"],
					"right"=>$val["right"],
					"img_path"=>"telecast/".$val["programId"]."/".$val["img"].".png"
				);
				$x = D("ProgramQuestion")->where(array("program_id"=>$val["programId"],"start_time"=>$val["sTime"]))->find();
				if($x){
					$r = D("ProgramQuestion")->where(array("program_id"=>$val["programId"],"start_time"=>$val["sTime"]))->save($message);
				}else {
					$r = D("ProgramQuestion")->add($message);
				}
				if($r){
					$success[] = $message; 
				}
			}
		}
		$this->response($success, "json");
	}
	
	public function questionimg()
	{
		set_time_limit(0);
		$id = I("id", "");
		$question = I("question", array());
		
		if(!empty($id) && !empty($question)){
			foreach($question as $val){
				$img = "/telecast/".$id."/".$val[4].".png";
				$message = array(
					"program_id"=> $id,
					"start_time"=>$val[0]
				);
				$map = array("program_id"=> $id,"start_time"=>$val[0]);
				$rx = D("ProgramQuestion")->where($message)->find();
				$r = D("ProgramQuestion")->where($map)->save(array("img_path"=>$img));
				if($r){
					$success[] = array("rx"=>$rx,"message"=>$message,"img"=>$img); 
				}
			}
		}
		$this->response($success, "json");
	}
	
	//复制推荐位
	public function fzprogram()
	{
		$channel = I("channel",0);
		$app_id = I("appId", 0);
		$message["tag"] = array(array("like", "%xm-%"));
		$r = D("Promotion")->where($message)->select();
		$list = array();
		foreach($r as $val){
			$xx = array(
				"tag"=>str_replace("xm",$channel,$val["tag"]),
				"app_id"=>$app_id,
				"img_path"=>$val["img_path"],
				"type"=>$val["type"],
				"value"=>$val["value"],
				"title"=>$val["title"],
				"trailer_id"=>$val["trailer_id"],
				"update_time"=>date("Y-m-d H:i:s"),
				"status"=>1
			);
			$fd = D("Promotion")->where(array("tag"=>$xx["tag"]))->find();
			if($fd){
				$hh = D("Promotion")->where(array("tag"=>$xx["tag"]))->save($xx);
			}else{
				$hh = D("Promotion")->add($xx);
			}
			$list[] = $hh ? 1:0;
		}
		$this->response($list, "json");
	}
	//复制产品包
	public function fzproduct()
	{
		$app_id = I("appId", 0);
		$channel = I("channel",0);
		if(empty($app_id)){
			$this->response(-1, "json");
		}else {
			$list = array();
			$r = D("Product")->where(array("app_id"=>"a_jvus01q5eavau0r7"))->select();
			foreach($r as $val){
				$message = array(
					"product"=>str_replace("dangbei", $channel, $val["product"]),
					"channel"=>$channel,
					"app_id"=>$app_id,
					"mod"=>$val["mod"],
					"price"=>$val["price"],
					"sales"=>$val["sales"],
					"expire_days"=>$val["expire_days"],
					"title"=>$val["title"],
					"img_path"=>$val["img_path"],
					"description"=>$val["description"],
					'status'=>$val["status"]
				);
				$x = D("Product")->where(array("product"=>$message["product"]))->find();
				if(!$x){
					$bk = D("Product")->add($message);					
				}
				$list[] = $bk ? 1:0;
			}
			$this->response($list, "json");
		}
	}
	//修改节目问题的节目id
	public function importchage(){
		set_time_limit(0);
		$list = I("list", array());
		$channel = I("channel", "");
		if(empty($list) || empty($channel)){
			$this->response(-1, "json");
		}
		$back = array();
		foreach($list as $val){
			if($val[$channel]){
				$r = D("ProgramQuestion")->where(array("program_id"=>$val["ott"]))->save(array("program_id"=>$val[$channel]));
				$back[] = $r ? $r."ottid".$val["ott"] : -1;				
			}
		}
		$this->response($back, "json");
	}
	//修改客户时间
	public function customer_course()
	{
		$uid = I("uid", "");
    	$courseId = I("courseId", 0);
    	$optDNum = I("optDNum", 1);
    	$optTNum = I("optTNum", 1);
    	$back = array("error"=>-1);

        if(empty($uid) || empty($courseId) || empty($optDNum) || empty($optTNum)){
        	$back["body"] = array(
        		"uid"=>$uid,
        		"courseId"=>$courseId,
        		"optDNum"=>$optDNum,
        		"optTNum"=>$optTNum
        	);
        	$this->response($back, "json");
        }

    	$r = D("AiCourseChose")->courseSelect($uid, $courseId, $optDNum, $optTNum);
    	if($r){
    		$back = array("error"=>0);
    	}
    	$this->response($back, "json");
	}
}
?>
