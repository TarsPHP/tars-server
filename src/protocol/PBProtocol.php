<?php
/**
 * Created by PhpStorm.
 * User: liangchen
 * Date: 2018/3/4
 * Time: 下午4:53.
 */

namespace Tars\protocol;

use Tars\core\Request;
use Tars\Code;
use Tars\core\Response;
use Tars\route\Route;

class PBProtocol implements Protocol
{
    protected $response = null;
    
    public function setRoute(Route $route)
    {
    
    }
    
    public function route(Request $request, Response $response, $tarsConfig = [])
    {
        $response->header('content-type', 'application/grpc');
        $response->header('trailer', 'grpc-status, grpc-message');
        $this->response = $response;

        $path = $request->data['server']['request_uri'];
        $pathArr = explode('/', $path);
        $packageName = $pathArr[1];
        $sFuncName = $pathArr[2];

        $packageNameArr = explode($packageName, '.');
        $objName = end($packageNameArr);

        $paramInfos = $request->paramInfos;
        // 发生了注释异常,说明生成的注释有问题
        if (!isset($paramInfos[$sFuncName])) {
            throw new \Exception(Code::TARSSERVERUNKNOWNERR);
        }
        $paramInfo = $paramInfos[$sFuncName];

        // 需要一个函数,专门把参数,转换为args

        $args0 = new $paramInfo['inParams'][0]['type'];
        $args1 = new $paramInfo['outParams'][0]['type'];

        $args0 = self::deserializeMessage($args0, $request->reqBuf);

        return [
            'args' => [$args0, $args1],
            'unpackResult' => $request->reqBuf,
            'sFuncName' => $sFuncName,
        ];
    }
    /**
     * @param $unpackResult
     * @param $code
     * @param $msg
     *
     * @return mixed
     */
    public function packErrRsp($unpackResult, $code, $msg)
    {
        $this->response->resource->trailer("grpc-status", $code);
        $this->response->resource->trailer("grpc-message", $msg);
        return '';
    }

    public function packRsp($paramInfo, $unpackResult, $args, $returnVal)
    {
        $this->response->resource->trailer("grpc-status", "0");
        $this->response->resource->trailer("grpc-message", '');

        $returnObj = $args[1];
        return self::serializeMessage($returnObj);
    }

    public function parseAnnotation($docblock)
    {
        $docblock = trim($docblock, '/** ');
        $lines = explode('*', $docblock);
        $validLines = [];
        foreach ($lines as $line) {
            $line = trim($line);
            if (!empty($line)) {
                $validLines[] = $line;
            }
        }
        // 对解析出来的annotation进行处理
        $index = 0;
        $inParams = [];
        $outParams = [];

        foreach ($validLines as $validLine) {
            // 说明是参数类型
            if (strstr($validLine, '@param')) {
                ++$index;
                // 说明是输出参数
                if (strstr($validLine, '=out=')) {
                    $parts = explode(' ', $validLine);

                    $outParams[] = [
                        'type' => $parts[1],
                        'name' => trim($parts[2], '$'),
                        'tag' => $index,
                    ];
                } // 输入参数
                else {
                    $parts = explode(' ', $validLine);

                    $inParams[] = [
                        'type' => $parts[1],
                        'name' => trim($parts[2], '$'),
                        'tag' => $index,
                    ];
                }
            }
        }

        return [
            'inParams' => $inParams,
            'outParams' => $outParams,
            'return' => [],
        ];
    }

    protected static function serializeMessage($data)
    {
        $data = $data->serializeToString();
        return pack('CN', 0, strlen($data)) . $data;
    }

    protected static function deserializeMessage($className, $value)
    {
        if (empty($value)) {
            return null;
        }

        $value = substr($value, 5);
        if (!is_object($className)) {
            $obj = new $className();
        } else {
            $obj = $className;
        }

        $obj->mergeFromString($value);

        return $obj;
    }

//    protected static function parseToResultArray($response, $deserialize): array
//    {
//        if (!$response) {
//            return ['No response', GRPC_ERROR_NO_RESPONSE, $response];
//        } else if ($response->statusCode !== 200) {
//            return ['Http status Error', $response->errCode ?: $response->statusCode, $response];
//        } else {
//            $grpc_status = (int)($response->headers['grpc-status'] ?? 0);
//            if ($grpc_status !== 0) {
//                return [$response->headers['grpc-message'] ?? 'Unknown error', $grpc_status, $response];
//            }
//            $data = $response->data;
//            $reply = self::deserializeMessage($deserialize, $data);
//            $status = (int)($response->headers['grpc-status'] ?? 0 ?: 0);
//            return [$reply, $status, $response];
//        }
//    }
}
