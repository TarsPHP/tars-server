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
        if (empty($application) || empty($serverName)) {
            echo 'AppName or ServerName empty! Please check config!' . PHP_EOL;
            exit;
        }

        // 检查一下业务必须的配置是否存在
        $basePath = $tarsServerConfig['basepath'];

        $servicesInfo = require $basePath . 'src/services.php';

        //老版本检查，提示, 新版本中services.php中应该是objName 为key 的二维数组
        if (isset($servicesInfo['home-class']) || isset($servicesInfo['home-api'])  || isset($servicesInfo['namespace'])) {
            echo 'you use old version service.php, need update, please check services.php!' . PHP_EOL;
            exit();
        }

        foreach ($tarsServerConfig['adapters'] as $key => $adapter) {
            if (empty($servicesInfo[$adapter['objName']])) {
                echo $adapter['objName'] . ' not defined in services.php, please check it!' . PHP_EOL;
                exit();
            }

            $serviceInfo = $servicesInfo[$adapter['objName']];

            if (!isset($serviceInfo['serverType'])) {
                $servicesInfo[$adapter['objName']]['serverType'] = $adapter['protocol'] == 'not_tars' || $adapter['protocol'] == 'not_taf' ? 'http' : 'tcp';
            }

            if (!isset($serviceInfo['protocolName'])) {
                $servicesInfo[$adapter['objName']]['serverType'] = $adapter['protocol'] == 'not_tars' || $adapter['protocol'] == 'not_taf' ? 'http' : 'tars';
            }

            if (in_array($servicesInfo[$adapter['objName']]['serverType'], ['tcp', 'udp'])
                && (!isset($serviceInfo['home-class']) || !isset($serviceInfo['home-api']))) {
                echo $adapter['objName'] . ' home-class or home-api not exist, please check services.php!' . PHP_EOL;
                exit;
            }
        }

        $tarsServerConfig['servicesInfo'] = $servicesInfo;

        $tarsConfig['tars']['application']['server'] = $tarsServerConfig;

        $name = $application . '.' . $serverName;
        $ret = $this->getProcess($name);
        if ($ret['exist'] === true) {
            echo "{$name} start  \033[34;40m [FAIL] \033[0m process already exists" . PHP_EOL;
            exit;
        }

        $server = new Server($tarsConfig);

        //创建成功
        echo "{$name} start  \033[32;40m [SUCCESS] \033[0m" . PHP_EOL;

        $server->start();
    }
}
