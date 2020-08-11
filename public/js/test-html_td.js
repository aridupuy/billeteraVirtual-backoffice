
var decidir = null
var selectedForm = undefined
var decidirAgro = undefined
//const URL_LOCAL = "http://localhost:456/externo/landing_pagos_pei.php";
const URL_LOCAL = "https://www.cobrodigital.com:14365/externo/landing_pagos_pei.php"

function intializeExample() {

    var date = new Date();
    var month = date.getMonth();
    var year = date.getFullYear();
//  console.log(date.getFullYear());
//  var m = month;
    var y = parseInt(year.toString().substring(2));
    for (var i = 1; i <= 12; i++) {
        var elem = document.createElement('option');
        if (i < 10) {
            elem.setAttribute('value', "0" + i);
            elem.append(document.createTextNode("0" + i.toString()));
        } else {
            elem.setAttribute('value', i);
            elem.append(document.createTextNode(i.toString()));
        }
        $("select[data-decidir=card_expiration_month]").append(elem)

    }
    for (var i = 1; i <= 20; i++) {
        var elem = document.createElement('option');
        if (y < 10) {
            elem.setAttribute('value', "0" + y);
            elem.append(document.createTextNode("0" + y.toString()));
        } else {
            elem.setAttribute('value', y);
            elem.append(document.createTextNode(y.toString()));
        }
        $("select[data-decidir=card_expiration_year]").append(elem)

        y = y + 1;
    }


//elem.append(document.createTextNode('20'));
//$("select[data-decidir=card_expiration_month]").append(elem)

    $('#button').click(function (event) {
        sendForm(event);
    });

}


function sendForm(e) {

    var submit_btn = document.querySelector('#button');
    submit_btn.className = "boton_fondo_corredizo_base";
    submit_btn.value = "Espera...";
    var a = $('#miFormulario').serialize();
    console.log(a);
    $.ajax({
        data: a,
        url: URL_LOCAL,
        type: 'post',
        success: function (response) {
            var json = JSON.parse((response));

            if (json.estado == "true") {
                console.log(json.mensaje);
                submit_btn.className = "ok";
                submit_btn.value = "PAGAR";
                $("#success").hide();
                $("#error").hide();
                $("#success").show();
            } else {
                console.log("Error: " + json.mensaje);
                submit_btn.className = "ok";
                submit_btn.value = "REINTENTAR";
                $("#success").hide();
                $("#error").hide();
                $("#error").show();
            }
        },
        error: function () {
            submit_btn.className = "ok";
            submit_btn.value = "REINTENTAR";
            $("#success").hide();
            $("#error").hide();
            $("#error").show();
        }
    });
}
function parse_error($string) {
    switch ($string) {
        case "expiry_date":
            return "Fecha de expiracion invalida";
        case "curl error: Operation timed out after 30000 milliseconds with 0 bytes received":
            return "La operacion ha fallado, Reintente";
        case "curl error: Operation timed out after 30001 milliseconds with 0 bytes received":
            return "La operacion ha fallado, Reintente";
    }
    return $string;
}

function cleanHtmlElement(element) {
    element.innerText = ''


}
