<?php
namespace DataCenter\lib\table;

/**
 * User: Lite Scaffold
 */
use Lite\DB\Model as Model;

/**
 * Class TableTaskLog

 * @property-read int $id 
 * @property int $task_id 对应的脚本id
 * @property int $run_time 运行耗时(秒)
 * @property mixed $error_msg 错误信息
 * @property mixed $result_data 执行结果
 * @property string $create_time 添加时间
 */
abstract class TableTaskLog extends Model {
	public function __construct($data=array()){
		$this->setPropertiesDefine(array(
			'id' => array(
				'alias' => 'id',
				'type' => 'int',
				'length' => 10,
				'primary' => true,
				'required' => true,
				'readonly' => true,
				'min' => 0,
				'entity' => true
			),
			'task_id' => array(
				'alias' => '对应的脚本id',
				'type' => 'int',
				'length' => 11,
				'required' => true,
				'entity' => true
			),
			'run_time' => array(
				'alias' => '运行耗时',
				'type' => 'int',
				'length' => 11,
				'description' => '秒',
				'default' => 0,
				'entity' => true
			),
			'error_msg' => array(
				'alias' => '错误信息',
				'type' => 'simple_rich_text',
				'length' => 0,
				'default' => null,
				'entity' => true
			),
			'result_data' => array(
				'alias' => '执行结果',
				'type' => 'simple_rich_text',
				'length' => 0,
				'default' => null,
				'entity' => true
			),
			'create_time' => array(
				'alias' => '添加时间',
				'type' => 'datetime',
				'length' => 0,
				'default' => date('Y-m-d H:i:s'),
				'entity' => true
			),
		));
		parent::__construct($data);
	}

	/**
	 * current model table name
	 * @return string
	 */
	public function getTableName() {
		return 'tb_task_log';
	}

	/**
	* get database config
	* @return array
	*/
	protected function getDbConfig(){
		return include dirname(__DIR__).'/db.inc.php';
	}

	/**
	* 获取模块名称
	* @return string
	*/
	public function getModelDesc(){
		return '任务调度日志';
	}
}