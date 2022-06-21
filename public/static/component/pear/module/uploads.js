layui.define(['jquery',], function (exports) {
    var $ = layui.jquery;
    if ($('button.upload-image').length) {
        $('button.upload-image').each(function () {
            var _this = $(this);
            _uploadImage(_this, {
                success: function (res) {
                    if (res.code == 0) {
                        layer.msg(res.msg, {
                            icon: 1
                        });
                        _this.next().val(res.data.src).next().find('img.upload-image').attr('src', res.data.src);
                    } else {
                        layer.msg(res.msg, {
                            icon: 2
                        });
                    }
                }
            });
        });
        $('div.upload-image span').click(function () {
            $(this).next().attr('src', '').parent().prev().val('');
        });
    }

    if ($('button.upload-file').length) {
        $('button.upload-file').each(function () {
            var _this = $(this);
            _uploadFiles(_this, {
                success: function (res) {
                    var echo = _this.attr('echo');
                    if (res.code == 0) {
                        layer.msg(res.msg, {
                            icon: 1
                        });
                        if (document.getElementById(echo + "-name")) document.getElementById(echo + "-name").value = res.data.name;
                        if (document.getElementById(echo + "-ext")) document.getElementById(echo + "-ext").value = res.data.ext;
                        if (document.getElementById(echo + "-size")) document.getElementById(echo + "-size").value = res.data.size;
                        if (document.getElementById(echo + "-url")) document.getElementById(echo + "-url").value = res.data.url;
                    } else {
                        layer.msg(res.msg, {
                            icon: 2
                        });
                    }
                }
            });
        });
    }

    function _uploadImage(elem, options) {
        var form, name = 'file',
            accept = 'image/*',
            url = UPLOAD_IMAGE_PATH;
        form = Math.random().toString(36).substr(2);
        var input = '<input accept="' + accept + '" name="' + name + '" type="file"/>';
        $('body').append('<form enctype="multipart/form-data" id="' + form + '" style="display: none;">' + input + '</form>');
        $('body').on('change', '#' + form + ' input', function () {
            layer.msg('上传中', { icon: 16, time: 0, shade: 0.3 });
            var _this = $(this);
            var data = new FormData();
            data.append(name, _this[0].files[0]);
            data.append('name', name);
            settings = {
                contentType: false,
                data: data,
                processData: false,
                url: url,
                async: true,
                cache: false,
                complete: function (xhr) {
                    xhr = null;
                },
                dataType: 'json',
                type: 'post',
            };
            $.extend(settings, options);
            $.ajax(settings);
            _this.remove();
            $('#' + form).append(input);
        });
        $(elem).click(function () {
            $('#' + form + ' input').click();
        });
    }

    function _uploadFiles(elem, options) {
        var form, name = 'file',
            accept = elem.attr('accept') ?? '',
            url = UPLOAD_IMAGE_PATH;
        form = Math.random().toString(36).substr(2);
        var input = '<input accept="' + accept + '" name="' + name + '" type="file"/>';
        $('body').append('<form enctype="multipart/form-data" id="' + form + '" style="display: none;">' + input + '</form>');
        $('body').on('change', '#' + form + ' input', function () {
            layer.msg('上传中', { icon: 16, time: 0, shade: 0.3 });
            var _this = $(this);
            var data = new FormData();
            data.append(name, _this[0].files[0]);
            data.append('name', name);
            settings = {
                contentType: false,
                data: data,
                processData: false,
                url: url,
                async: true,
                cache: false,
                complete: function (xhr) {
                    xhr = null;
                },
                dataType: 'json',
                type: 'post',
            };
            $.extend(settings, options);
            $.ajax(settings);
            _this.remove();
            $('#' + form).append(input);
        });
        $(elem).click(function () {
            $('#' + form + ' input').click();
        });
    }
    exports('uploads', {});
});