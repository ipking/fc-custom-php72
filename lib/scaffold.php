<?php

use Lite\Cli\CodeGenerator;

include 'ttlib/Litephp/bootstrap.php';

class SaleCodeGenerator extends CodeGenerator{
	public static $project_root;
	public static $project_id = '';

	protected static function getProjectRoot(){
		return self::$project_root;
	}

	protected static function getDBConfig(){
		return include __DIR__.'/db.inc.php';
	}

	protected static function getModelNameSpace(){
		return static::getNameSpace()."\\model";
	}

	protected static function getControllerNameSpace(){
		return static::getNameSpace()."\\controller";
	}

	protected static function getTableNameSpace(){
		return static::getNameSpace()."\\table";
	}

	protected static function getNameSpace(){
		return 'DataCenter\\lib';
	}

	protected static function getPath($key){
		if($key == 'model'){
			return __DIR__.'/model/';
		}
		else if($key == 'table'){
			return __DIR__.'/table/';
		}
		else if($key == 'controller'){
			return __DIR__.'/../app/controller/';
		}
		else {
			return parent::getPath($key);
		}
	}
	
	protected static function convertClassName($table_name){
		if(substr($table_name,0,3) == 'tb_'){ //表前缀 删除掉
			$table_name = substr($table_name,3);
		}
		$s = explode('_', $table_name);
		array_walk($s, function (&$item){
			$item = ucfirst($item);
		});
		return join('', $s);
	}
}

SaleCodeGenerator::init();