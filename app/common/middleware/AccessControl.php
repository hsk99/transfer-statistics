<?php

namespace app\common\middleware;

use Webman\MiddlewareInterface;
use Webman\Http\Response;
use Webman\Http\Request;

/**
 * 访问控制中间件
 *
 * @author HSK
 * @date 2021-12-16 14:41:46
 */
class AccessControl implements MiddlewareInterface
{
    public function process(Request $request, callable $next): Response
    {
        $session = $request->session();

        // 执行登录跳过验证
        if (strtolower($request->controller) === 'app\controller\login') {
            // 已登录，跳出登录
            if ($session->has('isLogin') && strtolower($request->action) === 'index') {
                return redirect('/');
            }

            return $next($request);
        }

        // 验证是否登录
        if (!$session->has('isLogin')) {
            return redirect('/login');
        }

        return $next($request);
    }
}
