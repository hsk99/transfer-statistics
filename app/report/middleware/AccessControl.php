<?php

namespace app\report\middleware;

class AccessControl implements \Webman\MiddlewareInterface
{
    public function process(\Webman\Http\Request $request, callable $next): \Webman\Http\Response
    {
        $authorization = request()->header('authorization');
        if (
            empty($authorization) ||
            get_system('authorization', md5(date('Y'))) !== $authorization
        ) {
            return api([], 400, 'error');
        }

        return $next($request);
    }
}
