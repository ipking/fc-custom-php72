<?php
namespace DataCenter\lib\table;

/**
 * User: Lite Scaffold
 */
use Lite\DB\Model as Model;

/**
 * Class TableTaskAcountConfig

 * @property string $id 主键标识
 * @property string $sys_code 系统代码
 * @property string $platform 平台
 * @property string $account 账号
 * @property string $site 站点
 * @property mixed $config 配置信息
 * @property int $status 状态 0-正常（默认） 1-停用
 * @property int $get_orders_job 拉单任务id
 * @property int $create_time 创建时间
 * @property int $modify_time 更新时间
 */
abstract class TableTaskAcountConfig extends Model {
	public function __construct($data=array()){
		$this->setPropertiesDefine(array(
			'id' => array(
				'alias' => '主键标识',
				'type' => 'string',
				'length' => 20,
				'primary' => true,
				'required' => true,
				'min' => 0,
				'entity' => true
			),
			'sys_code' => array(
				'alias' => '系统代码',
				'type' => 'string',
				'length' => 20,
				'required' => true,
				'entity' => true
			),
			'platform' => array(
				'alias' => '平台',
				'type' => 'string',
				'length' => 32,
				'required' => true,
				'entity' => true
			),
			'account' => array(
				'alias' => '账号',
				'type' => 'string',
				'length' => 64,
				'required' => true,
				'entity' => true
			),
			'site' => array(
				'alias' => '站点',
				'type' => 'string',
				'length' => 16,
				'default' => null,
				'entity' => true
			),
			'config' => array(
				'alias' => '配置信息',
				'type' => 'simple_rich_text',
				'length' => 0,
				'required' => true,
				'entity' => true
			),
			'status' => array(
				'alias' => '状态 0-正常（默认） 1-停用',
				'type' => 'int',
				'length' => 4,
				'default' => 0,
				'entity' => true
			),
			'get_orders_job' => array(
				'alias' => '拉单任务id',
				'type' => 'int',
				'length' => 11,
				'default' => 0,
				'entity' => true
			),
			'create_time' => array(
				'alias' => '创建时间',
				'type' => 'timestamp',
				'length' => 0,
				'default' => date('Y-m-d H:i:s'),
				'entity' => true
			),
			'modify_time' => array(
				'alias' => '更新时间',
				'type' => 'timestamp',
				'length' => 0,
				'readonly' => true,
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
		return 'tb_task_acount_config';
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
		return '计划任务平台账号配置表';
	}
}