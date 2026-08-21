/*
 * Copyright © 2018-2026 RBSoft (Ravi Patel). All rights reserved.
 *
 * Author: Ravi Patel
 * Website: https://rbsoft.org/downloads/sms-gateway
 *
 * This software is licensed, not sold. Buyers are granted a limited, non-transferable license
 * to use this software exclusively on a single domain, subdomain, or computer. Usage on
 * multiple domains, subdomains, or computers requires the purchase of additional licenses.
 *
 * Redistribution, resale, sublicensing, or sharing of the source code, in whole or in part,
 * is strictly prohibited. Modification (except for personal use by the licensee), reverse engineering,
 * or creating derivative works based on this software is strictly prohibited.
 *
 * Unauthorized use, reproduction, or distribution of this software may result in severe civil
 * and criminal penalties and will be prosecuted to the fullest extent of the law.
 *
 * For licensing inquiries or support, please visit https://support.rbsoft.org.
 */

$.validator.setDefaults({
    highlight: function (element) {
        $(element).closest('.form-group').addClass('has-error');
    },
    unhighlight: function (element) {
        $(element).closest('.form-group').removeClass('has-error');
    },
    errorElement: 'span',
    errorClass: 'help-block',
    errorPlacement: function (error, element) {
        if (element.parent('.input-group').length) {
            error.insertAfter(element.parent());
        } else {
            error.insertAfter(element);
        }
    }
});

function ajaxRequest(url, postData) {
    return new Promise((resolve, reject) => {
        let request = {
            type: "POST",
            url: url,
            dataType: 'json',
            data: postData,
            success: function (data) {
                if (data.redirect) {
                    document.location.href = data.redirect;
                } else if (data.error) {
                    reject(data.error);
                } else {
                    resolve(data.result);
                }
            },
            error: function (jqXHR, textStatus, errorThrown) {
                reject(errorThrown);
            }
        };
        if (postData instanceof FormData) {
            request.contentType = false;
            request.processData = false;
        }
        $.ajax(request);
    });
}

function disableInput(checkBoxId, input) {
    if ($(checkBoxId).is(':checked')) {
        $(input).prop("disabled", false);
    } else {
        $(input).prop("disabled", true);
    }
}