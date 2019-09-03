<?php
/**
 * User: Hola
 * Date: 2018/12/24
 * Time: 14:21
 */
namespace Home\Controller;
use Think\Controller\RestController;
Class ApkController extends RestController
{

    public function index()
    {

    }

    public function upgrade()
    {
        $channel = I("channel","");
        $package = I("pkg","");
        $versionName = I("name","");

        $s = array("error"=>-1);

        $map = ["status"=>1];
        $channel && $map["channel"] = $channel;
        $package && $map["package"] = $package;
        $versionName && $map["version_name"] = $versionName;

        $apk = D("Apk")->where($map)->find();
        if(empty($apk)){
            $this->response($s, 'json');
        }
        $map = ["apk_id"=>$apk["id"],"status"=>1];
        $patch = D("ApkPatch")->where($map)->order("version_name desc")->find();
        if(empty($patch)){
            $this->response($s, 'json');
        }
        $s["error"] = 0;
        $s["body"] = [
            "channel"=>$apk["channel"],
            "versionName"=>$apk["version_name"],
            "versionCode"=>$apk["version_code"],
            "patchVersion"=>$patch["version_name"],
            "message"=>empty($patch["tips"])?"":$patch["tips"],
            "url"=>domain_download($patch["file_path"]),
            "md5"=>$patch["file_md5"]
        ];

        $this->response($s, 'json');
    }
}