
var chooseState, endHtml = 0,
    html = "",
    domRight = document.getElementById("rightCont"),
    chooseCount = 0,
    oldSearch = "",
    chooseList = [],
    chooselist_name=[],
    baseDom = document.createElement("div"),
    baseData = [],
    searchid=0;

function group(a) {
    baseData = a, this.go = function() {

        dataFormat(baseData), document.getElementById("leftCont").innerHTML = renderLeft(baseData, baseDom), chooseParent(), renderRight(baseData),searchid= setInterval(function() {
            realSearch()
        }, 100)

    }
}

function dataFormat(a) {
    function d(a, c, d) {
        var e = "";
        return e = b + "-" + d
    }

    function e(a) {
        var f, g;
        for (f = 0; f < a.length; f++) g = a[f], b = d(c, g, f), g.group = b, g.children.length > 0 && (c++, parentData = g, e(g.children)), c--, b = b.replace(/-\d+$/g, "")
    }
    var b = "",
        c = 1;
    e(a)
}

function getPeoNum(a) {
    function c(a) {
        var d, e;
        for (d = 0; d < a.length; d++) e = a[d], e.children.length > 0 && c(e.children), 1 == e.last && b++
    }
    var b = 0;
    return c(a.children), b
}

function renderLeft(a, b) {
    var c, d, e, f, g, h, i, j, k, l, m, n;

    if (b.innerHTML = "", "[object Array]" === Object.prototype.toString.call(a))

        for (c = 0; c < a.length; c++)

                d = "",
                e = "icon__round--false",
                f = "", g = getPeoNum(a[c]),
                h = "",
                0 == a[c].last && (h = '<div class="to__number">（ ' + g + " 人）</div>"),
                oldSearch && getParent(a[c].group) && (f = "（" + getParent(a[c].group) + "）"),
                1 == a[c].active && (e = "icon__round--true", d = "checked = true"),
                    i = "to__subItem",
                    i = "to__subItem",
                    j = document.createElement("div"),
                    k = '<span class="to__dropdownList" ><i onclick="dropClick(this)"><svg t="1550632829702" class="icon" style="" viewBox="0 0 1024 1024" version="1.1" xmlns="http://www.w3.org/2000/svg" p-id="1783" xmlns:xlink="http://www.w3.org/1999/xlink" width="100%" height="100%"><defs><style type="text/css"></style></defs><path d="M959.52557 254.29773 511.674589 702.334953 63.824631 254.29773Z" p-id="1784"></path></svg></i></span>',
                1 == a[c].last && (k = ""),
                    l = "",
                    l = 1 == a[c].last ? a[c].img || "../addons/ewei_shopv2/plugin/union/template/web/manage/images/header-icon.png" : a[c].img || "../addons/ewei_shopv2/plugin/union/template/web/manage/images/icon-folder.svg",
                    m = '<span class="to__prefix"><img src="' + l + '"></img></span>',
                    j.innerHTML = '<div class="to__item " id="group_' + a[c].group + '">' + k + '<span onclick="nodeClick(this)"><input id="input' + a[c].group + '" type="checkbox"  ' + d + '" name="cName"  value="' + a[c].name + '" onclick="checkboxClick(this)" />' + m + '<div class="to__name">' + a[c].name + "</div>" + h + '<div class="to__parentName">' + f + '</div><span class="icon__round ' + e + '"></span></span></div>',
                    b.appendChild(j),
                    n = document.createElement("div"),
                    n.className = i,
                    j.appendChild(n),
                    a[c].children.length >0 && 1 != a[c].last ? (d = !1, i = "to__subItem",   renderLeft(a[c].children, n)) : (d = !1, i = "to__subItem");
    return baseDom.innerHTML
}

function chooseParent() {
    var b, c, d, e, a = document.getElementById("leftCont").querySelectorAll(".icon__round--true");
    for (b = 0; b < a.length; b++) c = a[b], d = c.parentNode.firstChild, d.checked = !0, e = d.id.replace("input", ""), chooseState(baseData, e, !0)
}

function renderRight(a) {
    domRight=document.getElementById("rightCont");
    chooseCount = 0, domRight.innerHTML = "", chooseList = [],chooselist_name=[], loopRight(a),document.getElementById("selectNum").innerHTML = chooseCount
}

function loopRight(a) {
    var b, c, d, e;
    for (b = 0; b < a.length; b++)
        c = a[b], c.children.length > 0 && 1 != c.last ? loopRight(c.children) : 1 != c.last && !oldSearch || 1 != c.active || (d = "", getParent(c.group) && (d = "（" + getParent(c.group) + "）"),
            chooseCount++, e = document.createElement("div"),
            e.className = "to__item",
            e.innerHTML = '<span class="to__prefix"><img src="../addons/ewei_shopv2/plugin/union/template/web/manage/images/header-icon.png"></img></span><span class="to__name" id="render' + c.group + '" >' + c.name + '</span><div class="to__parentName">' + d + '</div><span class="to__close" onclick="nodeCencel(this)"><i></i></span>',
            domRight.appendChild(e),
        c.value && chooseList.push(c.value),
                c.value && chooselist_name.push(c.name)
        )
}

function loopParentState(a) {
    function b(c) {
        var d, e, f, g, h, i;
        for (d = 0; d < c.length; d++)
            if (e = c[d], e.group == a) {
                for (f = 0, g = 0; g < e.children.length; g++) h = e.children[g], 1 == h.active && f++;
                e.active = f == e.children.length ? !0 : 0 == f ? !1 : !1, document.getElementById("group_" + e.group) && (i = document.getElementById("group_" + e.group), i.querySelector("input").checked = e.active, i.querySelector(".icon__round").className = "icon__round icon__round--" + e.active), loopParentState(a)
            } else b(e.children)
    }
    a = a.replace(/-\d+$/g, ""), b(baseData)
}

function getParent(a) {
    function c(d) {
        var e, f;
        for (e = 0; e < d.length; e++) f = d[e], f.group == a && (b = f.name), f.children.length > 0 && c(f.children)
    }
    if (!a) return {};
    a = a.replace(/-\d+$/g, "");
    var b = "";
    return c(baseData), b
}

function chooseState(a, b, c) {
    function f(a, b) {
        for (var c = 0; c < a.length; c++) a[c].active = b, a[c].children.length > 0 && f(a[c].children, b)
    }
    var d, e;
    for (d = 0; d < a.length; d++) e = a[d], e.group == b && (1 != c && (e.active = !e.active), e.children.length > 0 && f(e.children, e.active), loopParentState(e.group)), e.children.length > 0 && chooseState(e.children, b, c)
}

function checkboxClick(a) {
    a.checked = !a.checked
}

function nodeClick(a) {
    var d, e, f, g, h, i, b = a.querySelector("input");
    if (b.checked = !b.checked, b.value, a.lastElementChild.className = "icon__round icon__round--" + b.checked, d = a.parentNode.nextElementSibling)
        for (e = d.querySelectorAll(".to__item"), f = 0; f < e.length; f++) g = e[f], g.querySelector("input").checked = b.checked, h = g.querySelector("input").parentNode.querySelector(".icon__round"), h.className = "icon__round icon__round--" + b.checked;
    i = b.id.replace("input", ""),
        chooseState(baseData, i),
        renderRight(baseData)
}

function nodeCencel(a) {
    var d, e, f, g, h, i, b = a.parentNode,
        c = Array.prototype.indexOf.call(b.parentNode.children, b);
    for (0 == c ? chooseList.shift() : c == chooseList.length - 1 ? chooseList.pop() : chooseList.splice(c, 1), console.log("删除后的数组: "), console.log(chooseList), d = a.parentNode.querySelector(".to__name").id.replace("render", ""), chooseState(baseData, d), renderRight(baseData), e = document.getElementsByName("cName"), f = 0; f < e.length; f++)
        if (g = e[f], h = g.id.replace("input", ""), h == d) {
            g.checked = !1, i = g.parentNode.querySelector(".icon__round"), i.className = "icon__round icon__round--" + g.checked;
            break
        }
}

function updateNode(a) {
    function c(d) {
        var e, f;
        for (e = 0; e < d.length; e++) f = d[e], f.name.indexOf(a) > -1 && 1 == f.last && b.push(f), f.children.length > 0 && 1 != f.last && c(f.children)
    }
    var b = [];
    c(baseData), document.getElementById("leftCont").innerHTML = renderLeft(b, baseDom)
}

function realSearch() {
    var a = document.getElementById("searchInput");
    if(a==null){
        window.clearInterval(searchid);
    }
    oldSearch != a.value && (oldSearch = a.value, updateNode(a.value), "" == oldSearch && (document.getElementById("leftCont").innerHTML = renderLeft(baseData, baseDom), chooseParent()))
}

function dropClick(a) {
    a.className = a.className.indexOf("to__roate") > -1 ? "" : "to__roate";
    var b = a.parentNode.parentNode.nextElementSibling;
    b.className = b.className.indexOf("to__show") > -1 ? "to__subItem" : "to__subItem to__show"
}
