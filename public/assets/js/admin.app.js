/*! CoreApp v1.3.2 | Copyright by Softnio. */
(function (a) {
    var b = a("#ajax-modal");
    _button_submit = "button[type=submit]";
    var c = a("form#addUserForm");
    0 < c.length && ajax_form_submit(c);
    var d = a("form#user_account_update");
    0 < d.length && ajax_form_submit(d, !1);
    var e = a("form#notification");
    0 < e.length && ajax_form_submit(e, !1);
    var f = a("form#security");
    0 < f.length && ajax_form_submit(f, !1);
    var g = a("form#pwd_change");
    0 < g.length && ajax_form_submit(g);
    var h = a(".activity-delete"),
        i = a("#activity_action").val();
    0 < h.length &&
        h.on("click", function () {
            swal({ title: "Are you sure?", text: "Once Delete, You will not get back this log in future!", icon: "warning", buttons: !0, dangerMode: !0 }).then((b) => {
                if (b) {
                    var c = a(this).data("id");
                    a.post(i, { _token: csrf_token, delete_activity: c }).done((b) => {
                        "success" == b.msg &&
                            ("all" == c
                                ? a("#activity-log tr").fadeOut(1e3, function () {
                                      a(this).remove(), a("#activity-log").hide();
                                  })
                                : h.parents("tr.activity-" + c).fadeOut(1e3, function () {
                                      a(this).remove();
                                  }));
                    });
                }
            });
        });
    a(document).on("click", "a#clear-cache", function (b) {
        b.preventDefault(),
            a.get(clear_cache_url).done((a) => {
                cl(a), "success" == a.msg && (show_toast(a.msg, a.message, "ti ti-trash"), window.location.reload());
            });
    });
    var j = a("form#update_settings");
    if (0 < j.length) {
        var k = j.find(_button_submit);
        j.find(".input-switch, .select, .input-checkbox, .input-bordered").on("keyup change", function () {
            btn_actived(k);
        }),
            ajax_form_submit(j, !1);
    }
    var l = a("form#update_social_settings");
    if (0 < l.length) {
        var m = l.find(_button_submit);
        l.find(".input-switch, .select, .input-checkbox, .input-bordered").on("keyup change", function () {
            btn_actived(m);
        }),
            ajax_form_submit(l, !1);
    }
    var n = a("form#update_general_settings");
    if (0 < n.length) {
        var o = n.find(_button_submit);
        n.find(".input-switch, .select, .input-checkbox, .input-bordered").on("keyup change", function () {
            btn_actived(o);
        }),
            ajax_form_submit(n, !1);
    }
    var p = a("form#update_api_settings");
    if (0 < p.length) {
        var q = p.find(_button_submit);
        p.find(".input-switch, .select, .input-checkbox, .input-bordered").on("keyup change", function () {
            btn_actived(q);
        }),
            ajax_form_submit(p, !1);
    }
    var r = a("form#update_code_settings");
    if (0 < r.length) {
        var s = r.find(_button_submit);
        r.find(".input-switch, .select, .input-checkbox, .input-bordered").on("keyup change", function () {
            btn_actived(s);
        }),
            ajax_form_submit(r, !1);
    }
    var t = a("form#email_settings");
    0 < t.length && ajax_form_submit(t, !1);
    a(document).on("click", "a.et-item", function (c) {
        c.preventDefault();
        var d = null == a(this).data("slug") ? (null == a(this).data("id") ? "" : a(this).data("id")) : a(this).data("slug");
        a.post(get_et_url, { get_template: d, _token: csrf_token }).done((a) => {
            cl(a), b.html(a), init_inside_modal(), b.children(".modal").modal("show");
        });
    }),
        ($resendverifyemail = a(".resend-verify-email")),
        $resendverifyemail.on("click", function (b) {
            b.preventDefault();
            var c = a(this).attr("href");
            a.get(c, { _token: csrf_token }).done(() => {
                show_toast("success", "A fresh verification link has been sent to your email address.", "ti ti-email");
            });
        });
    a(document).on("click", ".user-email-action", function () {
        var b = a(this).data("uid");
        a("input#user_id").val(b);
    });

    //tiger
    a(document).on("click", ".user-change-action", function () {
        var b = a(this).data("uid");
        console.log(b);
        a("input#user_change_id").val(b);
    });
    var u = a("form#emailToUser");
    0 < u.length && ajax_form_submit(u, !0);
    a(document).on("click", "a.user-action", function (c) {
        c.preventDefault();
        var d = a(this).data("type"),
            e = "suspend_user" == d ? "warning" : "info",
            f = a(this).data("uid");
        "transactions" == d || "activities" == d || "referrals" == d
            ? (b.empty(),
              b.parent().find(".modal-backdrop").remove(),
              a.post(show_user_info, { uid: f, req_type: d, _token: csrf_token }).done((c) => {
                  c.status && "die" == c.status ? show_toast(c.msg, c.message, "ti ti-lock") : (bs_modal_hide(a(this)), b.html(c), b.children(".modal").modal("show"));
              }))
            : ("suspend_user" == d || "active_user" == d || "reset_pwd" == d || "reset_2fa" == d) &&
              swal({ title: "Are you sure?", icon: e, buttons: ["Cancel", "Yes"], dangerMode: !0 }).then((c) => {
                  c &&
                      a.post(view_user_url, { uid: f, req_type: d, _token: csrf_token }).done((c) => {
                          null != c.msg && show_toast(c.msg, c.message),
                              ("active_user" == d || (d = "suspend_user")) &&
                                  a(this).fadeOut(200, function () {
                                      a(this).remove();
                                  }),
                              a(document)
                                  .find(".status_user")
                                  .find(".badge")
                                  .removeAttr("class")
                                  .addClass("badge badge-" + c.css + " ucap"),
                              a(".more-menu-" + f).append('<li><a href="#" data-uid="' + f + '" data-type="' + c.status + '" class="user-action"><em class="fas fa-ban"></em>' + ("suspend_user" == d ? "Active" : "Suspend") + "</a></li>"),
                              b.html(c),
                              b.children(".modal").modal("show");
                      });
              });
    });
    var v = a("a.get_kyc");
    v.on("click", function (c) {
        c.preventDefault();
        var d = a(this).data("type"),
            e = null == a(this).data("id") ? "" : a(this).data("id");
        a.post(get_kyc_url, { req_type: d, get_id: e, _token: csrf_token }).done((c) => {
            if ((cl(c), b.html(c), "kyc_settings" == d)) {
                var e = a("form#kyc_settings"),
                    f = e.find(_button_submit),
                    g = !1;
                0 < e.length && ajax_form_submit(e, !1),
                    e.find(".input-switch, .select, .input-checkbox, .input-bordered").on("change", function () {
                        (g = !0), btn_actived(f);
                    }),
                    f.on("click", function () {
                        g = !1;
                    }),
                    a(".modal-close").on("click", function (b) {
                        b.preventDefault(), !0 === g ? confirm("You made some changes, \nDo you realy close without save?") && (bs_modal_hide(a(this)), (g = !1)) : bs_modal_hide(a(this));
                    });
            }
            init_inside_modal(), b.children(".modal").modal("show");
        });
    });
    a(document).on("click", ".kyc_action", function () {
        (kid = a(this).data("id")), a("input#kyc_id").val(kid);
    });
    var w = a("#actionkyc .status-btn");
    w.on("click", function (b) {
        b.preventDefault();
        var c = a(this).data("val");
        a("#actionkyc .status-btn").removeAttr("style"), a(this).css("border", "2px solid #34425d"), a("#actionkyc input#status").val(c);
    });
    ($quick_update = ".update_kyc"),
        ($kyc_form = "#kyc_status_form"),
        a(document).on("click", $quick_update, function (b) {
            b.preventDefault();
            var c = a($kyc_form).find("#kyc_id").val();
            a.post(update_kyc_url, { req_type: "update_kyc_status", _token: csrf_token, status: a(this).data("value"), kyc_id: c }).done((a) => {
                cl(a), show_toast(a.msg, a.message, "ti ti-trash"), ("success" == a.msg || "warning" == a.msg) && window.location.reload();
            });
        }),
        a(document).on("click", "a.kyc_reject", function (b) {
            b.preventDefault(),
                swal({ title: "Are you sure?", text: "Once Rejected, the client will get one email for Resubmit KYC!", icon: "warning", buttons: !0, dangerMode: !0 }).then((b) => {
                    var c = a(this),
                        d = a(this).data("current"),
                        e = a(this).data("id"),
                        f = a(".data-item-" + e);
                    b &&
                        a.post(update_kyc_url, { req_type: "update_kyc_status", _token: csrf_token, status: "rejected", kyc_id: e }).done((b) => {
                            cl(b),
                                show_toast("warning", b.message, "ti ti-trash"),
                                f
                                    .find("span.badge")
                                    .removeClass("badge-" + d)
                                    .addClass("badge-danger"),
                                f.find("span.badge").text("Rejected"),
                                c.fadeOut(300),
                                1 > a(".more-menu-" + e).find(".kyc_approve").length &&
                                    a(".more-menu-" + e).append('<li><a class="kyc_action" href="#" data-id="' + e + '" data-toggle="modal" data-target="#actionkyc"><em class="far fa-check-square"></em>Approve</a></li>'),
                                1 > a(".more-menu-" + e).find(".kyc_delete").length && a(".more-menu-" + e).append('<li><a href="javascript:void(0)" data-id="' + e + '" class="kyc_delete"><em class="fas fa-trash-alt"></em>Delete</a></li>');
                        });
                });
        }),
        a(document).on("click", "a.kyc_delete", function (b) {
            b.preventDefault(),
                swal({ title: "Are you sure?", text: "Once deleted, You can not restore this KYC application!", icon: "error", buttons: !0, dangerMode: !0 }).then((b) => {
                    var c = a(this).data("id"),
                        d = a(".data-item-" + c);
                    b &&
                        a.post(update_kyc_url, { req_type: "delete", _token: csrf_token, kyc_id: c }).done((b) => {
                            cl(b),
                                d.fadeOut(500, function () {
                                    a(this).remove();
                                }),
                                show_toast(b.msg, b.message, "ti ti-trash");
                        });
                });
        });
    var x = a("form#ico_stage");
    if (0 < x.length) {
        var y = x.find(_button_submit);
        x.find(".input-switch, .select, .input-checkbox, .input-bordered").on("keyup change", function () {
            btn_actived(y);
        }),
            ajax_form_submit(x, !1);
    }
    var z = a("form#update_tokens"),
        A = a("button.update-token");
    0 < z.length &&
        A.on("click", function () {
            confirm("Are you sure?") && (ajax_form_submit(z, !1), A.parents("form#update_tokens").submit());
        });
    var B = a("form#ico_stage_price");
    if (0 < B.length) {
        var C = B.find(_button_submit);
        B.find(".input-switch, .select, .input-checkbox, .input-bordered").on("keyup change", function () {
            btn_actived(C);
        }),
            ajax_form_submit(B, !1);
    }
    var D = a("form#ico_stage_bonus");
    if (0 < D.length) {
        var E = D.find(_button_submit);
        D.find(".input-switch, .select, .input-checkbox, .input-bordered").on("keyup change", function () {
            btn_actived(E);
        }),
            ajax_form_submit(D, !1);
    }
    var F = a("form#stage_setting_details_form");
    if (0 < F.length) {
        var G = F.find(_button_submit);
        F.find(".input-switch, .select, .input-checkbox, .input-bordered").on("keyup change", function () {
            btn_actived(G);
        }),
            ajax_form_submit(F, !1);
    }
    var H = a("form#stage_setting_purchase_form");
    if (0 < H.length) {
        var I = H.find(_button_submit);
        H.find(".input-switch, .select, .input-checkbox, .input-bordered").on("keyup change", function () {
            btn_actived(I);
        }),
            ajax_form_submit(H, !1),
            H.find(".active_method").change(function () {
                var b = a(".active_method").val();
                (b = b.toLowerCase()), H.find(".all_methods").removeAttr("disabled");
                var c = H.find("#pw-" + b);
                c.is(":checked") ? null : c.click(), c.attr("disabled", !0);
            });
    }
    var J = a("form#referral_setting_form");
    if (0 < J.length) {
        var K = J.find(_button_submit);
        J.find(".input-switch, .select, .input-checkbox, .input-bordered").on("keyup change", function () {
            btn_actived(K);
        }),
            ajax_form_submit(J, !1);
    }
    var L = a("form#upanel_setting_form");
    if (0 < L.length) {
        var K = L.find(_button_submit);
        L.find(".input-switch, .select, .input-checkbox, .input-bordered").on("keyup change", function () {
            btn_actived(K);
        }),
            ajax_form_submit(L, !1);
    }
    var M = a("form.payment_methods_form");
    if (0 < M.length) {
        var N = M.find(_button_submit);
        M.find(".input-switch, .select, .input-checkbox, .input-bordered").on("keyup change", function () {
            btn_actived(N);
        }),
            ajax_form_submit(M, !1);
    }
    var O = a("a.quick-action");
    0 < O.length &&
        "undefined" != typeof quick_update_url &&
        O.on("click", function () {
            var b = a(this);
            a.post(quick_update_url, { _token: csrf_token, type: b.data("name") })
                .done((a) => {
                    show_toast(a.msg, a.message),
                        setTimeout(function () {
                            window.location.reload();
                        }, 300);
                })
                .fail(() => {
                    show_toast("error", "Something is wrong!");
                });
        });
    var P = a("form#pm_manage_form");
    if (0 < P.length) {
        var Q = P.find(_button_submit);
        P.find(".input-switch, .select, .input-checkbox, .input-bordered").on("keyup change", function () {
            btn_actived(Q);
        }),
            ajax_form_submit(P, !1);
    }
    var R = a("a.get_pm_manage");
    R.on("click", function (c) {
        c.preventDefault();
        var d = a(this).data("type");
        b.empty(),
            a
                .post(pm_manage_url, { req_type: d, _token: csrf_token })
                .done((a) => {
                    b.html(a), init_inside_modal(), b.children(".modal").modal("show");
                })
                .fail(function (a, b, c) {
                    _log(a, b, c), show_toast("error", "Something is wrong!\n" + c), show_toast("error", "Something is wrong!\n" + c);
                });
    });
    a(document).on("click", "a.get_trnx", function (c) {
        c.preventDefault();
        var d = a(this).data("type"),
            e = null == a(this).data("id") ? "" : a(this).data("id");
        a.post(get_trnx_url, { req_type: d, get_id: e, _token: csrf_token }).done((a) => {
            cl(a), b.html(a), init_inside_modal(), b.children(".modal").modal("show");
        });
    });
    a(document).on("click", ".stages-ajax-action", function (c) {
        c.preventDefault();
        var d = a(this),
            e = d.data(),
            f = null,
            g = e.action,
            h = e.stage,
            i = d.parents(".stage-action");
        i.find(".toggle-tigger").add(".toggle-class").removeClass("active"),
            "overview" == g && "undefined" != typeof stage_action_url && (f = stage_action_url),
            null !== f && h
                ? ((e._token = csrf_token),
                  a
                      .post(f, e)
                      .done(function (a) {
                          if ("undefined" != typeof a.modal && a.modal) b.html(a.modal), init_inside_modal(), 0 < b.children(".modal").length && b.children(".modal").modal("show");
                          else if (a.message) {
                              var c = a.icon ? a.icon : "ti ti-info-alt";
                              show_toast(a.msg, a.message, c);
                          }
                      })
                      .fail(function (a, b, c) {
                          show_toast("error", "Something is wrong!\n" + c, "ti ti-alert"), _log(a, b, c);
                      }))
                : show_toast("info", "Nothing to proceed!", "ti ti-info-alt");
    });
    var S = a("a#update_stage");
    S.on("click", function (b) {
        b.preventDefault();
        var c = a(this).data("type"),
            d = a(this).data("id"),
            e = "",
            f = "";
        (f = "active_stage" == c ? stage_active_url : stage_pause_url),
            "active_stage" == c
                ? (e = "Once you make this stage active, other stage will inactive and stop sale on that stage.")
                : "pause_stage" == c
                ? (e = "Do you want to pause temporary your running sales and purchase option disabled?")
                : "resume_stage" == c && (e = "Do you want to resume your sales and contributor able to purchase token?"),
            swal({ title: "Are you sure?", text: e, icon: "info", buttons: ["Cancel", "Yes"], dangerMode: !1 }).then((b) => {
                b &&
                    a
                        .post(f, { _token: csrf_token, id: d, type: c })
                        .done((a) => {
                            cl(a), show_toast(a.msg, a.message, "ti ti-eye"), "success" == a.msg && window.location.reload();
                        })
                        .fail((a) => {
                            cl(a);
                        });
            });
    });
    var T = a("form#add_token");
    0 < T.length && ajax_form_submit(T, !1);
    a(document).on("click", ".tnx-action", function (b) {
        b.preventDefault();
        var c = a(this),
            d = a(".modal-backdrop"),
            e = c.data("type"),
            f = c.data("id");
        (token = c.data("token") ? c.data("token") : 0),
            (chk_adjust = c.data("_chk") ? c.data("_chk") : 0),
            (adjusted_token = c.data("_adjusted") ? c.data("_adjusted") : 0),
            (base_bonus = c.data("_b_bonus") ? c.data("_b_bonus") : 0),
            (token_bonus = c.data("_t_bonus") ? c.data("_t_bonus") : 0),
            (amount = c.data("_amount") ? c.data("_amount") : 0),
            (swal_icon = "approved" == e ? "info" : "warning"),
            (swal_cta = "approved" == e ? "Approve" : "Yes"),
            (swal_ctac = "approved" == e ? "" : "danger"),
            "approved" == e && null != amount && 0 >= amount
                ? show_toast("warning", "Invalid Received Amount!")
                : swal({
                      title: "Are you sure?",
                      text: "refund" == e ? "If you refund for this transactions, then the token will be added to the stage and subtract the token from user balance." : "",
                      icon: swal_icon,
                      buttons: { cancel: { text: "Cancel", visible: !0 }, confirm: { text: swal_cta, className: swal_ctac } },
                      content: { element: "refund" == e ? "input" : "span", attributes: { placeholder: "refund" == e ? "Write a note..." : "", type: "text" } },
                      dangerMode: !1,
                  }).then((b) => {
                      (null != b || "" == b) &&
                          a
                              .post(trnx_action_url, {
                                  _token: csrf_token,
                                  req_type: e,
                                  tnx_id: f,
                                  token: token,
                                  chk_adjust: chk_adjust,
                                  adjusted_token: adjusted_token,
                                  base_bonus: base_bonus,
                                  token_bonus: token_bonus,
                                  amount: amount,
                                  message: b,
                              })
                              .done((b) => {
                                  show_toast(b.msg, b.message),
                                      b.status ||
                                          ("approved" == e &&
                                              (a("#tnx-item-" + f)
                                                  .find(".token-amount")
                                                  .text("+" + b.data.total_tokens),
                                              a("#tnx-item-" + f)
                                                  .find(".amount-pay")
                                                  .text("+" + b.data.amount),
                                              a("#ds-" + f)
                                                  .removeAttr("class")
                                                  .addClass("data-state data-state-approved"),
                                              a("#more-menu-" + f).html('<li><a href="' + base_url + "/admin/transactions/view/" + f + '"><em class="ti ti-eye"></em> View Details</a></li>')),
                                          "canceled" == e &&
                                              (a("#more-menu-" + f)
                                                  .find("#canceled")
                                                  .fadeOut(400, function () {
                                                      a(this).remove();
                                                  }),
                                              a("#ds-" + f)
                                                  .removeAttr("class")
                                                  .addClass("data-state data-state-canceled"),
                                              a("#more-menu-" + f).append('<li><a href="javascript:void(0)" class="tnx-action" data-type="deleted" data-id="' + f + '"><em class="fas fa-trash-alt"></em>Delete</a></li>')),
                                          "deleted" == e &&
                                              a("#tnx-item-" + f).fadeOut(400, function () {
                                                  a(this).remove();
                                              })),
                                      c.parents("div.modal").modal("toggle"),
                                      d.remove(),
                                      "undefined" != typeof b.reload &&
                                          b.reload &&
                                          setTimeout(function () {
                                              window.location.reload();
                                          }, 150);
                              })
                              .fail(function (a, b, c) {
                                  _log(a, b, c), show_toast("error", "Something is wrong!\n" + c);
                              });
                  });
    });
    a(document).on("click", ".tnx-transfer-action", function (b) {
        b.preventDefault();
        var c = a(this),
            d = c.data("status"),
            e = "rejected" == d ? "Reject" : "Approve",
            f = "rejected" == d ? "danger" : "";
        swal({
            title: "Are you sure?",
            icon: "rejected" == d ? "warning" : "info",
            text: "rejected" == d ? "The requested token amount will re-adjust into sender account balance once rejected." : "Another transaction will create for receiver and update balance with requested amount once approved.",
            buttons: { cancel: { text: "Cancel", visible: !0 }, confirm: { text: e, className: f } },
            dangerMode: !("rejected" != d),
        }).then(function (b) {
            (null != b || "" == b) &&
                a
                    .post(transfer_action_url, c.data())
                    .done(function (a) {
                        var b = a.icon ? a.icon : "ti ti-info-alt";
                        show_toast(a.msg, a.message, b),
                            "success" == a.msg &&
                                setTimeout(function () {
                                    window.location.reload();
                                }, 1200);
                    })
                    .fail(function (a, b, c) {
                        _log(a, b, c), show_toast("error", "Something is wrong!\n" + c, "ti ti-alert");
                    });
        });
    });
    a(document).on("click", "#adjust_token", function (c) {
        c.preventDefault();
        var d = a(this),
            e = d.data("id");
        b.html(""),
            a
                .post(trnx_adjust_url, { _token: csrf_token, tnx_id: e })
                .done((a) => {
                    console.log(a), a.status && "die" == a.status ? show_toast(a.msg, a.message, "ti ti-lock") : (b.empty().html(a.modal), init_inside_modal(), b.children(".modal").modal("show"));
                })
                .fail(function (a, b, c) {
                    _log(a, b, c), show_toast("error", "Something is wrong!\n" + c);
                });
    });
    var U = a(".wh-upload-zone");
    if (0 < U.length) {
        Dropzone.autoDiscover = !1;
        if (0 < a(".whitepaper_upload").length) {
            var V = new Dropzone(".whitepaper_upload", {
                url: whitepaper_uploads,
                uploadMultiple: !1,
                maxFilesize: 5,
                maxFiles: 1,
                acceptedFiles: "application/pdf",
                hiddenInputContainer: ".hiddenFiles",
                paramName: "whitepaper",
                headers: { "X-CSRF-TOKEN": csrf_token },
            });
            V.on("success", function (a, b) {
                cl(b);
                var c = b.message;
                "danger" == b.msg && (alert(c), V.removeFile(a)), show_toast(b.msg, b.message, "ti ti-filter");
            });
        }
    }
    a(document).on("click", "a.get_page", function (c) {
        c.preventDefault();
        var d = a(this).data("slug");
        a.post(view_page_url, { page: d, _token: csrf_token }).done((c) => {
            cl(c),
                "error" == c.msg &&
                    (a(".faq-" + get_id).fadeOut(400, function () {
                        a(this).remove();
                    }),
                    show_toast(c.msg, c.message)),
                b.html(c),
                init_inside_modal(),
                b.children(".modal").modal("show");
        });
    });
    a(document).on("click", ".wallet-change-action", function (b) {
        b.preventDefault();
        var c = a(this).data("id"),
            d = a(this).data("action"),
            e = a(this);
        swal({ title: "Are you sure to " + d + " this request.", icon: "approve" == d ? "info" : "warning", buttons: ["Cancel", "Yes"], dangerMode: !0 }).then((b) => {
            b &&
                a.post(wallet_change_url, { id: c, action: d, _token: csrf_token }).done((b) => {
                    "success" == b.msg && a(".request-" + c).hide(400), show_toast(b.msg, b.message);
                });
        });
    });
    var W = a(".delete-unverified-user");
    W.on("click", function () {
        swal({ title: "Want to delete unverified users?", icon: "warning", text: "Please proceed, If you really want to delete all the unverified users permanently from database.", buttons: ["Cancel", "Yes"], dangerMode: !0 }).then((b) => {
            b &&
                a
                    .post(unverified_delete_url, { _token: csrf_token })
                    .done((a) => {
                        show_toast(a.msg, a.message),
                            "no" != a.alt &&
                                setTimeout(function () {
                                    window.location.reload();
                                }, 2e3);
                    })
                    .fail((a) => {
                        cl(a);
                    });
        });
    });
    var X = a(".advsearch-opt"),
        Y = a(".search-adv-wrap"),
        Z = Y.find("form");
    0 < X.length &&
        (X.on("click", function (b) {
            b.preventDefault(), a(this).toggleClass("active"), Y.slideToggle();
        }),
        Z.find(":input").prop("disabled", !1),
        Z.submit(function () {
            return (
                a(this)
                    .find(":input")
                    .filter(function () {
                        return !this.value;
                    })
                    .attr("disabled", "disabled"),
                !0
            );
        }));
    var $ = a(".date-opt"),
        _ = a(".date-hide-show");
    0 < $.length &&
        ("custom" == $.val() && _.show(),
        $.on("change", function () {
            "custom" == a(this).val() ? _.show() : _.hide();
        }));
    var aa = a("form.update-meta"),
        ba = aa.find("a");
    0 < aa.length &&
        ba.on("click", function (b) {
            b.preventDefault();
            var c = a(this),
                d = c.closest("form").data("type") ? c.closest("form").data("type") : "",
                e = null == c.data("meta") ? "" : c.data("meta");
            a.post(meta_update_url, { type: d, meta: e, _token: csrf_token }).done((a) => {
                var b = "success" == a.msg ? "ti ti-check" : "ti ti-alert";
                show_toast(a.msg, a.message, b), "success" == a.msg && window.location.reload();
            });
        });
    var ca = a(".goto-page");
    0 < ca.length &&
        ca.on("change", function () {
            window.location.href = a(this).val();
        });

    //tiger

    a(document).on("click", "#ChangeUserRole", function (c) {
        c.preventDefault();
        var id = document.getElementById("user_change_id").value;
        var role_type = document.getElementById("role_type").value;
        
        a.post(user_role_change, { user_id: id, type: role_type, _token: csrf_token }).done((c) => {
            show_toast(c.msg, c.message);
            a('#ChangeUser').modal('hide');

        });
    });

})(jQuery);
