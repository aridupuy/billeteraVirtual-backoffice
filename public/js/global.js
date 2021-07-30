//MENU
var ordering = [0, 'desc'];
var col_ordering = $('#col-ordering').attr('value');
var way_ordering = $('#way-ordering').attr('value');

if (col_ordering !== undefined && way_ordering !== undefined) {
    ordering = [parseInt(col_ordering), way_ordering];
}

var datatable_config = {
    "paging": true,
    "language": {
        "lengthMenu": "Ver _MENU_ Registros por pagina",
        "info": "Viendo _START_ al _END_ de _TOTAL_ Registros",
        "zeroRecords": "No hay registros para mostrar",
        "paginate": {
            "next": "Siguiente",
            "previous": "Anterior"
        }
    },
    columnDefs: [{
            targets: 'dateorder',
            sType: 'date-arg'
        }

    ],
    "sPaginationType": "full_numbers",
    "lengthMenu": [
        [10, 25, 50, 100, -1],
        [10, 25, 50, 100, "Todos"]
    ],
    "lengthChange": true,
    "ordering": true,
    "order": ordering,
    "info": true,
    "searching": false,
    "scrollCollapse": true

};
var flag = false;


//SCROLL LINKS
function scrollLinks() {
    jQuery(document).on('click', '.anchor', function (event) {
        event.preventDefault();

        jQuery('html, body').animate({
                scrollTop: jQuery(jQuery.attr(this, 'href')).offset().top
            }, 500),
            setTimeout(function () {
                $('.menu.sticky').slideUp("slow");
            }, 1000);
    });
}

//STICKY HEADER
function stickyHeader() {
    var altura = $('body').offset().top;

    $(window).on('scroll', function () {
        if ($(window).scrollTop() > altura) {
            $('header').addClass('sticky');
        } else {
            $('header').removeClass('sticky');
        }
    });
}

//FUNCIONAMIENTO LINKS MENU
function navActive() {
    $('nav ul li').click(function () {
        $(this).addClass('active');
        $(this).siblings('nav ul li').removeClass('active');
    });
}

//MOSTRAR ELEMENTOS CON ANIMACIÓN
function itemsShowup() {
    $(window).scroll(function () {

        var wScroll = $(this).scrollTop();

        $('.bottom-up, .left-right, .right-left, .zoom-in').each(function () {
            if (wScroll > $(this).offset().top - 600) {
                $(this).addClass('showing');
            } else {
                if (wScroll < $(this).offset().top - 800) {
                    $(this).removeClass('showing');
                }
            }
        });
    });
}

//FUNCIONAMIENTO MENU THIRD / ACORDEÓN
function menuThird() {
    var contador = 1;

    $('.trigger-third').click(function () {
        if (contador == 1) {
            $(this).addClass('active');
            $(this).children('.acordeon').addClass('active');
            contador = 0;
        } else {
            contador = 1;
            $(this).removeClass('active');
            $(this).children('.acordeon').removeClass('active');
        }
    });
}

//POPUP LOGOUT
function popLogout() {
    $('.header-logout').click(function () {
        Swal.fire({
            text: '¿Desea salir del sistema?',
            type: 'question',
            confirmButtonText: 'Si',
            confirmButtonColor: '#218838',
            cancelButtonText: 'No',
            cancelButtonColor: '#d33',
            showCancelButton: true
        }).then(function (result) {
            if (result.value) {
                link_logout('', '', '');
            }
        });
    });


    //    $('.trigger-logout').click(function () {
    //        $('#pop-logout').addClass('active');
    //    });
    //
    //    $('.ctas-pop-up .button').click(function () {
    //        $('#pop-logout').removeClass('active');
    //    });
}

function popNoti(cuerpo, title) {
    $('#popNoti').addClass('active');
    var titulo = $('#popNoti').children().children(".titulo-not-importante");
    var text = $('#popNoti').children().children(".contenido-not-importante");
    $(titulo).html(' <h6>Notificacion</h6> <h5><span class="icono-importante"><img src="public/img/icono-importante.svg"></span>' + title + '</h5></div>');
    //$(a);
    $(text).html(cuerpo);
    $('.ctas-pop-up .button').click(function () {
        $('#pop-logout').removeClass('active');
    });
    $("#cerrar").click(function () {
        $("#popNoti").removeClass("active");
    });
    $("#popNoti a").click(function (e) {
        var a = e.preventDefault();
        $("#popNoti").removeClass("active");
        return a;
    });

}
//POPUP LOGOUT
function cerrarPopup() {

    $('.cerrar-popup').click(function () {
        $(this).parents('.contenedor-pop-up').removeClass('active');
    });
}

//SIDEBAR TABLET
function sidebarTablet() {
    var contador = 1;

    $('.solapa-sidebar').click(function () {
        if (contador == 1) {
            $('.sidebar').addClass('active');
            contador = 0;
        } else {
            contador = 1;
            $('.sidebar').removeClass('active');
        }
    });

    //CLOSE SIDEBAR
    $('.second .button, .third .button').click(function () {
        if ($('.sidebar').hasClass('active')) {
            contador = 1;
            $('.sidebar').removeClass('active');
        }
    });
}

//ICONO SIDEBAR ROJO
function iconoSidebar() {
    $('.primary .desplegable').mouseover(function () {
        $(this).parent('.primary').find('.icono-sidebar').css({
            'filter': 'none'
        });
    });
    $('.primary .desplegable').mouseout(function () {
        $(this).parent('.primary').find('.icono-sidebar').css({
            'filter': 'grayscale(100%) brightness(150%)'
        });
    });
    $('.primary').mouseover(function () {
        $(this).find('.icono-sidebar').css({
            'filter': 'none'
        });
    });
    $('.primary').mouseout(function () {
        $(this).find('.icono-sidebar').css({
            'filter': 'grayscale(100%) brightness(150%)'
        });
    });
}

//SWITCH ACTIVAR-DESACTIVAR
function switchActivar() {
    $('.accion-activar').click(function () {
        $(this).children('span').toggleClass('active');
    });
}

//DESPLEGAR CUENTA BANCARIA
function desplegarCuenta() {
    $('.cuenta-bancaria .accion-desplegar').click(function () {
        $(this).parents('.titulo-cuenta-bancaria').siblings('.detalles-cuenta-bancaria').toggleClass('active');
        $(this).toggleClass('active');
    });
}

//DESPLEGAR USUARIO
function desplegarUsuario() {
    $('.usuario .accion-desplegar').click(function () {
        $(this).parents('.titulo-usuario').siblings('.detalles-usuario').toggleClass('active');
        $(this).toggleClass('active');
    });
}

//SELECCIONAR CATEGORÍA PERMISOS
function categoriaPermisos() {
    $('.cat-permisos-usuario').click(function () {
        $(this).siblings('.cat-permisos-usuario').removeClass('activo');
        $(this).addClass('activo');
    });
}
var i = 0;

function pantallaNotificaciones() {
    {
        if (parseInt($("#nro_noti").html()) == 0) {
            $("#nro_noti").attr("style", "display:none");
        } else {
            $("#nro_noti").removeAttr("style");
        }
        if (flag == false) {
            $('.categorias-notificaciones ul li').click(function () {
                $(this).addClass('active');
                $(this).siblings().removeClass('active');
                var a = $(this).children("div").children("input")
                var name = a.attr("name");
                var value = a.attr("id");
                var nav = $('.categorias-notificaciones input[data-nav="nav"]').attr("name");
                //uso el name que se hashea

                link_reemplazo(nav, name, "caja_notis", function () {
                    flag = true;
                    pantallaNotificaciones_acciones();
                });

            });
        }
    }





    //    $('').click(function () {
    //        $(this).toggleClass('active');
    ////        alert(this);
    ////        link_reemplazo(nav, name, false, function () {
    //////          pantallaNotificaciones();
    ////            modifiNotification(object, nro_noti);
    ////            modifiNotification_img($("#img-noleido"));
    ////        });
    //        
    //    });




}

function pantallaNotificaciones_acciones() {
    $(".icono-not").click(function () {
        $(this).toggleClass('active');
        let nav = $(this).attr("name");
        let name = $(this).attr("name");
        let id = $(this).attr("data-id");
        var nro_noti = $("#nro_noti").html();
        var object = $(this);
        link_reemplazo(nav, id, false, function () {
            //          pantallaNotificaciones();
            //            modifiNotification(object, nro_noti);
            modifiNotification_img($(object));
        });
    });
    $('.titulo-not').click(function () {
        console.log($(this).parents(".header-not").children(".cerrar-not"));
        if (!$(this).parent(".header-not").children(".cerrar-not").hasClass("active")) {
            $(this).parents('.notificacion').siblings().children('.cuerpo-not').removeClass('active');
            $(this).parents('.header-not').siblings('.cuerpo-not').addClass('active');

            let nav = $("#titulo_not").attr("name");
            let val = $("#ver_mas").attr("name");
            //            alert(val);C
            let nro_noti = $("#nro_noti").html();
            let object = $(this);
            //            if()
            if ($(this).siblings(".not-izq").children("#img-noleido").hasClass("active")) {
                link_reemplazo(nav, val, false, function () {
                    //                pantallaNotificaciones();
                    modifiNotification(object, nro_noti);
                    //                modifiNotification_img($("#img-noleido"));
                });
            }
        }
    });
    $('.categorias-notificaciones ul li').click(function () {
        $(this).addClass('active');
        $(this).siblings().removeClass('active');
    });

    $('.titulo-not').click(function () {
        $(this).parents('.notificacion').siblings().children('.cuerpo-not').removeClass('active');
        $(this).parents('.header-not').siblings('.cuerpo-not').addClass('active');
    });
    $('.cerrar-not').click(function () {
        $(this).parents('.cuerpo-not').removeClass('active');
    });
    $('.not-importante .btn-aceptar').click(function () {
        $(this).parents('.not-importante').removeClass('active');
    });
}
//NOTIFICACIONES ORIGINAL
//function pantallaNotificaciones() {

function modifiNotification(object, nro_total) {

    var categoria = $(object).parent().parent().siblings(".categorias-notificaciones").children("ul").children(".active");
    var noleido = $(object).siblings(".not-izq").children("#img-noleido").filter(".active").length;
    var nro_noleidas = $("#nro_no_leido").html();
    var nro_leidas = $("#nro_leidas").html();
    if (noleido > 0) {
        $("#nro_no_leido").html(parseInt(nro_total) - 1);
        $("#nro_noti").html(parseInt(nro_total) - 1);
        $("#nro_leidas").html(parseInt(nro_leidas) + 1);
        $(object).siblings(".not-izq").children("#img-noleido").toggleClass('active');

    }
}

function modifiNotification_img(object) {
    var id = object.attr("class");
    var activo = $(object).filter(".active").length;
    switch (id) {
        case 'icono-not leido-not active':
        case 'icono-not leido-not':
            if (activo != 0) {
                let no_leidas = parseInt($("#nro_no_leido").html());
                $("#nro_no_leido").html(parseInt($("#nro_no_leido").html()) + 1);
                $("#nro_leidas").html(parseInt($("#nro_leidas").html()) - 1);
                $("#nro_noti").html(parseInt(no_leidas) + 1);

            } else {
                let no_leidas = parseInt($("#nro_no_leido").html());
                if (parseInt($("#nro_no_leido").html()) > 0 && parseInt($("#nro_noti").html()) > 0) {
                    $("#nro_no_leido").html(parseInt($("#nro_no_leido").html()) - 1);
                    $("#nro_leidas").html(parseInt($("#nro_leidas").html()) + 1);
                    $("#nro_noti").html(no_leidas - 1);
                }
            }
            break;
        case 'icono-not estrella-not active':
        case 'icono-not estrella-not':
            if (activo != 0) {
                $("#nro_destacadas").html(parseInt($("#nro_destacadas").html()) + 1);
            } else {
                if (parseInt($("#nro_destacadas").html()) > 0)
                    $("#nro_destacadas").html(parseInt($("#nro_destacadas").html()) - 1);
            }
            break;
        case 'icono-not archivar-not active':
        case 'icono-not archivar-not':
            if (activo != 0) {
                $("#nro_archivadas").html(parseInt($("#nro_archivadas").html()) + 1);
            } else {
                if (parseInt($("#nro_archivadas").html()) > 0)
                    $("#nro_archivadas").html(parseInt($("#nro_archivadas").html()) - 1);
            }
            break;
    }
    if (parseInt($("#nro_noti").html()) == 0) {
        $("#nro_noti").attr("style", "display:none");
    } else {
        $("#nro_noti").removeAttr("style");
    }
}

//
//
//}

//VER MAS PERMISOS DE USUARIO
function verPermisos() {
    $('.permisos-mas').click(function () {
        $('.item-permiso').removeClass('second');
    });
}

//FILTROS USUARIOS
function filtrosUsuarios() {
    $('.filtro-usuarios div').click(function () {
        $(this).siblings('.filtro-usuarios div').removeClass('activo');
        $(this).addClass('activo');
    });
}

//OPCIONES ADELANTOS
function opcionesAdelantos() {
    $('.opciones-adelantos #adelanto-transferencia').click(function () {
        $('.adelantos-con-transferencia').addClass('active');
        $('.adelantos-con-cheque').removeClass('active');
        $('#adelanto-cheque').attr('checked', false);
    });
    $('.opciones-adelantos #adelanto-cheque').click(function () {
        $('.adelantos-con-cheque').addClass('active');
        $('.adelantos-con-transferencia').removeClass('active');
        $('#adelanto-transferencia').attr('checked', false);
    });
}

//OPCIONES TARJETA DE COBRANZA
function opcionesTarjeta() {
    $('.total-tarjetas #plastica').click(function () {
        console.log('plastica');
        $('#codigo').attr('checked', false);
    });
    $('.total-tarjetas #codigo').click(function () {
        console.log('codigo');
        $('#plastica').attr('checked', false);
    });
}

//BOTON DE PAGO - MEDIOS DE PAGO
function mediosBtnPago() {
    $('.opcion-madre .checkbox-medios-btn-pago').click(function () {
        if ($(this).is(":checked")) {
            $(this).parents('.grupo-opciones').find('.contenedor-opciones-medios .opcion .checkbox-medios-btn-pago').attr('checked', true);
        } else {
            $(this).parents('.grupo-opciones').find('.contenedor-opciones-medios .opcion .checkbox-medios-btn-pago').attr('checked', false);
        }
    });
}

//BOTON DE PAGO - OPCION COLOR
function colorBtnPago() {
    $('.opcion-color').click(function () {
        $(this).addClass('active');
        $(this).siblings('.opcion-color').removeClass('active');
    });
}

//BOTÓN DE PAGO - CAMPOS
function camposBtnPago() {

}

//LISTADO DE MOVIMIENTOS
function listadoMovimientos() {
    $('#movimientos-resumen').click(function () {
        $(this).addClass('activa');
        $('#movimientos-detalle').removeClass('activa');
        $('#resumen-movimientos').show();
        $('#detalle-movimientos').hide();
        $('.nota-tabla-detalles').hide();
    });
    $('#movimientos-detalle').click(function () {
        $(this).addClass('activa');
        $('#movimientos-resumen').removeClass('activa');
        $('#resumen-movimientos').hide();
        $('#detalle-movimientos').show();

        $('.nota-tabla-detalles').show();
    });

    //Mostrar/ocultar detalle
    $('.mostrar-detalle').click(function () {
        $(this).toggleClass('activo');
        $(this).parents('tr').next('.desplegable-detalle').toggleClass('visible');
    });
    $('.ocultar-detalle').click(function () {
        $(this).parents('.desplegable-detalle').removeClass('visible');
        $(this).parents('tr').prev('tr').find('.mostrar-detalle').removeClass('activo');
    });
    $('#desplegar-detalle-todos').click(function () {
        if ($(this).is(":checked")) {
            $('.desplegable-detalle').addClass('visible');
            $('.mostrar-detalle').addClass('activo');
        } else {
            $('.desplegable-detalle').removeClass('visible');
            $('.mostrar-detalle').removeClass('activo');
        }

    });
}

//ESTADO DE LAS COBRANZAS
function estadoCobranzas() {
    $('.tabs-tabla-cobranzas .tab').click(function () {
        $(this).addClass('activa');
        $(this).siblings('.tab').removeClass('activa');
    });
    $('.tab#cobranzas-todos_tab').click(function () {
        $('table#cobranzas-todos').siblings(".dataTables_wrapper").hide()
        $('div#cobranzas-todos_wrapper').siblings('.dataTables_wrapper').hide();
        $('table#cobranzas-todos').show();
        $('div#cobranzas-todos_wrapper').show();
        $('table#cobranzas-todos').siblings('table').hide();
        //dataTables_wrapper no-footer
    });
    $('.tab#cobranzas-cobrados_tab').click(function () {
        $('table#cobranzas-cobrado').siblings(".dataTables_wrapper").hide()
        $('div#cobranzas-cobrado_wrapper').siblings('.dataTables_wrapper').hide();
        $('table#cobranzas-cobrado').show();
        $('div#cobranzas-cobrado_wrapper').show();
        $('table#cobranzas-cobrado').siblings('table').hide();

    });
    $('.tab#cobranzas-pendientes_tab').click(function () {
        $('table#cobranzas-pendiente').siblings(".dataTables_wrapper").hide()
        $('div#cobranzas-pendiente_wrapper').siblings('.dataTables_wrapper').hide();
        $('table#cobranzas-pendiente').show();
        $('div#cobranzas-pendiente_wrapper').show();
        $('table#cobranzas-pendiente').siblings('table').hide();
    });
    $('.tab#cobranzas-vencidos_tab').click(function () {
        $('table#cobranzas-vencido').siblings(".dataTables_wrapper").hide()
        $('div#cobranzas-vencido_wrapper').siblings('.dataTables_wrapper').hide();
        $('table#cobranzas-vencido').show();
        $('div#cobranzas-vencido_wrapper').show();
        $('table#cobranzas-vencido').siblings('table').hide();

    });
    $('.tab#cobranzas-rechazados_tab').click(function () {
        $('table#cobranzas-rechazado').show();
        //        $('table#cobranzas-rechazado').siblings('table').hide();
        $('table#cobranzas-rechazado').siblings(".dataTables_wrapper").hide()
        $('div#cobranzas-rechazado_wrapper').siblings('.dataTables_wrapper').hide();
        $('div#cobranzas-rechazado_wrapper').show();
        $('table#cobranzas-rechazado').siblings('table').hide();
    });
    $('.tab#cobranzas-reversados_tab').click(function () {
        $('table#cobranzas-reversado').siblings(".dataTables_wrapper").hide()
        $('div#cobranzas-reversado_wrapper').siblings('.dataTables_wrapper').hide();
        $('table#cobranzas-reversado').show();
        $('div#cobranzas-reversado_wrapper').show();
        //$('table#cobranzas-reversado').siblings('table').hide();
        $('div#cobranzas-reversado_wrapper').show();
    });

    //Popups Filtros Tablas
    $('.trigger-filtros-tabla#todos').click(function () {
        $('.pop-up-filtros-tabla#todos').show();
    });

    $('.pop-up-filtros-tabla .btn-aplicar-filtros').click(function () {
        $(this).parents('.pop-up-filtros-tabla').hide();
    });

    $('.pop-up-filtros-tabla .cerrar-popup').click(function () {
        $(this).parents('.pop-up-filtros-tabla').hide();
    });
}

//INPUTS "FILE"
function inputsFile() {
    //$('.carga-file').hide();
    $('.contenedor-file').on("click", function () {
        $(this).siblings('.carga-file').trigger("click");
    });
}

//BOTON DE PAGO - OPCION COLOR
function colorBtnPago() {
    $('.opcion-color').click(function () {
        $(this).addClass('active');
        $(this).siblings('.opcion-color').removeClass('active');
        var bgcolor = $(this).css("background");
        var color = $(this).css("color");
        $('.preview-btn').css({
            'background': bgcolor
        });
        $('.preview-btn').css({
            'color': color
        });
        $("#color").val(color);
    });
}

//BOTÓN DE PAGO - CAMPOS
function camposBtnPago() {
    $('.btn-agregar-campo').click(function () {
        $('.popup-campos').toggleClass('visible');
    });

    $('.popup-campos ul li').click(function () {
        $(this).hide();
        $('.popup-campos').toggleClass('visible');
    });

    $(".btn-pago-nombre").hide();
    $(".btn-pago-apellido").hide();
    $(".btn-pago-email").hide();
    $(".btn-pago-documento").hide();
    $(".btn-pago-direccion").hide();
    $(".btn-pago-vencimiento").hide();
    $(".btn-pago-comentarios").hide();
    var params = $("#JSON").val();
    //    console.log(params);
    $("input[type='checkbox'").click(function () {
        var input = $("input[type='checkbox'").parent().children("input[type='text']");
        input.attr('disabled', false);
        $(this).attr("value", "1");
    });
    $("#file").change(function () {
        $(".preview-img").show();
        filePreview(this, "preview-img");
    });

    $("#nom").click(function () {
        $("#solicitar_nombre").click();
    })
    $("#apel").click(function () {
        $("#solicitar_apellido").click();
    })
    $("#emil").click(function () {
        $("#solicitar_correo").click();
    })
    $("#comm").click(function () {
        $("#solicitar_comentario").click();
    })
    $("#doc").click(function () {
        $("#solicitar_documento").click();
    })
    $("#dir").click(function () {
        $("#solicitar_direccion").click();
    })
    $("#fechvto").click(function () {
        $("#precargar_fecha_vencimiento").click();
    })


    $('.popup-campos ul li').click(function () {
        $(this).hide();
        $('.popup-campos').toggleClass('visible');
    });

    $('.insertar-campo-nombre').click(function () {
        $('.btn-pago-nombre').fadeIn();
    });
    $('.insertar-campo-apellido').click(function () {
        $('.btn-pago-apellido').fadeIn();
    });
    $('.insertar-campo-email').click(function () {
        $('.btn-pago-email').fadeIn();
    });
    $('.insertar-campo-direccion').click(function () {
        $('.btn-pago-direccion').fadeIn();
    });
    $('.insertar-campo-documento').click(function () {
        $('.btn-pago-documento').fadeIn();
    });
    $('.insertar-campo-vencimiento').click(function () {
        $('.btn-pago-vencimiento').fadeIn();
    });
    $('.insertar-campo-comentarios').click(function () {
        $('.btn-pago-comentarios').fadeIn();
    });

    $(".opcion-color").click(function () {
        var val = $(this).attr("style");
        $("#color").val(val);
    })
    $(".contenedor-img-btn-pago .btn-examinar").click(function () {
        $("#file").click();
    });
    $("#titulo_input").keyup(function (e) {
        var val = $(this).val();
        $("#titulo").html("");
        $("#titulo").append(document.createTextNode(val.toString()));
    });
    $("#importe_input").keyup(function (e) {
        var val = $(this).val();
        $("#importe").html(val);
    });
    $("#boton_texto").keyup(function (e) {
        $("#boton_aceptar").val($(this).val());
    });

}

//BOTÓN DE PAGO - FORMATO
function formatoBtnPago() {
    //    $('.opcion-color').click(function () {
    //        alert("ntmágp");
    //
    //    });
    $("select#forma-btn").change(function () {
        var formaBtn = $(this);
        var selectedForma = $(this).find('option:selected').attr("data-value");
        $('#boton_aceptar').css({
            'border-radius': selectedForma
        });
    });

    $("select#size-btn").change(function () {
        var sizeBtn = $(this);
        var selectedWidth = $(sizeBtn, 'option:selected').val();
        var selectedPadding = $(this).find('option:selected').attr('pad');
        var selectedFSize = $(this).find('option:selected').attr('fsize');


        $('#boton_aceptar').css({
            'width': selectedWidth
        });
        $('#boton_aceptar').css({
            'padding': selectedPadding
        });
        $('#boton_aceptar').css({
            'font-size': selectedFSize
        });
    });


    $("#fuentes").change(function () {
        var formaBtn = $(this);
        var selectedForma = $(this).find('option:selected').attr("data-value");
        $('#boton_aceptar').css({
            'font-family': selectedForma
        });
    });
}

function goodModals() {
    var msj = $('#texto').attr('title');
    if (msj != "")
        if (msj != undefined) {
            //alert(msj);
            msj = $.parseJSON(msj);

            Swal.fire(
                msj
            );
        }

}

function despues_de_cargar() {
    scrollLinks();
    stickyHeader();
    navActive();
    itemsShowup();
    menuThird();
    popLogout();
    cerrarPopup();
    sidebarTablet();
    iconoSidebar();
    switchActivar();
    desplegarCuenta();
    desplegarUsuario();
    categoriaPermisos();
    pantallaNotificaciones();
    verPermisos();
    filtrosUsuarios();
    opcionesAdelantos();
    opcionesTarjeta();
    formatoBtnPago();
    colorBtnPago();
    camposBtnPago();
    listadoMovimientos();
    estadoCobranzas();
    inputsFile();
    goodModals();
}
$(document).ready(function () {
    despues_de_cargar();
});