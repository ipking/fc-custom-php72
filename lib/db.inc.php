<?php

$tmpDbConfig = array(
	'driver'   => 'pdo',
	'host' => 'rdsqquavqrrqbai334.mysql.rds.aliyuncs.com',
	'login' => 'tterp',
	'password' => 'kingdee',
	'database' => 'tt_datacenter_dev',
	'charset'  => 'utf8',
	'prefix'   => '',
	'port'   => '3306',
);

if($tmpDbConfig['login']){
	$tmpDbConfig['user'] = $tmpDbConfig['login'];
}
return $tmpDbConfig;