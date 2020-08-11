
var decidir = null
var selectedForm = undefined
var decidirAgro = undefined
const URL_LOCAL = "http://localhost:456/externo/landing_pagos_tc.php";
//const URL_LOCAL = "https://www.cobrodigital.com:14365/externo/landing_pagos_tc.php";
//Aux variables
var decidirDevelConCS = new Decidir('https://live.decidir.com/api/v2', true);
decidirDevelConCS.setPublishableKey('792ead6671d24c59933a7394f13e7101'); //para generar el device-fingerprint-id
//decidirDevelConCS.setPublishableKey('e9cdb99fff374b5f91da4480c8dca741'); //para generar el device-fingerprint-id
function FactoryDecidir() {
    this.create = function (environment, cybersource) {

        var decidirInstance = null;

        if (false) { // Si usa Cybersource
            decidirInstance = decidirDevelConCS;
        } else {

            decidirInstance = new Decidir('https://live.decidir.com/api/v2', true);
            decidirInstance.setPublishableKey('792ead6671d24c59933a7394f13e7101');
//      decidirInstance.setPublishableKey('e9cdb99fff374b5f91da4480c8dca741');
        }

        let timeout = cybersource ? 20000 : 10000;
        decidirInstance.setTimeout(timeout);
        return decidirInstance;
    }
}

function intializeExample() {
    changeRequestType('miFormulario');
    withAgro(true);
    let element = document.querySelectorAll('form[name=token-form');
    for (var i = 0; element.length > i; i++) {
        let form = element[i];
        addEvent(form, 'submit', sendForm);
    }
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
    addEvent(document.querySelector('input[data-decidir][name="card_number"]'), 'keyup', guessingPaymentMethod);
    addEvent(document.querySelector('input[data-decidir][name="card_number"]'), 'change', guessingPaymentMethod);
    $('#button').click(function (event) {
        sendForm(event);
    });

}


function addEvent(el, eventName, handler) {
    if (el.addEventListener) {
        el.addEventListener(eventName, handler);
    } else {
        el.attachEvent('on' + eventName, function () {
            handler.call(el);
        });
    }
}
;

function withAgro(isAgro) {
    decidirAgro = isAgro;

    let environment = document.querySelector('input[name="environment"]:checked').value;

    var factory = new FactoryDecidir();
    if (isAgro) {
        //  document.querySelector('#btnGenerateToken').setAttribute('hidden', true);
        document.querySelector('#agro_set').removeAttribute('hidden');
        var $formAgro = document.querySelector('#agro_data_form');

        decidir = factory.create(environment, false);
//    decidir.setUpAgro($formAgro, 184, 200); //184: días de pacto - 200: monto total de la operacion
    } else {
//    document.querySelector('#agro_set').setAttribute('hidden','true');
        document.querySelector('#btnGenerateToken').removeAttribute('hidden');
    }
}

function sdkResponseHandler(status, response) {

    console.log('respuesta', response);

//  let error= document.querySelector('#error');
//  cleanHtmlElement(error);
//  let success = document.querySelector('#success');
//  cleanHtmlElement(success);
    $("#success").hide();
    $("#error").hide();
    if (status != 200 && status != 201) {
        //$("#error").html(parse_error(response.error[0].param));
//    $("#error").html("error en los parametros");
        $("#desc_error").html("Se Detectaron Errores en los Campos");
        $("#error").show();
        console.log('Error! code: ' + status + ' - response: ' + JSON.stringify(response));
//    ('Error gato! code: ' + status +' - response: ' + JSON.stringify(response))
    } else {
        //createHtmlListFromObject(response,resultado);
        $("#token").val(response.id);
        var a = $('#miFormulario').serialize();
        var submit_btn = document.querySelector('#button');
        submit_btn.className = "boton_fondo_corredizo_base";
        submit_btn.value = "Espera...";

        console.log(a);
        $.ajax({
            data: a,
            url: URL_LOCAL,
            type: 'post',
            success: function (response) {
                var json = JSON.parse((response));

                if (json.estado == "true") {
                    console.log(json.mensaje);
                    //$("#success").html(json.mensaje);
                    submit_btn.className = "ok";
                    submit_btn.value = "OK";
                    $("#success").show();
                } else {
                    $("#desc_error").html(json.mensaje);
                    console.log("Error: " + json.mensaje);
                    submit_btn.className = "error";
                    submit_btn.value = "PAGAR";
                    $("#error").show();
                }
            },
            error: function () {
//            $("#error").html("Error al registrar el pago");
                $("#error").hide();
            }
        });
    }

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

function createHtmlListFromObject(object, parentElement) {
    let ul = document.createElement('ul')
    for (let prop in object) {
        let li = document.createElement('li')
        let spanLabel = document.createElement('span')
        let spanValue = document.createElement('span')
        spanLabel.innerText = prop + ': '
        if (typeof (object[prop]) === 'object') {
            createHtmlListFromObject(object[prop], spanValue)
        } else {
            spanValue.innerText = object[prop]
        }
        li.appendChild(spanLabel)
        li.appendChild(spanValue)
        ul.appendChild(li)
    }
    parentElement.appendChild(ul)
}

function cleanHtmlElement(element) {
    element.innerText = ''

    /*for (var i=0; children.length > i; i++) {
     let x = children[i];
     element.removeChild(x)
     }*/
}




function sendForm(event) {
    event.preventDefault();

    var $form = document.querySelector('#' + selectedForm);

    let environment = 1;
    let useCybersource = false;

    if (decidirAgro !== true) {
        var factory = new FactoryDecidir(); //Agro usa configuracion local por defecto.
        decidir = factory.create(environment, useCybersource);
    }

    console.log('Decidir.createToken()');
    decidir.createToken($form, sdkResponseHandler);
//    document.querySelector('#agro_set').removeAttribute('hidden');
    return event;
}
;

function guessingPaymentMethod() {

    var cardNumber = document.querySelector('input[data-decidir][name="card_number"]').value;
    var bin = decidir.getBin(cardNumber);
    var issuedInput = document.querySelector('#issued');
    var nombre_tarj = document.querySelector('#nombre_tarj');

    var bin_view = document.querySelector('#bin');
    issuedInput.value = decidir.cardType(cardNumber);
    nombre_tarj.value = issuedInput.value;

    //  alert(issuedInput.value);
//    document.querySelector('input[class][name="num_tarjeta"]').class='num-tarjeta '+ issuedInput.value
    $(".num-tarjeta").addClass(issuedInput.value);
    bin_view.value = bin;
    console.log('bin', bin);
}


function changeRequestType(value) {
    selectedForm = value
    let  containers = document.querySelectorAll('form')
    for (var i = 0; containers.length > i; i++) {
        let e = containers[i];

        e.setAttribute('hidden', 'true');
    }
    form = document.querySelector('#' + value);
    console.log(form);
    form.removeAttribute('hidden');

}