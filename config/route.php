<?php

use Webman\Route;
use support\Request;
use support\Response;
use support\Log;

// 登录
Route::get('/login', [\app\controller\Login::class, 'index'])->name('login');
Route::get('/captcha', [\app\controller\Login::class, 'captcha'])->name('captcha');
Route::post('/do_login', [\app\controller\Login::class, 'doLogin'])->name('do_login');
Route::get('/logout', [\app\controller\Login::class, 'logout'])->name('logout');

// 首页
Route::get('/', [\app\controller\Index::class, 'index'])->name('/');
// 首页调用记录
Route::any('/tracing_list', [\app\controller\Index::class, 'tracingList'])->name('/tracing_list');

// 项目（应用）
Route::get('/project', [\app\controller\Project::class, 'index'])->name('/project');
// 项目（应用）调用记录
Route::any('/project_tracing_list', [\app\controller\Project::class, 'tracingList'])->name('/project_tracing_list');

// 项目（应用）调用入口
Route::get('/project_transfer', [\app\controller\Project::class, 'transfer'])->name('/project_transfer');
// 项目（应用）入口调用记录
Route::any('/project_transfer_tracing_list', [\app\controller\Project::class, 'transferTracingList'])->name('/project_transfer_tracing_list');

// 项目（应用）调用IP
Route::get('/project_ip', [\app\controller\Project::class, 'ip'])->name('/project_ip');
// 项目（应用）IP调用记录
Route::any('/project_ip_tracing_list', [\app\controller\Project::class, 'ipTracingList'])->name('/project_ip_tracing_list');

// 项目（应用）状态码
Route::get('/project_code', [\app\controller\Project::class, 'code'])->name('/project_code');
// 项目（应用）状态码调用记录
Route::any('/project_code_tracing_list', [\app\controller\Project::class, 'codeTracingList'])->name('/project_code_tracing_list');

// 回退路由
Route::fallback(function (Request $request) {
    $time = microtime(true);

    // JSON响应
    if ($request->expectsJson()) {
        $response = new Response(404, [
            'Content-Type' => 'application/json',
            'Server'       => 'hsk99'
        ], json_encode([
            'code' => 404,
            'msg'  => '404 not found'
        ], 320));
    }
    // 视图响应
    else {
        $response = new Response(404, [
            'Server' => 'hsk99'
        ], file_get_contents(public_path() . '/404.html'));
    }

    // 响应数据
    if (
        strpos($response->rawBody(), '<!DOCTYPE html>') !== false
        || strpos($response->rawBody(), '<!doctype html>') !== false
        || strpos($response->rawBody(), '<h1>') !== false
    ) {
        $body = 'html view';
    } else {
        $body = $response->rawBody();
    }

    // 运行时长
    $runTime = microtime(true) - $time;

    // 处理请求交互信息
    $requestLog = [
        'time'            => date('Y-m-d H:i:s.', $time) . substr($time, 11),   // 请求时间（包含毫秒时间）
        'channel'         => 'request',                                         // 日志通道
        'level'           => 'DEBUG',                                           // 日志等级
        'message'         => '',                                                // 描述
        'run_time'        => $runTime ?? 0,                                     // 运行时长
        'ip'              => $request->getRealIp($safe_mode = true) ?? '',      // 请求客户端IP
        'url'             => $request->path() ?? '',                            // 请求URL
        'method'          => $request->method() ?? '',                          // 请求方法
        'request_param'   => $request->all() ?? [],                             // 请求参数
        'request_header'  => $request->header() ?? [],                          // 请求头
        'cookie'          => $request->cookie() ?? [],                          // 请求cookie
        'session'         => $request->session()->all() ?? [],                  // 请求session
        'response_code'   => $response->getStatusCode() ?? 404,                 // 响应码
        'response_header' => $response->getHeaders() ?? [],                     // 响应头
        'response_body'   => $body ?? [],                                       // 响应数据
    ];

    // 记录日志
    Log::channel('request')->debug('', $requestLog);

    return $response;
});

// 关闭默认路由
Route::disableDefaultRoute();
