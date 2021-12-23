// 初始化layui
var layer = layui.layer,
    form = layui.form,
    element = layui.element,
    laydate = layui.laydate,
    upload = layui.upload,
    table = layui.table,
    tree = layui.tree,
    colorpicker = layui.colorpicker,
    laypage = layui.laypage,
    carousel = layui.carousel,
    util = layui.util,
    dropdown = layui.dropdown,
    flow = layui.flow;

// AJAX全局设置
$.ajaxSetup({
    type: "post",
    dataType: "json"
});

// 去掉所有input的autocomplete, 显示指定的除外 
$(function () {
    $('input:not([autocomplete]),textarea:not([autocomplete]),select:not([autocomplete])').attr('autocomplete', 'off');
});

// 点击事件
$('body').on('click', '*[admin-event]', function () {
    var othis = $(this),
        attrEvent = othis.attr('admin-event');
    admin_events[attrEvent] && admin_events[attrEvent].call(this, othis);
});

// 自定义事件
var admin_events = {
    // 通用GET请求（AJAX）
    get: function (othis) {
        var _url = othis.attr('url');

        var reg = new RegExp("[\\u4E00-\\u9FFF]+", "g");
        if (reg.test(othis.html())) {
            var title = othis.html();
        } else {
            var title = othis.attr('title');
        }

        if (_url !== undefined) {
            layer.open({
                title: title,
                shade: false,
                content: '确定操作？',
                btn: ['确定', '取消'],
                yes: function (index) {
                    var unique = new Date().getTime();

                    $.ajax({
                        type: "get",
                        url: _url,
                        dataType: "json",
                        beforeSend: function () {
                            window['loading_' + unique] = top.layer.load(2, { shade: [0.3, '#E6E6E6'] });
                            window['loading_tips_' + unique] = top.layer.msg('操作中，请耐心等候...', {
                                icon: 16,
                                time: 100 * 10000
                            });
                        },
                        error: function () {
                            top.layer.close(window['loading_tips_' + unique]);
                            top.layer.close(window['loading_' + unique]);
                        },
                        success: function (response) {
                            top.layer.close(window['loading_tips_' + unique]);
                            if (response.code === 200) {
                                setTimeout(function () {
                                    top.layer.close(window['loading_' + unique]);
                                    window.location.reload();
                                }, 1000);
                            } else {
                                top.layer.close(window['loading_' + unique]);
                            }
                            top.layer.msg(response.msg, { icon: (response.code === 200 ? 1 : 2) });
                        }
                    });
                    layer.close(index);
                }
            });
        }
        return false;
    },
    // 通用界面弹框
    open: function (othis) {
        var _url = othis.attr('url');
        var _w = othis.attr('w');
        var _h = othis.attr('h');

        if (_w == undefined) {
            _w = '95%';
        }
        if (_h == undefined) {
            _h = '95%';
        }

        if (_url !== undefined) {
            layer.open({
                type: 2,
                title: othis.html(),
                anim: 5,
                resize: false,
                move: false,
                offset: 'auto',
                area: [_w, _h],
                content: _url,
                cancel: function () {
                    window.location.reload();
                }
            });
        }
        return false;
    },
    // URL跳转
    url: function (othis) {
        var _url = othis.attr('url');
        if (_url !== undefined) {
            window.location.href = _url;
        }
        return false;
    },
    // 通用审核操作
    audit: function (othis) {
        var _url = othis.attr('url');

        if (_url !== undefined) {
            layer.confirm('是否通过审核？', {
                btn: ['通过', '驳回']
            }, function (index) {
                post(_url, { status: 1 });
                layer.close(index);
            }, function () {
                layer.prompt({
                    formType: 2,
                    title: '请输入驳回原因',
                    value: '',
                    area: ['350px', '200px']
                }, function (value, index, elem) {
                    post(_url, { status: 0, illustrate: value });
                    layer.close(index);
                });
            });
        }
        return false;
    },
};

// AJAX表单提交
form.on('submit(*)', function (data) {
    var unique = new Date().getTime();

    $.ajax({
        type: data.form.method,
        url: data.form.action,
        data: $(data.form).serialize(),
        dataType: "json",
        beforeSend: function () {
            window['loading_' + unique] = top.layer.load(2, { shade: [0.3, '#E6E6E6'] });
            window['loading_tips_' + unique] = top.layer.msg('操作中，请耐心等候...', {
                icon: 16,
                time: 100 * 10000
            });
        },
        error: function () {
            top.layer.close(window['loading_tips_' + unique]);
            top.layer.close(window['loading_' + unique]);
        },
        success: function (response) {
            top.layer.close(window['loading_tips_' + unique]);
            if (response.code === 200) {
                setTimeout(function () {
                    top.layer.close(window['loading_' + unique]);
                    var index = parent.layer.getFrameIndex(window.name);
                    if (index == undefined) {
                        window.location.reload();
                    } else {
                        parent.layer.close(index);
                        window.parent.location.reload();
                    }
                }, 1000);
            } else {
                top.layer.close(window['loading_' + unique]);
            }
            top.layer.msg(response.msg, { icon: (response.code === 200 ? 1 : 2) });
        }
    });

    return false;
});

// GET表单提交
form.on('submit(get)', function (data) {
    window.location.href = data.form.action + "?" + $(data.form).serialize();
});

// 关闭页面
form.on('submit(cancel)', function (data) {
    var index = parent.layer.getFrameIndex(window.name);
    if (index == undefined) {
        window.location.reload();
    } else {
        parent.layer.close(index);
        window.parent.location.reload();
    }
});

// POST请求
function post(url, param, callback) {
    var unique = new Date().getTime();

    $.ajax({
        type: "post",
        url: url,
        data: param,
        dataType: "json",
        beforeSend: function () {
            window['loading_' + unique] = top.layer.load(2, { shade: [0.3, '#E6E6E6'] });
            window['loading_tips_' + unique] = top.layer.msg('操作中，请耐心等候...', {
                icon: 16,
                time: 100 * 10000
            });
        },
        error: function () {
            top.layer.close(window['loading_tips_' + unique]);
            top.layer.close(window['loading_' + unique]);
        },
        success: function (response) {
            top.layer.close(window['loading_tips_' + unique]);
            top.layer.msg(response.msg, { icon: (response.code === 200 ? 1 : 2) });

            if ('function' === typeof (callback)) {
                top.layer.close(window['loading_' + unique]);
                callback(response);
            } else {
                if (response.code === 200) {
                    setTimeout(function () {
                        top.layer.close(window['loading_' + unique]);
                        window.location.reload();
                    }, 1000);
                } else {
                    top.layer.close(window['loading_' + unique]);
                }
            }
        }
    });
}

// 消息通知
function msg(type, content, title, time) {
    if (type === undefined || content === undefined) {
        return false;
    }

    toastr.options = {
        newestOnTop: true,
        closeButton: true,
        progressBar: true,
        positionClass: 'toast-top-right',
        timeOut: time === undefined ? 0 : time,
        extendedTimeOut: 0,
        showDuration: 400,
        hideDuration: 1000,
        showEasing: 'swing',
        hideEasing: 'linear',
        showMethod: 'fadeIn',
        hideMethod: 'fadeOut'
    };

    switch (type) {
        case 'warning':
            toastr.warning(content, title);
            document.getElementById("sound").src = PACKAGE + "/toastr/toastr.wav";
            break;
        case 'success':
            toastr.success(content, title);
            document.getElementById("sound").src = PACKAGE + "/toastr/toastr.wav";
            break;
        case 'error':
            toastr.error(content, title);
            document.getElementById("sound").src = PACKAGE + "/toastr/toastr.wav";
            break;
        case 'info':
            toastr.info(content, title);
            document.getElementById("sound").src = PACKAGE + "/toastr/toastr.wav";
            break;
    }
}

// 文件链接到流下载
function fileLinkToStreamDownload(url, fileName) {
    let xhr = new XMLHttpRequest();
    xhr.open('get', url, true);
    xhr.responseType = "blob";
    xhr.onload = function () {
        if (this.status == 200) {
            var blob = this.response;
            downloadNormalFile(blob, fileName);
        }
    }
    xhr.send();
}

// 下载普通文件
function downloadNormalFile(blob, filename) {
    var eleLink = document.createElement("a");
    let href = blob;
    if (typeof blob == "string") {
        eleLink.target = '_blank';
    } else {
        href = window.URL.createObjectURL(blob);
    }
    eleLink.href = href;
    eleLink.download = filename;
    eleLink.style.display = "none";
    document.body.appendChild(eleLink);
    eleLink.click();
    document.body.removeChild(eleLink);
    if (typeof blob == "string") {
        window.URL.revokeObjectURL(href);
    }
}

// 向iframe页面指定函数推送数据
function push_iframe(method, data) {
    var iframe = document.getElementsByTagName("iframe");
    for (let index = 0; index < iframe.length; index++) {
        const othis = iframe[index];

        if (eval("othis.contentWindow." + method) != undefined) {
            eval("othis.contentWindow." + method)(data);
        }
    }
}

// 自定义表单验证
form.verify({
    phone: function (value) {
        var mobile = /^1[3|4|5|7|8]\d{9}$/,
            phone = /^0\d{2,3}-?\d{7,8}$/;
        var result = mobile.test(value) || phone.test(value);
        if (!result) {
            return '请输入正确座机号码或手机号';
        }
    },
    email: [
        /^([a-zA-Z0-9]+[_|_|\-|.]?)*[a-zA-Z0-9]+@([a-zA-Z0-9]+[_|_|.]?)*[a-zA-Z0-9]+\.[a-zA-Z]{2,3}$/,
        '请输入正确邮箱'
    ]
});

// 格式十进制
function formatDecimal(num, decimal) {
    if (num < 0) {
        return parseFloat(1).toFixed(decimal)
    }

    num = num.toString()
    let index = num.indexOf('.')
    if (index !== -1) {
        num = num.substring(0, decimal + index + 1)
    } else {
        num = num.substring(0)
    }
    return parseFloat(num).toFixed(decimal)
}