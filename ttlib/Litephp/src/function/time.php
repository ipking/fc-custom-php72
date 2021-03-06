<?php
/**
 * 时间相关操作函数
 * User: sasumi
 * Date: 17/7/17
 * Time: 22:13
 */
namespace Lite\func;

use DateTime;
use Lite\Exception\Exception;
const DATETIME_FMT = 'Y-m-d H:i:s';
const ONE_DAY = 86400;

/**
 * 获取制定开始时间、结束时间的上中下旬分段数组
 * @param $start_str
 * @param $end_str
 * @return array [[period_th, start_time, end_time],...]
 * @throws \Lite\Exception\Exception
 */
function time_get_month_period_ranges($start_str, $end_str){
	$start = strtotime($start_str);
	$end = strtotime($end_str);
	$ranges = [];
	$period_map = ['-01 00:00:00', '-11 00:00:00', '-21 00:00:00'];

	if($start > $end){
		throw new Exception('time range parameter error: '.$start_str.'-'.$end_str);
	}

	$start_d = date('d', $start);
	$end_d = date('d', $end);
	$start_period = $start_d > 20 ? 2 : ($start_d > 10 ? 1 : 0);
	$end_period = $end_d > 20 ? 2 : ($end_d > 10 ? 1 : 0);

	//in same month
	if(date('Y-m', $start) == date('Y-m', $end)){
		$ym_str = date('Y-m', $end);
		for($i = $start_period; $i<=$end_period; $i++){
			$s = max(strtotime($ym_str . $period_map[$i]), $start);
			$e = $i == $end_period ? $end : min(strtotime($ym_str . $period_map[$i+1])-1, $end);
			$ranges[] = [$i, date(DATETIME_FMT, $s), date(DATETIME_FMT, $e)];
		}
		return $ranges;
	}

	//in next month
	else if(date('Y-m', strtotime('+1 month', strtotime(date('Y-m-01',$start)))) == date('Y-m', $end)){
		$st_ym_str = date('Y-m', $start);
		$ranges = array_merge($ranges, time_get_month_period_ranges(date('Y-m-d H:i:s',$start), $st_ym_str.'-'.date("t",strtotime($st_ym_str.'-01')).' 23:59:59'));
		$ranges = array_merge($ranges, time_get_month_period_ranges(date('Y-m-01 00:00:00', $end), date('Y-m-d H:i:s',$end)));
	}

	//sep by months
	else {
		//start of first month
		$st_ym_str = date('Y-m', $start);
		$ranges = array_merge($ranges, time_get_month_period_ranges(date('Y-m-d H:i:s',$start), $st_ym_str.'-'.date("t",strtotime($st_ym_str.'-01')).' 23:59:59'));

		//middle months
		$s = new DateTime();
		$s->setTimestamp($start);
		$e = new DateTime();
		$e->setTimestamp($end);
		$months = $s->diff($e)->m + $s->diff($e)->y*12;
		for($m = 1; $m<$months; $m++){
			$tmp = strtotime("+$m month", strtotime(date('Y-m-01', $start)));
			$month_st = date('Y-m-01 00:00:00', $tmp);
			$month_ed = date('Y-m-'.date('t', $tmp).' 23:59:59', $tmp);

			if(strtotime($month_ed) > $end){
				break;
			}
			$ranges = array_merge($ranges, time_get_month_period_ranges($month_st, $month_ed));
		}

		//end of last month
		$ranges = array_merge($ranges, time_get_month_period_ranges(date('Y-m-01 00:00:00', $end), date('Y-m-d H:i:s',$end)));
	}
	return $ranges;
}

/**
 * @param $ranges
 * @param string $default_start
 * @param string $default_end
 * @param bool $datetime
 * @return array
 */
function filter_date_range($ranges, $default_start='', $default_end='', $datetime=false){
	list($start, $end) = $ranges ?: [];
	if(!isset($start) && $default_start){
		$start = is_numeric($default_start) ? date('Y-m-d', $default_start) : $default_start;
	}
	if($datetime && $start && !$default_start){
		$start .= ' 00:00:00';
	}
	if(!isset($end) && $default_end){
		$end = is_numeric($default_end) ? date('Y-m-d', $default_end) : $default_end;
	}
	if($datetime && $end && !$default_end){
		$end .= ' 23:59:59';
	}
	return [$start, $end];
}

/**
 * Calculate a precise time difference.
 * @param string $start result of microtime()
 * @param string $end result of microtime(); if NULL/FALSE/0/'' then it's now
 * @return float difference in seconds, calculated with minimum precision loss
 */
function microtime_diff($start, $end = null){
	if (!$end) {
		$end = microtime();
	}
	list($start_usec, $start_sec) = explode(" ", $start);
	list($end_usec, $end_sec) = explode(" ", $end);
	$diff_sec = intval($end_sec) - intval($start_sec);
	$diff_usec = floatval($end_usec) - floatval($start_usec);
	return floatval($diff_sec) + $diff_usec;
}

/**
 * convert microtime format to date
 * @param $microtime
 * @param string $format
 * @return false|string
 */
function microtime_to_date($microtime, $format='Y-m-d H:i:s'){
	return date($format, explode(' ', $microtime)[1]);
}

/**
 * 格式化友好显示时间
 * @param $timestamp
 * @param bool $as_html 是否使用span包裹
 * @return string
 */
function pretty_time($timestamp, $as_html = false){
	$str = '';
	$offset = time()-$timestamp;
	$before = $offset>0;
	$offset = abs($offset);
	$unit_cal = array(
		'年'  => 31104000,
		'个月' => 2592000,
		'天'  => 86400,
		'小时' => 3600,
		'分钟' => 60,
	);
	if($offset>30 && $offset<60){
		$str = $before ? '刚才' : '等下';
	} else if($offset<=30){
		$str = $before ? '刚刚' : '马上';
	} else{
		$us = array();
		foreach($unit_cal as $u){
			$tmp = $offset>=$u ? floor($offset/$u) : 0;
			$offset -= $tmp ? $u : 0;
			$us[] = $tmp;
		}
		foreach($us as $k => $u){
			if($u){
				$str = $u.array_keys($unit_cal)[$k].($before ? '前' : '后');
				break;
			}
		}
	}
	return $as_html ? '<span title="'.date('Y-m-d H:i:s', $timestamp).'">'.$str.'</span>' : $str;
}

/**
 * 求两个日期之间相差的天数
 * (针对1970年1月1日之后，求之前可以采用泰勒公式)
 * @param string $day1
 * @param string $day2
 * @return number
 */
function diffBetweenTwoDays ($day1, $day2)
{
    $second1 = strtotime($day1);
    $second2 = strtotime($day2);

    if ($second1 < $second2) {
        $tmp = $second2;
        $second2 = $second1;
        $second1 = $tmp;
    }
    return ($second1 - $second2) / 86400;
}