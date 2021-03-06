<?php
namespace Lite\DB\Driver;

use Lite\Exception\Exception;
use mysqli;
use mysqli_result;

/**
 * MYSQLi驱动类
 * @package Lite\DB\Driver
 */
class DriverMySQLi extends DBAbstract{
	/** @var \mysqli $conn */
	private $conn;

	public function dbQuery($query){
		return $this->conn->query($query.'');
	}

	public function getAffectNum(){
		return $this->conn->affected_rows;
	}

	/**
	 * @param mysqli_result $resource
	 * @return array
	 */
	public function fetchAll($resource){
		$ret = $resource->fetch_all(MYSQLI_ASSOC);
		return $ret;
	}

	public function setLimit($sql, $limit){
		if(preg_match('/\sLIMIT\s/i', $sql)){
			throw new Exception('SQL LIMIT BEEN SET:' . $sql);
		}
		if(is_array($limit)){
			return $sql . ' LIMIT ' . $limit[0] . ',' . $limit[1];
		}
		return $sql . ' LIMIT ' . $limit;
	}

	public function getLastInsertId(){
		return $this->conn->insert_id;
	}

	public function commit(){
		static::$IS_IN_TRANSACTION = false;
		$this->conn->commit();
	}

	public function rollback(){
		static::$IS_IN_TRANSACTION = false;
		$this->conn->rollback();
	}

	public function beginTransaction(){
		static::$IS_IN_TRANSACTION = true;
		$this->conn->autocommit(true);
	}

	public function cancelTransactionState(){
		$this->conn->autocommit(false);
	}
	
	/**
	 * connect to specified config database
	 * @param array $config
	 * @param boolean $re_connect 是否重新连接
	 * @return void
	 */
	public function connect(array $config, $re_connect = false){
		$conn = new mysqli($config['host'], $config['user'], $config['password'], $config['database'], $config['port']);
		$this->conn = $conn;
	}
}