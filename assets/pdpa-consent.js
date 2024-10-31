/***
 * Package: pdpa-consent
 * (c) Apinan Woratrakun <iamapinan@gmail.com>
 */

document.addEventListener("DOMContentLoaded", function () {
    if (pdpa_ajax.consent_enable === "yes" && pdpa_get_cookie() == null) {
        document.getElementById("PDPANotAllow").addEventListener("click", function() {
            pdpa_consent_call('pdpa-not-allow');
            document.getElementById("pdpa_screen").style.display = 'none'
        });
        document.getElementById("PDPAAllow").addEventListener("click", function() {
            pdpa_consent_call('pdpa-allow');
            document.getElementById("pdpa_screen").style.display = 'none'
        });
    }
})

const pdpa_consent_call = (action_require) => {
    var pdpa_request_data = new FormData();
    pdpa_request_data.append("action", "pdpa_action");
    pdpa_request_data.append("set_status", action_require);
    pdpa_request_data.append("security", pdpa_ajax.pdpa_nonce);

    axios({
        method: 'POST',
        url: pdpa_ajax.ajax_url,
        data: pdpa_request_data
    })
    .then(function (response) {
        //handle success
        pdpa_cookie_process(response.data);
    })
    .catch(function (response) {
        //handle error
        console.log(response);
    });
}

const pdpa_cookie_process = (d) => {
    var cookie_string = '';
    if (d.type == 'user_allow') {
        cookie_string = d.cookie_name + "=1; expires=" + d.cookie_expire + "; domain=" + d.cookie_domain + "; path=/";
    } else if (d.type == 'user_not_allow') {
        cookie_string = d.cookie_name + "=0; expires=" + d.cookie_expire + "; domain=" + d.cookie_domain + "; path=/";
    } else if(d.type == 'reset') {
        cookie_string = d.cookie_name + "=0; expires=Thu, 01 Jan 1970 00:00:00 GMT; domain=" + d.cookie_domain + "; path=/";
        window.location.reload()
    } else {
        console.log("error.", d)
    }
    console.log(cookie_string)
    document.cookie = cookie_string;
}

const pdpa_delete_all_cookie = () => {
    var cookies = document.cookie.split(";");

    for (var i = 0; i < cookies.length; i++) {
        var cookie = cookies[i];
        var eqPos = cookie.indexOf("=");
        var name = eqPos > -1 ? cookie.substr(0, eqPos) : cookie;
        document.cookie = name + "=;expires=Thu, 01 Jan 1970 00:00:00 GMT";
    }
}

function pdpa_get_cookie() {
    var cookieArr = document.cookie.split(";");
    
    for(var i = 0; i < cookieArr.length; i++) {
        var cookiePair = cookieArr[i].split("=");
        if('pdpa_accepted' == cookiePair[0].trim()) {
            return decodeURIComponent(cookiePair[1]);
        }
    }

    return null;
}