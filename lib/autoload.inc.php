<?php
namespace DataCenter\lib;

spl_autoload_register(function($class){
	if(strpos($class, __NAMESPACE__) === 0){
		$f = str_replace(__NAMESPACE__, '', $class);
		$f = __DIR__.str_replace("\\", '/', $f).'.php';
		if(is_file($f)){
			include_once $f;
		}
	}
});


