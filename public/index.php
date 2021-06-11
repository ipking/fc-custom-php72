<?php

$path_info = ($_SERVER['PATH_INFO'] ?: $_SERVER['REDIRECT_PATH_INFO']) ?: $_SERVER['REDIRECT_URL'];
$path = trim($path_info, '/');

$path_arr =  explode('/', $path);

print_r($path_arr);