<?php

namespace DataCenter\lib\tool;

use Exception;

class Util{
	const CONSOLE_TYPE_NORMAL = "Normal";
	const CONSOLE_TYPE_SUCCESS = "Success";
	const CONSOLE_TYPE_ERROR = "Error";
	const CONSOLE_TYPE_CONTENT = "Content";
	static $consoleTypeMap = array(
		self::CONSOLE_TYPE_NORMAL  => array("st" => "__@SCVAR", 'ed' => "__@ECVAR"),
		self::CONSOLE_TYPE_CONTENT => array("st" => "__@SCONTENT", 'ed' => "__@ECONTENT"),
		self::CONSOLE_TYPE_SUCCESS => array("st" => "__@SUCCESS", "ed" => "__@SUCCESS"),
		self::CONSOLE_TYPE_ERROR   => array("st" => "__@ERROR", "ed" => "__@ERROR"),
	);
	
	/**
	 *数字金额转换成中文大写金额的函数
	 *String Int  $num  要转换的小写数字或小写字符串
	 *return 大写字母
	 *小数位为两位
	 * @param $num
	 * @return string
	 * @throws \Exception
	 */
	public static function getChtNumber($num){
		$c1 = "零壹贰叁肆伍陆柒捌玖";
		$c2 = "分角元拾佰仟万拾佰仟亿";
		$num = round($num, 2);
		$num = $num*100;
		if(strlen($num)>10){
			throw new Exception('number overflow:'.$num);
		}
		$i = 0;
		$c = "";
		while(1){
			if($i == 0){
				$n = substr($num, strlen($num)-1, 1);
			} else{
				$n = $num%10;
			}
			$p1 = substr($c1, 3*$n, 3);
			$p2 = substr($c2, 3*$i, 3);
			if($n != '0' || ($n == '0' && ($p2 == '亿' || $p2 == '万' || $p2 == '元'))){
				$c = $p1.$p2.$c;
			} else{
				$c = $p1.$c;
			}
			$i = $i+1;
			$num = $num/10;
			$num = (int)$num;
			if($num == 0){
				break;
			}
		}
		$j = 0;
		$str_len = strlen($c);
		while($j<$str_len){
			$m = substr($c, $j, 6);
			if($m == '零元' || $m == '零万' || $m == '零亿' || $m == '零零'){
				$left = substr($c, 0, $j);
				$right = substr($c, $j+3);
				$c = $left.$right;
				$j = $j-3;
				$str_len = $str_len-3;
			}
			$j = $j+3;
		}
		
		if(substr($c, strlen($c)-3, 3) == '零'){
			$c = substr($c, 0, strlen($c)-3);
		}
		if(empty($c)){
			return "零元整";
		} else{
			return $c."整";
		}
	}
	
	
	/**
	 * 只针对德国拆门牌号
	 * @param string $country_code
	 * @param array $street
	 * @return array
	 */
	public static function parsingStreet($country_code, $street){
//		if(!in_array($country_code, array('DE', 'FR', 'EN', 'IT', 'AT'))){ //德，法，意，英，奥地利 才截取
//			return $street;
//		}
		
		//packstation 地址单独做处理 截取末尾的三个数字
		$address_str = strtolower($street['street1'].$street['street2'].$street['street3']);
		if(strstr($address_str, "paketstation") || strstr($address_str, "packstation") || strstr($address_str, "postfiliale") || strstr($address_str, "paketshop")){
			return $street = self::getPackstation($street);
			
		}
		$street1 = self::getDoorplate(trim($street['street1']));
		if($street1['doorplate']){
			$street['street1'] = $street1['street'];
			$street['doorplate'] = trim($street1['doorplate']);
			return $street;
		}
		$street2 = self::getDoorplate(trim($street['street2']));
		if($street2['doorplate']){
			$street['street2'] = $street2['street'];
			$street['doorplate'] = trim($street2['doorplate']);
			return $street;
		}
		$street3 = self::getDoorplate(trim($street['street3']));
		$street['street3'] = $street3['street'];
		$street['doorplate'] = trim($street3['doorplate']);
		return $street;
	}
	
	/**
	 * 获取packstation 的门牌号
	 * @param array $street
	 * @return mixed array
	 */
	public static function getPackstation($street){
		//街道1
		$street['doorplate'] = "";
		if(strstr(strtolower($street['street1']), "packstation") || strstr(strtolower($street['street1']), "paketstation") || strstr(strtolower($street['street1']), "postfiliale") || strstr(strtolower($street['street1']), "paketshop")){
			preg_match_all('/packstation[\s]{0,1}[0-9]{3}$/', strtolower($street['street1']), $matches); //以数结尾
			preg_match_all('/paketstation[\s]{0,1}[0-9]{3}$/', strtolower($street['street1']), $matches1); //以数结尾
			preg_match_all('/postfiliale[\s]{0,1}[0-9]{3}$/', strtolower($street['street1']), $matches2); //以数结尾
			preg_match_all('/paketshop[\s]{0,1}[0-9]{3}$/', strtolower($street['street1']), $matches3); //以数结尾
			$matches = $matches[0][0] ? $matches : ($matches1[0][0] ? $matches1 : ($matches2[0][0] ? $matches2 : $matches3));
			if($matches[0][0]){
				$street['street1'] = substr($street['street1'], 0, strlen($street['street1'])-3);
				$street['doorplate'] = substr($matches[0][0], strlen($matches[0][0])-3, 3);
			}
			return $street;
		}
		//街道2
		if(strstr(strtolower($street['street2']), "packstation") || strstr(strtolower($street['street2']), "paketstation") || strstr(strtolower($street['street2']), "postfiliale") || strstr(strtolower($street['street2']), "paketshop")){
			preg_match_all('/packstation[\s]{0,1}[0-9]{3}$/', strtolower($street['street2']), $matches); //以数结尾
			preg_match_all('/paketstation[\s]{0,1}[0-9]{3}$/', strtolower($street['street2']), $matches1); //以数结尾
			preg_match_all('/postfiliale[\s]{0,1}[0-9]{3}$/', strtolower($street['street2']), $matches2); //以数结尾
			preg_match_all('/paketshop[\s]{0,1}[0-9]{3}$/', strtolower($street['street2']), $matches3); //以数结尾
			$matches = $matches[0][0] ? $matches : ($matches1[0][0] ? $matches1 : ($matches2[0][0] ? $matches2 : $matches3));
			if($matches[0][0]){
				$street['street2'] = substr($street['street2'], 0, strlen($street['street2'])-3);
				$street['doorplate'] = substr($matches[0][0], strlen($matches[0][0])-3, 3);
			}
			return $street;
		}
		//街道3
		if(strstr(strtolower($street['street3']), "packstation") || strstr(strtolower($street['street3']), "paketstation") || strstr(strtolower($street['street3']), "postfiliale") || strstr(strtolower($street['street3']), "paketshop")){
			preg_match_all('/packstation[\s]{0,1}[0-9]{3}$/', strtolower($street['street3']), $matches); //以数结尾
			preg_match_all('/paketstation[\s]{0,1}[0-9]{3}$/', strtolower($street['street3']), $matches1); //以数结尾
			preg_match_all('/postfiliale[\s]{0,1}[0-9]{3}$/', strtolower($street['street3']), $matches2); //以数结尾
			preg_match_all('/paketshop[\s]{0,1}[0-9]{3}$/', strtolower($street['street3']), $matches3); //以数结尾
			$matches = $matches[0][0] ? $matches : ($matches1[0][0] ? $matches1 : ($matches2[0][0] ? $matches2 : $matches3));
			if($matches[0][0]){
				$street['street3'] = substr($street['street3'], 0, strlen($street['street3'])-3);
				$street['doorplate'] = substr($matches[0][0], strlen($matches[0][0])-3, 3);
			}
			return $street;
		}
		
		return $street;
	}
	
	/**
	 * 从地址中获得门牌号
	 * @param $street
	 * @return array
	 */
	public static function getDoorplate($street){
		//去除结尾的点
		if(strrchr($street, ".") == "."){
			$street = substr($street, 0, strlen($street)-1);
		}
		//preg_match('/^[0-9]{1,3}[a-zA-Z]{0,1}\s+/', $str,$m);
		preg_match_all('/[\s|.][0-9]{1,3}[\s]{0,1}[a-zA-Z]{0,1}$/', $street, $matches); //以数字字母结尾
		$street = preg_replace("/{$matches[0][0]}$/i", "", $street);
		if(!$matches[0][0]){
			preg_match_all("/^[0-9]{1,3}[a-zA-Z]{0,1}\s|$/", $street, $matches);
			$street = preg_replace("/^{$matches[0][0]}/i", "", $street);
		}
		if(!$matches[0][0]){
			preg_match_all("/^[0-9]{1,3}[a-zA-Z]{0,1}$/", $street, $matches);
			$street = preg_replace("/^{$matches[0][0]}/i", "", $street);
		}
		return array(
			"street"    => $street,
			"doorplate" => $matches[0][0] ? str_replace(".", "", $matches[0][0]) : ""
		);
	}
	
	public static function encrypt($string, $operation, $key = ''){
		$key = md5($key);
		$key_length = strlen($key);
		$string = $operation == 'D' ? base64_decode($string) : substr(md5($string.$key), 0, 8).$string;
		$string_length = strlen($string);
		$rnd_key = $box = array();
		$result = '';
		for($i = 0; $i<=255; $i++){
			$rnd_key[$i] = ord($key[$i%$key_length]);
			$box[$i] = $i;
		}
		for($j = $i = 0; $i<256; $i++){
			$j = ($j+$box[$i]+$rnd_key[$i])%256;
			$tmp = $box[$i];
			$box[$i] = $box[$j];
			$box[$j] = $tmp;
		}
		for($a = $j = $i = 0; $i<$string_length; $i++){
			$a = ($a+1)%256;
			$j = ($j+$box[$a])%256;
			$tmp = $box[$a];
			$box[$a] = $box[$j];
			$box[$j] = $tmp;
			$result .= chr(ord($string[$i])^($box[($box[$a]+$box[$j])%256]));
		}
		if($operation == 'D'){
			if(substr($result, 0, 8) == substr(md5(substr($result, 8).$key), 0, 8)){
				return substr($result, 8);
			} else{
				return '';
			}
		} else{
			return str_replace('=', '', base64_encode($result));
		}
	}
	
	
	/**
	 * @param $utcTime
	 * @return false|string
	 */
	public static function getLocalTime($utcTime){
		return date("Y-m-d H:i:s", strtotime($utcTime));
	}
	
	/**
	 * @param $date
	 * @param $hour
	 * @param $minute
	 * @return false|string
	 */
	public static function adjustDate($date,$hour,$minute){
		$time = strtotime($date);
		//如果是UTC 直接返回即可
		if(strpos(strtolower($date),'z') !== false){
			return date("Y-m-d H:i:s",$time);
		}
		return date("Y-m-d H:i:s", mktime(date('H', $time)+$hour, date('i', $time)+$minute,  date('s', $time), date('m', $time), date('d', $time), date('Y', $time)));
	}
	
	/**
	 * @param $localTime
	 * @return false|string
	 */
	public static function getEbayTime($localTime){
		return gmdate("Y-m-d H:i:s", strtotime($localTime));
	}
	
	/**
	 * 需要后续解析内容
	 * @param $var
	 * @param $type
	 * @throws \Exception
	 */
	public static function printConsoleVar($var, $type = self::CONSOLE_TYPE_NORMAL){
		
		echo self::$consoleTypeMap[$type]['st'].$var.self::$consoleTypeMap[$type]['ed'];
	}
	
	/**
	 * 从字符串中解析控制台变量
	 * @param $str
	 * @param $type string
	 * @return mixed
	 * @throws \Exception
	 */
	public static function resolveConsoleVar($str, $type = self::CONSOLE_TYPE_NORMAL){
		
		$start = self::$consoleTypeMap[$type]['st'];
		$end = self::$consoleTypeMap[$type]['ed'];
		preg_match_all("/".preg_quote($start)."(.*?)".preg_quote($end)."/", $str, $matches);
		return $matches[1] ? json_encode($matches[1]) : "";
	}
	
	/**
	 * 把一个xml串转换成数组
	 * @param $xml
	 * @return array
	 */
	static public function xmlToArray($xml){
		try{
			$data = (array)simplexml_load_string($xml);
			return json_decode(json_encode($data), true);
		} catch(Exception $ex){
			return null;
		}
	}
	
	static public function toMulti($data){
		if (empty($data)){
			return $data;
		}
		if (!isset($data[0])){
			return array('0'=>$data);
		}
		return $data;
	}
	
	/**
	 * @param string $data
	 * @return float|int
	 */
	public static function getLongTimeByDate($data){
		return strtotime($data)*1000;
	}
}
