<?php

namespace DataCenter\lib\tool;

/**
 * 
 * User: Administrator
 * Date: 2018/3/13
 * Time: 14:55
 */
class MonitorManager{
	/**
	 * 统一截获并处理错误
	 */
	public static function registerErrorHandler(){
		$e_types = array(
			E_ERROR   => 'PHP Fatal',
			E_WARNING => 'PHP Warning',
			E_PARSE   => 'PHP Parse Error',
			E_NOTICE  => 'PHP Notice'
		);
		register_shutdown_function(function() use ($e_types){
			$error = error_get_last();
			if($error['type'] != E_NOTICE && !empty($error['message'])){
				self::error_handler($error);
			}
		});
		set_error_handler(function($type, $message, $file, $line) use ($e_types){
			if($type != E_NOTICE && !empty($message)){
				$error = array(
					'type'    => $type,
					'message' => $message,
					'file'    => $file,
					'line'    => $line,
				);
				
				self::error_handler($error);
				// 被截获的错误，重新输出到错误文件
				error_log(($e_types[$type] ?: 'Unknown Problem').' :  '.$message.' in '.$file.' on line '.$line."\n");
			}
		}, E_ALL);
	}
	
	/**
	 * 保存错误信息到数据库
	 * @param $error
	 */
	private static function error_handler($error){
		
	}
}