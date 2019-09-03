<?php
// 本类由系统自动生成，仅供测试用途
namespace Home\Controller;
use Think\Controller;

class IndexController extends Controller 
{

	public function index()
	{
        header('HTTP/1.1 404 Not Found');
        // 确保FastCGI模式下正常
        header('Status:404 Not Found');
	}


}

