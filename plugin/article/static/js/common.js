define(['core', 'tpl', 'http://api.map.baidu.com/getscript?v=2.0&ak=ZQiFErjQB7inrGpx27M1GR5w3TxZ64k7'], function (core, tpl) {
    var modal = {};
    modal.init = function (params) {
        modal.areas = params.areas;
        modal.aid = params.aid;
        modal.shareid = params.shareid;
        modal.cando = true;
        if (modal.areas) {
            var geolocation = new BMap.Geolocation();
            geolocation.getCurrentPosition(function (r) {
                if (this.getStatus() == BMAP_STATUS_SUCCESS) {
                    var city = r.address.city;
                    var province = r.address.province;
                    var areaok = false;
                    $.each(modal.areas, function (i, area) {
                        if ($.trim(area) == city || $.trim(area) == province) {
                            areaok = true
                        }
                    });
                    if (areaok) {
                        modal.getContent(true);
                        if (modal.shareid > 0) {
                            modal.doShare()
                        }
                    } else {
                        modal.cando = false;
                        modal.getContent(false)
                    }
                } else {
                    FoxUI.toast.show("API错误，请刷新重试!")
                }
            }, {enableHighAccuracy: true})
        } else {
            //modal.getContent(true)
        }
        $("#likebtn").click(function () {
            var _this = $(this);
            var num = _this.data('num');
            var state = _this.data('state');
            if (!modal.cando) {
                return
            }
            if (state) {
                _this.find("i").removeClass("icon-likefill").removeClass("text-danger").addClass("icon-like");
                _this.data({'state': 0});
                if (String(num).indexOf('+') < 0) {
                    _this.data({'num': num - 1}).find("span").text(num - 1)
                }
            } else {
                _this.find("i").addClass("icon-likefill").addClass("text-danger").removeClass("icon-like");
                _this.data({'state': 1});
                if (String(num).indexOf('+') < 0) {
                    var endnum = num + 1 > 100000 ? '100000+' : num + 1;
                    _this.data({'num': endnum}).find("span").text(endnum)
                }
            }
            core.json('article/like', {aid: modal.aid})
        })
    };
    modal.doShare = function () {
        core.json('article/share', {aid: modal.aid, shareid: modal.shareid}, false, false, true)
    };
    modal.getContent = function (state) {
        if (state) {
            $(".fui-article-content .fui-article-gps").remove();
            $(".fui-article-content .fui-article-notread").remove();
            core.json('article/getcontent', {aid: modal.aid}, function (r) {
                var con = r.result.content;
                if (r.status) {
                    $(".fui-article-content").html(modal.base64(con));
                } else {
                    $(".fui-page").hide();
                    FoxUI.message.show({
                        icon: 'icon icon-wrong',
                        content: r.result.message,
                        buttons: [{
                            text: '确定', extraClass: 'btn-default', onclick: function () {
                                WeixinJSBridge.call('closeWindow')
                            }
                        }]
                    })
                }
            })
        } else {
            $(".fui-article-content .fui-article-gps").remove();
            $(".fui-article-content .fui-article-notread").show()
        }
    };
    modal.base64 = function (str) {
        return decodeURIComponent(escape(window.atob(str)))
    };
    return modal
});