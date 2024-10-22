window.Modernizr = function (a, b, c) {
    function d(a) {
        r.cssText = a
    }

    function e(a, b) {
        return typeof a === b
    }

    function f(a, b) {
        return !!~("" + a).indexOf(b)
    }

    function g(a, b) {
        for (var d in a) {
            var e = a[d];
            if (!f(e, "-") && r[e] !== c)
                return "pfx" == b ? e : !0
        }
        return !1
    }

    function h(a, b, d) {
        for (var f in a) {
            var g = b[a[f]];
            if (g !== c)
                return d === !1 ? a[f] : e(g, "function") ? g.bind(d || b) : g
        }
        return !1
    }

    function i(a, b, c) {
        var d = a.charAt(0).toUpperCase() + a.slice(1),
                f = (a + " " + w.join(d + " ") + d).split(" ");
        return e(b, "string") || e(b, "undefined") ? g(f, b) : (f = (a + " " + x.join(d + " ") + d).split(" "), h(f, b, c))
    }

    function j() {
        n.input = function (c) {
            for (var d = 0, e = c.length; e > d; d++)
                A[c[d]] = !!(c[d] in s);
            return A.list && (A.list = !(!b.createElement("datalist") || !a.HTMLDataListElement)), A
        }("autocomplete autofocus list placeholder max min multiple pattern required step".split(" ")), n.inputtypes = function (a) {
            for (var d, e, f, g = 0, h = a.length; h > g; g++)
                s.setAttribute("type", e = a[g]), d = "text" !== s.type, d && (s.value = t, s.style.cssText = "position:absolute;visibility:hidden;", /^range$/.test(e) && s.style.WebkitAppearance !== c ? (o.appendChild(s), f = b.defaultView, d = f.getComputedStyle && "textfield" !== f.getComputedStyle(s, null).WebkitAppearance && 0 !== s.offsetHeight, o.removeChild(s)) : /^(search|tel)$/.test(e) || (d = /^(url|email)$/.test(e) ? s.checkValidity && s.checkValidity() === !1 : s.value != t)), z[a[g]] = !!d;
            return z
        }("search tel url email datetime date month week time datetime-local number range color".split(" "))
    }
    var k, l, m = "2.7.1",
            n = {},
            o = b.documentElement,
            p = "modernizr",
            q = b.createElement(p),
            r = q.style,
            s = b.createElement("input"),
            t = ":)",
            u = ({}.toString, " -webkit- -moz- -o- -ms- ".split(" ")),
            v = "Webkit Moz O ms",
            w = v.split(" "),
            x = v.toLowerCase().split(" "),
            y = {},
            z = {},
            A = {},
            B = [],
            C = B.slice,
            D = {}.hasOwnProperty;
    l = e(D, "undefined") || e(D.call, "undefined") ? function (a, b) {
        return b in a && e(a.constructor.prototype[b], "undefined")
    } : function (a, b) {
        return D.call(a, b)
    }, Function.prototype.bind || (Function.prototype.bind = function (a) {
        var b = this;
        if ("function" != typeof b)
            throw new TypeError;
        var c = C.call(arguments, 1),
                d = function () {
                    if (this instanceof d) {
                        var e = function () {};
                        e.prototype = b.prototype;
                        var f = new e,
                                g = b.apply(f, c.concat(C.call(arguments)));
                        return Object(g) === g ? g : f
                    }
                    return b.apply(a, c.concat(C.call(arguments)))
                };
        return d
    }), y.canvas = function () {
        var a = b.createElement("canvas");
        return !(!a.getContext || !a.getContext("2d"))
    }, y.geolocation = function () {
        return "geolocation" in navigator
    }, y.video = function () {
        var a = b.createElement("video"),
                c = !1;
        try {
            (c = !!a.canPlayType) && (c = new Boolean(c), c.ogg = a.canPlayType('video/ogg; codecs="theora"').replace(/^no$/, ""), c.h264 = a.canPlayType('video/mp4; codecs="avc1.42E01E"').replace(/^no$/, ""), c.webm = a.canPlayType('video/webm; codecs="vp8, vorbis"').replace(/^no$/, ""))
        } catch (d) {
        }
        return c
    }, y.audio = function () {
        var a = b.createElement("audio"),
                c = !1;
        try {
            (c = !!a.canPlayType) && (c = new Boolean(c), c.ogg = a.canPlayType('audio/ogg; codecs="vorbis"').replace(/^no$/, ""), c.mp3 = a.canPlayType("audio/mpeg;").replace(/^no$/, ""), c.wav = a.canPlayType('audio/wav; codecs="1"').replace(/^no$/, ""), c.m4a = (a.canPlayType("audio/x-m4a;") || a.canPlayType("audio/aac;")).replace(/^no$/, ""))
        } catch (d) {
        }
        return c
    };
    for (var E in y)
        l(y, E) && (k = E.toLowerCase(), n[k] = y[E](), B.push((n[k] ? "" : "no-") + k));
    return n.input || j(), n.addTest = function (a, b) {
        if ("object" == typeof a)
            for (var d in a)
                l(a, d) && n.addTest(d, a[d]);
        else {
            if (a = a.toLowerCase(), n[a] !== c)
                return n;
            b = "function" == typeof b ? b() : b, "undefined" != typeof enableClasses && enableClasses && (o.className += " " + (b ? "" : "no-") + a), n[a] = b
        }
        return n
    }, d(""), q = s = null,
            function (a, b) {
                function c(a, b) {
                    var c = a.createElement("p"),
                            d = a.getElementsByTagName("head")[0] || a.documentElement;
                    return c.innerHTML = "x<style>" + b + "</style>", d.insertBefore(c.lastChild, d.firstChild)
                }

                function d() {
                    var a = s.elements;
                    return "string" == typeof a ? a.split(" ") : a
                }

                function e(a) {
                    var b = r[a[p]];
                    return b || (b = {}, q++, a[p] = q, r[q] = b), b
                }

                function f(a, c, d) {
                    if (c || (c = b), k)
                        return c.createElement(a);
                    d || (d = e(c));
                    var f;
                    return f = d.cache[a] ? d.cache[a].cloneNode() : o.test(a) ? (d.cache[a] = d.createElem(a)).cloneNode() : d.createElem(a), !f.canHaveChildren || n.test(a) || f.tagUrn ? f : d.frag.appendChild(f)
                }

                function g(a, c) {
                    if (a || (a = b), k)
                        return a.createDocumentFragment();
                    c = c || e(a);
                    for (var f = c.frag.cloneNode(), g = 0, h = d(), i = h.length; i > g; g++)
                        f.createElement(h[g]);
                    return f
                }

                function h(a, b) {
                    b.cache || (b.cache = {}, b.createElem = a.createElement, b.createFrag = a.createDocumentFragment, b.frag = b.createFrag()), a.createElement = function (c) {
                        return s.shivMethods ? f(c, a, b) : b.createElem(c)
                    }, a.createDocumentFragment = Function("h,f", "return function(){var n=f.cloneNode(),c=n.createElement;h.shivMethods&&(" + d().join().replace(/[\w\-]+/g, function (a) {
                        return b.createElem(a), b.frag.createElement(a), 'c("' + a + '")'
                    }) + ");return n}")(s, b.frag)
                }

                function i(a) {
                    a || (a = b);
                    var d = e(a);
                    return !s.shivCSS || j || d.hasCSS || (d.hasCSS = !!c(a, "article,aside,dialog,figcaption,figure,footer,header,hgroup,main,nav,section{display:block}mark{background:#FF0;color:#000}template{display:none}")), k || h(a, d), a
                }
                var j, k, l = "3.7.0",
                        m = a.html5 || {},
                        n = /^<|^(?:button|map|select|textarea|object|iframe|option|optgroup)$/i,
                        o = /^(?:a|b|code|div|fieldset|h1|h2|h3|h4|h5|h6|i|label|li|ol|p|q|span|strong|style|table|tbody|td|th|tr|ul)$/i,
                        p = "_html5shiv",
                        q = 0,
                        r = {};
                !function () {
                    try {
                        var a = b.createElement("a");
                        a.innerHTML = "<xyz></xyz>", j = "hidden" in a, k = 1 == a.childNodes.length || function () {
                            b.createElement("a");
                            var a = b.createDocumentFragment();
                            return "undefined" == typeof a.cloneNode || "undefined" == typeof a.createDocumentFragment || "undefined" == typeof a.createElement
                        }()
                    } catch (c) {
                        j = !0, k = !0
                    }
                }();
                var s = {
                    elements: m.elements || "abbr article aside audio bdi canvas data datalist details dialog figcaption figure footer header hgroup main mark meter nav output progress section summary template time video",
                    version: l,
                    shivCSS: m.shivCSS !== !1,
                    supportsUnknownElements: k,
                    shivMethods: m.shivMethods !== !1,
                    type: "default",
                    shivDocument: i,
                    createElement: f,
                    createDocumentFragment: g
                };
                a.html5 = s, i(b)
            }(this, b), n._version = m, n._prefixes = u, n._domPrefixes = x, n._cssomPrefixes = w, n.testProp = function (a) {
        return g([a])
    }, n.testAllProps = i, n.prefixed = function (a, b, c) {
        return b ? i(a, b, c) : i(a, "pfx")
    }, n
}(this, this.document);