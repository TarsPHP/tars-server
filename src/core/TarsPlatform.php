<?php
/**
 * Created by PhpStorm.
 * User: liangchen
 * Date: 2018/12/20
 * Time: 14:07
 */

namespace Tars\core;

use Tars\App;

class TarsPlatform
{

    // 这个东西需要单列出去
    public static function keepaliveInit($tarsConfig, $master_pid)
    {
        $tarsServerConf = $tarsConfig['tars']['application']['server'];

        // 加载tars需要的文件 - 最好是通过autoload来加载
        // 初始化的上报
        $serverInfo = new \Tars\report\ServerInfo();
        $serverInfo->adapter = $tarsServerConf['adapters'][0]['adapterName'];
        $serverInfo->application = $tarsServerConf['app'];
        $serverInfo->serverName = $tarsServerConf['server'];
        $serverInfo->pid = $master_pid;


        $serverF = App::getServerF();
        try {
            $serverF->keepAlive($serverInfo);
            $serverInfo->adapter = 'AdminAdapter';
            $serverF->keepAlive($serverInfo);
        } catch (\Exception $e) {
            App::getLogger()->error((string)$e);
        }

    }

    //拉取配置到指定目录
    public static function loadTarsConfig($tarsConfig, $saveTarsConfigFileDir, $saveTarsConfigFileName)
    {
        $tarsServerConf = $tarsConfig['tars']['application']['server'];

        $fileNameArr = array_filter($saveTarsConfigFileName);

        $application = $tarsServerConf['app'];
        $serverName = $tarsServerConf['server'];

        if (!empty($fileNameArr) && $application != '' && $serverName != '') {
            $configServant = App::getConfigF();

            foreach ($fileNameArr as $filename) {
                $savefile = $filename;
                if (substr($filename, 0, 1) != DIRECTORY_SEPARATOR) {
                    //相对路径转绝对路径
                    $savefile = $tarsServerConf['basepath'] . $saveTarsConfigFileDir . $filename;
                }
                try {
                    $configStr = '';
                    $configServant->loadConfig($application, $serverName, $filename, $configStr);
                    if ($configStr != '') { //保存文件
                        $file = fopen($savefile, "w");
                        fwrite($file, $configStr);
                        fclose($file);
                    }
                } catch (\Exception $e) {
                    App::getLogger()->error((string)$e);
                }
            }
        }
    }

    public static function keepaliveReport($data)
    {
        $application = $data['application'];
        $serverName = $data['serverName'];
        $masterPid = $data['masterPid'];
        $adapter = $data['adapter'];

        // 进行一次上报
        $serverInfo = new \Tars\report\ServerInfo();
        $serverInfo->adapter = $adapter;
        $serverInfo->application = $application;
        $serverInfo->serverName = $serverName;
        $serverInfo->pid = $masterPid;

        try {
            $serverF = App::getServerF();
            $serverF->keepAlive($serverInfo);

            $adminServerInfo = new \Tars\report\ServerInfo();
            $adminServerInfo->adapter = 'AdminAdapter';
            $adminServerInfo->application = $application;
            $adminServerInfo->serverName = $serverName;
            $adminServerInfo->pid = $masterPid;
            $serverF->keepAlive($adminServerInfo);
        } catch (\Exception $e) {
            App::getLogger()->error((string)$e);
        }
    }

    //基础特性上报，上报：内存/cpu使用情况，worker进程数，连接数
    public static function basePropertyMonitor($serverName)
    {
        $localIP = swoole_get_local_ip();
        $ip = isset($localIP['eth0'])
            ? $localIP['eth0']
            : (isset(array_values($localIP)[0]) ? array_values($localIP)[0] : '127.0.0.1');
        $msgHeadArr = [];
        $msgBodyArr = [];

        //系统内存使用情况
        exec("free -m", $sysMemInfo);
        preg_match_all("/\d+/s", $sysMemInfo[2], $matches);
        if (isset($matches[0][0])) {
            $msgHead = [
                'ip' => $ip,
                'propertyName' => 'system.memoryUsage'
            ];
            $msgBody = [
                'policy' => 'Avg',
                'value' => isset($matches[0][0]) ? (int)$matches[0][0] : 0,
            ];
            $msgHeadArr[] = $msgHead;
            $msgBodyArr[] = $msgBody;
        }
        //服务内存使用情况
        exec("ps -e -ww -o 'rsz,cmd' | grep {$serverName} | grep -v grep | awk '{count += $1}; END {print count}'",
            $serverMemInfo);
        if (isset($serverMemInfo)) {
            $msgHead = [
                'ip' => $ip,
                'propertyName' => $serverName . '.memoryUsage'
            ];
            $msgBody = [
                'policy' => 'Avg',
                'value' => isset($serverMemInfo[0]) ? (int)$serverMemInfo[0] : 0
            ];
            $msgHeadArr[] = $msgHead;
            $msgBodyArr[] = $msgBody;
        }
        //系统cpu使用情况
        exec("command -v mpstat > /dev/null && mpstat -P ALL | awk '{if($12!=\"\") print $12}' | tail -n +3",
            $cpusInfo);
        if (isset($cpusInfo)) {
            foreach ($cpusInfo as $key => $cpuInfo) {
                $cpuUsage = 100 - $cpuInfo;
                $msgHead = [
                    'ip' => $ip,
                    'propertyName' => "system.cpu{$key}Usage"
                ];
                $msgBody = [
                    'policy' => 'Avg',
                    'value' => (int)$cpuUsage
                ];
                $msgHeadArr[] = $msgHead;
                $msgBodyArr[] = $msgBody;
            }
        }
        //swoole特性
        exec("ps wwaux | grep {$serverName} | grep 'event worker process' | grep -v grep | wc -l",
            $swooleWorkerNum);
        if (isset($swooleWorkerNum)) {
            $msgHead = [
                'ip' => $ip,
                'propertyName' => $serverName . '.swooleWorkerNum'
            ];
            $msgBody = [
                'policy' => 'Avg',
                'value' => isset($swooleWorkerNum[0]) ? $swooleWorkerNum[0] : 0
            ];
            $msgHeadArr[] = $msgHead;
            $msgBodyArr[] = $msgBody;
        }
        //连接数
        exec("ps -ef -ww | grep {$serverName} | grep -v grep | awk '{print $2}'", $serverPidInfo);
        $tmpId = [];
        foreach ($serverPidInfo as $pid) {
            $tmpId[] = $pid . "/";
        }
        $grepPidInfo = implode("|", $tmpId);
        $command = "command -v netstat > /dev/null && netstat -anlp | grep tcp | grep -E '{$grepPidInfo}' | awk '{print $6}' | awk -F: '{print $1}'|sort|uniq -c|sort -nr";
        exec($command, $netStatInfo);
        foreach ($netStatInfo as $statInfo) {
            $statArr = explode(' ', trim($statInfo));
            $msgHead = [
                'ip' => $ip,
                'propertyName' => $serverName . '.netStat.' . $statArr[1]
            ];
            $msgBody = [
                'policy' => 'Avg',
                'value' => isset($statArr[0]) ? (int)$statArr[0] : 0
            ];
            $msgHeadArr[] = $msgHead;
            $msgBodyArr[] = $msgBody;
        }
        try {
            $propertyFWrapper = App::getPropertyF();
            $propertyFWrapper->monitorPropertyBatch($msgHeadArr, $msgBodyArr);
        } catch (\Exception $e) {
            App::getLogger()->error((string)$e);
        }
    }

    public static function processAdmin($tarsConfig, $unpackResult, $sFuncName, $response, $master_pid)
    {
        $tarsServerConf = $tarsConfig['tars']['application']['server'];
        $tarsClientConf = $tarsConfig['tars']['application']['client'];
        $application = $tarsServerConf['app'];
        $serverName = $tarsServerConf['server'];

        $sBuffer = $unpackResult['sBuffer'];
        $iVersion = $unpackResult['iVersion'];
        $iRequestId = $unpackResult['iRequestId'];
        switch ($sFuncName) {
            case 'shutdown':
                {
                    $cmd = "kill -15 " . $master_pid;
                    exec($cmd, $output, $r);
                    break;
                }
            case 'notify':
                {
                    if ($iVersion === \Tars\Consts::TUPVERSION) {
                        $cmd = \TUPAPI::getString('cmd', $sBuffer, false, $iVersion);

                    } elseif ($iVersion === \Tars\Consts::TARSVERSION) {
                        $cmd = \TUPAPI::getString(1, $sBuffer, false, $iVersion);
                    }

                    $returnStr = '';
                    // 查看服务状态
                    if ($cmd == "tars.viewstatus") {
                        $returnStr = "[1]:==================================================\n[proxy config]:\n";
                        foreach ($tarsClientConf as $key => $value) {
                            $returnStr .= "$key      " . $value;
                            $returnStr .= "\n";
                        }
                        $returnStr .= "--------------------------------------------------\n[server config]:\n";
                        foreach ($tarsServerConf as $key => $value) {
                            if ($key == "adapters") {
                                continue;
                            }
                            $returnStr .= "$key      " . $value;
                            $returnStr .= "\n";
                        }

                        foreach ($tarsServerConf['adapters'] as $adapter) {
                            $returnStr .= "--------------------------------------------------\n";
                            foreach ($adapter as $key => $value) {
                                $returnStr .= "$key      " . $value;
                                $returnStr .= "\n";
                            }
                        }
                        $returnStr .= "--------------------------------------------------\n";
                    } // 加载服务配置
                    else {
                        if (strstr($cmd, "tars.loadconfig")) {
                            // 这个事,最好是起一个task worker去干比较好
                            $parts = explode(' ', $cmd);
                            $fileName = $parts[1];


                            $configF = App::getConfigF();
                            $configContent = '';
                            try {
                                $configF->loadConfig($application, $serverName, $fileName, $configContent);

                                file_put_contents($tarsServerConf['basepath'] . '/src/conf/' . $fileName,
                                    $configContent);

                                $returnStr = '[notify file num:1][located in {ServicePath}/bin/conf]';
                            } catch (\Exception $e) {
                                App::getLogger()->error("Load config failed: " . (string)$e);
                            }

                        }
                    }

                    $str = \TUPAPI::putString(0, $returnStr, 1);
                    $cPacketType = 0;
                    $iMessageType = 0;

                    $rspBuf = \TUPAPI::encodeRspPacket($iVersion, $cPacketType,
                        $iMessageType, $iRequestId, 0, '', [$str], []);
                    $response->send($rspBuf);

                    break;
                }
            default:
                break;
        }
    }

}