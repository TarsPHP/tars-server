<?php
/**
 * Created by PhpStorm.
 * User: liangchen
 * Date: 2018/3/9
 * Time: 下午3:06.
 */

namespace Tars\route;

use Tars\core\Request;
use Tars\core\Response;

interface Route
{
    // 支持替换路由引擎，支持实现自定义路由引擎（在读入配置时会有差别）
    public function dispatch(Request $request, Response $response);
}
