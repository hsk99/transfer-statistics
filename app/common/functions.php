<?php

/**
 * 处理响应，根据请求类型执行不同响应
 *
 * @author HSK
 * @date 2021-11-18 13:55:45
 *
 * @param integer $code
 * @param string $msg
 * @param array $data
 *
 * @return \Webman\Http\Response
 */
function handle_response($data = [], $code = 200, $msg = 'success')
{
    // API响应
    if (request()->expectsJson()) {
        return api($data, $code, $msg);
    }
    // 视图响应
    else {
        $controller = request()->controller;
        $controller = substr($controller, strpos($controller, 'controller\\') + 11);
        $controller = parse_name($controller);
        $action     = request()->action;
        $action     = parse_name($action);

        return view($controller . '/' . $action, $data);
    }
}

/**
 * API响应
 *
 * @author HSK
 * @date 2021-11-18 10:40:39
 *
 * @param array $data
 * @param integer $code
 * @param string $msg
 *
 * @return \Webman\Http\Response
 */
function api($data = [], $code = 200, $msg = 'success')
{
    return json([
        'code' => $code,
        'msg'  => $msg,
        'data' => $data,
    ], 320);
}

/**
 * 生成URL，带有参数
 *
 * @author HSK
 * @date 2021-11-17 16:22:55
 *
 * @param string $name
 * @param array $parameters
 *
 * @return string
 */
function url($name, $parameters = []): string
{
    $route = route($name);
    if (!$route) {
        return '';
    }

    if (!empty($parameters)) {
        return $route . '?' . http_build_query($parameters);
    }

    return $route;
}

/**
 * 字符串命名风格转换
 *
 * @author HSK
 * @date 2021-08-22 16:37:40
 *
 * @param string $name
 * @param integer $type
 * @param boolean $ucfirst
 *
 * @return string
 */
function parse_name(string $name, int $type = 0, bool $ucfirst = true): string
{
    if ($type) {
        $name = preg_replace_callback('/_([a-zA-Z])/', function ($match) {
            return strtoupper($match[1]);
        }, $name);

        return $ucfirst ? ucfirst($name) : lcfirst($name);
    }

    return strtolower(trim(preg_replace("/[A-Z]/", "_\\0", $name), "_"));
}
