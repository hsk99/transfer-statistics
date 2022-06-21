<?php

namespace app\index\middleware;

class AccessControl implements \Webman\MiddlewareInterface
{
    public function process(\Webman\Http\Request $request, callable $next): \Webman\Http\Response
    {
        $token        = request()->cookie('RequestAuthentication', "");
        $refreshToken = request()->cookie('RequestAuthenticationRefresh', "");

        // 存在Token
        if (!empty($token)) {
            try {
                $tokenResult = \Tinywan\Jwt\JwtToken::verify(1, $token);

                $request->authData = $tokenResult['extend'];
            } catch (\Throwable $th) {
                switch ($th->getMessage()) {
                    case '获取的扩展字段不存在':
                    case '身份验证会话已过期，请重新登录！':
                    case '身份验证令牌尚未生效':
                    case '身份验证令牌无效':
                        break;
                    default:
                        \Hsk99\WebmanException\RunException::report($th);
                        break;
                }
            }
        }
        // 存在刷新Token
        else if (!empty($refreshToken)) {
            try {
                $refreshTokenResult = \Tinywan\Jwt\JwtToken::verify(2, $refreshToken);

                $request->authData = $refreshTokenResult['extend'];

                $newTokenInfo = \Tinywan\Jwt\JwtToken::generateToken($request->authData);
            } catch (\Throwable $th) {
                switch ($th->getMessage()) {
                    case '获取的扩展字段不存在':
                    case '身份验证会话已过期，请重新登录！':
                    case '身份验证令牌尚未生效':
                    case '身份验证令牌无效':
                        break;
                    default:
                        \Hsk99\WebmanException\RunException::report($th);
                        break;
                }
            }
        }

        switch (true) {
            case strtolower(request()->controller) !== "app\\" . request()->app . "\controller\login" && !isset($request->authData):
                // 不存在Token、刷新Token，跳转登录页面
                return redirect("/" . request()->app . "/login/index");
                break;
            case strtolower(request()->controller) === "app\\" . request()->app . "\controller\login" && !isset($request->authData):
                // 执行登录，跳出校验
                return $next($request);
                break;
            case strtolower(request()->controller . '::' . request()->action) === "app\\" . request()->app . "\controller\login::index" && 'GET' === request()->method() && isset($request->authData):
                // 已登录，跳出页面
                $response = redirect("/" . request()->app);
                if (isset($newTokenInfo)) {
                    $response->cookie('RequestAuthentication', $newTokenInfo['access_token'], $newTokenInfo['expires_in'], '/');
                }
                return $response;
                break;
        }

        // 校验Token所属客户端校验
        if (empty($request->authData['check']) || $request->authData['check'] !== md5(request()->header('user-agent') . '---' . request()->sessionId())) {
            return redirect("/" . request()->app . "/login/index")
                ->cookie('RequestAuthentication', '', 1, '/')
                ->cookie('RequestAuthenticationRefresh', '', 1, '/');
        }

        $response = $next($request);
        if (isset($newTokenInfo)) {
            $response->cookie('RequestAuthentication', $newTokenInfo['access_token'], $newTokenInfo['expires_in'], '/');
        }
        return $response;
    }
}
