var mensaje_error = '<section class="contenedor-contenido sesion"><div class="centrado fullheight"><div class="ilustracion-centrada"><img src="public/img/ilus-500.svg" alt="error_500"></div><h1>Error 500</h1><h4>Error del servidor</h4><a type="button" name="" class="button btn-centrado" href="/">Volver a cargar</div></div></section>';
var cargando = "<div class=\"loading\"><img src=\"public/img/logo_loader.gif\" style=\"width:50px; margin: auto;\"></div>";
// // Nuevo desarrollo
var url_servidor = 'index.php';
var destino_respuesta_servidor = '#main';

// Cobrodigital.com
// var url_servidor='apps/externo/index.php';
// var destino_respuesta_servidor='#miFormulario';

$(function () {
    load(0);

});
$(".actions-not-importante .btn-aceptar").click(function (){
    $("#popNoti").removeClass("active");
});
function filePreview(input,id_previw) {
        if (input.files && input.files[0]) {
        var reader = new FileReader();
        reader.onload = function (e) {
//            $('#'+id_previw+'').parent().remove("img");
            $('#'+id_previw+'').children("img").remove();
            //$('#'+id_previw+'').removeAttr("style");
            //$('#'+id_previw).append('<img src="'+e.target.result+'" width="450" height="300"/>');
            
            $('#'+id_previw).append('<img src="'+e.target.result+'" width="450" height="300"/>');
            $('#imagen').val(e.target.result);
            
            
            
        }
        reader.readAsDataURL(input.files[0]);
    } 
    
}
function filePreviewBackground(input,id_previw) {
        if (input.files && input.files[0]) {
        var reader = new FileReader();
        reader.onload = function (e) {
//            $('#'+id_previw+'').parent().remove("img");
            $('#'+id_previw+'').children("img").remove();
            $('#'+id_previw+'').removeAttr("style");
            //$('#'+id_previw).append('<img src="'+e.target.result+'" width="450" height="300"/>');
            $('#'+id_previw).attr("style",'background-image: url("'+e.target.result+'")');
            console.log(e.target.result);
            $('#imagen').val(e.target.result);
            
        }
        reader.readAsDataURL(input.files[0]);
    }
}
function load(nav) {
    select_browser(); 
    
    if(parseInt($("#nro_noti").html())==0){
	$("#nro_noti").attr("style","display:none");	
     }
else{
	$("#nro_noti").removeAttr("style");  	
 }

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

        if($(this).attr('name') != undefined){
            link($(this).attr('name'), $(this).attr('id'), '');
        }
    });
//    $('[data-toggle="tooltip"]').tooltip();
//    $('[data-toggle="popover"]').popover();

    // SETEO DE VARIABLES PARA ORDENAMIENTO DE COLUMNAS EN DATATABLE
    var ordering = [0, 'desc'];
    var col_ordering = $('#col-ordering').attr('value');
    var way_ordering = $('#way-ordering').attr('value');
    
    if(col_ordering !== undefined && way_ordering !== undefined){
        ordering = [parseInt(col_ordering), way_ordering];
    }
    
    //console.log(ordering);case 1550369: $codigo_pariente=1007375413;break; #nponce
    var table;
    if ($('#dataTable').children("thead").length > 0) {
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
    if($.fn.DataTable!=null){
        $.fn.DataTable.ext.pager.numbers_length = 4;
        $.fn.dataTable.ext.errMode = 'none';
    }
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
//    escuchar_checkboxes();
    escuchar_mensaje_log();
    escuchar_paginador();
    if(nav==null){
        dropdown(null);
        $("#miFormulario").html(mensaje_error);
        $("#main").html(mensaje_error);
    }
    popLogout();
}

function link(nav, id, pagina_a_mostrar) {
    if($("#miFormulario").attr("action")!=null){
	console.log("encontre action en el form no se usa ajax| cobro_digital.js linea 187");
	$("#miFormulario").submit();
	return false;
	}
	
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
            $("#main").html(mensaje_error);
            load(null);
        }
    });
}

function link_logout(nav, id, pagina_a_mostrar) {
    var checkboxes_string = obtener_checkboxes_sin_tildar();
    var parametros = {
        "nav": nav,
        "pagina": 'ceNdhCRHQk66LNJ3xBs0/g==',
        "id": id,
        "logout_post": 'logout_post',
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
            despues_de_navegar_logout(response);
            load(nav);
        },

        error: function () {
            load(null);
            $("#main").html(mensaje_error);
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
        if (typeof $("[type=date]").datepicker !== "undefined") { 
    // safe to use the function
        $("[type=date]").datepicker({
            dateFormat: 'yy-mm-dd'
        });
    }
}

function formatCurrency(total) {
//    return total;
    var neg = false;
    if(total < 0) {
        neg = true;
//        total = Math.abs(total);
    }
//    console.log(total);
//    alert(total.toString().replace(".",","));
//    total=total.toString().replace(".",",");
//    total=parseFloat(total).toFixed(2).toString().replace(".",",");
    
    //return '$' + parseFloat(total, 10).toFixed(2).replace(".",",").replace(/(\d)(?=(\d{3})+\.)/g, "$1.").toString();
    return '$' + parseFloat(total, 10).toFixed(2).replace(".",",").replace(/(\d)(?=(\d\d\d)+(?!\d))/g, "$1.").toString();
//    return (neg ? "-$" : '$') + total.toFixed(2).replace(/(\d)(?=(\d{3})+\.)/g, "$1.");
//    return total;
}

function floatFormat(number){
    number = number.replace(/[.]/g, '');
    number = number.replace(',','.');
    return number;
}


function link_json(nav, id, id_elemento_a_reemplazar, fx) {
    var checkboxes_string = obtener_checkboxes_sin_tildar();
    var parametros = {
        "nav": nav,
        "id": id,
        "json": 1,
        "data": $("#miFormulario").serialize() + checkboxes_string
    };

    $.ajax({
        data: parametros,
        url: url_servidor,
        type: 'post',
        beforeSend: function () {
            // Hacer esto si el pedido supera determinado tiempo!
            //$("#"+id_elemento_a_reemplazar).html(cargando);
        },
        success: function (response) {
//            $("#" + id_elemento_a_reemplazar).html(response);
            load(nav);
            if (fx) {
                fx();
            }
            return response;

        },

        error: function () {
            $("#main").html(mensaje_error);
        }
    });
}

// MODALES

function show_modal_success(titulo, texto, boton){
    Swal.fire({
        title: titulo,
        text: texto,
        type: 'success',
        showLoaderOnConfirm: true,
        confirmButtonText: boton
    });
}

function show_modal_error(titulo, texto, boton){
    Swal.fire({
        title: titulo,
        text: texto,
        type: 'error',
        showLoaderOnConfirm: true,
        confirmButtonText: boton
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
            $("#main").html(mensaje_error);
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
            var a=response;//.getResponseHeader('Location');
            //console.log(a);
            despues_de_navegar(response);
            load(nav);

        },

        error: function () {
             
            $("#main").html(mensaje_error);
        }
    });
}
function file_link_reemplazo(nav, nombre_archivo, id_archivo,id_elemento_a_reemplazar) {
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
//            antes_de_navegar();
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
            $("#main").html(mensaje_error);
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
    $("#main").html(cargando);
    $(".debug").remove();
    // Hacer esto si el pedido supera determinado tiempo!
}
function isJson(str) {
    console.log('ENTRO');
    try {
        JSON.parse($.trim(str));
    } catch (e) {
        console.log(e);
        return false;
    }
    return true;
}
function decode_base64 (s)
{
    var e = {}, i, k, v = [], r = '', w = String.fromCharCode;
    var n = [[65, 91], [97, 123], [48, 58], [43, 44], [47, 48]];

    for (z in n)
    {
        for (i = n[z][0]; i < n[z][1]; i++)
        {
            v.push(w(i));
        }
    }
    for (i = 0; i < 64; i++)
    {
        e[v[i]] = i;
    }

    for (i = 0; i < s.length; i+=72)
    {
        var b = 0, c, x, l = 0, o = s.substring(i, i+72);
        for (x = 0; x < o.length; x++)
        {
            c = e[o.charAt(x)];
            b = (b << 6) + c;
            l += 6;
            while (l >= 8)
            {
                r += w((b >>> (l -= 8)) % 256);
            }
         }
    }
    return r;
}
function despues_de_navegar(response) {
//    console.log(destino_respuesta_servidor);
   
    if(isJson(response)){
        var res=JSON.parse($.trim(response));
        var view=decode_base64(res.view);
        
        $(destino_respuesta_servidor).html(view);
        if(("reload" in res)){
            var reload=res.reload;
//            $("html").html(view);
            switch (reload){
                case 1 : 
                    location.href = location.href
                    break;
                case 2 : 
                    showPopExpiredSession();
                    break;
            }
//            document.write(view);
        }
    }
    else{
//        console.log(response);
        $(destino_respuesta_servidor).html(response);
        despues_de_cargar();
    }
    var nav=$("#nav_marchand").attr("name");
    var id = null
    var elemento="minirs";
    if(cargar){
        $("#minirs").html("Cargando");
        link_reemplazo(nav,id,elemento);
    }
}
var cargar=true;
function despues_de_navegar_logout(response) {
    //console.log(destino_respuesta_servidor);
   
    if(isJson(response)){
        var res=JSON.parse($.trim(response));
        var view=decode_base64(res.view);
        //$(destino_respuesta_servidor).html(view);
        if(("reload" in res)){
            var reload=res.reload;
//            $("html").html(view);
            switch (reload){
                case 1 : 
                    location.href = location.href
                    break;
                case 2 : 
                    showPopExpiredSession();
                    break;
            }
//            document.write(view);
        }
    }
    else{
        //$(destino_respuesta_servidor).html(response);
        despues_de_cargar();
    }
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

function validate_2_dates(desde, hasta){
    date_desde = new Date(desde);
    date_hasta = new Date(hasta);
    if(desde !== "" && hasta !== "")
        return desde <= hasta;
    return true;
}


function formatFactory(html) {
    function parse(html, tab = 0) {
        var tab;
        var html = $.parseHTML(html);
        var formatHtml = new String();   

        function setTabs () {
            var tabs = new String();

            for (i=0; i < tab; i++){
              tabs += '\t';
            }
            return tabs;    
        };


        $.each( html, function( i, el ) {
            if (el.nodeName == '#text') {
                if (($(el).text().trim()).length) {
                    formatHtml += setTabs() + $(el).text().trim() + '\n';
                }    
            } else {
                var innerHTML = $(el).html().trim();
                $(el).html(innerHTML.replace('\n', '').replace(/ +(?= )/g, ''));
                

                if ($(el).children().length) {
                    $(el).html('\n' + parse(innerHTML, (tab + 1)) + setTabs());
                    var outerHTML = $(el).prop('outerHTML').trim();
                    formatHtml += setTabs() + outerHTML + '\n'; 

                } else {
                    var outerHTML = $(el).prop('outerHTML').trim();
                    formatHtml += setTabs() + outerHTML + '\n';
                }      
            }
        });

        return formatHtml;
    };   
    
    return parse(html.replace(/(\r\n|\n|\r)/gm," ").replace(/ +(?= )/g,''));
}; 
