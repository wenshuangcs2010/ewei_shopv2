define(['core', 'tpl'], function (core, tpl) {
    var modal = {
        cate: '',
        cateset: '',
        children: '',
        grandchildren: new Array,
        recommend_children: new Array,
        recommend_grandchildren: new Array,
    };
    modal.init = function (cate, cateset) {
        modal.cate = cate;
        modal.cateset = cateset;
        core.tpl('#container', 'tpl_shop_category_list', modal.cate, false);
        $("#tab nav").click(function (e) {

            $(this).siblings().removeClass("on");
            $(this).addClass("on");
            var result = {};
            if ($(this).data("src")){
               $('#advimg').attr('src',$(this).data('src'));
            }
            
            $("#advurl").prop("href", $(this).data("href"));
            if ($(this).data("cate") == 'recommend') {
                modal.recommend_children = new Array;
                modal.recommend_grandchildren = new Array;
                modal.get_recommend();
                result.recommend = 1
            } else {
                modal.get_category($(this).data("cate"));
                result.recommend = 0
            }
            result.children = modal.children;
            result.recommend_children = modal.recommend_children;
            result.grandchildren = modal.grandchildren;
            result.recommend_grandchildren = modal.recommend_grandchildren;
            core.tpl('#container', 'tpl_shop_category_list', result, false);
            modal.click_grandchildren()
        });
        $("#tab nav[data-cate=recommend]").click()
    };
    modal.get_category = function (cateid) {
        modal.children = modal.cate.children[cateid];
        if (typeof(modal.children) != 'undefined') {
            $.each(modal.children, function (index, item) {
                modal.get_grandchildren(item.id)
            })
        }
        return modal.children
    };
    modal.get_recommend = function () {
        $.each(modal.cate.parent[0], function (pindex, pitem) {
            if (modal.cate.children[pitem.id] != undefined) {
                $.each(modal.cate.children[pitem.id], function (cindex, citem) {
                    if (citem.isrecommand == 1) {
                        modal.recommend_children.push(citem);
                    }
                    if (modal.cate.grandchildren[citem.id] != undefined) {
                        $.each(modal.cate.grandchildren[citem.id], function (gindex, gitem) {
                            if (gitem.isrecommand == 1) {
                                modal.recommend_grandchildren.push(gitem);
                            }
                        })
                    }
                })
            }
        })
    };
    modal.get_children = function (parentid) {
        if (modal.cate.children[parentid] != undefined) {
            modal.children[parentid] = modal.cate.children[parentid];
            $.each(modal.children[parentid], function (index, item) {
                modal.get_grandchildren(item.id)
            });
            return modal.children[parentid]
        }
    };
    modal.get_grandchildren = function (childrenid) {
        if (modal.cate.grandchildren[childrenid] != undefined) {
            modal.grandchildren[childrenid] = modal.cate.grandchildren[childrenid];
            return modal.grandchildren[childrenid]
        }
    };
    modal.click_grandchildren = function () {
        $(".show").click(function () {
            var result = {};
            $("#advimg").hide();
            if ($(this).data("src") != '') {
                $("#advimg").attr("src", $(this).data("src")).show()
            }
            $("#advurl").attr("href", $(this).data("href"));
            result.pid = $(this).data("pid");
            result.children = modal.get_grandchildren($(this).data("children"));
            core.tpl('#container', 'tpl_shop_category_show_list', result, false);
            modal.click_prev()
        })
    };
    modal.click_prev = function () {
        $(".fui-icon-col.prev").click(function () {
            var pid = $(this).data('prev');
            $("#tab nav[data-cate=" + pid + "]").click()
        })
    };
    return modal
});