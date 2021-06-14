<?php

$tmpDbConfig = array(
	'driver'   => 'pdo',
	'host' => 'rm-bp1bkhjm2unm61hq9.mysql.rds.aliyuncs.com',
	'login' => 'tk',
	'password' => 'Tk123456',
	'database' => 'erp',
	'charset'  => 'utf8',
	'prefix'   => '',
	'port'   => '3306',
);

if($tmpDbConfig['login']){
	$tmpDbConfig['user'] = $tmpDbConfig['login'];
}
return $tmpDbConfig;