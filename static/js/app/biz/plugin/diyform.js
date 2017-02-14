define(['core', 'tpl'], function (core, tpl) {

    var modal = {};
    modal.getData = function(element){

        var container = $(element);
        var cells = container.find('.fui-cell');
        var data = {};
        var stop =false;

        cells.each(function(){

            var $this = $(this);
            var itemid =  $this.data('itemid') ,
                field ='#' + itemid ,
                field2 ='#' + itemid + '_2',
                must = $this.data('must')=='1',
                type=$this.data('type'),
                name = $this.data('name'),
                name2 = $this.data('name2'),
                tp_is_default = $this.data('tp_is_default'),
                key = $this.data('key');

            if(must){

                //0  文本框  1  多行文本
                if( type==0 || type==1 ){
                    if($(field).isEmpty()){
                        FoxUI.toast.show('请填写' + name);
                        stop = true;
                        return false;
                    }
                    //手机号
                    if(type==0 && tp_is_default==3){
                        if(!$(field).isMobile()){
                            FoxUI.toast.show('请填写' + name);
                            stop = true;
                            return false;
                        }
                    }
                }
                //2 单选
                if(type==2){
                    if($(field).isEmpty()){
                        FoxUI.toast.show('请选择' + name);
                        stop = true;
                        return false;
                    }
                }
                //3 多选
                if(type==3){
                    var j = 0;
                    var checkeds = $(":checkbox[name^='" + itemid +"']:checked",$this).length;
                    if(checkeds<=0){
                        FoxUI.toast.show('请选择' + name);
                        stop = true;
                        return false;
                    }
                }
                //5图片
                if(type==5){
                    if( $(field + '_images').find('li').length<=0 ){
                        FoxUI.toast.show('请选择' + name);
                        stop = true;
                        return false;
                    }
                }
                //6 身份证件
                if(type==6){
                    if($(field).isEmpty() || !$(field).isIDCard()){
                        FoxUI.toast.show('请填写正确的' + name);
                        stop = true;
                        return false;
                    }
                }
                //7 日期
                if(type==7){
                    if($(field).isEmpty()){
                        FoxUI.toast.show('请选择' + name);
                        stop = true;
                        return false;
                    }
                }
                //8 日期范围
                if(type==8){
                    if($(field+'_0').isEmpty() || $(field+'_1').isEmpty()){
                        FoxUI.toast.show('请选择' + name);
                        stop = true;
                        return false;
                    }
                }
                //城市
                if(type==9){
                    if($(field).isEmpty()){
                        FoxUI.toast.show('请选择' + name);
                        stop = true;
                        return false;
                    }
                }
                //确认文本
                if(type==10){
                    if($(field).isEmpty()){
                        FoxUI.toast.show('请填写' + name);
                        stop = true;
                        return false;
                    }

                    if($(field2).isEmpty()){
                        FoxUI.toast.show('请填写' + name2);
                        stop = true;
                        return false;
                    }

                    if ($(field).val() != $(field2).val()) {
                        FoxUI.toast.show(name + '与' + name2 + '不一致');
                        stop = true;
                        return false;
                    }
                }

            }
            //手机号
            if(type==0 && tp_is_default==3){
                if(!$(field).isEmpty() && !$(field).isMobile()){
                    FoxUI.toast.show('请填写' + name);
                    stop = true;
                    return false;
                }
            }

            //身份证
            if(type==6){
                if(!$(field).isEmpty() && !$(field).isIDCard()){
                    FoxUI.toast.show('请填写正确的' + name);
                    stop = true;
                    return false;
                }
            }

            //拼接数据
            if(type==3){ //多选
                data[key] = [];
                $("input[name^='" + itemid +"']:checked").each(function(){

                    data[key].push( $(this).val() );
                });
            }else if(type==5){ //图片
                data[key] = [];
                $(field + '_images').find('li').each(function(){
                    data[key].push( $(this).data('filename') );
                });
            }else if(type==8){ //日期范围
                data[key +'_0'] = $(field+'_0').val();
                data[key +'_1'] = $(field+'_1').val();
            } else if(type==9){
                var citys = $(field).val().split(' ');
                var province = citys.length>=1?citys[0]:'';
                var city =  citys.length>=2?citys[1]:'';
                data[key] =[ province,city ];
            } else if(type==10){
                var name1 = $(field).val();
                var name2 = $(field2).val();
                data[key] =[ name1,name2 ];
            }
            else {
                data[key]  = $(field).val();
            }

        });
        if(stop){
            return false;
        }
        return data;
    };
    return modal;
});