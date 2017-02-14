    define(['core', 'tpl'], function (core, tpl, op) {
        var modal = {
            params: {}
        };
        modal.init = function () {
                  $(".btn-search").click(function(){
                      if($('#verifycode').isEmpty()){
                          FoxUI.toast.show('请填写消费码或自提码');
                          return;
                      }
                      core.json('verify/page/search',{verifycode: $('#verifycode').val() },function(ret){
                            if(ret.status==0){
                                FoxUI.toast.show( ret.result.message);
                                return;
                            }
                            $.router.load( core.getUrl('verify/detail',{id: ret.result.orderid,single:1}),true);
                      },true,true);
                  })
        };
        modal.verify = function(btn){
        };
        return modal;
    });