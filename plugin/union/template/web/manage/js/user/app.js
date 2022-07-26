// +----------------------------------------------------------------------
// | Think.Admin
// +----------------------------------------------------------------------
// | 版权所有 2014~2017 广州楚才信息科技有限公司 [ http://www.cuci.cc ]
// +----------------------------------------------------------------------
// | 官方网站: http://think.ctolog.com
// +----------------------------------------------------------------------
// | 开源协议 ( https://mit-license.org )
// +----------------------------------------------------------------------
// | github开源项目：https://github.com/zoujingli/Think.Admin
// +----------------------------------------------------------------------

// 当前资源URL目录
var baseUrl = (function () {
    var scripts = document.scripts, src = scripts[scripts.length - 1].src;
    return src.substring(0, src.lastIndexOf("/") + 1);
})();

// RequireJs 配置参数
require.config({
    waitSeconds: 0,
    baseUrl: baseUrl,
    map: {'*': {css: baseUrl + '../plugs/require/require.css.js'}},
    paths: {
        // 自定义插件（源码自创建或已修改源码）
        'layui': ['../plugs/layui/layui'],
        'ueditor': ['../plugs/ueditor/ueditor'],
        'jquery': ['../plugs/jquery/jquery-2.1.4.min'],
        'jquery.gcjs': ['../plugs/jquery/jquery.gcjs'],
        'bootstrap': ['../plugs/bootstrap/bootstrap.min'],
        'jquery.basictable': ['../plugs/jquery/jquery.basictable.min'],
        'jquery.jqcandlestick': ['../plugs/jquery/jquery.jqcandlestick.min'],
        'jquery.nicescroll': ['../plugs/jquery/jquery.nicescroll'],
        'jquery-ui': ['../plugs/jquery/jquery-ui'],
        'zeroclipboard': ['../plugs/ueditor/third-party/zeroclipboard/ZeroClipboard.min'],
        'admin.plugs': ['../user/plugs'],
        'admin.listen': ['../user/listen'],
        'admin.digonew': ['../user/digonew'],
        'jquery.cookies': ['../plugs/jquery/jquery.cookie'],
        'pace': ['../plugs/jquery/pace.min'],
        'select2' : window.ROOT_URL+'/resource/components/select2/zh-CN',
        'jquery.nestable' : '../plugs/nestable/jquery.nestable',

    },
    shim: {
        'layui': {deps: ['jquery']},
        'laydate': {deps: ['jquery']},
        'jquery.basictable': {deps: ['jquery']},
        'jquery.jqcandlestick': {deps: ['jquery']},
        'jquery.nicescroll': {deps: ['jquery']},
        'jquery-ui': {deps: ['jquery']},
        'bootstrap':{deps: ['jquery']},
        'jquery.cookies': {deps: ['jquery']},
        'jquery.nestable': {deps: ['jquery']},
        'admin.plugs': {deps: ['jquery', 'layui']},
        'admin.listen': {deps: ['jquery', 'jquery.cookies', 'admin.plugs']},
        'select2': {
            deps: ['css!'+window.ROOT_URL+'/resource/components/select2/select2.min.css', './resource/components/select2/select2.min.js']
        },
    },

    // 开启debug模式，不缓存资源
    urlArgs: "ver=" + (new   Date()).getTime()
});
window.UEDITOR_HOME_URL =  baseUrl+ '../plugs/ueditor/';
console.log(window.ROOT_URL);
// UI框架初始化
require(['pace', 'jquery', 'layui', 'bootstrap', 'jquery.cookies'], function () {
    layui.config({dir: baseUrl + '../plugs/layui/'});

    layui.use(['layer','form','laydate','tree','element'], function () {

        window.layer = layui.layer;
        window.tree = layui.tree;
        window.form = layui.form;
        window.laydate = layui.laydate;
        window.element = layui.element;

        require(['admin.listen']);
    });


});
