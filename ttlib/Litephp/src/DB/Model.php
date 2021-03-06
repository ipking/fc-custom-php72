<?php
namespace Lite\DB;

use Lite\Core\Config;
use Lite\Core\DAO;
use Lite\Core\Hooker;
use Lite\DB\Driver\DBAbstract;
use Lite\Exception\BizException;
use Lite\Exception\Exception;
use Lite\Exception\RouterException;
use function Lite\func\array_clear_fields;
use function Lite\func\array_first;
use function Lite\func\array_group;
use function Lite\func\time_range_v;

/**
 * 数据库结合数据模型提供的操作抽象类, 实际业务逻辑最好通过集成该类来实现
 * 相应的业务数据功能逻辑.
 * @method static[]|Query order
 * User: sasumi
 * Date: 2015/01/06
 * Time: 9:49
 */
abstract class Model extends DAO{
	const DB_READ = 1;
	const DB_WRITE = 2;
	
	const LAST_OP_SELECT = Query::SELECT;
	const LAST_OP_UPDATE = Query::UPDATE;
	const LAST_OP_DELETE = Query::DELETE;
	const LAST_OP_INSERT = Query::INSERT;

	/** @var string current model last operate type */
	private $last_operate_type = self::LAST_OP_SELECT;
	
	/** @var array database config */
	private $db_config = array();

	/** @var Query db query object * */
	private $query = null;

	/**
	 * 获取当前调用ORM对象
	 * @return static|Query
	 */
	public static function meta(){
		$class_name = get_called_class();
		$obj = new $class_name;
		return $obj;
	}

	/**
	 * on before save event
	 * @return boolean
	 */
	public function onBeforeSave(){
		return true;
	}

	/**
	 * on before update
	 * @return boolean
	 */
	public function onBeforeUpdate(){
		return true;
	}

	/**
	 * 记录插入之前事件
	 * @return boolean
	 */
	public function onBeforeInsert(){
		return true;
	}

	/**
	 * 记录插入之后
	 */
	public function onAfterInsert(){

	}

	/**
	 * records on change event
	 */
	protected static function onBeforeChanged(){
		return true;
	}

	/**
	 * 获取当前数据库表表名（不含前缀）
	 * @return string
	 */
	abstract public function getTableName();
	
	/**
	 * 获取数据库表全名
	 * @return string
	 * @throws \Lite\Exception\Exception
	 */
	public function getTableFullName(){
		return $this->getDbTablePrefix().$this->getTableName();
	}

	/**
	 * 获取数据库表主键
	 * @return string
	 * @throws \Lite\Exception\Exception
	 */
	public function getPrimaryKey(){
		$defines = $this->getEntityPropertiesDefine();
		foreach($defines as $k => $def){
			if($def['primary']){
				return $k;
			}
		}
		throw new Exception('no primary key found in table defines');
	}
	
	/**
	 * 获取db记录实例对象
	 * @param int $operate_type
	 * @return DBAbstract
	 * @throws \Lite\Exception\Exception
	 */
	protected function getDbDriver($operate_type = self::DB_WRITE){
		$configs = $this->getDbConfig();
		$config = $this->parseConfig($operate_type, $configs);
		return DBAbstract::instance($config);
	}
	
	/**
	 * 解释SQL语句
	 * @param $query
	 * @return array
	 * @throws \Lite\Exception\Exception
	 */
	public static function explainQuery($query){
		$obj = self::meta();
		return $obj->getDbDriver(self::DB_READ)->explain($query);
	}
	
	/**
	 * 获取数据库配置
	 * 该方法可以被覆盖重写
	 * @return array
	 * @throws \Lite\Exception\Exception
	 */
	protected function getDbConfig(){
		return $this->db_config ?: Config::get('db');
	}

	/**
	 * 设置数据库配置到当前ORM
	 * 该方法可被覆盖、调用
	 * @param $db_config
	 */
	protected function setDbConfig($db_config){
		$this->db_config = $db_config;
	}
	
	/**
	 * 获取数据库表前缀
	 * @param int $type
	 * @return string
	 * @throws \Lite\Exception\Exception
	 */
	public static function getDbTablePrefix($type = self::DB_READ){
		/** @var Model $obj */
		$obj = static::meta();
		$configs = $obj->getDbConfig();
		$config = $obj->parseConfig($type, $configs);
		return $config['prefix'] ?: '';
	}

	/**
	 * 解析数据库配置
	 * 分析出配置中的读、写配置
	 * @param string $operate_type
	 * @param array $all_config
	 * @throws Exception
	 * @internal param array $all
	 * @return array
	 */
	private function parseConfig($operate_type, array $all_config){
		$read_list = array();
		$write_list = array();
		$depKey = 'host';

		// 解析数据库读写配置
		if($all_config[$depKey]){
			$read_list = $write_list = array(
				$all_config
			);
		} else if($all_config['read']){
			if($all_config['read'][$depKey]){
				$read_list = array(
					$all_config['read']
				);
			} else{
				$read_list = $all_config['read'];
			}
		}

		// 写表额外判断，预防某些系统只需要读的功能
		if(empty($write_list)){
			if($all_config['write']){
				if($all_config['write'][$depKey]){
					$write_list = array(
						$all_config['write']
					);
				} else{
					$write_list = $all_config['write'];
				}
			}
		}

		switch($operate_type){
			case self::DB_WRITE:
				$k = array_rand($write_list, 1);
				$host_config = $write_list[$k];
				break;

			case self::DB_READ:
			default:
				$k = array_rand($read_list, 1);
				$host_config = $read_list[$k];
				break;
		}

		$host_config = array_merge($host_config, array(
			'driver' => 'pdo',
			'type'   => 'mysql',
		), $all_config);

		if(empty($host_config[$depKey])){
			throw new Exception('DB CONFIG ERROR FOR DRIVER TYPE:'.$operate_type);
		}
		return $host_config;
	}

	/**
	 * 设置查询SQL语句
	 * @param string|Query $query
	 * @throws Exception
	 * @return static|Query
	 */
	public static function setQuery($query){
		if(is_string($query)){
			$obj = self::meta();
			$args = func_get_args();
			$query = new Query(self::parseConditionStatement($args, $obj));
		}
		if($query){
			$obj = self::meta();
			$obj->query = $query;
			return $obj;
		}
		throw new Exception('QUERY STRING REQUIRED');
	}

	/**
	 * 获取当前查询对象
	 * @return \Lite\DB\Query
	 */
	public function getQuery(){
		return $this->query;
	}

	/**
	 * 开始一个事务
	 * @param callable $handler 处理函数，若函数返回false，将终止事务处理
	 * @throws Exception
	 * @throws null
	 */
	public static function transaction($handler){
		$driver = self::meta()->getDbDriver(Model::DB_WRITE);
		$r = $driver->beginTransaction();
		if($r === false){
			throw new Exception('database begin transaction error');
		}
		try{
			if(call_user_func($handler) === false){
				throw new Exception('database transaction interrupt');
			}
			$driver->commit();
		} catch(\Exception $exception){
			try{
				$driver->rollback();
			} catch(\Exception $e){
				throw new Exception($exception->getMessage());
			}
			throw $exception;
		}
	}
	
	
	
	/**
	 * 执行当前查询
	 * @return \PDOStatement
	 * @throws \Lite\Exception\Exception
	 */
	public function execute(){
		$type = Query::isWriteOperation($this->query) ? self::DB_WRITE : self::DB_READ;
		$result = $this->getDbDriver($type)->query($this->query);
		return $result;
	}

	/**
	 * set model query cache
	 * @param Model[] $model_list
	 * @param callable $getter
	 * @param callable $setter
	 * @param callable $flusher
	 */
	public static function bindTableCacheHandler(array $model_list, callable $getter, callable $setter, callable $flusher){
		$check_table_hit = function ($query, $full_compare = true) use ($model_list){
			if($query && $query instanceof Query){
				foreach($model_list as $model){
					$tbl = Query::escapeKey($model::meta()->getTableFullName());
					if(($full_compare && $query->tables == [$tbl]) || in_array($tbl, $query->tables)){
						return $model;
					}
				}
			}
			return false;
		};
		
		//before get list, check cache
		Hooker::add(DBAbstract::EVENT_BEFORE_DB_GET_LIST, function($param) use ($check_table_hit, $getter){
			$model = $check_table_hit($param['query']);
			if($model){
				$result = call_user_func($getter, $model, $param['query']);
				if(isset($result)){
					$param['result'] = $result;
				}
			}
		});

		//after get list, set cache
		Hooker::add(DBAbstract::EVENT_AFTER_DB_GET_LIST, function($param) use ($check_table_hit, $setter){
			$model = $check_table_hit($param['query']);
			if($model){
				call_user_func($setter, $model, $param['query'], $param['result']);
			}
		});

		//flush table cache
		Hooker::add(DBAbstract::EVENT_AFTER_DB_QUERY, function($query) use($check_table_hit, $flusher){
			$model = $check_table_hit($query);
			if($model && Query::isWriteOperation($query)){
				call_user_func($flusher, $model, $query);
			}
		});
	}
	
	/**
	 * 查找
	 * @param string $statement 条件表达式
	 * @param string $var,... 条件表达式扩展
	 * @return static|Query
	 * @throws \Lite\Exception\Exception
	 */
	public static function find($statement = '', $var = null){
		$obj = static::meta();
		$prefix = self::getDbTablePrefix();
		$query = new Query();
		$query->setTablePrefix($prefix);

		$args = func_get_args();
		$statement = self::parseConditionStatement($args, $obj);
		$query->select()->from($obj->getTableName())->where($statement);
		$obj->query = $query;
		return $obj;
	}
	
	/**
	 * add more find condition
	 * @param array $args 查询条件
	 * @return static|Query
	 * @throws \Lite\Exception\Exception
	 */
	public function where(...$args){
		$statement = self::parseConditionStatement($args, $this);
		$this->query->where($statement);
		return $this;
	}
	
	/**
	 * 快速查询用户请求过来的信息，只有第二个参数为不为空的时候才去查询，空数组还是会去查。
	 * @param $st
	 * @param $val
	 * @return static|Query
	 * @throws \Lite\Exception\Exception
	 */
	public function whereOnSet($st, $val){
		$args = func_get_args();
		foreach($args as $k=>$arg){
			if(is_string($arg)){
				$args[$k] = trim($arg);
			}
		}
		if(is_array($val) || strlen($val)){
			$statement = self::parseConditionStatement($args, $this);
			$this->query->where($statement);
		}
		return $this;
	}
	
	/**
	 * 快速LIKE查询用户请求过来的信息，当LIKE内容为空时，不执行查询，如 %%。
	 * @param $st
	 * @param $val
	 * @return static|Query
	 */
	public function whereLikeOnSet($st, $val){
		$args = func_get_args();
		if(strlen(trim(str_replace('%','',$val)))){
			return call_user_func_array(array($this, 'whereOnSet'), $args);
		}
		return $this;
	}
	
	/**
	 * 批量LIKE查询（whereLikeOnSet方法快捷用法）
	 * @param array $fields
	 * @param $val
	 * @return static|Query
	 */
	public function whereLikeOnSetBatch(array $fields, $val){
		$st = join(' LIKE ? OR ', $fields).' LIKE ?';
		$values = array_fill(0, count($fields), $val);
		array_unshift($values, $st);
		return call_user_func_array([$this, 'whereLikeOnSet'], $values);
	}
	
	/**
	 * query where field between min & max (include equal)
	 * @param $field
	 * @param null $min
	 * @param null $max
	 * @return static|Query
	 */
	public function between($field, $min = null, $max = null){
		if(isset($min)){
			$min = addslashes($min);
			$this->query->where($field, ">=", $min);
		}
		if(isset($max)){
			$max = addslashes($max);
			$this->query->where($field, "<=", $max);
		}
		return $this;
	}
	
	/**
	 * 创建新对象
	 * @param $data
	 * @return bool|static|Query
	 * @throws \Lite\Exception\BizException
	 * @throws \Lite\Exception\Exception
	 */
	public static function create($data){
		$obj = static::meta();
		$obj->setValues($data);
		return $obj->save() ? $obj : false;
	}
	
	/**
	 * 由主键查询一条记录
	 * @param string $val
	 * @param bool $as_array
	 * @return static|Query|array
	 * @throws \Lite\Exception\Exception
	 */
	public static function findOneByPk($val, $as_array = false){
		$obj = static::meta();
		return static::find($obj->getPrimaryKey().'=?', $val)->one($as_array);
	}
	
	/**
	 * @param $val
	 * @param bool $as_array
	 * @return static|Query|array
	 * @throws \Lite\Exception\Exception
	 * @throws \Lite\Exception\RouterException
	 */
	public static function findOneByPkOrFail($val, $as_array = false){
		$data = static::findOneByPk($val, $as_array);
		if(!$data){
			throw new RouterException('No data found');
		}
		return $data;
	}
	
	/**
	 * 有主键列表查询多条记录
	 * 单主键列表为空，该方法会返回空数组结果
	 * @param array $pks
	 * @param bool $as_array
	 * @return static[]|Query[]
	 * @throws \Lite\Exception\Exception
	 */
	public static function findByPks(array $pks, $as_array = false){
		if(empty($pks)){
			return array();
		}
		$obj = static::meta();
		return static::find($obj->getPrimaryKey().' IN ?', $pks)->all($as_array);
	}
	
	/**
	 * 根据主键值删除一条记录
	 * @param string $val
	 * @return bool
	 * @throws \Lite\Exception\Exception
	 */
	public static function delByPk($val){
		$obj = static::meta();
		return static::deleteWhere(0, $obj->getPrimaryKey()."='$val'");
	}
	
	/**
	 * 根据主键值更新记录
	 * @param string $val 主键值
	 * @param array $data
	 * @return bool
	 * @throws \Lite\Exception\Exception
	 */
	public static function updateByPk($val, array $data){
		$obj = static::meta();
		$pk = $obj->getPrimaryKey();
		return static::updateWhere($data, 1, "$pk = ?", $val);
	}

	/**
	 * 根据主键值更新记录
	 * @param $pks
	 * @param array $data
	 * @return bool
	 * @throws \Lite\Exception\Exception
	 */
	public static function updateByPks($pks, array $data){
		$obj = static::meta();
		$pk = $obj->getPrimaryKey();
		return static::updateWhere($data, count($pks), "$pk IN ?", $pks);
	}
	
	/**
	 * 根据条件更新数据
	 * @param array $data
	 * @param int $limit 为了安全，调用方必须传入具体数值，如不限制删除数量，可设置为0
	 * @param string $statement 为了安全，调用方必须传入具体条件，如不限制，可设置为空字符串
	 * @return bool;
	 * @throws \Lite\Exception\Exception
	 */
	public static function updateWhere(array $data, $limit, $statement){
		if(self::onBeforeChanged() === false){
			return false;
		}

		$args = func_get_args();
		$args = array_slice($args, 2);
		$obj = static::meta();
		$statement = self::parseConditionStatement($args, $obj);
		$table = $obj->getTableName();
		$result = $obj->getDbDriver(self::DB_WRITE)->update($table, $data, $statement, $limit);
		return $result;
	}
	
	/**
	 * 根据条件从表中删除记录
	 * @param int $limit 为了安全，调用方必须传入具体数值，如不限制删除数量，可设置为0
	 * @param string $statement 为了安全，调用方必须传入具体条件，如不限制，可设置为空字符串
	 * @return bool
	 * @throws \Lite\Exception\Exception
	 */
	public static function deleteWhere($limit, $statement){
		$args = func_get_args();
		$args = array_slice($args, 1);

		$obj = static::meta();
		$statement = self::parseConditionStatement($args, $obj);
		$table = $obj->getTableName();
		$result = $obj->getDbDriver(self::DB_WRITE)->delete($table, $statement, $limit);
		return $result;
	}
	
	/**
	 * 获取所有记录
	 * @param bool $as_array return as array
	 * @param string $unique_key 用于组成返回数组的唯一性key
	 * @return static[]|Query[]
	 * @throws \Lite\Exception\Exception
	 */
	public function all($as_array = false, $unique_key = ''){
		$list = $this->getDbDriver(self::DB_READ)->getAll($this->query);
		if(!$list){
			return array();
		}
		if($as_array){
			if($unique_key){
				$list = array_group($list, $unique_key, true);
			}
			return $list;
		}

		$result = array();
		foreach($list as $item){
			$tmp = clone $this;
			$tmp->setValues($item);
			$tmp->resetValueChangeState();
			if($unique_key){
				$result[$item[$unique_key]] = $tmp;
			} else{
				$result[] = $tmp;
			}
		}
		return $result;
	}

	/**
	 * 以关联数组方式返回
	 * @deprecated 请使用 all，第二个参数已经支持
	 * @param bool $as_array 是否以数组方式返回，默认为Model对象
	 * @param null $key 唯一性下标，如id，默认为自然索引数组
	 * @return static[]|Query[]
	 * @throws \Lite\Exception\Exception
	 */
	public function allAsAssoc($as_array = false, $key = null){
		$key = $key ?: $this->getPrimaryKey();
		return $this->all($as_array, $key);
	}
	
	/**
	 * 获取一条记录
	 * @param bool $as_array 是否以数组方式返回，默认为Model对象
	 * @return static|Query|array|NULL
	 * @throws \Lite\Exception\Exception
	 */
	public function one($as_array = false){
		$data = $this->getDbDriver(self::DB_READ)->getOne($this->query);
		if($as_array){
			return $data;
		}
		if(!empty($data)){
			$this->setValues($data);
			$this->resetValueChangeState();
			return $this;
		}
		return null;
	}
	
	/**
	 * 获取一条记录，为空时抛异常
	 * @param bool $as_array 是否以数组方式返回，默认为Model对象
	 * @return array|static|Query|NULL
	 * @throws \Lite\Exception\Exception
	 * @throws \Lite\Exception\RouterException
	 */
	public function oneOrFail($as_array = false){
		$data = $this->one($as_array);
		if(!$data){
			throw new RouterException('No data found');
		}
		return $data;
	}
	
	/**
	 * 获取一个记录字段
	 * @param string|null $key 如字段为空，则取第一个结果
	 * @return mixed|null
	 * @throws \Lite\Exception\Exception
	 */
	public function ceil($key = ''){
		$obj = self::meta();
		$pro_defines = $obj->getEntityPropertiesDefine();
		if($key && $pro_defines[$key]){
			$this->query->field($key);
		}
		$data = $this->getDbDriver(self::DB_READ)->getOne($this->query);
		return $data ? array_pop($data) : null;
	}
	
	/**
	 * 计算字段值总和
	 * @param string|array $fields 需要计算字段名称（列表）
	 * @param array $group_by 使用指定字段（列表）作为合并维度
	 * @return number|array 结果总和，或以指定字段列表作为下标的结果总和
	 * @example
	 * <pre>
	 * $report->sum('order_price', 'original_price');
	 * $report->group('platform')->sum('order_price');
	 *
	 * sum('price'); //10.00
	 * sum(['price','count']); //[10.00, 14]
	
	 * sum(['price', 'count'], ['platform','order_type']); //
	 * [
	 *  ['platform,order_type'=>'amazon', 'price'=>10.00, 'count'=>14],
	 *  ['platform'=>'ebay', 'price'=>10.00, 'count'=>14],...
	 * ]
	 * sum(['price', 'count'], ['platform', 'order_type']);
	 * </pre>
	 */
	public function sum($fields, $group_by=[]){
		$fields = is_array($fields)?$fields:[$fields];
		$str = [];
		foreach($fields as $_=>$field){
			$str[] = "SUM($field) as $field";
		}
		
		if($group_by){
			$str = array_merge($str,$group_by);
			$this->query->group(implode(',',$group_by));
		}
		$this->query->field(join(',', $str));
		
		$data = $this->getDbDriver(self::DB_READ)->getAll($this->query);
		if($group_by){
			return $data;
		}
		if(count($fields) == 1){
			return array_first(array_first($data));
		} else {
			return array_values(array_first($data));
		}
	}
	
	/**
	 * 获取指定列，作为一维数组返回
	 * @param $key
	 * @return array
	 * @throws \Lite\Exception\Exception
	 */
	public function column($key){
		$obj = self::meta();
		$pro_defines = $obj->getEntityPropertiesDefine();
		if($pro_defines[$key]){
			$this->query->field($key);
		}
		$data = $this->getDbDriver(self::DB_READ)->getAll($this->query);
		return $data ? array_column($data, $key) : array();
	}

	/**
	 * 以映射数组方式返回
	 * <pre>
	 * $query->map('id', 'name'); //返回 [[id_val=>name_val],...] 格式数据
	 * $query->map('id', ['name']); //返回 [[id_val=>[name=>name_val],...] 格式数据
	 * $query->map('id', ['name', 'gender']); //返回 [[id_val=>[name=>name_val, gender=>gender_val],...] 格式数据
	 * </pre>
	 * @param $key
	 * @param $val
	 * @return array
	 * @throws Exception
	 */
	public function map($key, $val){
		if(is_string($val)){
			$this->query->field($key, $val);
			$tmp = $this->getDbDriver(self::DB_READ)->getAll($this->query);
			return array_combine(array_column($tmp, $key), array_column($tmp, $val));
		} else if(is_array($val)){
			$tmp = $val;
			$tmp[] = $key;
			$this->query->field($tmp);
			$tmp = $this->getDbDriver(self::DB_READ)->getAll($this->query);
			$ret = [];
			foreach($tmp as $item){
				$ret[$item[$key]] = [];
				foreach($val as $field){
					$ret[$item[$key]][$field] = $item[$field];
				}
			}
			return $ret;
		}
		throw new Exception('map parameter error', null, [$key, $val]);
	}

	/**
	 * 根据分段进行数据处理，常见用于节省WebServer内存操作
	 * @param int $size 分块大小
	 * @param callable $handler 回调函数
	 * @param bool $as_array 查询结果作为数组格式回调
	 * @return bool 是否执行了分块动作
	 * @throws Exception
	 */
	public function chunk($size, $handler, $as_array = false){
		$total = $this->count();
		$start = 0;
		if(!$total){
			return false;
		}

		$ds = DBAbstract::distinctQueryState();
		if($ds){
			DBAbstract::distinctQueryOff();
		}
		$page_index = 0;
		$page_total = ceil($total/$size);
		while($start<$total){
			$data = $this->paginate(array($start, $size), $as_array);
			if(call_user_func($handler, $data, $page_index++, $page_total, $total) === false){
				break;
			}
			$start += $size;
		}
		if($ds){
			DBAbstract::distinctQueryOn();
		}
		return true;
	}
	
	/**
	 * 数据记录监听
	 * @param callable $handler 处理函数，若返回false，则终端监听
	 * @param int $chunk_size 获取数据时的分块大小
	 * @param int $sleep_interval_sec 无数据时睡眠时长（秒）
	 * @param bool|callable|null $debugger 数据信息调试器
	 * @return bool 是否正常执行
	 * @throws \Lite\Exception\Exception
	 */
	public function watch(callable $handler, $chunk_size = 50, $sleep_interval_sec = 3, $debugger = true){
		if($debugger === true) {
			$debugger = function(...$args){
				echo "\n".date('Y-m-d H:i:s')."\t".join("\t", func_get_args());
			};
		} else if(!$debugger || !is_callable($debugger)){
			$debugger = function(){};
		}
		
		$dist_status = DBAbstract::distinctQueryState();
		DBAbstract::distinctQueryOff();
		while(true){
			$obj = clone($this);
			$break = false;
			$start = microtime(true);
			$exists = $obj->chunk($chunk_size, function($data_list, $page_index, $page_total, $item_total) use ($handler, $chunk_size, $debugger, $start, &$break){
				/** @var Model $item */
				foreach($data_list as $k => $item){
					$cur = $page_index*$chunk_size+$k+1;
					$now = microtime(true);
					$left = ($now-$start)*($item_total-$cur)/$cur;
					$left_time = time_range_v($left);
					$debugger('Handling item: ['.$cur.'/'.$item_total." - $left_time]", substr(json_encode($item->toArray()), 0, 200));
					$ret = call_user_func($handler, $item, $page_index, $page_total, $item_total);
					if($ret === false){
						$debugger('Handler Break!');
						$break = true;
						return false;
					}
				}
				return true;
			});
			unset($obj);
			if($break){
				$debugger('Handler Break!');
				return false;
			}
			if(!$exists){
				$debugger('No data found, sleep for '.$sleep_interval_sec.' seconds.');
				sleep($sleep_interval_sec);
			}
		}
		if($dist_status){
			DBAbstract::distinctQueryOn();
		}
		return true;
	}
	
	/**
	 * 获取当前查询条数
	 * @return int
	 * @throws \Lite\Exception\Exception
	 */
	public function count(){
		$count = $this->getDbDriver(self::DB_READ)->getCount($this->query);
		return $count;
	}
	
	/**
	 * 分页查询记录
	 * @param string $page
	 * @param bool $as_array 是否以数组方式返回，默认为Model对象数组
	 * @param string $unique_key 用于组成返回数组的唯一性key
	 * @return static[]|Query[]
	 * @throws \Lite\Exception\Exception
	 */
	public function paginate($page = null, $as_array = false, $unique_key = ''){
		$list = $this->getDbDriver(self::DB_READ)->getPage($this->query, $page);
		if($as_array){
			if($unique_key){
				$list = array_group($list, $unique_key, true);
			}
			return $list;
		}
		$result = array();
		if($list){
			foreach($list as $item){
				$tmp = clone $this;
				$tmp->setValues($item);
				$tmp->resetValueChangeState();
				if($unique_key){
					$result[$item[$unique_key]] = $tmp;
				} else{
					$result[] = $tmp;
				}
			}
		}
		return $result;
	}

	/**
	 * 更新当前对象
	 * @throws \Lite\Exception\BizException
	 * @throws \Lite\Exception\Exception
	 * @return number|bool
	 */
	public function update(){
		if($this->onBeforeUpdate() === false || self::onBeforeChanged() === false){
			return false;
		}

		$this->last_operate_type = self::LAST_OP_UPDATE;
		$data = $this->getValues();
		$pk = $this->getPrimaryKey();

		//只更新改变的值
		$change_keys = $this->getValueChangeKeys();
		$data = array_clear_fields(array_keys($change_keys), $data);
		list($data) = self::validate($data, Query::UPDATE, $this->$pk, true, $this);
		return $this->getDbDriver(self::DB_WRITE)->update($this->getTableName(), $data, $this->getPrimaryKey().'='.$this->$pk);
	}
	
	/**
	 * 插入当前对象
	 * @throws \Lite\Exception\BizException
	 * @return string|bool 返回插入的id，或者失败(false)
	 * @throws \Lite\Exception\Exception
	 */
	public function insert(){
		if($this->onBeforeInsert() === false || self::onBeforeChanged() === false){
			return false;
		}
		
		$this->last_operate_type = self::LAST_OP_INSERT;
		$data = $this->getValues();
		list($data) = self::validate($data, Query::INSERT, null, true, $this);

		$result = $this->getDbDriver(self::DB_WRITE)->insert($this->getTableName(), $data);
		if($result){
			$pk_val = $this->getDbDriver(self::DB_WRITE)->getLastInsertId();
			$this->setValue($this->getPrimaryKey(), $pk_val);
			$this->onAfterInsert();
			return $pk_val;
		}
		return false;
	}
	
	/**
	 * 替换数据
	 * @param array $data
	 * @param int $limit
	 * @param array ...$args 查询条件
	 * @return mixed
	 * @throws \Lite\Exception\BizException
	 * @throws \Lite\Exception\Exception
	 */
	public static function replace(array $data, $limit = 0, ...$args){
		$obj = self::meta();
		$statement = self::parseConditionStatement($args, $obj);
		$obj = static::meta();
		$table = $obj->getTableName();
		$result = $obj->getDbDriver(self::DB_WRITE)->replace($table, $data, $statement, $limit);
		return $result;
	}
	
	/**
	 * 增加或减少计数
	 * @param string $field 计数使用的字段
	 * @param int $offset 计数偏移量，如1，-1
	 * @param int $limit 条数限制，默认为0表示不限制更新条数
	 * @param array ...$args 查询条件
	 * @return int
	 * @throws \Lite\Exception\Exception
	 */
	public static function increase($field, $offset, $limit = 0, ...$args){
		$obj = self::meta();
		$statement = self::parseConditionStatement($args, $obj);

		$obj = static::meta();
		$table = $obj->getTableName();
		$result = $obj->getDbDriver(self::DB_WRITE)->increase($table, $field, $offset, $statement, $limit);
		return $result;
	}
	
	/**
	 * 获取字段-别名映射表
	 * @return array [field=>name, ...]
	 */
	public static function getEntityFieldAliasMap(){
		$obj = self::meta();
		$ret = [];
		$defines = $obj->getEntityPropertiesDefine();
		foreach($defines as $field=>$def){
			$ret[$field] = $def['alias'] ?: $field;
		}
		return $ret;
	}
	
	/**
	 * 数据校验
	 * @param array $src_data 元数据
	 * @param string $query_type 数据库操作类型
	 * @param null $pk_val 主键值
	 * @param bool $throw_exception 是否在校验失败时抛出异常
	 * @param null $model 元模型
	 * @return array [data,error_message]
	 */
	private static function validate($src_data = array(), $query_type = Query::INSERT, $pk_val = null, $throw_exception = true, $model = null){
		$obj = self::meta();
		$pro_defines = $obj->getEntityPropertiesDefine();
		$pk = $obj->getPrimaryKey();

		//转换set数据
		foreach($src_data as $k => $d){
			if($pro_defines[$k]['type'] == 'set' && is_array($d)){
				$src_data[$k] = join(',', $d);
			}
		}

		//移除矢量数值
		$data = array_filter($src_data, function($item){
			return is_scalar($item);
		});

		//unique校验
		foreach($pro_defines as $field => $def){
			if(!isset($data[$field])){
				continue;
			}
			if($def['unique']){
				if($query_type == Query::INSERT){
					$count = $obj->find("`$field`=?", $data[$field])->count();
				} else{
					$count = $obj->find("`$field`=? AND `$pk` <> ?", $data[$field], $pk_val)->count();
				}
				if($count){
					$msg = "{$def['alias']}：{$data[$field]}已经存在，不能重复添加";
					if($throw_exception){
						throw new BizException($msg);
					}
					return array($data, $msg);
				}
			}
		}

		//移除readonly属性
		$pro_defines = array_filter($pro_defines, function($def){
			return !$def['readonly'];
		});

		//清理无用数据
		$data = array_clear_fields(array_keys($pro_defines), $data);

		//插入时填充default值
		array_walk($pro_defines, function($def, $k) use (&$data, $query_type){
			if(array_key_exists('default', $def)){
				if($query_type == Query::INSERT){
					if((!isset($data[$k]) || strlen($data[$k]) == 0)){
						$data[$k] = $def['default'];
					}
				} else if(isset($data[$k]) && !strlen($data[$k])){
					$data[$k] = $def['default'];
				}
			}
		});
		
		//更新时，只需要处理更新数据的属性
		if($query_type == Query::UPDATE || $query_type == Query::REPLACE){
			foreach($pro_defines as $k => $define){
				if(!isset($data[$k])){
					unset($pro_defines[$k]);
				}
			}
		}


		//处理date日期默认为NULL情况
		foreach($data as $k => $val){
			if(in_array($pro_defines[$k]['type'], array(
					'date',
					'datetime',
					'time'
				)) && array_key_exists('default', $pro_defines[$k]) && $pro_defines[$k]['default'] === null && !$data[$k]
			){
				$data[$k] = null;
			}
		}

		//属性校验
		foreach($pro_defines as $k => $def){
			if(!$def['readonly']){
				if($msg = self::validateField($data[$k], $k, $model)){
					if($throw_exception){
						throw new BizException($msg, null, array('field' => $k, 'value'=>$data[$k], 'row' => $data));
					} else{
						return array($data, $msg);
					}
				}
			}
		}
		return array($data, null);
	}
	
	/**
	 * 字段校验
	 * @param $value
	 * @param $field
	 * @param null $model
	 * @return string
	 */
	private static function validateField(&$value, $field, $model = null){
		/** @var Model $obj */
		$obj = self::meta();
		$define = $obj->getPropertiesDefine($field);

		$err = '';
		$val = $value;
		$name = $define['alias'];
		if(is_callable($define['options'])){
			$define['options'] = call_user_func($define['options'], $model);
		}

		$required = $define['required'];

		//type
		if(!$err){
			switch($define['type']){
				case 'int':
					if($val != intval($val)){
						$err = $name.'格式不正确';
					}
					break;

				case 'float':
				case 'double':
				case 'decimal':
					if(!(!$required && !strlen($val.'')) && isset($val) && !is_numeric($val)){
						$err = $name.'格式不正确';
					}
					break;

				case 'enum':
					$err = !(!$required && !strlen($val.'')) && !isset($define['options'][$val]) ? '请选择'.$name : '';
					break;

				//string暂不校验
				case 'string':
					break;
			}
		}

		//required
		if(!$err && $define['required'] && strlen($val) == 0){
			$err = "请输入{$name}";
		}

		//length
		if(!$err && $define['length'] && $define['type'] != 'datetime' && $define['type'] != 'date' && $define['type'] != 'time'){
			
			if($define['precision']){
				if(is_numeric($val) && is_numeric($define['precision'])){
					$val = sprintf("%.{$define['precision']}f",$val);
				}
				$int_len = strlen(substr($val, 0, strpos($val, '.')));
				$precision_len = strpos($val, '.') !== false ? strlen(substr($val, strpos($val, '.')+1)) : 0;
				if($int_len > $define['length'] || $precision_len > $define['precision']){
					$err = $obj->getTableName()."{$name}长度超出：$value";
				}
			} else {
				$err = strlen($val)>$define['length'] ?$obj->getTableName(). "{$name}长度超出：$value" : '';
			}
		}

		if(!$err){
			$value = $val;
		}
		return $err;
	}

	/**
	 * 批量插入数据
	 * 由于这里插入会涉及到数据检查，最终效果还是一条一条的插入
	 * @param $data_list
	 * @param bool $break_on_fail
	 * @return array|bool
	 * @throws \Exception
	 * @throws \Lite\Exception\Exception
	 */
	public static function insertMany($data_list, $break_on_fail = true){
		if(count($data_list, COUNT_RECURSIVE) == count($data_list)){
			throw new Exception('2 dimension array needed');
		}
		$obj = static::meta();
		$return_list = array();
		foreach($data_list as $data){
			try{
				$tmp = clone($obj);
				$tmp->setValues($data);
				$result = $tmp->insert();
				if($result){
					$pk_val = $tmp->getDbDriver(self::DB_WRITE)->getLastInsertId();
					$return_list[] = $pk_val;
				}
			} catch(\Exception $e){
				if($break_on_fail){
					throw $e;
				}
			}
		}
		return $return_list;
	}

	/**
	 * 快速批量插入数据，不进行ORM检查
	 * @param $data_list
	 * @return mixed
	 * @throws \Lite\Exception\Exception
	 */
	public static function insertManyQuick($data_list){
		if(self::onBeforeChanged() === false){
			return false;
		}
		$obj = static::meta();
		$result = $obj->getDbDriver(self::DB_WRITE)->insert($obj->getTableName(), $data_list);
		return $result;
	}
	
	/**
	 * 从数据库从删除当前对象对应的记录
	 * @return bool
	 * @throws \Lite\Exception\Exception
	 */
	public function delete(){
		$pk_val = $this[$this->getPrimaryKey()];
		$this->last_operate_type = self::LAST_OP_DELETE;
		return static::delByPk($pk_val);
	}
	
	/**
	 * 解析SQL查询中的条件表达式
	 * @param array $args 参数形式可为 [""],但不可为 ["", "aa"] 这种传参
	 * @param \Lite\DB\Model $obj
	 * @return string
	 * @throws \Lite\Exception\Exception
	 */
	private static function parseConditionStatement($args, Model $obj){
		$statement = $args[0];
		$args = array_slice($args, 1);
		if(!empty($args) && $statement){
			$arr = explode('?', $statement);
			$rst = '';
			foreach($args as $key => $val){
				if(is_array($val)){
					array_walk($val, function(&$item) use ($obj){
						$item = $obj->getDbDriver(self::DB_READ)->quote($item);
					});

					if(!empty($val)){
						$rst .= $arr[$key].'('.join(',', $val).')';
					} else{
						$rst .= $arr[$key].'(NULL)'; //This will never match, since nothing is equal to null (not even null itself.)
					}
				} else{
					$rst .= $arr[$key].$obj->getDbDriver(self::DB_READ)->quote($val);
				}
			}
			$rst .= array_pop($arr);
			$statement = $rst;
		}
		return $statement;
	}
	
	/**
	 * 保存当前对象变更之后的数值
	 * @return bool
	 * @throws \Lite\Exception\BizException
	 * @throws \Lite\Exception\Exception
	 */
	public function save(){
		if($this->onBeforeSave() === false){
			return false;
		}
		
		if(!$this->getValueChangeKeys()){
			return false;
		}

		$data = $this->getValues();
		$has_pk = !empty($data[$this->getPrimaryKey()]);
		if($has_pk){
			return $this->update();
		} else if(!empty($data)){
			return $this->insert();
		}
		return false;
	}
	
	/**
	 * 对象克隆，支持查询对象克隆
	 */
	public function __clone(){
		if(is_object($this->query)){
			$this->query = clone $this->query;
		}
	}
	
	/**
	 * 调用查询对象其他方法
	 * @param $method_name
	 * @param $params
	 * @return static|Query
	 * @throws Exception
	 */
	final public function __call($method_name, $params){
		if(method_exists($this->query, $method_name)){
			call_user_func_array(array($this->query, $method_name), $params);
			return $this;
		}

		throw new Exception("METHOD NO EXIST:".$method_name);
	}

	/**
	 * 重载DAO属性设置方法，实现数据库SET提交
	 * @param $key
	 * @param $val
	 */
	public function __set($key, $val){
		if(is_array($val)){
			$define = $this->getPropertiesDefine($key);
			if($define && $define['type'] == 'set'){
				$val = join(',', $val);
			}
		}
		parent::__set($key, $val);
	}

	/**
	 * 配置getter
	 * <p>
	 * 支持：'name' => array(
	 *          'has_one'=>callable,
	 *          'target_key'=>'category_id',
	 *          'source_key'=>默认当前对象PK)
	 * 支持：'children' => array(
	 *          'has_many'=>callable,
	 *          'target_key'=>'category_id',
	 *          'source_key' => 默认当前对象PK)
	 * 支持：'name' => array(
	 *          'getter' => function($k){
	 *          }
	 *      )
	 * 支持：'name' => array(
	 *          'setter' => function($k, $v){
	 *          }
	 *      )
	 * </p>
	 * @param $key
	 * @throws \Lite\Exception\Exception
	 * @return mixed
	 */
	public function __get($key){
		$define = $this->getPropertiesDefine($key);

		if($define){
			if($define['getter']){
				return call_user_func($define['getter'], $this);
			} else if($define['has_one'] || $define['has_many']){
				$source_key = $define['source_key'];
				$target_key = $define['target_key'];

				if($define['has_one']){
					if(!$target_key && !$source_key){
						throw new Exception('has one config must define target key or source key');
					}
					$match_val = $this->getValue($source_key ?: $this->getPrimaryKey());
					/** @var Model $class */
					$class = $define['has_one'];
					if(!$target_key){
						return $class::findOneByPk($match_val);
					} else{
						return $class::find("$target_key = ?", $match_val)->one();
					}
				}
				if($define['has_many']){
					if(!$target_key){
						throw new Exception('has many config must define target key');
					}
					/** @var Model $class */
					$class = $define['has_many'];
					$match_val = $this->getValue($source_key ?: $this->getPrimaryKey());
					return $class::find("$target_key = ?", $match_val)->all();
				}
			}
		}
		$v = parent::__get($key);
		
		/**
		 * @todo 这里由于在update/add模板共用情况下，很可能使用 $model->$field 进行直接拼接action，需要重新审视这里抛出exception是否合理
		//如果当前属性未定义，或者未从数据库中获取相应字段
		//则抛异常
		$kvs = array_keys($this->getValues());
		if(!isset($v) && !in_array($key, $kvs)){
			throw new Exception('model fields not set in query result', null, $key);
		}
		**/
		return $v;
	}

	/**
	 * 获取数据库表描述名称
	 * @return string
	 */
	public function getModelDesc(){
		return $this->getTableName();
	}

	/**
	 * 转换当前查询对象为字符串
	 * @return string
	 */
	public function __toString(){
		return $this->query.'';
	}

	/**
	 * 获取影响条数
	 * @return int
	 */
	public function getAffectNum(){
		$type = Query::isWriteOperation($this->query) ? self::DB_WRITE : self::DB_READ;
		return $this->getDbDriver($type)->getAffectNum();
	}
	
	/**
	 * @return string
	 */
	public function getLastOperateType(){
		return $this->last_operate_type;
	}
}