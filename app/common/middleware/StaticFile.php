<?php

namespace app\common\middleware;

use Webman\MiddlewareInterface;
use Webman\Http\Response;
use Webman\Http\Request;

class StaticFile implements MiddlewareInterface
{
    public function process(Request $request, callable $next): Response
    {
        $domain = parse_url($request->header('referer'))['host'] ?? '';

        // 校验防盗
        if (config('deploy', false) && (strpos($domain, 'hsk99.com.cn') === false) && (strpos($domain, 'hushuaikang.top') === false)) {
            return response('', 503);
        }

        // Access to files beginning with. Is prohibited
        if (strpos($request->path(), '/.') !== false) {
            return response('<h1>403 forbidden</h1>', 403);
        }

        /** @var Response $response */
        $response = $next($request);

        // Add cross domain HTTP header
        /*$response->withHeaders([
            'Access-Control-Allow-Origin'      => '*',
            'Access-Control-Allow-Credentials' => 'true',
        ]);*/

        $response->withHeaders([
            'Server' => 'hsk99'
        ]);

        return $response;
    }
}
