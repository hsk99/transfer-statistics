<?php

namespace app\index\controller;

class Index
{
    /**
     * 首页
     *
     * @author HSK
     * @date 2022-06-19 02:35:00
     *
     * @param \support\Request $request
     *
     * @return \support\Response
     */
    public function index(\support\Request $request)
    {
        return view('index/index');
    }

    /**
     * 获取菜单
     *
     * @author HSK
     * @date 2022-06-19 02:37:14
     *
     * @param \support\Request $request
     *
     * @return \support\Response
     */
    public function menu(\support\Request $request)
    {
        $menuList = [
            1 => [
                "id"    => 1,
                "pid"   => 0,
                "title" => "系统管理",
                "icon"  => "layui-icon layui-icon-set",
                "type"  => 0,
                "href"  => "",
            ],
            2 => [
                "id"       => 2,
                "pid"      => 1,
                "title"    => "系统设置",
                "icon"     => "layui-icon layui-icon-circle",
                "type"     => 1,
                "openType" => "_iframe",
                'href'     => "/" . request()->app . "/config/index",
            ],
            3 => [
                "id"    => 3,
                "pid"   => 0,
                "title" => "应用管理",
                "icon"  => "layui-icon layui-icon-app",
                "type"  => 0,
                "href"  => "",
            ]
        ];
        $projectList = \think\facade\Db::name('project')->column('project');
        foreach ($projectList as $key => $project) {
            $menuList[$key + 4] = [
                "id"       => $key + 4,
                "pid"      => 3,
                "title"    => $project,
                "icon"     => "layui-icon layui-icon-circle",
                "type"     => 1,
                "openType" => "_iframe",
                'href'     => "/" . request()->app . "/project/index?project=" . $project,
            ];
        }

        return json(get_tree($menuList));
    }

    /**
     * 清除缓存
     *
     * @author HSK
     * @date 2022-04-01 16:08:22
     *
     * @param \support\Request $request
     *
     * @return \support\Response
     */
    public function cache(\support\Request $request)
    {
        remove_dir(runtime_path() . '/views/');

        return api([], 200, '清除缓存成功');
    }

    /**
     * 通用上传
     *
     * @author HSK
     * @date 2022-06-19 02:45:48
     *
     * @param \support\Request $request
     *
     * @return \support\Response
     */
    public function upload(\support\Request $request)
    {
        try {
            /**
             * @var \Webman\Http\UploadFile
             */
            $file = request()->file('file');

            if ($file && $file->isValid()) {
                $path = public_path() . '/upload/' . $file->getUploadExtension() . '/' . date('Ymd') . '/' . uniqid() . '.' . $file->getUploadExtension();
                $file->move($path);

                $data = [
                    'name' => $file->getUploadName(),
                    'href' => str_replace(public_path(), '', $path),
                    'mime' => $file->getUploadMineType(),
                    'size' => byte_size(filesize($path) ?? 0),
                    'type' => 1,
                    'ext'  => $file->getUploadExtension(),
                ];
            } else {
                return api([], 400, '上传失败');
            }

            return api([
                'name' => $data['name'],
                'ext'  => $data['ext'],
                'size' => $data['size'],
                'href' => $data['href'],
                'url'  => $data['href'],
                'src'  => $data['href'],
            ], 0, '上传成功');
        } catch (\Throwable $th) {
            \Hsk99\WebmanException\RunException::report($th);
            return api([], 400, '上传失败');
        }
    }
}
