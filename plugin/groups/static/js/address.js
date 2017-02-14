define(['core', 'tpl'], function (core, tpl) {
    var modal = {};
    modal.initList = function () {
        if (typeof(window.editAddressData) !== 'undefined') {
            var item = $(".address-item[data-addressid='" + window.editAddressData.id + "']");
            if (item.length <= '0') {
                var first = $(".address-item");
                if (first.length > '0') {
                    var html = tpl('tpl_address_item', {address: window.editAddressData});
                    $(first).first().before(html)
                } else {
                    window.editAddressData.isdefault = 1;
                    var html = tpl('tpl_address_item', {address: window.editAddressData});
                    $('.content-empty').hide();
                    $('.fui-content').html(html)
                }
            } else {
                var address = window.editAddressData;
                item.find('.realname').html(address.realname);
                item.find('.mobile').html(address.mobile);
                item.find('.address').html(address.areas.replace(/ /ig, '') + ' ' + address.address)
            }
            delete window.editAddressData
        }
        $(document).on('click', '[data-toggle=delete]', function () {
            var item = $(this).closest('.address-item');
            var id = item.data('addressid');
            FoxUI.confirm('删除后无法恢复, 确认要删除吗 ?', function () {
                core.json('groups/address/delete', {id: id}, function (ret) {
                    if (ret.status == 1) {
                        if (ret.result.defaultid) {
                            $("[data-addressid='" + ret.result.defaultid + "']").find(':radio').prop('checked', true)
                        }
                        item.remove();
                        setTimeout(function () {
                            if ($(".address-item").length <= 0) {
                                $('.content-empty').show()
                            }
                        }, 100);
                        return
                    }
                    FoxUI.toast.show(ret.result.message)
                }, true, true)
            })
        });
        $(document).on('click', '[data-toggle=setdefault]', function () {
            var item = $(this).closest('.address-item');
            var id = item.data('addressid');
            core.json('groups/address/setdefault', {id: id}, function (ret) {
                if (ret.status == 1) {
                    $('.fui-content').prepend(item);
                    FoxUI.toast.show("设置默认地址成功");
                    return
                }
                FoxUI.toast.show(ret.result.message)
            }, true, true)
        })
    };
    modal.initPost = function (params) {
        require(['foxui.picker'], function () {
            $('#areas').cityPicker({'title': '请选择所在城市'})
        });
        $(document).on('click', '#btn-address', function () {
            WeixinJSBridge.invoke('editAddress', params.shareAddress, function (res) {
                if (res.err_msg == 'edit_address:ok') {
                    $("#realname").val(res.userName);
                    $('#mobile').val(res.telNumber);
                    $('#address').val(res.addressDetailInfo);
                    $('#address').val(res.addressDetailInfo);
                    $('#areas').val(res.proviceFirstStageName + " " + res.addressCitySecondStageName + " " + res.addressCountiesThirdStageName)
                } else {
                    console.log(res.err_msg)
                }
            })
        });
        $(document).on('click', '#btn-submit', function () {
            if ($(this).attr('submit')) {
                return
            }
            if ($('#realname').isEmpty()) {
                FoxUI.toast.show("请填写收件人");
                return
            }
            if (!$('#mobile').isMobile()) {
                FoxUI.toast.show("请填写正确手机号码");
                return
            }
            if ($('#areas').isEmpty()) {
                FoxUI.toast.show("请填写所在地区");
                return
            }
            if ($('#address').isEmpty()) {
                FoxUI.toast.show("请填写详细地址");
                return
            }
            $('#btn-submit').html('正在处理...').attr('submit', 1);
            window.editAddressData = {
                realname: $('#realname').val(),
                mobile: $('#mobile').val(),
                address: $('#address').val(),
                areas: $('#areas').val()
            };
            core.json('groups/address/submit', {
                id: $('#addressid').val(),
                addressdata: window.editAddressData
            }, function (json) {
                $('#btn-submit').html('保存地址').removeAttr('submit');
                window.editAddressData.id = json.result.addressid;
                if (json.status == 1) {
                    FoxUI.toast.show('保存成功!');
                    history.back()
                } else {
                    FoxUI.toast.show(json.result.message)
                }
            }, true, true)
        })
    };
    modal.initSelector = function () {
        if (typeof(window.editAddressData) !== 'undefined') {
            var address = window.editAddressData;
            var item = $(".address-item[data-addressid='" + address.id + "']", $('#page-address-selector'));
            if (item.length > 0) {
                item.find('.realname').html(address.realname);
                item.find('.mobile').html(address.mobile);
                item.find('.address').html(address.areas.replace(/ /ig, '') + ' ' + address.address)
            } else {
                var html = tpl('tpl_address_item', {address: window.editAddressData});
                $('.fui-list-group').prepend(html)
            }
            delete window.editAddressData
        }
        var selectedAddressID = false;
        if (typeof(window.selectedAddressData) !== 'undefined') {
            selectedAddressID = window.selectedAddressData.id;
            delete window.selectedAddressData
        } else if (typeof(window.orderSelectedAddressID) !== 'undefined') {
            selectedAddressID = window.orderSelectedAddressID
        }
        if (selectedAddressID) {
            $(".address-item[data-addressid='" + selectedAddressID + "'] .fui-radio", $('#page-address-selector')).prop('checked', true)
        }
        $('.address-item .fui-list-media,.address-item .fui-list-inner', $('#page-address-selector')).click(function () {
            var $this = $(this).closest('.address-item');
            window.selectedAddressData = {
                'realname': $this.find('.realname').html(),
                'address': $this.find('.address').html(),
                'mobile': $this.find('.mobile').html(),
                'id': $this.data('addressid')
            };
            history.back();
        })
    };
    return modal;
});