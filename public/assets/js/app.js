/*! CoreApp v1.3.2 | Copyright by Softnio. */
var max_tkn_msg = "undefined" == typeof max_token_msg ? "You can purchase the maximum token amount per contribution." : max_token_msg,
    min_tkn_msg = "undefined" == typeof min_token_msg ? "Enter the minimum token amount and select currency!" : min_token_msg,
    msg_is_wrong = "undefined" == typeof msg_wrong ? "Something is Wrong!" : msg_wrong,
    msg_confirm = "undefined" == typeof msg_sure ? "Are you sure?" : msg_sure,
    msg_del_log = "undefined" == typeof msg_delete_log ? "Once Delete, You will not get back this log in future!" : msg_delete_log,
    msg_order_cancel = "undefined" == typeof msg_cancel_order ? "Do you really cancel your order?" : msg_cancel_order,
    msg_process_unable = "undefined" == typeof msg_unable_process ? "Unable process request!" : msg_unable_process,
    isNormat = "undefined" != typeof num_fmt && num_fmt;
function trim_number(a) {
    if (0 != a - Math.floor(a)) {
        for (var b, c = a.split("."), d = c[0], e = "", f = c[1].split(""), g = !0, h = f.length - 1; 0 <= h; h--) (b = f[h]), "0" == b ? !0 == g && (b = "") : (g = !1), (e = b + e);
        return "" == e ? $.number(d, decimals.max, ".", "") : d + "." + e;
    }
    return $.number(a, 0, ".", "");
}
function token_pay(a) {
    var b = $(a).val() ? $(a).val() : base_currency;
    return b.toLowerCase();
}
function token_alert(a, b, c = "") {
    var b = "undefined" == typeof b ? "" : b;
    return "icon" === c
        ? void a.find(".note-icon").html('<i class="fas fa-' + b + '"></i>')
        : "token" === c
        ? void a.find(".min-token").text(b)
        : "amount" === c
        ? void a.find(".min-amount").text(b)
        : "text" === c
        ? (a.find(".note-text-alert").html(b), void a.find(".note-text-alert").addClass("text-danger"))
        : void a.html(trim_number(b));
}
function token_bonus(a, b = "total") {
    var c,
        d = a ? parseFloat(a) : 0,
        e = 0,
        f = b ? b : "total",
        g = 0,
        h = base_bonus ? parseFloat(base_bonus) : 0,
        i = amount_bonus ? amount_bonus : { 1: 0 };
    for (c in i) (c = parseInt(c)), (g = d >= c ? parseFloat(i[c]) : g);
    var j = (d * h) / 100,
        k = (d * g) / 100;
    return (e = j + k), ("base" === f || "amount" === f) && (e = "base" === f ? j : k), (e = isNaN(e) || "undefined" == typeof e ? 0 : trim_number($.number(e, 0, ".", ""))), e;
}
function token_calc(a) {
    var b = $(a),
        c = b.parents(".token-purchase"),
        d = c.find(".final-pay"),
        // e = c.find(".pay-currency"),
        f = c.find(".tokens-bonuses"),
        g = c.find(".tokens-bonuses-sale"),
        h = c.find(".tokens-bonuses-amount"),
        // i = c.find(".tokens-total"),
        j = c.find(".pay-method:checked")
	if (!j.length) j = c.find(".pay-method")
	var k = c.find(".token-note"),
        l = $(".payment-btn"),
        m = $("#data_amount"),
        n = $("#data_currency"),
        o = $(".modal-payment"),
        p = o.find(".final-pay"),
        q = o.find(".pay-currency"),
        // r = o.find(".token-bonuses"),
        // s = o.find(".token-total"),
        // t = o.find("input#pay_currency"),
        u = o.find(".gateway-name"),
        // v = o.find("input#token_amount"),
        // w = isNaN(parseFloat(b.val())) ? 0 : parseFloat(b.val()),
        x = token_pay(j),
        // y = token_price[x],
        z = 1,
        A = token_price.base,
        // B = minimum_token * y,
        C = 0,
        D = 0,
        E = 0,
        F = 0;
		
    // if (b.is(".token-number")) {
    //     (z = w), (A = trim_number($.number(w * y, decimals.max, ".", "")));
    //     var G = isNormat ? $.number(parseFloat(A), decimals.max) : parseFloat(A);
    //     $(".pay-amount").val(parseFloat(A)), $(".pay-amount-u").text(G);
    // }
    // if (b.is(".pay-amount")) {
    //     (A = w), (z = trim_number($.number(w / y, decimals.min, ".", "")));
    //     var H = isNormat ? $.number(parseFloat(z), decimals.max) : parseFloat(z);
    //     $(".token-number").val(parseFloat(z)), $(".token-number-u").text(H);
    // }
    0 === z
        ? (token_alert(k, "info-circle", "icon"), l.addClass("disabled").removeAttr("data-amount"))
        : z >= minimum_token && z <= maximum_token
        ? (token_alert(k, "check-circle text-success", "icon"), token_alert(k, A * minimum_token, "amount"), token_alert(k, "", "text"), l.removeClass("disabled"))
        : z < minimum_token
        ? (token_alert(k, "times-circle text-danger", "icon"), l.addClass("disabled").removeAttr("data-amount"))
        : z > maximum_token && (token_alert(k, max_tkn_msg, "text"), l.addClass("disabled").removeAttr("data-amount")),
        (C = parseFloat(token_bonus(z, "base"))),
        (D = parseFloat(token_bonus(z, "amount"))),
        (E = parseFloat(token_bonus(z, "total"))),
        (F = parseFloat(z) + E),
        (A = isNaN(A) ? 0 : A),
        (F = isNaN(F) ? 0 : F);
    var I = trim_number($.number(F, decimals.min, ".", ""));
    // token_alert(k, trim_number($.number(B, decimals.max, ".", ",")), "amount"), e.text(x);
    var J = isNormat ? $.number(E) : E,
        K = isNormat ? $.number(C) : C,
        L = isNormat ? $.number(D) : D,
        M = isNormat ? $.number(I) : I;
    f.text(J),
        // g.text(K),
        h.text(L),
        d.text(trim_number($.number(A, decimals.max, ".", ","))),
        // i.text(M),
        p.text(trim_number($.number(A, decimals.max, ".", ","))),
        q.text(x),
        // r.text(J),
        // s.text(M),
        // t.val(x),
        // v.val(z),
        ("btc" == x || "ltc" == x || "eth" == x) && u.text('"Coingate"'),
        ("usd" == x || "eur" == x || "gbp" == x) && u.text('"Paypal"');
    var N = amount_bonus ? amount_bonus : { 1: 0 };
    for (_t in N) (_t = parseInt(_t)), z >= _t ? $(".bonus-tire-" + N[_t]).addClass("active") : $(".bonus-tire-" + N[_t]).removeClass("active");
    m.val(z), n.val(x), address_btn(n.val(), minimum_token, maximum_token, z);
}
function address_btn(a, b, c, d) {
    "usd" == a.toLowerCase() || "gbp" == a.toLowerCase() || "eur" == a.toLowerCase() || (+d >= +b && +d <= +c ? $("a.payment-btn.offline_payment").removeClass("disabled") : $("a.offline_payment").addClass("disabled"));
}
function purchase_form_submit(a = $(".validate-form"), b = !0, c = "ti ti-info-alt") {
    a.validate({
        errorClass: "text-danger border-danger error",
        submitHandler: function (d) {
            $(d).ajaxSubmit({
                dataType: "json",
                success: function (e) {
                    if ((btn_actived(a.find("button.save-disabled"), !1), e.trnx || show_toast(e.msg, e.message, c), "success" == e.msg || (!0 === b && $(d).clearForm()), e.link)) {
                        if (e.param) {
                            var f = e.param,
                                g = f.cta,
                                h = { tnx: f.tnx, notify: f.notify, user: f.user, system: f.system };
                            ajax_email(g, h);
                        }
                        setTimeout(function () {
                            window.location.href = e.link;
                        }, 1500);
                    }
                    if (e.modal) {
                        var i = a.parents(".modal"),
                            j = !0;
                        (is_changed = !0),
                            i.modal("hide").addClass("hold"),
                            i.find(".modal-content").html(e.modal),
                            init_inside_modal(),
                            i.on("hidden.bs.modal", function () {
                                !0 == j ? (i.modal("show"), (j = !1)) : i.modal("hide");
                            });
                    }
                    if (e.stripe) {
                        var k = Stripe(e.stripe.pk);
                        k.redirectToCheckout({ sessionId: e.stripe.session_id });
                    }
                },
                error: function (a, b, d) {
                    a.responseJSON && 0 < a.responseJSON.length ? cl(a.responseJSON.exception + "\n" + a.responseJSON.message) : cl(a);
                    var e = null == d ? "API Issue" : d;
                    show_toast("warning", msg_is_wrong + "\n(" + e + ")", c), cl("Ajax Error!!");
                },
            });
        },
    });
}
!(function (a) {
    "use strict";
    var b = a("#ajax-modal"),
        c = a("#nio-user-personal, #nio-user-settings, #nio-user-password");
    0 < c.length && ajax_form_submit(c, !1);
    var d = a("form#user_wallet_update");
    0 < d.length && ajax_form_submit(d, !1);
    var e = a("#activity_action").val();
    0 < a(".activity-delete").length &&
        a(document).on("click", ".activity-delete", function () {
            swal({ title: msg_confirm, text: msg_del_log, icon: "warning", buttons: !0, dangerMode: !0 }).then((b) => {
                if (b) {
                    var c = a(this).data("id");
                    a.post(e, { _token: csrf_token, delete_activity: c })
                        .done((b) => {
                            "success" == b.msg &&
                                ("all" == c
                                    ? a("#activity-log tr").fadeOut(1e3, function () {
                                          a(this).remove(), a("#activity-log").hide();
                                      })
                                    : a(".activity-delete")
                                          .parents("tr.activity-" + c)
                                          .fadeOut(1e3, function () {
                                              a(this).remove();
                                          }));
                        })
                        .fail(function (a, b, c) {
                            show_toast("error", msg_is_wrong + "\n" + c), _log(a, b, c);
                        });
                }
            });
        });
    var f = a(".document-type");
    0 < f.length &&
        f.on("click", function () {
            var b = a(this).data("title"),
                c = a(".doc-upload-d2"),
                d = "undefined" != typeof a(this).data("change"),
                e = a(this).data("img");
            a(".doc-type-name").text(b), a("._image").attr("src", e), 0 < c.length && d ? c.removeClass("hide") : c.addClass("hide");
        });
    var g = a("form#kyc_submit");
    0 < g.length && ajax_form_submit(g, !1);
    var h = a(".upload-zone");
    if (0 < h.length) {
        Dropzone.autoDiscover = !1;
        var i = a("input#file_uploads").val(),
            j = a('meta[name="csrf-token"]').attr("content"),
            k = ".document_one";
        if (0 < a(k).length) {
            var l = new Dropzone(k, {
                url: i,
                uploadMultiple: !1,
                maxFilesize: 5.1,
                maxFiles: 1,
                addRemoveLinks: !0,
                acceptedFiles: "image/jpeg,image/png,application/pdf",
                hiddenInputContainer: ".hiddenFiles",
                paramName: "kyc_file_upload",
                headers: { "X-CSRF-TOKEN": j },
            });
            l.on("sending", function (a, b, c) {
                c.append("docType", "doc-one");
            })
                .on("success", function (b, c) {
                    cl(c);
                    var d = c.message;
                    "error" == c.msg ? (alert(d), l.removeFile(b)) : a('input[name="document_one"]').val(c.file_name);
                })
                .on("removedfile", function (b) {
                    var c = a('input[name="document_one"]').val();
                    0 < c.length &&
                        b.accepted &&
                        a.post(i, { _token: csrf_token, action: "delete", file: c }).done((b) => {
                            cl(b), a('input[name="document_one"]').val("");
                        });
                });
        }
        if (0 < a(".document_two").length) {
            var m = new Dropzone(".document_two", {
                url: i,
                uploadMultiple: !1,
                maxFilesize: 5.1,
                maxFiles: 1,
                addRemoveLinks: !0,
                acceptedFiles: "image/jpeg,image/png,application/pdf",
                hiddenInputContainer: ".hiddenFiles",
                paramName: "kyc_file_upload",
                headers: { "X-CSRF-TOKEN": j },
            });
            m.on("sending", function (a, b, c) {
                c.append("docType", "doc-two");
            })
                .on("success", function (b, c) {
                    cl(c);
                    var d = c.message;
                    "error" == c.msg ? (alert(d), m.removeFile(b)) : a('input[name="document_two"]').val(c.file_name);
                })
                .on("removedfile", function (b) {
                    var c = a('input[name="document_two"]').val();
                    0 < c.length &&
                        b.accepted &&
                        a.post(i, { _token: csrf_token, action: "delete", file: c }).done((b) => {
                            cl(b), a('input[name="document_two"]').val("");
                        });
                });
        }
        if (0 < a(".document_upload_hand").length) {
            var n = new Dropzone(".document_upload_hand", {
                url: i,
                uploadMultiple: !1,
                maxFilesize: 5.1,
                maxFiles: 1,
                addRemoveLinks: !0,
                acceptedFiles: "image/jpeg,image/png,application/pdf",
                hiddenInputContainer: ".hiddenFiles",
                paramName: "kyc_file_upload",
                headers: { "X-CSRF-TOKEN": j },
            });
            n.on("sending", function (a, b, c) {
                c.append("docType", "doc-hand");
            })
                .on("success", function (b, c) {
                    cl(c);
                    var d = c.message;
                    "error" == c.msg ? (alert(d), n.removeFile(b)) : a('input[name="document_image_hand"]').val(c.file_name);
                })
                .on("removedfile", function (b) {
                    var c = a('input[name="document_image_hand"]').val();
                    0 < c.length &&
                        b.accepted &&
                        a.post(i, { _token: csrf_token, action: "delete", file: c }).done((b) => {
                            cl(b), a('input[name="document_image_hand"]').val("");
                        });
                });
        }
    }
    var o = a(".token-number"),
        p = a(".pay-amount"),
        q = a(".pay-method");
    o.numericInput({ allowFloat: !1 }),
        p.numericInput({ allowFloat: !0 }),
        o.add(p).on("keyup", function () {
            token_calc(this);
        }),
        q.on("change", function () {
            token_calc(o);
        });
    var r = a("form#offline_payment");
    0 < r.length && purchase_form_submit(r, !1);
    var s = !1;
    a(".modal-close").on("click", function (b) {
        b.preventDefault(), !0 === s ? confirm(msg_order_cancel) && (bs_modal_hide(a(this)), (s = !1)) : bs_modal_hide(a(this));
    });
    var t = a(".token-payment-btn"),
        u = a("#payment-modal"),
        v = a("#data_amount"),
        w = a("#data_currency"),
        X = a("input[name=pp_token]");
    t.on("click", function (b) {
        b.preventDefault();
        var c = a(this),
            d = c.data("type") ? c.data("type") : "offline",
            e = v.val(),
            f = w.val(),
            g = X.val();
        e >= minimum_token && "" != f
            ? a
                  .post(access_url, { _token: csrf_token, req_type: d, min_token: minimum_token, token_amount: g, currency: f })
                  .done((b) => {
                      u.find(".modal-content").html(b.modal), init_inside_modal(), u.modal("show"), 0 < a("#offline_payment").length && purchase_form_submit(a("#offline_payment")), (e = f = "");
                  })
                  .fail(function (a, b, c) {
                      show_toast("error", msg_is_wrong + "\n" + c), _log(a, b, c);
                  })
            : ((e = v.val()), (f = w.val()), show_toast("warning", min_tkn_msg));
    });
    var x = a("a.user-wallet");
    x.on("click", function (c) {
        c.preventDefault(),
            5 < user_wallet_address.length &&
                a
                    .post(user_wallet_address, { _token: csrf_token })
                    .done((a) => {
                        b.html(a), init_inside_modal(), 0 < b.children(".modal").length && b.children(".modal").modal("show");
                    })
                    .fail(function (a, b, c) {
                        show_toast("error", msg_is_wrong + "\n" + c), _log(a, b, c);
                    });
    });
    a(document).on("click", "a.view-transaction", function (c) {
        c.preventDefault();
        var d = a(this).data("id");
        a.post(view_transaction_url, { tnx_id: d, _token: csrf_token })
            .done((a) => {
                b.html(a), 0 < b.children(".modal").length && b.children(".modal").modal("show");
            })
            .fail(function (a, b, c) {
                show_toast("error", msg_is_wrong + "\n" + c), _log(a, b, c);
            });
    });
    a(document).on("click", "a.user-modal-request", function (c) {
        c.preventDefault();
        var d = null,
            e = a(this).data("action"),
            f = a(this).data("type") ? a(this).data("type") : "";
        "send-token" == e && "undefined" != typeof user_token_send ? (d = user_token_send) : "withdraw-token" == e && "undefined" != typeof user_token_withdraw && (d = user_token_withdraw),
            null !== d && f
                ? a
                      .post(d, { type: f })
                      .done(function (a) {
                          if ("undefined" != typeof a.modal) b.html(a.modal), init_inside_modal(), 0 < b.children(".modal").length && b.children(".modal").modal("show");
                          else if (a.message) {
                              var c = a.icon ? a.icon : "ti ti-info-alt";
                              show_toast(a.msg, a.message, c);
                          }
                      })
                      .fail(function (a, b, c) {
                          show_toast("error", msg_is_wrong + "\n" + c), _log(a, b, c);
                      })
                : show_toast("warning", msg_process_unable, "ti ti-info-alt");
    });
    a(document).on("click", "a.user_tnx_trash", function (b) {
        b.preventDefault();
        var c = a(this).data("tnx_id"),
            d = a(this).attr("href");
        confirm(msg_confirm) &&
            a
                .post(d, { tnx_id: c, _token: csrf_token })
                .done((b) => {
                    a("tr.tnx-item-" + c).fadeOut(400, function () {
                        a(this).remove();
                    }),
                        cl(c),
                        show_toast(b.msg, b.message, "ti ti-trash");
                })
                .fail(function (a, b, c) {
                    show_toast("error", msg_is_wrong + "\n" + c), _log(a, b, c);
                });
    });
})(jQuery);
