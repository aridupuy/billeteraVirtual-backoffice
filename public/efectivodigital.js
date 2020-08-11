var mensaje_error = "<div class='teatro'><i class='fa fa-5x fa-flash'></i>Ha ocurrido un error. recargue la página.</div>";
var cargando = "<div class='teatro'><i class='fa fa-5x fa-circle-o-notch fa-spin'></i></div>";

// // Nuevo desarrollo
var url_servidor = 'index.php';
var destino_respuesta_servidor = '#main';

// Cobrodigital.com
// var url_servidor='apps/externo/index.php';
// var destino_respuesta_servidor='#miFormulario';

$(function () {
    load(0);

});

function load(nav) {
    select_browser();
    // $(".datepicker").datepicker();
    // $(".datepicker").datepicker( "option", "dateFormat", 'dd/mm/yy' );   
    $(".debug-container").append($(".debug"));
    $("[type='submit']").off('click');
    $("[type='submit']").on('click', function (event) {
        if ($('#miFormulario')[0].checkValidity()) {
            $(this).attr('type', 'button');
            if ($("[type='file']").size() == '1')
                file_link($(this).attr('name'), $("#miFormulario [type='file']").eq(0).attr('name'), $("#miFormulario [type='file']").eq(0).attr('id'));
            else
                link($(this).attr('name'), event.target.getAttribute('id'), '');
        }
    });

    $("[type='button']").off('click');
    $("[type='button']").on('click', function (event) {
        link($(this).attr('name'), $(this).attr('id'), '');
    });
    $('[data-toggle="tooltip"]').tooltip();
    $('[data-toggle="popover"]').popover();

    // SETEO DE VARIABLES PARA ORDENAMIENTO DE COLUMNAS EN DATATABLE
    var ordering = [0, 'desc'];
    var col_ordering = $('#col-ordering').attr('value');
    var way_ordering = $('#way-ordering').attr('value');
    
    if(col_ordering !== undefined && way_ordering !== undefined){
        ordering = [parseInt(col_ordering), way_ordering];
    }
    
    //console.log(ordering);
    var table;
    if ($('#dataTable').children("thead").length > 0) {
        console.log('hsdf');
        $('#dataTable').DataTable({
            "paging": true,
//            "pagingType": "full_numbers",
            "language": {
                "lengthMenu": "Ver _MENU_ Registros por pagina",
		"info": "Viendo _START_ al _END_ de _TOTAL_ Registros",
                "zeroRecords": "No hay registros para mostrar",
                 "paginate": {
                    "next": "Siguiente",
                    "previous": "Anterior"
              }
            },
            columnDefs: [
                { targets: 'dateorder', sType: 'date-arg'}
                
            ],
            "sPaginationType":"full_numbers",
            "lengthMenu": [[10, 25, 50,100, -1], [10, 25, 50,100, "Todos"]],
            "lengthChange": true,
            "ordering": true,
            "order": ordering,
            "info": true,
            "searching": false,
            "scrollCollapse": true
           
        });
    }
//    $.fn.DataTable.ext.pager.numbers_length = 4;

    jQuery.extend( jQuery.fn.dataTableExt.oSort, {
        "date-arg-pre": function ( a ) {
            var ukDatea = a.split('/');
//            console.log(ukDatea);
            return (ukDatea[2] + ukDatea[1] + ukDatea[0]) * 1;
        },

        "date-arg-asc": function ( a, b ) {
            return ((a < b) ? -1 : ((a > b) ? 1 : 0));
        },

        "date-arg-desc": function ( a, b ) {
            return ((a < b) ? 1 : ((a > b) ? -1 : 0));
        }
    } );
    
    $.fn.dataTable.ext.errMode = 'none';
    
    $.fn.dataTable.moment = function ( format, locale ) {
        var types = $.fn.dataTable.ext.type;
//        console.log(locale);
        // Add type detection
        types.detect.unshift( function ( d ) {
            return moment( d, format, locale, true ).isValid() ?
                'moment-'+format :
                null;
        } );

        // Add sorting method - use an integer for the sorting
        types.order[ 'moment-'+format+'-pre' ] = function ( d ) {
            return moment( d, format, locale, true ).unix();
        };
    };
//    table.columns.adjust().draw();
//    $("#dataTable_wrapper").on('click', function () {
//         load(0);
//    })
//    var $var=$(".paginate_button").click;
//    $(".paginate_button").click(function (A,B,C){

////        alert($var);
//        $var(A,B,C);
//        $(this).click($var);
//    });
    escuchar_checkboxes();
    escuchar_mensaje_log();
    escuchar_paginador();
}

function link(nav, id, pagina_a_mostrar) {
    var checkboxes_string = obtener_checkboxes_sin_tildar();
    var parametros = {
        "nav": nav,
        "pagina": pagina_a_mostrar,
        "id": id,
        "data": $("#miFormulario").serialize() + checkboxes_string
    };

    $.ajax({
        data: parametros,
        url: url_servidor,
        type: 'post',
        beforeSend: function () {
            antes_de_navegar();
        },
        success: function (response) {
            despues_de_navegar(response);
            load(nav);
        },

        error: function () {
            $("#miFormulario").html(mensaje_error);
        }
    });
}

function select_browser() {
    var matched, browser;
    jQuery.uaMatch = function (ua) {
        ua = ua.toLowerCase();

        var match = /(chrome)[ \/]([\w.]+)/.exec(ua) ||
                /(webkit)[ \/]([\w.]+)/.exec(ua) ||
                /(opera)(?:.*version|)[ \/]([\w.]+)/.exec(ua) ||
                /(msie) ([\w.]+)/.exec(ua) ||
                ua.indexOf("compatible") < 0 && /(mozilla)(?:.*? rv:([\w.]+)|)/.exec(ua) || [];

        return {
            browser: match[1] || "",
            version: match[2] || "0"
        };
    };

    matched = jQuery.uaMatch(navigator.userAgent);
    browser = {};

    if (matched.browser) {
        browser[matched.browser] = true;
        browser.version = matched.version;
    }

    // Chrome is Webkit, but Webkit is also Safari.
    if (browser.chrome) {
        browser.webkit = true;
    } else if (browser.webkit) {
        browser.safari = true;
    }

    jQuery.browser = browser;
    if (jQuery.browser.mozilla == true)
        $("[type=date]").datepicker({
            dateFormat: 'yy-mm-dd'
        });
}

function link_reemplazo(nav, id, id_elemento_a_reemplazar, fx,data=true) {
    var checkboxes_string = obtener_checkboxes_sin_tildar();
    if (data) {
        var parametros = {
            "nav": nav,
            "id": id,
            "data": $("#miFormulario").serialize() + checkboxes_string
        };
    } else {
        var parametros = {
            "nav": nav,
            "id": id
//            "data": $("#miFormulario").serialize() + checkboxes_string
        };
    }

    $.ajax({
        data: parametros,
        url: url_servidor,
        type: 'post',
        beforeSend: function () {
            // Hacer esto si el pedido supera determinado tiempo!
            //$("#"+id_elemento_a_reemplazar).html(cargando);
        },
        success: function (response) {
            $("#" + id_elemento_a_reemplazar).html(response);
            load(nav);
            if (fx) {
                fx();
            }

        },

        error: function () {
            $("#miFormulario").html(mensaje_error);
        }
    });
}

function file_link(nav, nombre_archivo, id_archivo) {
    var inputFileImage = document.getElementById(id_archivo);
    var file = inputFileImage.files[0];
    var objeto = new FormData();
    var checkboxes_string = obtener_checkboxes_sin_tildar();
    objeto.append('archivo', file);
    objeto.append('nav', nav);
    objeto.append('data', $("#miFormulario").serialize() + checkboxes_string);

    $.ajax({
        url: url_servidor,
        type: 'POST',
        contentType: false,
        data: objeto,
        processData: false,
        cache: false,
        beforeSend: function () {
            antes_de_navegar();
        },
        success: function (response) {
            despues_de_navegar(response);
            load(nav);

        },

        error: function () {
            $("#miFormulario").html(mensaje_error);
        }
    });
}

function escuchar_paginador() {
    var pagina_actual = parseInt($('.paginador').attr('data-pagina-actual'));
    var cantidad_paginas = parseInt($('.paginador').attr('data-cantidad-paginas'));
    var controller = $('.paginador').attr('name');
    $('.paginador .fa-fast-forward').on('click', function (event) {
        if (pagina_actual < cantidad_paginas)
            link(controller, '', cantidad_paginas);
    });
    $('.paginador .fa-forward').on('click', function (event) {
        var pagina_a_mostrar = parseInt($('.paginador').attr('data-pagina-actual')) + 1;
        if (cantidad_paginas >= pagina_a_mostrar)
            link(controller, '', pagina_a_mostrar);
    });
    $('.paginador .fa-backward').on('click', function (event) {
        var pagina_a_mostrar = parseInt($('.paginador').attr('data-pagina-actual')) - 1;
        if (pagina_a_mostrar >= 1)
            link(controller, '', pagina_a_mostrar);
    });
    $('.paginador .fa-fast-backward').on('click', function (event) {
        if (pagina_actual != 1)
            link(controller, '', 1);
    });
}

function escuchar_checkboxes() {
    $("[type='checkbox']").on('click', function (event) {
        if ($(this).attr('checked') == 'checked' || $(this).attr('checked') == true) {
            $(this).attr('value', 0);
            $(this).attr('checked', false);
        } else {
            $(this).attr('value', 1);
            $(this).attr('checked', true);
        }
    });
}

function escuchar_mensaje_log() {
    $(".mensaje_log").on('click', function (event) {
        $(".mensaje_log").animate({
            'margin-left': '+=400'
        }, 250, 'linear', function (event) {
            $(this).hide();
        });

    });
}

function antes_de_navegar() {
    $('.paginador .fa-fast-forward').off('click');
    $('.paginador .fa-forward').off('click');
    $('.paginador .fa-backward').off('click');
    $('.paginador .fa-fast-backward').off('click');
    $("[type='button']").off('click');
    $("[type='submit']").off('click');
    $("#miFormulario").html(cargando);
    $(".debug").remove();
    // Hacer esto si el pedido supera determinado tiempo!
}

function despues_de_navegar(response) {
    $(destino_respuesta_servidor).html(response);
}

function obtener_checkboxes_sin_tildar() {
    // Usamos esta funcion para enviar los checkboxes sin tildar
    // solo aquellos que tengan value=0
    var string = '';
    $("#miFormulario [type='checkbox']").each(function () {
        if ($(this).val() != 1) {
            //Esto puede traer problemas de memoria?
            string = string + '&' + $(this).attr('name') + '=0';
        }
    });
    return string;
}

function validaCuit(sCUIT) {
    var aMult = '6789456789';
    var aMult = aMult.split('');
    var sCUIT = String(sCUIT);
    var iResult = 0;
    var aCUIT = sCUIT.split('');

    if (aCUIT.length == 11) {
        // La suma de los productos 
        for (var i = 0; i <= 9; i++) {
            iResult += aCUIT[i] * aMult[i];
        }
        // El módulo de 11 
        iResult = (iResult % 11);

        // Se compara el resultado con el dígito verificador 
        return (iResult == aCUIT[10]);
    }
    return false;
}

function validarLargoCBU(cbu) {
    if (cbu.length != 22) {
        return false
    }
    return true
}

function validarCodigoBanco(codigo) {
    if (codigo.length != 8) {
        return false
    }
    var banco = codigo.substr(0, 3);
    var digitoVerificador1 = codigo[3]
    var sucursal = codigo.substr(4, 3)
    var digitoVerificador2 = codigo[7]

    var suma = banco[0] * 7 + banco[1] * 1 + banco[2] * 3 + digitoVerificador1 * 9 + sucursal[0] * 7 + sucursal[1] * 1 + sucursal[2] * 3;

    var diferencia = (10 - (suma % 10)) % 10;
    // var diferencia = 10 - (suma % 10)

    return diferencia == digitoVerificador2;
}

function validarCuenta(cuenta) {
    if (cuenta.length != 14) {
        return false;
    }
    var digitoVerificador = cuenta[13];
    var suma = cuenta[0] * 3 + cuenta[1] * 9 + cuenta[2] * 7 + cuenta[3] * 1 + cuenta[4] * 3 + cuenta[5] * 9 + cuenta[6] * 7 + cuenta[7] * 1 + cuenta[8] * 3 + cuenta[9] * 9 + cuenta[10] * 7 + cuenta[11] * 1 + cuenta[12] * 3;
    //var diferencia = 10 - (suma % 10)
    var diferencia = (10 - (suma % 10)) % 10;
    return diferencia == digitoVerificador;
}

function validarCBU(cbu) {
    var largo = validarLargoCBU(cbu);

    var banco = validarCodigoBanco(cbu.substr(0, 8));
    var cuenta = validarCuenta(cbu.substr(8, 14));
    if (!largo) {
        //alert('mal largo');
        return false;
    }

    if (!banco) {
        // alert('mal banco');
        return false;
    }
    if (!cuenta) {
        // alert('mal cuenta');
        return false;
    }

    return largo && banco && cuenta;

}

