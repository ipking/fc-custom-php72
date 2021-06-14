<?php

$GLOBALS['g_request_time'] = microtime(true);


use Lite\Core\Application;
use Lite\Core\Config;

include_once 'ttlib/Litephp/bootstrap.php';
include_once dirname(__DIR__) . '/lib/autoload.inc.php';


$path_info = ($_SERVER['PATH_INFO'] ?: $_SERVER['REDIRECT_PATH_INFO']) ?: $_SERVER['REDIRECT_URL'];
$path = trim($path_info, '/');

$path_arr =  explode('/', $path);
$api = $path_arr[0];
if (!in_array($api, ['api'])) {
	Application::init("DataCenter");
	die;
}

//api 接口
Application::init('DataCenter', null, Application::MODE_CLI);
header('Content-Type:text/json;charset=utf-8');
try {
	$flag = false;
	
	$file = Config::get('app/path');
	
	$file_path = '';
	
	$count = count($path_arr);
	foreach($path_arr as $key=>$value){
		$tem_arr = array_slice($path_arr,0,$count);
		
		$class = array_pop($tem_arr);
		$tem_arr[] = ucfirst($class);
		$file_path = $file.join('/',$tem_arr).'.php';
		
		
		if (is_file($file_path)) {
			$flag = true;
			
			$act_arr = array_slice($path_arr,$count,1);
			$act = $act_arr[0];
			$act_params = join('/',array_slice($path_arr,$count+1));
			
			break;
		}
		$count--;
		
	}
	if(!$flag){
		throw new Exception('api no found');
	}
	$class_full = Application::getNamespace() . "\\".join("\\",$tem_arr);
	
	
	include $file_path;
	
	if (!class_exists($class_full)) {
		throw new Exception('class no found');
	}
	
	$act =$act?:'index';
	
	$instance = new $class_full();
	if (!method_exists($instance, $act)) {
		throw new Exception('api action no found');
	}
	if($act_params){
		if ($rsp = $instance->$act($act_params)) {
			exit($rsp);
		}
	}else{
		if ($rsp = $instance->$act()) {
			exit($rsp);
		}
	}
	
	throw new Exception('server error');
} catch (\Exception $e) {
	$ret = array(
		'code'        => $e->getCode() == 0 ? '-1' : $e->getCode(),
		'msg'         => $e->getMessage(),
		'timestamp'   => time() . '000',
		'tracking_id' => '0',
		'usage_time'  => '10'
	);
	echo json_encode($ret);
}

