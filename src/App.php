<?php
/**
 * Created by PhpStorm.
 * User: liangchen
 * Date: 2018/12/20
 * Time: 17:02
 */

namespace Tars;

use Tars\monitor\StatFServer;
use Tars\monitor\PropertyFServer;
use Tars\report\ServerFWrapper;
use Tars\config\ConfigWrapper;

class App
{
    /**
     * 服务启动相关配置
     * @var array
     */
    public static $tarsConfig;

    /**
     * 主调上报对象
     * @var StatFServer
     */
    public static $statF;

    /**
     * 特性上报对象
     * @var PropertyFServer
     */
    public static $propertyF;

    /**
     * 定时上报对象
     * @var ServerFWrapper
     */
    public static $serverF;

    /**
     * 配置中心对象
     * @var ConfigWrapper
     */
    public static $configF;

    /**
     * 日志对象
     * @var \Monolog\Logger
     */
    public static $logger;

    /**
     * swoole对象
     * @var \Swoole\Server
     */
    public static $swooleInstance;

    /**
     * @return \Monolog\Logger
     */
    public static function getLogger()
    {
        return self::$logger;
    }

    /**
     * @param \Monolog\Logger $logger
     */
    public static function setLogger($logger)
    {
        self::$logger = $logger;
    }

    /**
     * @return ConfigWrapper
     */
    public static function getConfigF()
    {
        return self::$configF;
    }

    /**
     * @param ConfigWrapper
     */
    public static function setConfigF(ConfigWrapper $configWrapper)
    {
        self::$configF = $configWrapper;
    }

    /**
     * @return ServerFWrapper
     */
    public static function getServerF()
    {
        return self::$serverF;
    }

    /**
     * @param ServerFWrapper
     */
    public static function setServerF(ServerFWrapper $serverFWrapper)
    {
        self::$serverF = $serverFWrapper;
    }

    /**
     * @return StatFServer
     */
    public static function getStatF()
    {
        return self::$statF;
    }

    /**
     * @param StatFServer $storeCache
     */
    public static function setStatF(StatFServer $storeCache)
    {
        self::$statF = $storeCache;
    }

    /**
     * @return PropertyFServer
     */
    public static function getPropertyF()
    {
        return self::$propertyF;
    }

    /**
     * @param PropertyFServer $propertyF
     */
    public static function setPropertyF(PropertyFServer $propertyF)
    {
        self::$propertyF = $propertyF;
    }

    /**
     * @return array
     */
    public static function getTarsConfig()
    {
        return self::$tarsConfig;
    }

    /**
     * @param array $tarsConfig
     */
    public static function setTarsConfig(array $tarsConfig)
    {
        self::$tarsConfig = $tarsConfig;
    }

    /**
     * @return \Swoole\Server
     */
    public static function getSwooleInstance()
    {
        return self::$swooleInstance;
    }

    /**
     * @param \Swoole\Server $swooleInstance
     */
    public static function setSwooleInstance($swooleInstance)
    {
        self::$swooleInstance = $swooleInstance;
    }
}