<?php

namespace app\controller;

use support\Cache;

/**
 * 登录
 *
 * @author HSK
 * @date 2021-12-16 14:07:26
 */
class Login
{

    /**
     * 登录界面
     *
     * @author HSK
     * @date 2021-12-16 14:07:32
     *
     * @param \support\Request $request
     *
     * @return \support\Response
     */
    public function index(\support\Request $request)
    {
        // 获取背景图
        try {
            // 验证是否存在缓存且缓存日期是当天，则重新获取背景图
            if (!Cache::has('background') || (Cache::has('background') && Cache::get('background')['time'] !== date('Ymd'))) {
                $client = new \GuzzleHttp\Client(['verify' => false]);
                $response = $client->request('GET', 'https://cn.bing.com/HPImageArchive.aspx?format=js&idx=0&n=1&mkt=zh-CN');

                if ($response->getStatusCode() === 200) {
                    $image = json_decode($response->getBody(), true)['images'][0];

                    $background = 'https://cn.bing.com' . $image['url'];

                    // 缓存背景图地址
                    Cache::set('background', [
                        'time'  => date('Ymd'),
                        'image' => $background,
                    ]);
                }
            } else {
                $background = Cache::get('background')['image'];
            }
        } catch (\Throwable $th) {
            $background = public_path() . '/static/images/bg.jpg';
        }

        return view('login/index', ['background' => $background]);
    }

    /**
     * 执行登录
     *
     * @author HSK
     * @date 2021-12-16 14:07:53
     *
     * @param \support\Request $request
     *
     * @return \support\Response
     */
    public function doLogin(\support\Request $request)
    {
        $data = $request->post();

        if (md5($data['captcha']) !== $request->session()->get('captcha')) {
            return api([], 400, '输入的验证码不正确');
        }

        if (
            $data['username'] !== config('app.admin_name')
            || $data['password'] !== config('app.admin_password')
        ) {
            return api([], 400, '用户名或密码错误');
        }

        $request->session()->delete('captcha');
        $request->session()->set('isLogin', true);

        return api(['url' => url('/')], 200, '登录成功');
    }

    /**
     * 退出登录
     *
     * @author HSK
     * @date 2021-12-16 14:08:09
     *
     * @param \support\Request $request
     *
     * @return \support\Response
     */
    public function logout(\support\Request $request)
    {
        $request->session()->delete('isLogin');

        return api(['url' => url('/')], 200, '退出成功');
    }

    /**
     * 输出验证码图像
     *
     * @author HSK
     * @date 2021-12-16 14:08:26
     *
     * @param \support\Request $request
     *
     * @return \support\Response
     */
    public function captcha(\support\Request $request)
    {
        $fontSize = 30;
        $bg       = [243, 251, 254];
        $x        = random_int(10, 30);
        $y        = random_int(1, 9);
        $bag      = "{$x} + {$y} = ";

        $request->session()->set('captcha', md5($x + $y));

        // 图片宽(px)
        $imageW = 5 * $fontSize * 1.5 + 5 * $fontSize / 2;

        // 图片高(px)
        $imageH = $fontSize * 2.5;

        // 建立一幅 $imageW x $imageH 的图像
        $im = imagecreate($imageW, $imageH);

        // 设置背景
        imagecolorallocate($im, $bg[0], $bg[1], $bg[2]);

        // 验证码字体随机颜色
        $color = imagecolorallocate($im, mt_rand(1, 150), mt_rand(1, 150), mt_rand(1, 150));

        // 验证码使用随机字体
        $font = public_path() . '/static/font/captcha/' . random_int(1, 6) . '.ttf';

        // 绘杂点
        $codeSet = '2345678abcdefhijkmnpqrstuvwxyz';
        for ($i = 0; $i < 10; $i++) {
            //杂点颜色
            $noiseColor = imagecolorallocate($im, mt_rand(150, 225), mt_rand(150, 225), mt_rand(150, 225));
            for ($j = 0; $j < 5; $j++) {
                // 绘杂点
                imagestring($im, 5, mt_rand(-10, $imageW), mt_rand(-10, $imageH), $codeSet[mt_rand(0, 29)], $noiseColor);
            }
        }

        // 绘验证码
        $text = str_split($bag);
        foreach ($text as $index => $char) {
            $x     = $fontSize * ($index + 1) * mt_rand(1.2, 1.6) * 1;
            $y     = $fontSize + mt_rand(10, 20);
            $angle = 0;

            imagettftext($im, $fontSize, $angle, $x, $y, $color, $font, $char);
        }

        // 输出图像
        ob_start();
        imagepng($im);
        $content = ob_get_clean();
        imagedestroy($im);

        return response($content, 200, ['Content-Type' => 'image/png']);
    }
}
