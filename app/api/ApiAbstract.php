<?php

namespace DataCenter\api;



abstract class ApiAbstract
{
	const ERROR_AUTH = 1001;
	const ERROR_TOKEN = 1002;
	const ERROR_IDENTIFY = 1003;
	const ERROR_BUSINESS = 1004;//业务出错
	const ERROR_SYSTEM = 9999; //系统出错
	const ERROR_ILLEGAL_REQUEST = 9998;//非法请求
	const ERROR_DEFAULT = 2020;

	const DEFAULT_PAGE_SIZE = 20;
	const MAX_PAGE_SIZE = 100;

	protected $user;
	protected $data;

	public function __construct()
	{
	}

	public function setData($data)
	{
		$this->data = !is_array($data) ? $data : json_encode($data);
	}


	/**
	 * 过滤仅需要的字段
	 * @param array $fields
	 * @return array
	 */
	protected function getParam($fields)
	{
		$ret = array();
		if (empty($fields)) {
			return $ret;
		}
		$data = $this->getData();
		foreach ($fields as $field) {
			$ret[$field] = $data[$field];
		}
		return $ret;
	}

	final protected function getData()
	{
		return $this->data ? json_decode($this->data, true) : json_decode(file_get_contents('php://input', 'r'), true);
	}

	/**
	 * @param array | string | int $data
	 * @param bool $is_save_request_log
	 * @param string $message
	 * @return string
	 */
	protected function success($data, $is_save_request_log = true, $message = 'success')
	{
		$ret = array(
			'code'        => 0,
			'msg'         => $message ?: 'success',
			'data'        => $data,
			'timestamp'   => microtime(true) * 1000,
			'tracking_id' => '0',
			'usage_time'  => '8'
		);
		$j_ret = json_encode($ret);
		
		return $j_ret;
	}

	/**
	 * @param string $message
	 * @param int $code
	 * @return string
	 */
	protected function error($message, $code = self::ERROR_DEFAULT)
	{
		$ret = array(
			'code'        => $code,
			'msg'         => $message,
			'timestamp'   => microtime(true) * 1000,
			'tracking_id' => '0',
			'usage_time'  => '10'
		);
		$j_ret = json_encode($ret);

		
		return $j_ret;
	}

	/**
	 * 获取分页大小
	 * @param $page_size
	 * @param null $page_number
	 * @return array
	 */
	protected function getPageSize($page_size, $page_number = null)
	{
		$page_size = intval($page_size);
		if ($page_size) {
			$p = $page_size > self::MAX_PAGE_SIZE ? self::MAX_PAGE_SIZE : $page_size;
		} else {
			$p = self::DEFAULT_PAGE_SIZE;
		}

		$n = intval($page_number);
		$n = $n <= 0 ? 1 : $n;

		return array(($n - 1) * $p, $p);
	}
}