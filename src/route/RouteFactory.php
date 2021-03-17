<?php
/**
 * Created by PhpStorm.
 * User: liangchen
 * Date: 2018/3/9
 * Time: 下午2:36.
 */

namespace Tars\route;

class RouteFactory
{
    /**
     * @param string $routeName
     * @return DefaultRoute
     */
    public static function getRoute($routeName = '')
    {
        if (class_exists($routeName)) {
            return new $routeName;
        } else {
            return new DefaultRoute();
        }
    }
    
}
