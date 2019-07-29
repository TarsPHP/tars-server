<?php
/**
 * Created by PhpStorm.
 * User: liangchen
 * Date: 2018/3/9
 * Time: 下午3:06.
 */

namespace Tars\core;

use Tars\protocol\Protocol;
use Tars\Code;

class Event
{
    /** @var Protocol $protocol */
    protected $protocol;
    protected $basePath;
    protected $tarsConfig;

    public function setProtocol(Protocol $protocol)
    {
        $this->protocol = $protocol;
    }

    public function setBasePath($basePath)
    {
        $this->basePath = $basePath;
    }

    public function setTarsConfig($tarsConfig)
    {
        $this->tarsConfig = $tarsConfig;
    }

    public function onReceive(Request $request, Response &$response)
    {
        $impl = $request->impl;
        $paramInfos = $request->paramInfos;

        try {
            // 这里通过protocol先进行unpack
            $result = $this->protocol->route($request, $response, $this->tarsConfig);


            $sFuncName = $result['sFuncName'];
            $args = $result['args'];
            $unpackResult = $result['unpackResult'];
            if (method_exists($impl, $sFuncName)) {
                $returnVal = $impl->$sFuncName(...$args);
            } else {
                throw new \Exception(Code::TARSSERVERNOFUNCERR);
            }
            $paramInfo = $paramInfos[$sFuncName];

            $rspData = $this->protocol->packRsp($paramInfo, $unpackResult, $args, $returnVal);

            return $rspData;

        } catch (\Exception $e) {
            $unpackResult['iVersion'] = 1;
            $rspData = $this->protocol->packErrRsp($unpackResult, $e->getCode(), $e->getMessage());

            return $rspData;
        }
    }

    /**
     * @param Request $request
     * @param Response $response
     * 针对http进行处理
     */
    public function onRequest(Request $request, Response $response)
    {
        if ($request->data['server']['request_uri'] == '/monitor/monitor') {
            $response->header('Content-Type', 'application/json');
            $response->send("{'code':0}");

            return;
        }
        $namespaceName = $request->namespaceName;

        $route = $this->protocol->route($request, $response);
        if (!$route) {
            $class = $namespaceName . 'controller\IndexController';
            $fun = 'actionIndex';
        } else {
            $class = $namespaceName . 'controller\\' . $route['class'];
            $fun = $route['action'];
        }

        if ((!class_exists($class) || !method_exists(($class), ($fun)))) {
            if ($response->servType == 'http') {
                $response->status(404);
            }
            $response->send('not found');

            return;
        }
        $obj = new $class($request, $response);
        if (method_exists(($class), ('run'))) {
            $obj->run($fun);
        } else {
            $obj->$fun();
        }
    }
}
