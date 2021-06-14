<?php
namespace DataCenter\lib\model;
use DataCenter\lib\table\TableTask;

/**
 * User: Lite Scaffold
 */
class Task extends TableTask {

    const TASK_REPEAT_SINGLE = 'Single';
    const TASK_REPEAT_REPEAT = 'Repeat';

    public static $task_repeat_map = array(
        self::TASK_REPEAT_SINGLE => '单次',
        self::TASK_REPEAT_REPEAT => '重复',
    );
	
	public static $task_error_account = array(
		'puppy&kitty_JP',
		'sd1-de',
		'sd1-it',
		'sd1-es',
		'sd1-uk',
		'Muboo-US',
		'FW-FR',
		'FW-DE',
		'FW-IT',
		'SpringunionLED',
	);

	public function __construct($data = array()){
		parent::__construct($data);
	}
	
	public static function no($err){
		return strpos($err,'1062 Duplicate entry');
	}
}