<!DOCTYPE html>
<html lang="zh-CN">

<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="/static/component/pear/css/pear.css" />
    <script src="/static/component/layui/layui.js"></script>
    <script src="/static/component/pear/pear.js"></script>
</head>

<body class="pear-container">
    <div class="layui-card">
        <div class="layui-card-body">
            <form class="layui-form" action="">
                <div class="layui-form-item">
                    {{$index_search}}
                    <div class="layui-form-item layui-inline">
                        <button class="pear-btn pear-btn-md pear-btn-primary" lay-submit lay-filter="query">
                            <i class="layui-icon layui-icon-search"></i>
                            查询
                        </button>
                        <button type="reset" class="pear-btn pear-btn-md">
                            <i class="layui-icon layui-icon-refresh"></i>
                            重置
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    <div class="layui-card">
        <div class="layui-card-body">
            <table id="dataTable" lay-filter="dataTable"></table>
        </div>
    </div>

    <script type="text/html" id="toolbar">
        <button class="pear-btn pear-btn-primary pear-btn-md" lay-event="add">
            <i class="layui-icon layui-icon-add-1"></i>
            新增
        </button>
        <button class="pear-btn pear-btn-danger pear-btn-md" lay-event="batchRemove">
            <i class="layui-icon layui-icon-delete"></i>
            删除
        </button>
        <button class="pear-btn pear-btn-md" lay-event="recycle">
            <i class="layui-icon layui-icon-delete"></i>
            回收站
        </button>
    </script>

    {{$index_status}}

    <script type="text/html" id="options">
        <button class="pear-btn pear-btn-primary pear-btn-sm" lay-event="edit"><i class="layui-icon layui-icon-edit"></i></button>
        <button class="pear-btn pear-btn-danger pear-btn-sm" lay-event="remove"><i class="layui-icon layui-icon-delete"></i></button>
    </script>
    <script>
        layui.use(['table', 'form', 'jquery', 'common', 'laydate'], function () {
            let table = layui.table;
            let form = layui.form;
            let $ = layui.jquery;
            let common = layui.common;
            let laydate = layui.laydate;
            let MODULE_PATH = "/{:request()->app}/{{$left}}/{{$right_hump}}/";
            {{$js_search}}
            let cols = [
                [{
                    type: 'checkbox'
                }, {{$index_list}}{
                    title: '操作',
                    toolbar: '#options',
                    unresize: true,
                    align: 'center',
                    width: 180,
                }]
            ]

            table.render({
                elem: '#dataTable',
                url: MODULE_PATH + 'index',
                page: true,
                limit: 10,
                parseData: function (params) {
                    return {
                        "code": params.code,
                        "msg": params.msg,
                        "count": params.data.total,
                        "data": params.data.data
                    };
                },
                request: {
                    pageName: 'page',
                    limitName: 'limit'
                },
                response: {
                    statusCode: 200
                },
                cols: cols,
                cellMinWidth: 100,
                skin: 'line',
                toolbar: '#toolbar',
                defaultToolbar: [{
                    title: '刷新',
                    layEvent: 'refresh',
                    icon: 'layui-icon-refresh',
                }, 'filter', 'print', 'exports']
            });

        table.on('tool(dataTable)', function (obj) {
            if (obj.event === 'remove') {
                window.remove(obj);
            } else if (obj.event === 'edit') {
                window.edit(obj);
            }
        });

        table.on('toolbar(dataTable)', function (obj) {
            if (obj.event === 'add') {
                window.add();
            } else if (obj.event === 'refresh') {
                window.refresh();
            } else if (obj.event === 'batchRemove') {
                window.batchRemove(obj);
            } else if (obj.event === 'recycle') {
                window.recycle(obj);
            }
        });

        form.on('submit(query)', function (data) {
            table.reload('dataTable', {
                where: data.field,
                page: { curr: 1 }
            })
            {{$js_search}}
            return false;
        });
        {{$index_status_js}}
        //弹出窗设置 自己设置弹出百分比
        function screen() {
            if (typeof width !== 'number' || width === 0) {
                width = $(window).width() * 0.8;
            }
            if (typeof height !== 'number' || height === 0) {
                height = $(window).height() - 20;
            }
            return [width + 'px', height + 'px'];
        }

        window.add = function () {
            layer.open({
                type: 2,
                maxmin: true,
                title: '新增{{$ename}}',
                shade: 0.1,
                area: screen(),
                content: MODULE_PATH + 'add'
            });
        }

        window.edit = function (obj) {
            layer.open({
                type: 2,
                maxmin: true,
                title: '修改{{$ename}}',
                shade: 0.1,
                area: screen(),
                content: MODULE_PATH + 'edit?id=' + obj.data['id']
            });
        }

        window.recycle = function () {
            layer.open({
                type: 2,
                maxmin: true,
                title: '回收站',
                shade: 0.1,
                area: screen(),
                content: MODULE_PATH + 'recycle',
                cancel: function () {
                    table.reload('dataTable');
                }
            });
        }


        window.remove = function (obj) {
            layer.confirm('确定要删除该{{$ename}}', {
                icon: 3,
                title: '提示'
            }, function (index) {
                layer.close(index);
                let loading = layer.load();
                $.ajax({
                    url: MODULE_PATH + 'remove',
                    data: { id: obj.data['id'] },
                    dataType: 'json',
                    type: 'POST',
                    success: function (res) {
                        layer.close(loading);
                        //判断有没有权限
                        if (res && res.code == 999) {
                            layer.msg(res.msg, {
                                icon: 5,
                                time: 2000,
                            })
                            return false;
                        } else if (res.code == 200) {
                            layer.msg(res.msg, {
                                icon: 1,
                                time: 1000
                            }, function () {
                                obj.del();
                            });
                        } else {
                            layer.msg(res.msg, {
                                icon: 2,
                                time: 1000
                            });
                        }
                    }
                })
            });
        }

        window.batchRemove = function (obj) {
            let data = table.checkStatus(obj.config.id).data;
            if (data.length === 0) {
                layer.msg("未选中数据", {
                    icon: 3,
                    time: 1000
                });
                return false;
            }
            var ids = []
            var hasCheck = table.checkStatus('dataTable')
            var hasCheckData = hasCheck.data
            if (hasCheckData.length > 0) {
                $.each(hasCheckData, function (index, element) {
                    ids.push(element.id)
                })
            }
            layer.confirm('确定要删除这些{{$ename}}', {
                icon: 3,
                title: '提示'
            }, function (index) {
                layer.close(index);
                let loading = layer.load();
                $.ajax({
                    url: MODULE_PATH + 'batchRemove',
                    data: { ids: ids },
                    dataType: 'json',
                    type: 'POST',
                    success: function (res) {
                        layer.close(loading);
                        //判断有没有权限
                        if (res && res.code == 999) {
                            layer.msg(res.msg, {
                                icon: 5,
                                time: 2000,
                            })
                            return false;
                        } else if (res.code == 200) {
                            layer.msg(res.msg, {
                                icon: 1,
                                time: 1000
                            }, function () {
                                table.reload('dataTable');
                            });
                        } else {
                            layer.msg(res.msg, {
                                icon: 2,
                                time: 1000
                            });
                        }
                    }
                })
            });
        }

        window.refresh = function (param) {
            table.reload('dataTable');
        }
        })
    </script>
</body>

</html>