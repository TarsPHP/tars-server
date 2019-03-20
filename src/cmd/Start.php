<?php
/**
 * Created by PhpStorm.
 * User: dingpanpan
 * Date: 2017/12/2
 * Time: 16:02.
 */

namespace Tars\cmd;

use Tars\core\Server;

class Start extends CommandBase
{
    public function __construct($configPath)
    {
        parent::__construct($configPath);
    }

    public function execute()
    {
        $tarsConfig = $this->tarsConfig;
        $tarsServerConfig = $tarsConfig['tars']['application']['server'];

        $application = $tarsServerConfig['app'];
        $serverName = $tarsServerConfig['server'];

        //检查必须的服务名是否存在
        if (empty($application)
            || empty($serverName)) {
            echo 'AppName or ServerName empty! Please check config!' . PHP_EOL;
            exit;
        }

        // 检查一下业务必须的配置是否存在
        $basePath = $tarsServerConfig['basepath'];

        $servicesInfo = require $basePath . 'src/services.php';
        if ($tarsServerConfig['servType'] === 'tcp') {
            if (!isset($servicesInfo['home-class']) ||
                !isset($servicesInfo['home-api'])) {
                echo 'home-class or home-api not exist, please chech services.php!'
                    . PHP_EOL;
                exit;
            }
            $tarsServerConfig['servicesInfo'] = $servicesInfo;
        } else {
            $tarsServerConfig['servicesInfo'] = $servicesInfo;
        }

        $tarsConfig['tars']['application']['server'] = $tarsServerConfig;

        $name = $application . '.' . $serverName;
        $ret = $this->getProcess($name);
        if ($ret['exist'] === true) {
            echo "{$name} start  \033[34;40m [FAIL] \033[0m process already exists"
                . PHP_EOL;
            exit;
        }

        $server = new Server($tarsConfig);

        //创建成功
        echo "{$name} start  \033[32;40m [SUCCESS] \033[0m" . PHP_EOL;

        $server->start();
    }
}
