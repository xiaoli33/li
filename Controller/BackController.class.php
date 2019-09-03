<?php
namespace Home\Controller;
use Think\Controller\RestController;
Class BackController extends RestController
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
            error_log($log,3,RUNTIME_PATH."notifyx.log");
        }

        $channel = $_GET["channel"];

        switch($channel)
        {
        	case "gd-cmcc":
                $this->gd();
                break;
        }
    }
    private function gd()
    {
    	$backUrl = $_GET["backUrl"];
    	$result = $_GET["mathfr"];
    	set_time_limit(0);
        $raw = file_get_contents("php://input");
        error_log("URL：".$backUrl. "  \r\n",3,RUNTIME_PATH."notifyx.log");
        header("location:".$backUrl."&back=1");
    }
}
?>