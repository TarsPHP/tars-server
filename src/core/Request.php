<?php
/**
 * Created by PhpStorm.
 * User: yuanyizhi
 * Date: 15/8/12
 * Time: 上午11:04.
 */

namespace Tars\core;

class Request
{
    public $reqBuf;
    public $servType;
    public $data;
    public $server = array();

    public $paramInfos;
    public $impl;
    public $namespaceName; //标识当前服务的namespacePrefix
}
