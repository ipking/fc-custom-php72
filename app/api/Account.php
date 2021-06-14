<?php
namespace DataCenter\api;

use DataCenter\lib\model\TaskAccountConfig;
use DataCenter\lib\tool\Snowflake;
use Exception;

class Account extends ApiAbstract
{
	/**
	 * 新增或修改账号接口
	 * @return mixed
	 * @throws Exception
	 */
	public function save()
	{
		$param = $this->getParam([
			'account',
			"config",
			"id",
			"platform",
			"site",
			"status",
			"sys_code"
		]);

		if (empty($param['sys_code'])) {
		    return $this->error('系统代码不能为空');
        }
        //必填字段判断
		if (empty($param['platform'])) {
		    return $this->error('平台不能为空');
		}

		if (!TaskAccountConfig::$platform_map[$param['platform']]) {
            return $this->error('暂时不支持该平台');
		}
		
		
		//传了id只做更新
		if ($param['id']) {
		    $platform_account = TaskAccountConfig::findOneByPk($param['id']);
		    if (!$platform_account) {
		        return $this->error('账号不存在');
            }

            $platform_account->config = '';
            $platform_account->site = $param['site'];
            $platform_account->status = $param['status'];
			$platform_account->account = $param['account'];
            //先检查后保存
			$platform_account->save();
            return $this->success($platform_account->id);
        }

        $is_exists = TaskAccountConfig::find('account=? and sys_code=? and site=? and platform=?', $param['account'], $param['sys_code'], $param['site'], $param['platform'])->one();
        if ($is_exists) {
            return $this->error('账号已存在');
        }

        $id = Snowflake::instance()->getId();
        $platform_account = new TaskAccountConfig();
        $platform_account->id = $id;
        $platform_account->config = '';
        $platform_account->site = $param['site'];
        $platform_account->platform = $param['platform'];
        $platform_account->sys_code = $param['sys_code'];
        $platform_account->status = $param['status'];
        $platform_account->account = $param['account'];

		//先检查后保存
		$platform_account->insert();//未知原因
        return $this->success($id,true);
	}
	
	
}