<?php
/**
 * User: Hola
 * Date: 2018/9/17
 * Time: 16:06
 */
namespace Home\Controller;
use Think\Controller;

class MediaController extends Controller
{

    public function index()
    {

    }

    /*
     * http://qingke.diyike.vip:7101/api/epg/play/QK000000010000000000009007
     */
    public function play()
    {
        $code = I("code","");

        $mediaUrl = "";
        $programId = D("Movie")->where(["media"=>$code])->getField("program_id");
        if(!empty($programId)){
            $mediaUrl = D("Movie")->where(["program_id"=>$programId,"platform"=>"gitv"])->getField("media");
        }
        if(empty($mediaUrl)){
            $head = "HTTP/1.1 404 Not Found";
        }else{
            $head = "Location:{$mediaUrl}";
        }
        header($head);
        exit();
    }
}