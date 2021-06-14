<?php
namespace DataCenter;
use Lite\Core\Config;
use Lite\Core\View;
use Lite\Crud\ListOrderInterface;
use Lite\DB\Model;
use Lite\Exception\Exception;
use function Lite\func\array_clear_empty;
use function Lite\func\array_trim;
use function Lite\func\h;

/**
 *
 * User: sasumi
 * Date: 2015/9/29
 * Time: 21:52
 */
class ViewBase extends View {
	const CLASS_DRAFT = 'state-flag state-flag-draft';
	const CLASS_NORMAL = 'state-flag state-flag-normal';
	const CLASS_DONE = 'state-flag state-flag-done';
	const CLASS_WARN = 'state-flag state-flag-warn';
	const CLASS_DISABLED = 'state-flag state-flag-disabled';

	

	public static function prettyTime($time_str){
		if(!$time_str){
			return '-';
		}
		return '<span title="'.date('Y-m-d H:i:s', strtotime($time_str)).'">'.date('y/m/d H:i', strtotime($time_str));
	}

	public static function getImgUrl($file_name){
		return parent::getImgUrl($file_name ?: Config::get('app/default_image'));
	}

	

	/**
	 * display $model_instance
	 * @param $field
	 * @param Model $model_instance
	 * @param bool $confirm
	 * @return string
	 */
	public static function displayFieldQuickUpdate($field, Model $model_instance, $confirm = false){
		$define = $model_instance->getPropertiesDefine($field);
		if($define['options']){
			return self::displayOptionFieldQuickUpdate($field, $model_instance, $confirm);
		}
		return self::displayField($field, $model_instance);
	}


	/**
	 * @param ListOrderInterface|Model $model_instance
	 * @param $field
	 * @return string
	 */
	public static function displayListOrderUpdate($model_instance, $field){
		$val = $model_instance->$field;

		$pk = $model_instance->getPrimaryKey();
		$inc_url = self::getUrl(self::getControllerAbbr() . '/increaseField', array(
			'pk_val' => $model_instance->$pk,
			'field'  => $field,
			'offset'  => 1
		));
		$des_url = self::getUrl(self::getControllerAbbr() . '/increaseField', array(
			'pk_val' => $model_instance->$pk,
			'field'  => $field,
			'offset'  => -1
		));

		$html = '<a href="'.$inc_url.'" data-component="async" class="priority-change priority-increase"></a>';
		$html .= '<span class="priority-label">'.$val.'</span>';
		$html .= '<a href="'.$des_url.'" data-component="async" class="priority-change priority-decrease"></a>';
		return $html;
	}

	/**
	 * 显示快速更新字段
	 * @param $field
	 * @param Model $model_instance
	 * @param bool $confirm
	 * @return string
	 * @throws Exception
	 */
	public static function displayOptionFieldQuickUpdate($field, Model $model_instance, $confirm=false){
		$pk = $model_instance->getPrimaryKey();
		$define = $model_instance->getPropertiesDefine($field);
		$html = '<dl class="drop-list drop-list-left">'.
			'<dt><span>'.self::displayField($field, $model_instance).'</span></dt><dd>';
		if(is_callable($define['options'])){
			$define['options'] = call_user_func($define['options'], $model_instance);
		}
		foreach($define['options'] as $k=>$n){
			if($k != $model_instance->{$field}){
				if(!$confirm){
					$com = 'data-component="async"';
				} else {
					$com = 'data-component="confirm,async" data-confirm-message="确定进行该项操作？"';
				}
				$html .= '<a href="'.self::getUrl(self::getControllerAbbr().'/updateField', array($pk=>$model_instance->$pk, $field=>$k)).'" '.$com.'>'.h($n).'</a>';
			}
		}
		$html .= '</dd></dl>';
		return $html;
	}

	/**
	 * 获取状态变更按钮
	 * 如果是最后一个状态，则不扭转
	 * @deprecated 尚未测试，更新到updateField
	 * @param \Lite\DB\Model $model 数据模型
	 * @param null $field 状态字段名
	 * @param array $state_options 状态扭转选项（缺省读取options）
	 * @return string
	 * @throws \Lite\Exception\Exception
	 */
	public static function getNextStateBtn(Model $model, $field=null, $state_options=array()){
		if(!$field){
			throw new Exception('next state field not found');
		}
		$options = $state_options ?: $model->getPropertiesDefine($field)['options'];
		$val = $model->$field;
		$pk = $model->getPrimaryKey();

		$matched = false;
		foreach($options as $k=>$opt_name){
			if($val == $k){
				$matched = true;
			}
			else if($matched){
				return '<a href="'.self::getUrl(self::getControllerAbbr().'/updateField', array($field=>$k, $pk=>$model->$pk)).'" data-component="async" class="state-changer btn">'.$opt_name.'</a>';
			}
		}
		return '';
	}

	/**
	 * 显示集合
	 * @param $sets
	 * @param $options
	 * @param \Lite\DB\Model $model_instance
	 * @return string
	 */
	public static function displaySet($sets, $options, Model $model_instance){
		$vs = explode(',',$sets);
		$t = array();
		foreach($vs as $v){
			$t[] = $options[$v];
		}
		$html = '<ul class="tags"><li>'.join('</li><li>',$t).'</li></ul>';
		return $html;
	}

	public static function renderDateRangeElement($value_range, $field, $define=[], $model_instance=null, $extend_attr=array()){
		return '<span class="date-range-input">'.
			parent::renderDateRangeElement($value_range, $field, $define, $model_instance, $extend_attr).
		'</span>';
	}

	

	/**
	 * add element class
	 * @param $tag
	 * @param array $attributes
	 * @param array $define
	 * @return string
	 */
	public static function buildElement($tag, $attributes=array(), $define=array()){
		if($define['rel'] == 'keywords' || $define['rel'] == 'tags'){
			return self::buildTagsInput($tag, $attributes, $define);
		}

		$tag = strtolower($tag);
		$class = $attributes['class'];
		switch($tag){
			case 'input':
				if($define['type'] == 'date'){
					$attributes['type'] = 'date';
					$attributes['step'] = 1;
				}
				if($define['type'] == 'datetime' || $define['type'] == 'timestamp'){
					$attributes['type'] = 'datetime-local';
					$attributes['step'] = 1;
					$attributes['value'] = $attributes['value'] ? date('Y-m-d\TH:i:s', strtotime($attributes['value'])) : ''; //mysql 5.6 timestamp 输出为 datetime
				}
				if($define['type'] == 'time'){
					$attributes['type'] = 'time';
					$attributes['step'] = 1;
				}
				break;

			case 'textarea':
				if($define['type'] == 'simple_rich_text'){
					$attributes['data-component'] = 'richeditor';
					$attributes['data-richeditor-mode'] = 'lite';
					$class .= ' medium-txt';
				}
				else if($define['type'] == 'rich_text'){
					$attributes['data-component'] = 'richeditor';
					$attributes['data-richeditor-mode'] = 'normal';
					$class .= ' large-txt';
				}
				else {
					$class .= ' small-txt';
				}
				break;
		}

		if($class){
			$attributes['class'] = $class;
		}
		return parent::buildElement($tag, $attributes, $define);
	}

	private static function buildTagsInput($tag, $attributes=array(), $define=array()){
		$values = array_clear_empty(array_trim(explode(',', $attributes['text'])));
		$name = $attributes['name'];

		$html = '';
		$html .= '<div class="tags-input">';
		$html .= '<input type="hidden" name="'.$name.'" value="'.h($attributes['text'] ?: $attributes['value']).'"/>';
		if($values){
			$del = '<span class="del-tag" title="删除">x</span><span>';
			$html .= '<ul class="tags"><li>'.$del.join('</span></li><li>'.$del,$values).'</span></li></ul>';
		} else {
			$html .= '<ul class="tags"></ul>';
		}
		$html .= '<input type="text" value="" placeholder="回车输入" class="txt"/>';
		$html .= '</div>';
		return $html;
	}

	public static function buildUploadImage($src){
		return Config::get('upload/url').$src;
	}

	/**
	 * 设置面包屑
	 * @param array ...$array
	 * @return string
	 */
	public function buildBreadCrumbs($array){
		$html = '<ul class="breadcrumbs">';
		$html .= '<li><a href="'.$this->getUrl().'">首页</a></li>';
		foreach($array as $k => $v){
			if(is_numeric($k)){
				$html .= '<li><span>'.$v.'</span></li>';
			} else {
				$html .= '<li><a href="'.$this->getUrl($v).'">'.h($k).'</a></li>';
			}
		}
		$html .= '</ul>';
		return $html;
	}

	/**
	 * 获取table log查看链接
	 * @param \Lite\DB\Model $obj
	 * @return string
	 * @throws \Lite\Exception\Exception
	 */
	public static function getTableLogLink(Model $obj){
		$pkv = $obj->getValue($obj->getPrimaryKey());
		$url = static::getUrl('sys/SysTableLog/info', array('tbl_name'=>$obj->getTableFullName(), 'ref_id'=>$pkv));
		$html = '<a href="'.$url.'" data-component="popup">日志</a>';
		return $html;
	}
}