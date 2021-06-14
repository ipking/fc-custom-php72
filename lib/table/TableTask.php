<?php
namespace DataCenter\lib\table;

/**
 * User: Lite Scaffold
 */
use Lite\DB\Model as Model;

/**
 * Class TableTask

 * @property-read int $id 
 * @property string $sys_code 系统代码
 * @property string $app 应用名称
 * @property string $account 账号
 * @property string $command 命令
 * @property string $site 站点
 * @property mixed $status 状态(待执行,运行中,完成,失败,停用)
 * @property mixed $param 参数
 * @property int $priority 优先级
 * @property string $data_block_start 加载数据开始时间
 * @property string $data_block_end 加载数据结束时间
 * @property int $data_block_time_span 每次加载的数据块时间，秒
 * @property int $data_waiting_time_span 任务执行间隔时间
 * @property int $error_times 失败次数
 * @property mixed $result_data 返回结果
 * @property string $last_execute_time 任务最近一次执行时间
 * @property string $create_time 添加时间
 * @property string $update_time 更新时间
 */
abstract class TableTask extends Model {
	const STATUS_NORMAL = 'Normal';
	const STATUS_RUNNING = 'Running';
	const STATUS_FINISHED = 'Finished';
	const STATUS_ERROR = 'Error';
	const STATUS_DISABLED = 'Disabled';

	public static $status_map = array(
		self::STATUS_NORMAL => '待执行',
		self::STATUS_RUNNING => '运行中',
		self::STATUS_FINISHED => '完成',
		self::STATUS_ERROR => '失败',
		self::STATUS_DISABLED => '停用',
	);

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
			'sys_code' => array(
				'alias' => '系统代码',
				'type' => 'string',
				'length' => 20,
				'required' => true,
				'entity' => true
			),
			'app' => array(
				'alias' => '应用名称',
				'type' => 'string',
				'length' => 32,
				'default' => '',
				'entity' => true
			),
			'account' => array(
				'alias' => '账号',
				'type' => 'string',
				'length' => 32,
				'default' => '',
				'entity' => true
			),
			'command' => array(
				'alias' => '命令',
				'type' => 'string',
				'length' => 64,
				'default' => '',
				'entity' => true
			),
			'site' => array(
				'alias' => '站点',
				'type' => 'string',
				'length' => 8,
				'default' => '',
				'entity' => true
			),
			'status' => array(
				'alias' => '状态',
				'type' => 'enum',
				'default' => 'Normal',
				'options' => array('Normal'=>'待执行', 'Running'=>'运行中', 'Finished'=>'完成', 'Error'=>'失败', 'Disabled'=>'停用'),
				'entity' => true
			),
			'param' => array(
				'alias' => '参数',
				'type' => 'simple_rich_text',
				'length' => 0,
				'default' => null,
				'entity' => true
			),
			'priority' => array(
				'alias' => '优先级',
				'type' => 'int',
				'length' => 11,
				'default' => 5,
				'entity' => true
			),
			'data_block_start' => array(
				'alias' => '加载数据开始时间',
				'type' => 'datetime',
				'length' => 0,
				'default' => null,
				'entity' => true
			),
			'data_block_end' => array(
				'alias' => '加载数据结束时间',
				'type' => 'datetime',
				'length' => 0,
				'default' => null,
				'entity' => true
			),
			'data_block_time_span' => array(
				'alias' => '每次加载的数据块时间，秒',
				'type' => 'int',
				'length' => 11,
				'default' => 600,
				'entity' => true
			),
			'data_waiting_time_span' => array(
				'alias' => '任务执行间隔时间',
				'type' => 'int',
				'length' => 11,
				'default' => 300,
				'entity' => true
			),
			'error_times' => array(
				'alias' => '失败次数',
				'type' => 'int',
				'length' => 11,
				'default' => 0,
				'entity' => true
			),
			'result_data' => array(
				'alias' => '返回结果',
				'type' => 'simple_rich_text',
				'length' => 0,
				'default' => null,
				'entity' => true
			),
			'last_execute_time' => array(
				'alias' => '任务最近一次执行时间',
				'type' => 'datetime',
				'length' => 0,
				'default' => null,
				'entity' => true
			),
			'create_time' => array(
				'alias' => '添加时间',
				'type' => 'datetime',
				'length' => 0,
				'readonly' => true,
				'default' => date('Y-m-d H:i:s'),
				'entity' => true
			),
			'update_time' => array(
				'alias' => '更新时间',
				'type' => 'datetime',
				'length' => 0,
				'readonly' => true,
				'default' => null,
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
		return 'tb_task';
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
		return '任务调度';
	}
}