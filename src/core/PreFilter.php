<?php
/**
 * Created by PhpStorm.
 * User: liangchen
 * Date: 2019/7/29
 * Time: 18:52
 */

namespace Tars\core;


interface PreFilter
{
    public function doFilter(Request &$request);
}