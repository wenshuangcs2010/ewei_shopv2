define(['jquery'], function ($) {
    var modal={};
    $(".btwen_text").val("题目");
    $(".btwen_text_dx").val("单选题目");
    $(".btwen_text_duox").val("多选题目");
    $(".btwen_text_tk").val("填空题目");

    $(".leftbtwen_text").val("例子：CCTV1，CCTV2，CCTV3");
    $(".xxk_title li").click(function(){
        var xxkjs = $(this).index();
        $(this).addClass("on").siblings().removeClass("on");
        $(".xxk_conn").children(".xxk_xzqh_box").eq(xxkjs).show().siblings().hide();

    });
    $(document).on('mouseenter', ".movie_box", function() {//绑定鼠标进入事件
        var	html_cz = "<div class='kzqy_czbut'><a href='javascript:void(0)' class='sy'>上移</a><a href='javascript:void(0)'  class='xy'>下移</a><a href='javascript:void(0)'  class='bianji'>编辑</a><a href='javascript:void(0)' class='del' >删除</a></div>"
        $(this).css({"border":"1px solid #0099ff"});
        $(this).children(".wjdc_list").after(html_cz);
    });
    $(document).on('mouseleave', ".movie_box", function() {//绑定鼠标划出事件
        $(this).css({"border":"1px solid #fff"});
        $(this).children(".kzqy_czbut").remove();
    });


    //下移
    $(document).on("click", ".xy", function() {
   // $(".xy").live("click", function() {
        //文字的长度
        var leng = $(".yd_box").children(".movie_box").length;
        var dqgs = $(this).parent(".kzqy_czbut").parent(".movie_box").index();

        if(dqgs < leng-1){
            var czxx = $(this).parent(".kzqy_czbut").parent(".movie_box");
            var xyghtml = czxx.next().html();
            var syghtml = czxx.html();
            czxx.next().html(syghtml);
            czxx.html(xyghtml);
            //序号
            czxx.children(".wjdc_list").find(".nmb").text(dqgs+1);
            czxx.next().children(".wjdc_list").find(".nmb").text(dqgs+2);
        }else{
            alert("到底了");
        }
    });
    //上移
    $(document).on("click", ".sy", function() {
    //$(".sy").live("click", function() {
        //文字的长度
        var leng = $(".yd_box").children(".movie_box").length;
        var dqgs = $(this).parent(".kzqy_czbut").parent(".movie_box").index();
        if(dqgs > 0){
            var czxx = $(this).parent(".kzqy_czbut").parent(".movie_box");
            var xyghtml = czxx.prev().html();
            var syghtml = czxx.html();
            czxx.prev().html(syghtml);
            czxx.html(xyghtml);
            //序号
            czxx.children(".wjdc_list").find(".nmb").text(dqgs+1);
            czxx.prev().children(".wjdc_list").find(".nmb").text(dqgs);

        }else{
            alert("到头了");
        }
    });
    //删除
    $(document).on("click", ".del", function() {
    //$(".del").live("click", function() {
        var czxx = $(this).parent(".kzqy_czbut").parent(".movie_box");
        var zgtitle_gs = czxx.parent(".yd_box").find(".movie_box").length;
        var xh_num = 0;
        //重新编号
        czxx.parent(".yd_box").find(".movie_box").each(function() {
            $(".yd_box").children(".movie_box").eq(xh_num).find(".nmb").text(xh_num);
            xh_num++;
            //alert(xh_num);
        });
        czxx.remove();
    });
    $(document).on("click", ".bianji", function() {
    //编辑
    //$(".bianji").live("click", function() {
        //编辑的时候禁止其他操作
        $(this).siblings().hide();
        //$(this).parent(".kzqy_czbut").parent(".movie_box").unbind("hover");
        var dxtm = $(".dxuan").html();
        var duoxtm = $(".duoxuan").html();
        var tktm = $(".tktm").html();
        var jztm = $(".jztm").html();
        //接受编辑内容的容器
        var dx_rq = $(this).parent(".kzqy_czbut").parent(".movie_box").find(".dx_box");
        var title = dx_rq.attr("data-t");
        //alert(title);
        //题目选项的个数
        var timlrxm = $(this).parent(".kzqy_czbut").parent(".movie_box").children(".wjdc_list").children("li").length;



        //单选题目
        if(title==0){

            dx_rq.show().html(dxtm).find(".swcbj_but").html("完成编辑").attr('data-action',"edit");
            //模具题目选项的个数
            var bjxm_length = dx_rq.find(".title_itram").children(".kzjxx_iteam").length;
            var dxtxx_html = dx_rq.find(".title_itram").children(".kzjxx_iteam").html();
            //添加选项题目
            for (var i_tmxx = bjxm_length; i_tmxx < timlrxm-1 ; i_tmxx++) {
                dx_rq.find(".title_itram").append("<div class='kzjxx_iteam'>"+dxtxx_html+"</div>");
            }
            //赋值文本框
            //题目标题
            var texte_bt_val = $(this).parent(".kzqy_czbut").parent(".movie_box").find(".wjdc_list").children("li").eq(0).find(".tm_btitlt").children(".btwenzi").text();
            dx_rq.find(".btwen_text").val(texte_bt_val);




            //遍历题目项目的文字
            var  bjjs=0;
            $(this).parent(".kzqy_czbut").parent(".movie_box").find(".wjdc_list").children("li").each(function() {
                //可选框框
                var ktksfcz = $(this).find("input").hasClass("wenb_input");
                if(ktksfcz){
                    var jsxz_kk = $(this).index();
                    dx_rq.find(".title_itram").children(".kzjxx_iteam").eq(jsxz_kk-1).find("label").remove();
                }
                //题目选项
                var texte_val = $(this).find("span").text();
                dx_rq.find(".title_itram").children(".kzjxx_iteam").eq(bjjs-1).find(".input_wenbk").val(texte_val);
                bjjs++

            });
        }
        //多选题目
        if(title==1){
            dx_rq.show().html(duoxtm).find(".swcbj_but").html("完成编辑").attr('data-action',"edit");;
            //模具题目选项的个数
            var bjxm_length = dx_rq.find(".title_itram").children(".kzjxx_iteam").length;
            var dxtxx_html = dx_rq.find(".title_itram").children(".kzjxx_iteam").html();
            //添加选项题目
            for (var i_tmxx = bjxm_length; i_tmxx < timlrxm-1 ; i_tmxx++) {
                dx_rq.find(".title_itram").append("<div class='kzjxx_iteam'>"+dxtxx_html+"</div>");
                //alert(i_tmxx);
            }
            //赋值文本框
            //题目标题
            var texte_bt_val = $(this).parent(".kzqy_czbut").parent(".movie_box").find(".wjdc_list").children("li").eq(0).find(".tm_btitlt").children(".btwenzi").text();
            dx_rq.find(".btwen_text").val(texte_bt_val);

            //遍历题目项目的文字
            var  bjjs=0;
            $(this).parent(".kzqy_czbut").parent(".movie_box").find(".wjdc_list").children("li").each(function() {
                //可选框框
                var ktksfcz = $(this).find("input").hasClass("wenb_input");
                if(ktksfcz){
                    var jsxz_kk = $(this).index();
                    dx_rq.find(".title_itram").children(".kzjxx_iteam").eq(jsxz_kk-1).find("label").remove();
                }
                //题目选项
                var texte_val = $(this).find("span").text();
                dx_rq.find(".title_itram").children(".kzjxx_iteam").eq(bjjs-1).find(".input_wenbk").val(texte_val);
                bjjs++

            });
        }
        //填空题目
        if(title==2){
            dx_rq.show().html(tktm).find(".swcbj_but").html("完成编辑").attr('data-action',"edit");;
            //赋值文本框
            //题目标题
            var texte_bt_val = $(this).parent(".kzqy_czbut").parent(".movie_box").find(".wjdc_list").children("li").eq(0).find(".tm_btitlt").children(".btwenzi").text();
            dx_rq.find(".btwen_text").val(texte_bt_val);

        }
        //矩阵题目
        if(title==3){
            dx_rq.show().html(jztm).find(".swcbj_but").html("完成编辑").attr('data-action',"edit");;

        }
    });
    $(document).on("click", ".zjxx", function() {
    //增加选项
    //$(".zjxx").live("click", function() {
        var zjxx_html =  $(this).prev(".title_itram").children(".kzjxx_iteam").html();
        $(this).prev(".title_itram").append("<div class='kzjxx_iteam'>"+zjxx_html+"</div>");
    });

    //删除一行
    $(document).on("click",".del_xm", function() {
    //$(".del_xm").live("click", function() {
        //获取编辑题目的个数
        var zuxxs_num = $(this).parent(".kzjxx_iteam").parent(".title_itram").children(".kzjxx_iteam").length;
        if(zuxxs_num > 1){
            $(this).parent(".kzjxx_iteam").remove();
        }else{
            alert("手下留情");
        }
    });
    //取消编辑
    $(document).on("click",".dx_box .qxbj_but", function() {
    //$(".dx_box .qxbj_but").live("click", function() {
        $(this).parent(".bjqxwc_box").parent(".dx_box").empty().hide();
        $(".movie_box").css({"border":"1px solid #fff"});
        $(".kzqy_czbut").remove();
        //
    });

    //完成编辑（编辑）
    $(document).on("click",".swcbj_but", function() {
        var action=$(this).data("action");


            if(action=="edit"){
                var jcxxxx = $(this).parent(".bjqxwc_box").parent(".dx_box");
                //编辑题目选项的个数
                var bjtm_xm_length = jcxxxx.find(".title_itram").children(".kzjxx_iteam").length;
                var xmtit_length = jcxxxx.parent(".movie_box").children(".wjdc_list").children("li").length-1;
                //添加选项题目
                //添加选项
                if(bjtm_xm_length > xmtit_length){
                    var fzll = jcxxxx.parent(".movie_box").children(".wjdc_list").children("li").eq(1).html();
                    for(var toljs_add = 0 ; toljs_add < bjtm_xm_length - xmtit_length ; toljs_add++){
                        jcxxxx.parent(".movie_box").children(".wjdc_list").append("<li>"+fzll+"</li>")
                    }
                }
                //删除选项
                if(bjtm_xm_length < xmtit_length) {
                    for(var toljs = xmtit_length ; toljs > bjtm_xm_length ; toljs--){
                        jcxxxx.parent(".movie_box").children(".wjdc_list").children("li").eq(toljs).remove();
                    }
                }
                //赋值文本框
                //题目标题
                var texte_bt_val_bj = jcxxxx.find(".btwen_text").val();
                jcxxxx.parent(".movie_box").children(".wjdc_list").children("li").eq(0).find(".tm_btitlt").children(".btwenzi").text(texte_bt_val_bj);
                //遍历题目项目的文字
                var  bjjs_bj=0;
                jcxxxx.children(".title_itram").children(".kzjxx_iteam").each(function() {
                    //题目选项
                    var texte_val_bj = $(this).find(".input_wenbk").val();
                    jcxxxx.parent(".movie_box").children(".wjdc_list").children("li").eq(bjjs_bj+1).find("span").text(texte_val_bj);
                    bjjs_bj++
                    //可填空
                    var kxtk_yf = $(this).find(".fxk").is(':checked');
                    if(kxtk_yf){
                        //第几个被勾选
                        var jsxz = $(this).index();
                        //alert(jsxz);
                        jcxxxx.parent(".movie_box").children(".wjdc_list").children("li").eq(jsxz+1).find("span").after("<input name='' type='text' class='wenb_input'>");

                    }
                });
                //清除
                $(this).parent(".bjqxwc_box").parent(".dx_box").empty().hide();
            }else{//添加一个新题目的时候
                var  datatype=$(".xxk_title").find(".on").data("type");

                var jcxxxx = $(this).parent(".bjqxwc_box").parent();
                $(".yd_box").append("<div class=\"movie_box\" style=\"border: 1px solid rgb(255, 255, 255);\"><ul class=\"wjdc_list\"></ul><div class=\"dx_box\" data-t=\""+datatype+"\"></div></div>");
                var box_append=$(".yd_box").children(".movie_box:last-child");
                var index=$(".yd_box").children(".movie_box").size();
                var bjtm_xm_length = jcxxxx.find(".title_itram").children(".kzjxx_iteam").length;
                var xmtit_length = box_append.children(".wjdc_list").children("li").length-1;
                var texte_bt_val_bj = jcxxxx.find(".btwen_text").val();
                var fzll = modal.createHtml(datatype,index,texte_bt_val_bj);
                box_append.children(".wjdc_list").append("<li>"+fzll+"</li>");
                if(datatype==2){
                    box_append.children(".wjdc_list").append(modal.createliHtml(datatype,index,0,0));
                }
                if(datatype==3){
                    // console.log(jcxxxx.children(".title_itram").children(".kzjxx_iteam").size());
                    // console.log(jcxxxx.children("table").find(".leftbtwen_text").val());
                    // console.log(jcxxxx.children("table").find(".title_itram").children(".kzjxx_iteam").size());


                    box_append.children(".wjdc_list").append(modal.createliHtml(datatype,index,jcxxxx,0));
                }
                jcxxxx.children(".title_itram").children(".kzjxx_iteam").each(function(index_li,item) {
                    //题目选项
                    var texte_val_bj = $(this).find(".input_wenbk").val();
                    var html=modal.createliHtml(datatype,index,index_li,texte_val_bj);
                    box_append.children(".wjdc_list").append(html);
                    //可填空
                    var kxtk_yf = $(this).find(".fxk").is(':checked');
                    if(kxtk_yf){
                        //第几个被勾选
                        var jsxz = $(this).index();
                        //alert(jsxz);
                        box_append.children(".wjdc_list").children("li").eq(jsxz+1).find("span").after("<input name='' type='text' class='wenb_input'>");
                    }
                });
            }
    });
    modal.createHtml=function(datatype,index,text){
        var html="";
        switch (datatype) {
            case 0:
                var append='<span class="tip_wz">【单选】</span>';
                html="<div class=\"tm_btitlt\"><i class=\"nmb\">"+index+"</i>. <i class=\"btwenzi\">"+text+"</i>"+append+"</div>"
                break;
            case 1:
                var append='<span class="tip_wz">【多选】</span>';
                html="<div class=\"tm_btitlt\"><i class=\"nmb\">"+index+"</i>. <i class=\"btwenzi\">"+text+"</i>"+append+"</div>";
                break;
            case 2:
                var append="<span class=\"tip_wz\">【填空】</span>";
                html="<div class=\"tm_btitlt\"><i class=\"nmb\">"+index+"</i>. <i class=\"btwenzi\">"+text+"</i>"+append+"</div>";
                break;
            case 3 :
                html='<h4 class="title_wjht"><i class="nmb">'+index+'</i>.'+text+'</h4>';
                break;
        }
        return html;
    }
    modal.createliHtml=function(datatype,index,index_li,text){
        var html="";
        switch (datatype) {
            case 0:
                html='<li><label><input name="quest_'+index+'_'+datatype+'_'+index_li+'" type="radio" value=""> <span>'+text+'</span></label> </li>';
                break;
            case 1:
                html='<li><label><input name="quest_'+index+'_'+datatype+'all[]" type="checkbox" value=""><span>'+text+'</span></label></li>';
                break;
            case 2:
                html='<li><label><textarea name="text_'+index+'_'+datatype+'" cols="" rows="" class="input_wenbk btwen_text btwen_text_dx"></textarea></label></li>';
                break;
            case  3:
                html='<table width="100%" border="0" cellspacing="0" cellpadding="0" class="tswjdc_table">';
                html+='<tbody>';
                html+='<tr>';
                html+='<td class="lefttd_qk">&nbsp;</td>';
                var firsttr=index_li.find(".leftbtwen_text").val();
                var select=index_li.children("table").find(".ritwenz_xx").children(".xzqk").data("select");
                var size=index_li.children("table").find(".title_itram").children(".kzjxx_iteam").size()
                index_li.children("table").find(".title_itram").children(".kzjxx_iteam").each(function(i,v) {
                    //题目选项
                    var texte_val_bj = $(this).find(".input_wenbk").val();
                    html+='<td>'+texte_val_bj+'</td>';
                });
                html+='</tr>';
                var arr=firsttr.split(',');
                console.log(arr);
                $(arr).each(function(index_e,v){
                     html+='<tr class="os_bjqk">';
                     html+='<td class="lefttd_qk">'+v+'</td>';
                        for(var i=0;i<size;i++){
                            html+='<td><input name="radion_'+index_e+'_red" type="radio" value=""></td>';
                        }
                     html+='</tr>';
                })
                break;
        }
        return html;
    }


})