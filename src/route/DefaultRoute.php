<?php
/**
 * Created by PhpStorm.
 * User: chengxiaoli
 * Date: 2019/7/22
 * Time: 下午7:39
 */

namespace Tars\route;

use Tars\core\Request;
use Tars\core\Response;

class DefaultRoute implements Route {

    public function dispatch(Request $request, Response $response)
    {
        $uri = $request->data['server']['request_uri'];
        $verb = $request->data['server']['request_method'];
        $list = explode('/', $uri);
    
        // 这里的大小写和autoload需要确定一个规则
        $route = [
            'class' => ucwords($list[1]) . 'Controller',
            'action' => 'action' . ucwords($list[2]),
        ];
    
        $namespaceName = $request->namespaceName;
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
