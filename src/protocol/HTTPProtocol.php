<?php
/**
 * Created by PhpStorm.
 * User: liangchen
 * Date: 2018/5/7
 * Time: 下午4:05.
 */

namespace Tars\protocol;

use Tars\route\Route;

class HTTPProtocol implements Protocol
{
    public $route;
    
    // 决定是否要提供一个口子出来,让用户自定义启动服务之前的初始化的动作
    // 这里需要对参数进行规定
    
    public function setRoute(Route $route)
    {
        $this->route = $route;
    }

    public function packRsp($paramInfo, $unpackResult, $args, $returnVal)
    {
    }

    public function packErrRsp($unpackResult, $code, $msg)
    {
    }

    public function route(\Tars\core\Request $request, \Tars\core\Response $response, $tarsConfig = [])  //默认为
    {
        $this->route->dispatch($request, $response);
    }

    public function parseAnnotation($docblock)
    {
    }
}
