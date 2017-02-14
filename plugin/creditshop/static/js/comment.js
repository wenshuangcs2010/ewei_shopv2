define(['core', 'tpl'], function (core, tpl) {
    var modal = {};
    modal.init = function (params) {
        modal.params = params;
        modal.params.level = 0;
        $('.fui-stars').stars({
            'clearIcon': 'icon icon-round_close',
            'icon': 'icon icon-favor',
            'selectedIcon': 'icon icon-favorfill',
            'onSelected': function (value) {
                modal.params.level = value
            }
        });
        $('.fui-uploader').uploader({
            uploadUrl: core.getUrl('util/uploader'),
            removeUrl: core.getUrl('util/uploader/remove')
        });
        $('.goods-comment-btn').click(function () {
            var $this = $(this), selected = $(this).attr('sel') == '1';
            if (selected) {
                $this.removeAttr('sel').closest('.goods-list').next().slideUp();
                $this.find('i')[0].className = "icon icon-fold";
                return
            }
            $('.goods-comment-cell').slideUp();
            $('.goods-list').each(function () {
                $(this).find('.goods-comment-btn').removeAttr('sel');
                $(this).find('i')[0].className = "icon icon-fold"
            });
            $this.attr('sel', 1).closest('.goods-list').next().slideDown();
            $this.find('i')[0].className = "icon icon-unfold"
        });
        $('.btn-submit').click(function () {
            if ($(this).attr('stop')) {
                return
            }

            if (modal.params.iscomment == 0 && modal.params.level < 1) {
                FoxUI.toast.show('还没有评分');
                return
            }
            if ($('#comment').isEmpty()) {
                FoxUI.toast.show('说点什么吧!');
                return
            }
            var default_images = [];
            $('#images').find('li').each(function () {
                default_images.push($(this).data('filename'))
            });
            var default_comment = {
                'level': modal.params.level,
                'content': $('#comment').val(),
                'images': default_images
            };
            var comments = [];
            $('.goods-list').each(function () {
                var images = [];
                $(this).next().find('.fui-images').find('li').each(function () {
                    images.push($(this).data('filename'))
                });
                if (images.length <= 0) {
                    images = default_comment.images
                }
                var content = $(this).next().find('textarea').val();
                if ($.trim(content) == '') {
                    content = default_comment.content
                }
                var level = $(this).next().find('.fui-stars').data('value');
                if (level == '0') {
                    level = default_comment.level
                }
                comments.push({
                    'goodsid': $(this).data('goodsid'),
                    'level': level,
                    'content': content,
                    'images': images
                })
            });
            $(this).html('正在处理...').attr('stop', 1);
            core.json('creditshop/comment/submit', {'logid': modal.params.logid, 'comments': comments}, function (ret) {
                if (ret.status == 1) {
                    location.href = core.getUrl('creditshop/log/detail',{id:modal.params.logid});
                    return
                }
                $('.btn-submit').removeAttr('stop').html('提交评价');
                FoxUI.toast.show(ret.result.message)
            }, true, true)
        })
    };
    return modal
});