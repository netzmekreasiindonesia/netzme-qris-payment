! function() {
    var r, a, t, e, c = document.querySelector("#btn-download"),
        i = document.querySelector("#btn-pay"),
        n = document.querySelector("#qris-image-canvas"),
        o = document.querySelector(".popup-overlay"),
        l = document.querySelectorAll(".app-list a.app-item"),
        g = document.querySelector(".qris-netzme-logo"),
        h = document.querySelector("#btn-check-status"),
        y = document.createElement("canvas"),
        d = document.querySelector("body > .content");

    function f() {
        var t = document.querySelector(".app-list");
        if (t) {
            t.querySelectorAll("span.app-item").forEach(function(t) {
                return t.remove()
            });
            var e = t.querySelectorAll(".app-item");
            if (e && e.length) {
                var i = e[0].offsetWidth + 32,
                    n = t.offsetWidth,
                    n = Math.floor(n / i),
                    o = e.length % n ? n - e.length % n : 0;
                if (0 < o)
                    for (var r = 0; r < o; r++) {
                        var a = document.createElement("span");
                        a.classList.add("app-item"), t.appendChild(a)
                    }
            }
        }
    }

    function s() {
        o && (o.classList.remove("shown"), document.body.classList.remove("no-scroll"), d && d.classList.remove("no-scroll"))
    }

    function u(t) {
        var e, i, n = document.createElement("img"),
            o = (i = "load", ((e = n) && i ? new Promise(function(t) {
                e.addEventListener(i, t)
            }) : Promise.reject()).then(function() {
                return n
            }));
        return n.crossOrigin = "Anonymous", n.src = t, o
    }

    function w(t, e, i) {
        var n = v({
            width: e.naturalWidth,
            height: e.naturalHeight
        }, i);
        t.drawImage(e, i.x + (i.width / 2 - n.width / 2), i.y + (i.height / 2 - n.height / 2), n.width, n.height)
    }

    function v(t, e) {
        var i;
        return t && e ? (i = 1, {
            width: (i = (i = t.width > e.width ? e.width / t.width : i) * t.height > e.height ? e.height / t.height : i) * t.width,
            height: i * t.height
        }) : {}
    }

    function m(t, e, i, n, o, r) {
        void 0 === o && (o = "left"), void 0 === r && (r = null), t.font = ((n = n || {
            fontWeight: "normal",
            fontSize: 12,
            fontFamily: "Roboto, sans-serif",
            color: "#333"
        }).fontWeight || "normal") + " " + (n.fontSize || 12) + "px " + (n.fontFamily || "Roboto, sans-serif"), t.fillStyle = n.color || "#333", t.textAlign = o || "left", t.textBaseline = "middle", r ? t.fillText(e, i.x, i.y, r) : t.fillText(e, i.x, i.y)
    }

    function p(t, e, i, n) {
        t.strokeStyle = n || "#ccc", t.beginPath(), t.moveTo(e.x, e.y), t.lineTo(e.x + i, e.y), t.stroke()
    }

    function S(t, e, i, n, o) {
        switch (o) {
            case "center":
                m(t, e, {
                    x: i.x + i.width / 2,
                    y: i.y + (i.height || n.fontSize) / 2
                }, n, o, i.width);
                break;
            case "end":
            case "right":
                m(t, e, {
                    x: i.x + i.width,
                    y: i.y + (i.height || n.fontSize) / 2
                }, n, o, i.width);
                break;
            default:
                m(t, e, {
                    x: i.x,
                    y: i.y + (i.height || n.fontSize) / 2
                }, n, o, i.width)
        }
        return i.height || n.fontSize
    }
    l && l.length && l.forEach(function(t) {
        t.href && t.href !== window.location.href || (t.removeAttribute("close-popup-action"), t.style.pointerEvents = "none", t.addEventListener("click", function(t) {
            t.preventDefault()
        }))
    }), h && (l = h.getAttribute("data-invoice-status"), t = h.getAttribute("data-invoice-url"), "paid" === l && (location.href = t), h.addEventListener("click", function(t) {
        location.reload()
    })), c && c.addEventListener("click", function(t) {
        y && !a && setTimeout(function() {
            y.toBlob(function(t) {
                ! function(n, o) {
                    void 0 === o && (o = "download");
                    return new Promise(function(t) {
                        var e, i;
                        r || (a = !0, e = URL.createObjectURL(n), (r = document.createElement("a")).href = e, r.download = o || "download", r.target = "_blank", r.classList.add("btn"), r.innerText = c.innerText, c.classList.add("hidden"), c.parentElement.insertBefore(r, c), i = function() {
                            setTimeout(function() {
                                t()
                            }, 150)
                        }, window.addEventListener("beforeunload", function() {
                            URL.revokeObjectURL(e), r.removeEventListener("click", i), r.remove()
                        }), a = !1), r.click()
                    })
                }(t, e).then(function() {
                    return null
                })
            }, "image/jpeg", 1)
        }, 300)
    }), i && i.addEventListener("click", function(t) {
        o && (o.classList.add("shown"), document.body.classList.add("no-scroll"), d && d.classList.add("no-scroll"))
    }), o && (o.addEventListener("click", function(t) {
        (t.target.classList.contains("share-dialog") || t.target.classList.contains("popup-overlay")) && s()
    }), o.querySelectorAll("[close-popup-action]").forEach(function(t) {
        t.addEventListener("click", s)
    })), n && (l = document.head.querySelector('[property="og:image"]')) && (t = l.content, e = document.title.replace(/[\s#-+_%@$]/i, "") + ".jpg", u(t).then(function(m) {
        n.width = m.naturalWidth, n.height = +m.naturalHeight;
        var t = n.getContext("2d");
        t.clearRect(0, 0, n.width, n.height), t.fillStyle = "#fff", t.fillRect(0, 0, n.width, n.height), t.drawImage(m, 0, 0 * n.height), Promise.all([u("/static/images/logo_qris_border.png"), u("/static/images/img_netzme_horizontal_blue.png")]).then(function(t) {
            var e = m,
                i = t[0],
                t = t[1],
                t = (void 0 === i && (i = null), void 0 === t && (t = null), document.querySelector(".invoice-info-container > .info > .value")),
                n = document.querySelector(".header .title.merchant-name"),
                o = document.querySelector(".invoice-info-container .info-row.price > .value"),
                r = document.querySelector(".invoice-info-container .info-row.cost > .value"),
                a = document.querySelector(".qris-image-container > .label"),
                c = document.head.querySelector('meta[name="created-at"]'),
                l = document.head.querySelector('meta[name="merchant-location"]'),
                h = y,
                n = {
                    shopName: n.innerText,
                    location: l.content,
                    totalPrice: t.innerText,
                    price: o.innerText,
                    cost: r.innerText,
                    createdAt: c.content,
                    nmId: a.innerText
                },
                l = e,
                t = g,
                o = i,
                r = (h.width = 500, h.height = 880, h.getContext("2d")),
                c = (r.fillStyle = "#fff", r.fillRect(0, 0, h.width, h.height), "#29a7ec"),
                a = "#333",
                e = "#a5a5a5",
                i = "Roboto, sans-serif",
                d = .75 * h.width,
                f = h.width / 2 - d / 2,
                s = d,
                u = 24;
            r.fillStyle = r.createPattern(function(t, e, i) {
                void 0 === i && (i = .7);
                var n = document.createElement("canvas"),
                    o = (n.width = e.width, n.height = e.height, n.getContext("2d")),
                    e = v({
                        width: t.naturalWidth,
                        height: t.naturalHeight
                    }, {
                        width: e.width * i,
                        height: e.height * i
                    });
                return w(o, t, {
                    x: n.width / 2 - e.width / 2,
                    y: n.height / 2 - e.height / 2,
                    width: e.width,
                    height: e.height
                }), n
            }(o, {
                width: 24,
                height: 120
            }), "repeat"), r.save(), r.translate(8, 0), r.fillRect(0, 0, 24, h.height), r.restore(), r.save(), r.translate(h.width - 24 - 8, 0), r.fillRect(0, 0, 24, h.height), r.restore(), u = (u = (u = (u = (u += 20) + S(r, n.shopName, {
                x: f,
                y: 44,
                width: s
            }, {
                fontFamily: i,
                fontSize: 24,
                fontWeight: 500,
                color: a
            }, "center")) + 8) + S(r, n.location, {
                x: f,
                y: u,
                width: s
            }, {
                fontFamily: i,
                fontSize: 18,
                fontWeight: 300,
                color: e
            }, "center")) + 16, w(r, t, {
                x: h.width / 2 - d / 2,
                y: u,
                width: d,
                height: 60
            }), u += 66, w(r, l, {
                x: h.width / 2 - d / 2,
                y: u,
                width: d,
                height: d
            }), u = (u = (u = (u = (u = (u = (u += 8 + d) + S(r, n.nmId, {
                x: f,
                y: u,
                width: s
            }, {
                fontFamily: i,
                fontSize: 16,
                fontWeight: 300,
                color: e
            }, "center")) + 16) + S(r, n.totalPrice, {
                x: f,
                y: u,
                width: s
            }, {
                fontFamily: i,
                fontSize: 24,
                fontWeight: 500,
                color: c
            }, "center")) + 8) + S(r, "Pindai kode QR ini untuk melakukan pembayaran", {
                x: f,
                y: u + 5,
                width: s
            }, {
                fontFamily: i,
                fontSize: 16,
                fontWeight: 300,
                color: e
            }, "center")) + 16, p(r, {
                x: h.width / 2 - d / 2,
                y: u
            }, d, "#ccc"), u = (u = (u += 16) + S(r, "Detail Pembayaran", {
                x: h.width / 2 - d / 2,
                y: u,
                width: d
            }, {
                fontFamily: i,
                fontSize: 14,
                fontWeight: 500,
                color: c
            }, "left")) + 16, S(r, "Harga", {
                x: h.width / 2 - d / 2,
                y: u,
                width: d
            }, {
                fontFamily: i,
                fontSize: 14,
                fontWeight: 300,
                color: a
            }, "left"), u = (u += S(r, n.price, {
                x: h.width / 2 - d / 2,
                y: u,
                width: d
            }, {
                fontFamily: i,
                fontSize: 14,
                fontWeight: 300,
                color: a
            }, "right")) + 16, S(r, "Biaya layanan", {
                x: h.width / 2 - d / 2,
                y: u,
                width: d
            }, {
                fontFamily: i,
                fontSize: 14,
                fontWeight: 300,
                color: a
            }, "left"), u = (u += S(r, n.cost, {
                x: h.width / 2 - d / 2,
                y: u,
                width: d
            }, {
                fontFamily: i,
                fontSize: 14,
                fontWeight: 300,
                color: a
            }, "right")) + 16, S(r, "Dibuat", {
                x: h.width / 2 - d / 2,
                y: u,
                width: d
            }, {
                fontFamily: i,
                fontSize: 14,
                fontWeight: 300,
                color: a
            }, "left"), u = (u += S(r, n.createdAt, {
                x: h.width / 2 - d / 2,
                y: u,
                width: d
            }, {
                fontFamily: i,
                fontSize: 14,
                fontWeight: 300,
                color: a
            }, "right")) + 16, p(r, {
                x: h.width / 2 - d / 2,
                y: u
            }, d, "#ccc"), u = (u = (u = (u += 16) + S(r, "Cek aplikasi penyelenggara QRIS di :", {
                x: f,
                y: u,
                width: s
            }, {
                fontFamily: i,
                fontSize: 16,
                fontWeight: 300,
                color: e
            }, "center")) + 8) + S(r, "www.aspi-qris.id", {
                x: f,
                y: u,
                width: s
            }, {
                fontFamily: i,
                fontSize: 16,
                fontWeight: 300,
                color: c
            }, "center")
        })
    })), f(), window.addEventListener("resize", f)
}();

function downloadBase64File(base64Data, contentType, fileName) {
  const linkSource = base64Data;
  const downloadLink = document.createElement("a");
  downloadLink.href = linkSource;
  downloadLink.download = fileName;
  downloadLink.click();
}