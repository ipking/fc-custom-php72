<?php

namespace DataCenter\lib\tool;


use Exception;

class Snowflake
{
	const EPOCH = 0;    // 起始时间戳，毫秒
	
	const SEQUENCE_BITS = 12;   //序号部分12位
	const SEQUENCE_MAX = -1 ^ (-1 << self::SEQUENCE_BITS);  // 序号最大值
	
	const WORKER_BITS = 10; // 节点部分10位
	const WORKER_MAX = -1 ^ (-1 << self::WORKER_BITS);  // 节点最大数值
	
	const TIME_SHIFT = self::WORKER_BITS + self::SEQUENCE_BITS; // 时间戳部分左偏移量
	const WORKER_SHIFT = self::SEQUENCE_BITS;   // 节点部分左偏移量
	
	protected $timestamp;   // 上次ID生成时间戳
	protected $workerId;    // 节点ID
	protected $sequence;    // 序号
	protected $id;          // 上次ID值
	
	static private $_instance = array();
	
	/**
	 * @param int $workerId
	 * @return self
	 * @throws \Exception
	 */
	static public function instance($workerId=1){
		if (!self::$_instance){
			self::$_instance = new self($workerId);
		}
		return self::$_instance;
	}
	
	public function __construct($workerId)
	{
		if ($workerId < 0 || $workerId > self::WORKER_MAX) {
			throw new Exception("当前Worker ID[".$workerId."] 超出范围:0-".self::WORKER_MAX);
		}
		$this->timestamp = 0;
		$this->workerId = $workerId;
		$this->sequence = 0;
	}
	
	/**
	 * 生成ID
	 * @return int
	 * @throws \Exception
	 */
	public function getId()
	{
		$now = $this->now();
		if((log(PHP_INT_MAX + 1, 2) + 1) == 32){
			return rand(1,time());
		}
		
		if ($this->timestamp == $now) {
			$this->sequence++;
			
			if ($this->sequence > self::SEQUENCE_MAX) {
				// 当前毫秒内生成的序号已经超出最大范围，等待下一毫秒重新生成
				while ($now <= $this->timestamp) {
					$now = $this->now();
				}
			}
		} else {
			$this->sequence = 0;
		}
		
		$la = ($now - self::EPOCH) << self::TIME_SHIFT;
		$lb = $this->workerId << self::WORKER_SHIFT;
		$lc = $this->sequence;
		$this->timestamp = $now;    // 更新ID生时间戳
		
		$this->id = $id = sprintf("%s",$la |$lb | $lc);
		
		return $id;
	}
	
	/**
	 * 获取当前毫秒
	 * @return string
	 */
	public function now()
	{
		return sprintf("%.0f", microtime(true) * 1000);
	}
	
}