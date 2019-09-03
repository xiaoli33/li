<?php
/**
 * User: Hola
 * Date: 2018/12/24
 * Time: 16:56
 */
namespace Home\Controller;
use Think\Controller\RestController;
Class MobController extends RestController
{

    var $app;

    public function _initialize()
    {
        if (in_array(strtolower(ACTION_NAME), ["transmit"])) {
            return true;
        }
        $params = array_merge($_GET, $_POST);
        $sign = $params['sign'];
        if (empty($sign) || empty($params["appId"])) {
            $s = array("error" => -3);
            $this->response($s, 'json');
        } else {
            $app = D("App")->where(array("app_id" => $params["appId"]))->find();
            if ($app["status"] != 1) {
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
        $wechat = I("wechat","");

        $s = ["error"=>-1];

        if(empty($userId) && empty($wechat)){
            $this->response($s, 'json');
        }

        $info = [];
        if(!empty($userId)){
            $info["username"] = $userId;
        }
        if(!empty($wechat)){
            $info["wechat"] = $wechat;
        }
        $user = D("Customer")->login($info,$channel,$appId);
        if(is_array($user)){
            $s["error"] = 0;
            $s["body"] = $user;
        }

        $this->response($s, 'json');
    }

    public function course_list()
    {
        $appId = I("appId","");
        $grade = I("grade",1);
        $tag = I("tag","",urldecode);
        $page = I("page",1,intval);
        $count = I("count",20,intval);

        $s = ["error"=>-1];

        $r = D("CourseView")->getListByGradeTag($appId,$grade,$tag,$page,$count);
        if($r["total"]){
            $s["error"] = 0;
            $s["body"] = $r;
        }

        $this->response($s, 'json');
    }

    public function my_course_list()
    {
        $appId = I("appId","");
        $token = I("token","");
        $page = I("page",1,intval);
        $count = I("count",20,intval);

        $s = ["error"=>-1];

        $uid = D("Customer")->uid($token);
        if(empty($uid)){
            $s["error"] = -2;
            $this->response($s, 'json');
        }

        $r = D("CourseChose")->getList($uid,$page,$count);
        if($r["total"]){
            $s["error"] = 0;
            $s["body"] = $r;
        }

        $this->response($s, 'json');
    }

    public function course()
    {
        $courseId = I("courseId","");

        $s = ["error"=>-1];

        $course = D("AiCourse")->where(array("id"=>$courseId))->find();
        if(empty($course) || $course["status"] == 0){
            $this->response($s, 'json');
        }
        $sTime = "";
        $eTime = "";
        if(!empty($course["section"])){
            $sectionArray = json_decode($course["section"],true);
            $sDate = $sectionArray[0]["sDate"];
            $eDate = $sectionArray[0]["eDate"];
            $sTime = $sectionArray[0]["time"][0]["sTime"];
            $eTime = $sectionArray[0]["time"][0]["eTime"];
        }
        $s["error"] = 0;
        $s["body"] = [
            "course"=>[
                "title"=>empty($course["title"])?"":$course["title"],
                "subTitle"=>empty($course["sub_title"])?"":$course["sub_title"],
                "tags"=>empty($course["tags"])?"":$course["tags"],
                "bg"=>domain_img($course["img_path"]),
                "poster"=>domain_img($course["detail_img"]),
                "description"=>empty($course["description"])?"":$course["description"]
            ]
        ];
        if($course["teacher_id"]){
            $teacher = D("AiTeacher")->where(array("id"=>$course["teacher_id"]))->find();
            $s["body"]["teacher"] = [
                "name"=>empty($teacher["name"])?"":$teacher["name"],
                "img"=>domain_img($teacher["img_path"]),
                "description"=>empty($teacher["description"])?"":$teacher["description"]
            ];
        }
        $l = D("AiCourseLesson")->where(array("course_id"=>$courseId))->order("sort_num")->select();
        $s["body"]["count"] = count($l);
        foreach ($l as $v){
            $sDate = "";
            if(!empty($v["opts_date"])){
                $opts_date = json_decode($v["opts_date"],true);
                $sDate = $opts_date[0]["sDate"];
            }
            $s["body"]["list"][] = [
                "programId"=>$v["program_id"],
                "title"=>$v["title"],
                "sDate"=>$sDate,
                "sTime"=>$sTime,
                "eTime"=>$eTime
            ];
        }

        $s["body"]["systime"] = date("Y-m-d H:i:s");
        $this->response($s, 'json');
    }

    public function media()
    {
        $token = I("token","");
        $programId = I("programId","");

        $s = ["error"=>-1];

        $uid = D("Customer")->uid($token);
        if(empty($uid)){
            $s["error"] = -2;
            $this->response($s, 'json');
        }

        $body = D("Movie")->getList($programId);
        if(!empty($body["list"])){
            $s = array(
                "error"=>0,
                "body"=>$body
            );
        }

        $this->response($s, 'json');
    }

    public function course_auth()
    {
        $appId = I("appId","");
        $channel = I("channel","");
        $token = I("token","");
        $courseId = I("courseId","");
        $programId = I("programId","");

        $s = ["error"=>-1];

        if(empty($token) || (empty($courseId) && empty($programId))){
            $s = array("error" => -1);
            $this->response($s, 'json');
        }

        $uid = D("Customer")->uid($token);
        if(empty($uid)){
            $s["error"] = -2;
            $this->response($s, 'json');
        }
        $user = ["uid"=>$uid];
        $user["username"] = D("Customer")->where(array("uid"=>$uid))->getField("username");

        $authResult = false;
        if(!empty($courseId)){
            $contentId = $courseId."@course";
        }
        if(!empty($programId)){
            $contentId = [
                $programId."@program"
            ];
            $cp = D("CategoryProgram")->where(array("program_id"=>$programId))->select();
            foreach($cp as $v){
                $contentId[] = $v["category_id"]."@category";
            }
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
            switch ($channel){
                case "sxhe":
                    if(empty($pIds)){
                        $pIds = ["SX_XRS1"];
                    }
                    $authResult = D("HeCq")->auth($uid);
                    break;
                default:
                    $authResult = D("CustomerProduct")->productsAuth($user,$productList,$contentId);
            }
        }

        if(!empty($authResult)){
            $s["error"] = 0;
        }else{
            $s["error"] = -1;
        }

        $this->response($s, 'json');
    }



    public function order_he()
    {
        $appId = I("appId","");
        $channel = I("channel","");
        $token = I("token","");
        $product = I("product","");

        $s = array("error" => -1);

        if(empty($token) || empty($product)){
            $s = array("error" => -1);
            $this->response($s, 'json');
        }
        $uid = D("Customer")->uid($token);
        if(empty($uid)){
            $s["error"] = -2;
            $this->response($s, 'json');
        }
        $deviceType = "android";
        $agent = strtolower($_SERVER["HTTP_USER_AGENT"]);
        if(strpos($agent, 'iphone') !== false || strpos($agent, 'ipad') !== false){
            $deviceType = "ios";
        }

/*        $s["error"] = 0;
        $s["deviceType"] = $deviceType;*/
        $s = D("HeCq")->h5PayInfo1($uid,$product);
        echo $s;
        //$this->response($s, 'json');
        if(function_exists("fastcgi_finish_request")){
            fastcgi_finish_request();
        }

        if($channel == "sxhe" && $deviceType == "ios"){
            if(empty($product)){
                $product = "SX_XRS1";
            }
            D("HeSx")->sendTplSMS($uid,$product);
        }
    }


    public function my_order_list()
    {
        $appId = I("appId","");
        $channel = I("channel","");
        $token = I("token","");
        $page = I("page",1,intval);
        $count = I("count",20,intval);

        $s = array("error" => -1);

        if(empty($token)){
            $s = array("error" => -1);
            $this->response($s, 'json');
        }
        $uid = D("Customer")->uid($token);
        if(empty($uid)){
            $s["error"] = -2;
            $this->response($s, 'json');
        }

        $r = D("Order")->myList($uid,$channel,$appId,$page,$count);
        if($r["total"]){
            $s["error"] = 0;
            $s["body"] = $r;
        }

        $this->response($s, 'json');
    }

}
