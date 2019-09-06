<?php
namespace SeetaAiBuildingCommunity\Modules\Cli\Tasks;

use SeetaAiBuildingCommunity\Common\Manager\Utility;
use SeetaAiBuildingCommunity\Models\TAdmin;

require_once APP_PATH.'/common/defines.php';

class MainTask extends \Phalcon\Cli\Task
{
    public function mainAction()
    {
        echo "Congratulations! You are now flying with Phalcon CLI!";
    }

    public function createAdminAction()
    {
        try {
            $admin = TAdmin::findByUsername("admin");
            if (!empty($admin)) {
                die ("管理员账号已存在，请勿重复创建！\n");
            }

            $admin = new TAdmin([
                'username' => 'admin',
                'password' => '8d969eef6ecad3c29a3a629280e686cf0c3f5d5a86aff3ca12020c923adc6c92',
                'status' => ADMIN_STATUS_VALID,
            ]);

            $admin->save();
        }  catch (\Exception $exception) {
            //数据库出错
            Utility::log('logger', $exception->getMessage(), __METHOD__, __LINE__);
            die ("管理员创建失败!\n");
        }

        echo "管理员创建成功!\n账号：admin\n密码：123456\n请尽快修改初始密码!";
    }
}

