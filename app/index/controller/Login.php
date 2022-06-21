<?php

namespace app\index\controller;

class Login
{
    /**
     * 登录
     *
     * @author HSK
     * @date 2022-06-19 02:31:34
     *
     * @param \support\Request $request
     *
     * @return \support\Response
     */
    public function index(\support\Request $request)
    {
        if (request()->isAjax()) {
            try {
                $data = request()->post();

                if (empty($data['username']) || empty($data['password'])) {
                    return api([], 400, '参数错误');
                }

                if (1 == get_system('login_captcha') && md5($data['captcha']) !== session()->get('captcha')) {
                    return api([], 400, '输入的验证码不正确');
                }

                if (
                    get_system('username') !== $data['username'] ||
                    get_system('password') !== $data['password']
                ) {
                    return api([], 400, '用户名或密码错误');
                }

                session()->delete('captcha');

                $token = \Tinywan\Jwt\JwtToken::generateToken([
                    'id'    => 1,
                    'check' => md5(request()->header('user-agent') . '---' . request()->sessionId()),
                ]);

                return api([], 200, '登录成功')
                    ->cookie('RequestAuthentication', $token['access_token'], $token['expires_in'], '/')
                    ->cookie('RequestAuthenticationRefresh', $token['refresh_token'], isset($data['remember']) ? 86400 * 7 : 0, '/');
            } catch (\Throwable $th) {
                \Hsk99\WebmanException\RunException::report($th);
                return api([], 400, '登录失败，请稍候再试');
            }
        }

        return view('login/index');
    }

    /**
     * 退出登录
     *
     * @author HSK
     * @date 2022-04-01 14:06:02
     *
     * @param \support\Request $request
     *
     * @return \support\Response
     */
    public function logout(\support\Request $request)
    {
        return api([], 200, '退出成功')
            ->cookie('RequestAuthentication', '', 1, '/')
            ->cookie('RequestAuthenticationRefresh', '', 1, '/');
    }

    /**
     * 验证码
     *
     * @author HSK
     * @date 2022-03-24 17:07:09
     *
     * @param \support\Request $request
     *
     * @return \support\Response
     */
    public function verify(\support\Request $request)
    {
        $fontSize = 30;
        $bg       = [243, 251, 254];
        $x        = random_int(10, 30);
        $y        = random_int(1, 9);
        $bag      = "{$x} + {$y} = ";

        session()->set('captcha', md5($x + $y));

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
