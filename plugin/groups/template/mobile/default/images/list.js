    /*
     * 人人商城V2
     * 
     * @author ewei 狸小狐 QQ:22185157 
     */
    define(['core', 'tpl', 'biz/order/op'], function (core, tpl, op) {

        var modal = {
            page: 1,
            status: ''

        }
        modal.init = function () {

            op.init();
            modal.getList();


                    $('.fui-content').infinite({
                        onLoading: function () {
                            modal.page++;
                            modal.getList();
                        }
                    });
            //切换状态
            FoxUI.tab({
                container: $('#tab'),
                handlers: {
                    tab: function () {
                        modal.changeTab('');
                    }
                    , tab0: function () {
                        modal.changeTab(0);
                    }
                    , tab1: function () {
                        modal.changeTab(1);
                    }
                    , tab2: function () {
                        modal.changeTab(2);
                    }
                    , tab3: function () {
                        modal.changeTab(3);
                    }
                    , tab4: function () {
                        modal.changeTab(4);
                    }
                }
            });
        }
        modal.changeTab = function (status) {
            $('.fui-content').infinite('init');
            $('.content-empty').hide(), $('.content-loading').show(), $('.container').html('');
            modal.page = 1, modal.status = status, modal.getList();

        }
        modal.loading = function () {
            modal.page++;
        }
        modal.getList = function () {
            core.json('order/get_list', {page: modal.page, status: modal.status}, function (ret) {
                $('.content-loading').hide();
                var result = ret.result;
                if(result.total<=0){
                       $('.content-empty').show();
                       $('.fui-content').infinite('stop');
                } else{
                      $('.content-empty').hide();
                      $('.fui-content').infinite('init');
                      if(result.list.length<=0){
                          $('.fui-content').infinite('stop');
                      }
                }
                core.tpl('.container', 'tpl_list', result, modal.page > 1);
            });
        }
        return modal;
    });