/**
 * interno_util_cv.js - Cobro Digital
 * 
 * @description Biblioteca de funciones para el modulo interno/util_cv.home.html
 * @author Juampa
 * @date Julio 2020
 * @last-update: 2021-02-24
 */


//MANEJO DE INTERFACES
//MANEJO DEL CHECKBOX DE SELECCION
/**
 * Tilda todos los checkbox de seleccion de fila de la tabla principal
 * @param {object} elemento DOM que dispara la funcion
 * @returns {void}
 */
function seleccionarTodosLosItems(elemento){
   var listado = tabla.rows().nodes();

   if($(elemento).attr("checked") === 'checked'){   
        for(i=0; i < listado.length; i++){
            listado[i].firstChild.childNodes[0].removeAttribute("value");
            listado[i].firstChild.childNodes[0].removeAttribute("checked");
            listado[i].firstChild.childNodes[0].checked = false;
            descolorearFila(listado[i]);            
        }              
   }else{
        for(i=0; i < listado.length; i++){
            listado[i].firstChild.childNodes[0].setAttribute("checked", "checked");
            listado[i].firstChild.childNodes[0].checked = true;
            colorearFila(listado[i]);
        }
   }
}

/**
 * Destilda todos los checkbox de seleccion de fila de la tabla principal
 * @returns {void}
 */
function destildarTodosLosItems(){
    var listado = tabla.rows().nodes();

    for(i=0; i < listado.length; i++){
            listado[i].firstChild.childNodes[0].removeAttribute("value");
            listado[i].firstChild.childNodes[0].removeAttribute("checked");
            listado[i].firstChild.childNodes[0].checked = false;
            descolorearFila(listado[i]);
    }
}

/**
 * Destilda el checkbox del header de la tabla principal
 * @returns {void}
 */
function destildarItemHeader(){
    $("tr>th>input:nth-child(1)")[0].removeAttribute("checked");
    $("tr>th>input:nth-child(1)")[0].checked = false;
}


//MANEJO DE MODAL
/**
 * Activa y muestra un modal en pantalla
 * @param {string} selector Selector de modal a mostrar
 * @param {boolean} agregarBtnCierre Agregar listener de cierres al modal
 * @returns {void}
 */
function mostrarModal(selector, agregarBtnCierre = true){
    //event.preventDefault();
    modal = document.querySelector(selector);
    html = document.querySelector('html');
    modal.classList.add('is-active');
    html.classList.add('is-clipped');

    if(agregarBtnCierre === true){
        modal.querySelector('.delete').addEventListener('click', cerrarModal);
        modal.querySelector('#modal-btn-cancelar').addEventListener('click', cerrarModal);
    }
}

/**
 * Desactiva y cierra un modal en pantalla
 * @param {string|object} modal Elemento que llama a la funcion (object) o un selector del modal (string)
 * @returns {void}
 */
function cerrarModal(modal){
    if(typeof modal === 'string'){
        document.querySelector(modal).classList.remove('is-active');
        htmlSeleccionado = document.querySelector('html').classList.remove('is-clipped');
    }else{
        modal.preventDefault();
        $($("div.modal.is-active")[0]).removeClass("is-active");
        html.classList.remove('is-clipped');     
    }
}

/**
 * Activa y muestra un mensaje en pantalla.
 * Nota: el contenido y estilo se debe editar de manera aparte. Se supone siempre un mensaje a la vez.
 * @returns {void}
 */
function mostrarMensaje(){
    mensaje = document.querySelector('.message');
    mensaje.querySelector('.delete').addEventListener('click', cerrarMensaje);
    mensaje.classList.remove('mostrarAnimacion');
    mensaje.offsetWidth; //Necesario para volver a correr la animacion, es js.
    mensaje.classList.add('mostrarAnimacion');
}

/**
 * Desactiva y cierra un mensaje en pantalla.
 * Nota: presupone que se encuentra definica la variable mensaje. Definida por mostrarMensaje().
 * @param {object} elemento DOM que llama a la funcion
 * @returns {void}
 */
function cerrarMensaje(elemento){
    elemento.preventDefault();
    mensaje = document.querySelector('.message');
    mensaje.classList.remove('mostrarAnimacion');
}

/**
 * Muestra un modal con formato para error
 * @param {String} titulo Tipo de error
 * @param {String} error Texto descriptivo del error
 * @returns {void}
 */
function mostrarModalError(titulo = "¡Hubo un error!", error = "Ocurrió un error en la operación"){
    //listado = $("tr>td>input:checked");
    $('#modal-mensaje .modal-card-body .content').text('');
    var mensaje = "<span class='icon'><i class='fa fa-times-circle has-text-danger-dark fa-4x'><\/i><\/span>\n\
    <h4 style='margin-top:25px;'>"+titulo+"<\/h4>\n\
    <p>"+error+"<\/p>";
    $('#modal-mensaje .modal-card-body .content').append(mensaje);
    mostrarModal('#modal-mensaje');
}

/**
 * Muestra un modal para error de tipo de seleccion en retener movimientos seleccionados.
 * @returns {void}
 */
function mostrarErrorTipoSeleccionados(){
    $('#modal-mensaje .modal-card-body .content').text('');
    var mensaje = "<span class='icon'><i class='fa fa-times-circle has-text-danger-dark fa-4x'><\/i><\/span>\n\
        <h4 style='margin-top:25px;'>¡No se puede procesar las retenciones!<\/h4>\n\
        <p>El tipo de operaci\u00f3n es incorrecto<\/p>";
    $('#modal-mensaje .modal-card-body .content').append(mensaje);
    mostrarModal('#modal-mensaje');
}

/**
 * TO-DO: RESCRIBIR PARA HACERLO MÁS GENERAL
 * Muestra un modal para procesamientos correcto. 
 * @param {string} mensaje Mensaje a mostrar en el modal
 * @returns {void}
 */
function mostrarOKProcesamiento(mensaje="Todos los movimientos se procesaron de manera"){
    $('#modal-mensaje .modal-card-body .content').text('');
    var contenido = "<span class='icon'><i class='fa fa-check-circle has-text-success-dark fa-4x'><\/i><\/span>\n\
        <h4 style='margin-top:25px;'>¡Se proceso correctamente!<\/h4>\n\
        <p>"+mensaje+"<\/p>";
    $('#modal-mensaje .modal-card-body .content').append(contenido);
    mostrarModal('#modal-mensaje');
}

/**
 * Actualiza el estado de los botones de retenciones de las filas.
 * Nota: utiliza la variable cuasi-global: tabla => $('#tabla-datos').DataTable(...) del html->document.ready
 * @param {String} tipo de retencion al cual cargar los datos: iva o gnc
 * @param {String} datos Un objeto json
 * @returns {Boolean}
 */
function actualizarDatosRetencion(datos){
    //tabla.rows("tr[id=25355265]").nodes()[0].childNodes[5].textContent; Maneja nodes
    //$(tabla.rows("tr[id=25355265]").nodes()[0]).find("td#trow-porcentaje-iva");
    //fila = $("tr[id="+datos.id_move+"]"); //Solo encuentra la pagina actual
    var fila = $(tabla.rows("tr[id="+datos.id_move+"]").nodes()[0]);

    //Carga los datos
    var fecha = new Date(datos.fecha_gen);
    var fechaRetencion = fecha.getDate() + "/" + (fecha.getMonth()+01) + "/" + fecha.getFullYear().toString().substr(-2);

    //conversion de tipo
    if(datos.tipo === 'IVA'){
        var tipo = 'iva';
    }else if(datos.tipo === 'GANANCIAS'){
        var tipo = 'gnc';
    }else{
        console.error("El tipo de retencion no es correcto!. No se puede actualizar los datos de retencion.");
        return false;
    }
       
    fila.find("td#trow-fecha-" + tipo).text(fechaRetencion);
    fila.find("td#trow-porcentaje-" + tipo).text(datos.porcentaje_monto);
    fila.find("td#trow-monto-" + tipo).text(parseFloat(datos.monto_retenido).toLocaleString("es-AR"));
    fila.find("td#trow-devolucion-" + tipo).text('No');
    //desactiva el boton de retener
    console.log("Se va actualizar el btn retener");
    fila.find("td#trow-btn-retener-" + tipo + ">boton[data-accion=retener]").attr("disabled", "");
    fila.find("td#trow-btn-retener-" + tipo + ">boton[data-accion=retener]").removeClass("is-primary");
    fila.find("td#trow-btn-retener-" + tipo + ">boton[data-accion=retener]").removeAttr("onclick");
    //activa el boton de devolver
    fila.find("td#trow-btn-devolver-" + tipo + ">boton[data-accion=devolver]").addClass("is-warning");
    fila.find("td#trow-btn-devolver-" + tipo + ">boton[data-accion=devolver]").attr("data-id-move", datos.id_moves_retencion);
    fila.find("td#trow-btn-devolver-" + tipo + ">boton[data-accion=devolver]").attr("onclick","devolver(this)");
    fila.find("td#trow-btn-devolver-" + tipo + ">boton[data-accion=devolver]").removeAttr("disabled");

    return true;       
}


/**
 * Actualiza el estado de los botones de las filas de devoluciones
 * @param {String} datos Un objeto json
 * @returns {Boolean}
 */
function actualizarDatosDevolucion(datos){
    var fila = $(tabla.rows("tr[id="+datos.id_move+"]").nodes()[0]);//$("tr[id="+datos.id_move+"]");
    //conversion de tipo
    if(datos.tipo === 'IVA'){
        var tipo = 'iva';       
    }else if(datos.tipo === 'GANANCIAS'){
        var tipo = 'gnc';
    }else{
        console.error("El tipo de retencion no es correcto!. No se puede actualizar los datos de devolucion.");
        return false;
    }

    //Cambia el No por el Si
    fila.find("td#trow-devolucion-" + tipo).text('S\u00ed');
    //desactiva el boton de devolver
    fila.find("td#trow-btn-devolver-" + tipo + ">boton[data-accion=devolver]").attr("data-id-move-devolucion", datos.id_moves_devolucion);
    fila.find("td#trow-btn-devolver-" + tipo + ">boton[data-accion=devolver]").attr("disabled", "");
    fila.find("td#trow-btn-devolver-" + tipo + ">boton[data-accion=devolver]").removeClass("is-warning");       
    fila.find("td#trow-btn-devolver-" + tipo + ">boton[data-accion=devolver]").removeAttr("onclick");

    return true;       
}

//EJECUCION DE OPERACIONES
/**
 * Realiza retencion de un movimiento. Para ello analisa los atributos data-* del elemento que dispara el evento.
 * @param {object} elemento
 * @returns {boolean}
 */
function retener(elemento){
    //idMove = JSON.stringify([elemento.getAttribute('data-id-move')]);
    var movimiento = [];
    movimiento.push(elemento.getAttribute('data-id-move'));
    var tipoRetencion = elemento.getAttribute('data-tipo-retencion');
    var modulo = elemento.getAttribute('name');
    var datos = {"id-moves": movimiento, "retener": tipoRetencion};

    $.ajax({
    //data: {"prueba":"1", "nav":modulo, "datos":"id-moves="+JSON.stringify(movimiento)+"&retener="+tipoRetencion},
    data: {"prueba":"1", "nav":modulo, "datos":JSON.stringify(datos)},
    url: url_servidor,
    type: 'post',
    beforeSend: function () {
        console.log("ENVIADA SOLICITUD DE RETENCION:" + JSON.stringify(datos));
        $("body").css("cursor", "progress");
        elemento.classList.add('is-loading');
    },
    success: function (response, data, xml) {
        response = response.replace("<json>", "");
        response = response.replace("<\/json>", "");
        respuesta = JSON.parse(response);
        console.log(respuesta);
        $('.message-body').text('');

        if(respuesta.hasOwnProperty('estado') && respuesta.estado === "1"){
            datos = respuesta.datos;
            for (var item of datos) {
                actualizarDatosRetencion(item);
            }
            $('article.message').removeClass('is-danger');
            $('article.message').addClass('is-success');
            $('.message-body').append("Se procesó la retención correctamente.");

        }else{
            $('.message-body').append("No se procesó la retención correctamente.");
            $('article.message').removeClass('is-success');
            $('article.message').addClass('is-danger');
            //$('.modal-card-body').append("No se pudo procesar la retención.");
        }  
        //mostrarModal();
        mostrarMensaje();
        $("body").css("cursor", "default");
        elemento.classList.remove('is-loading');
    },

    error: function () {
        $('.modal-card-body').append("Hubo un error al procesar la solicitud. Comuniquese con el Área de Sistemas");
        $("body").css("cursor", "default");
        elemento.classList.add('is-loading');
    }
    });
}

/**
 * Realiza la devolucion de una retencion de un movimiento. Para ello analisa los atributos data-* del elemento que dispara el evento.
 * @param {object} elemento
 * @returns {void}
 */
function devolver(elemento){

    var movimiento = [];
    movimiento.push(elemento.getAttribute('data-id-move'));
    var tipoRetencion = elemento.getAttribute('data-tipo-retencion');
    var modulo = elemento.getAttribute('name');
    var datos = {"id-moves":movimiento, "devolver":tipoRetencion};
    
    $.ajax({
    //data: {"prueba":"1", "nav":modulo, "datos":"id-moves="+JSON.stringify(movimiento)+"&devolver="+tipoRetencion},
    data: {"prueba":"1", "nav":modulo, "datos":JSON.stringify(datos)},
    url: url_servidor,
    type: 'post',
    beforeSend: function () {
        console.log("ENVIADO SOLICITUD DE DEVOLUCION:" + JSON.stringify(datos));
        $("body").css("cursor", "progress");
        elemento.classList.add('is-loading');
    },
    success: function (response, data, xml) {
        response = response.replace("<json>", "");
        response = response.replace("<\/json>", "");
        respuesta = JSON.parse(response);
        $('.message-body').text('');

        if(respuesta.hasOwnProperty('estado') && respuesta.estado === "1"){
            datos = respuesta.datos;
            for (var item of datos) {
                actualizarDatosDevolucion(item);
            }
            $('article.message').addClass('is-success');
            $('.message-body').append("Se procesó la devolución correctamente.");

        }else{
            $('.message-body').append("No se pudo procesar la devolución correctamente.");
            $('article.message').addClass('is-danger');
            //$('.modal-card-body').append("No se pudo procesar la retención.");
        }  
        //mostrarModal();
        mostrarMensaje();
        $("body").css("cursor", "default");
        elemento.classList.remove('is-loading');

    },

    error: function () {
         $('.modal-card-body').append("Hubo un error al procesar la solicitud. Comuniquese con el Área de Sistemas");
        $("body").css("cursor", "default");
    }
    });
}

/**
 * Realiza la sumatoria del monto base de todos los movimientos de las filas seleccionadas.
 * @returns {Number}
 */
function obtenerMontoBaseSeleccionados(){
    var listado = tabla.rows().nodes();
    var montoBase = 0.00;

    for(var i = 0; i < listado.length; i++){
        //tabla.rows().nodes())[17].firstChild.childNodes[0].checked
        //(tabla.rows().nodes())[17].firstChild.childNodes[0].getAttributeNode('data-selector-id-move').nodeValue
        var item = (tabla.rows().nodes())[i].firstChild.childNodes[0];
        if(item.checked === true){
            monto = (tabla.rows().nodes())[i].childNodes[5].textContent.replaceAll('.', '');
            monto = monto.replaceAll(',', '.'); 
            montoBase += parseFloat(monto);
        }
    }

    return montoBase.toFixed(2);
}

/**
 * Realiza la sumatoria del monto retenido de un tipo de retencion de todos los movimientos de las filas seleccionadas.
 * @param {String} tipo Tipo de retencion: 'iva' o 'gnc'.
 * @returns {Number|Boolean}
 */
function obtenerMontoRetencionSeleccionados(tipo){

    if(tipo === 'iva' || tipo === 'gnc'){
        var listado = tabla.rows().nodes();
        var montoRetenido = 0.00;

        for(var i = 0; i < listado.length; i++){
            var fila = tabla.rows().nodes()[i];
            var item = fila.firstChild.childNodes[0];
            if(item.checked === true){
               monto = $(fila).find("td#trow-monto-"+tipo)[0].textContent.replaceAll('.', '');
               monto = monto.replaceAll(',', '.');
               montoRetenido += parseFloat(monto);
            }
        }

        return montoRetenido.toFixed(2);
    }

    return false;
}

/**
 * Obtiene el listado de id_moves de las filas seleccionadas
 * @returns {Array|Boolean}
 */
function obtenerMovimientosSeleccionados(){
        //listado.each(function(item){ $(this).parent().addClass("is-danger");});
    var listado = tabla.rows().nodes();
    movimientos = [];

    for(var i = 0; i < listado.length; i++){
        //tabla.rows().nodes())[17].firstChild.childNodes[0].checked
        //(tabla.rows().nodes())[17].firstChild.childNodes[0].getAttributeNode('data-selector-id-move').nodeValue
        var item = (tabla.rows().nodes())[i].firstChild.childNodes[0];
        if(item.checked === true){
           movimientos.push(item.getAttributeNode('data-selector-id-move').nodeValue);
        }
    }

    return (movimientos.length === 0) ? false : movimientos;
}

/**
 * Valida que haya filas seleccionadas para retener y dispara el modal de seleccion de tipo de retencion
 * @returns {void|Boolean}
 */
function elegirTipoRetencionSeleccionados(){
    movimientosSeleccionados = obtenerMovimientosSeleccionados();
    if(movimientosSeleccionados === false){
        mostrarModalError("¡No hay movimientos seleccionados!","Seleccione uno o más movimientos para realizar la operación");
        return false;
    }

    $("#modal-seleccionados-cantidad-moves>span").text(movimientosSeleccionados.length);
    $("#modal-seleccionados-monto-base>span").text(obtenerMontoBaseSeleccionados());
    mostrarModal("#modal-retenciones");
}

/**
 * Verifica que el tipo de retencion sea valido. En caso de no serlo muestra un modal con el error.
 * @returns {string|Boolean}
 */
function validarTipoRetencionSeleccionados(){
    var tipoRetencion = $("#select-modal-tipos-retenciones option:selected").attr("data-value");

    if(['iva', 'gnc', 'iva-gnc'].indexOf(tipoRetencion) === -1){
        console.error("No es valido el tipo de retencion.");
        mostrarErrorTipoSeleccionados();
        return false;
    }

    return tipoRetencion;
}

/**
 * Realiza retenciones a los movimientos seleccionados haciendo una peticion ajax al modulo de util_cv.retener()
 * @returns {void}
 */
function retenerSeleccionados(){
    tipoRetencion = validarTipoRetencionSeleccionados();

    if(tipoRetencion === false){
        console.log("No se encuentra seleccionado el tipo de retencion.");
        return false;
    }
    var datos = {"id-moves":movimientosSeleccionados, "retener":tipoRetencion};
    var modulo = document.querySelector("boton[data-tipo-btn=btn-retener-seleccionados]").attributes["name"].value;
    
//    movesAjax = JSON.stringify(movimientos);
   $.ajax({
    //data: {"prueba":"2", "nav":"util_cv.retener", "data":"id-moves="+movesAjax+"&retener="+tipoRetencion},
    data: {"prueba":"2", "nav":modulo, "datos":JSON.stringify(datos)},
    url: url_servidor,
    type: 'post',
    beforeSend: function () {
        console.log("ENVIADOS MOVIMIENTOS SELECCIONADOS A RETENER: " + JSON.stringify(datos));
        $("body").css("cursor", "progress");
        cerrarModal("#modal-retenciones");
        mostrarModal("#modal-retenciones-procesando", false);
        destildarTodosLosItems();
    },
    success: function (response, data, xml) {
        response = response.replace("<json>", "");
        response = response.replace("<\/json>", "");
        respuesta = JSON.parse(response);
        console.log(respuesta);
        cerrarModal("#modal-retenciones-procesando");

        if(respuesta.hasOwnProperty('estado') && respuesta.estado === "1"){
            datos = respuesta.datos;
            for (var item of datos) {
                actualizarDatosRetencion(item);
            }
            console.log("Se procesaron los movimientos seleccionados de manera correcta.");
            mostrarOKProcesamiento();

        }else{
            console.log("No se procesaron los movimientos seleccionados.");
            mostrarModalError("¡No se pudo procesar las retenciones!", "Vuelva a intentarlo o comuniquese con el Área de Sistemas");
            //$('.modal-card-body').append("No se pudo procesar la retención.");
        }  
        //mostrarModal();
        $("body").css("cursor", "default");
        movimientosSeleccionados = null; //Para normalizar la variable
    },

    error: function () {
        cerrarModal("#modal-retenciones-procesando");
        mostrarModalError("¡Error al solicitar las retenciones!", "Vuelva a intentarlo o comuniquese con el Área de Sistemas");
        console.error("Error en la respuesta de la solicitud ajax al solicitar retenciones de movimientos seleccionados.");
        $("body").css("cursor", "default");
        movimientosSeleccionados = null; //Para normalizar la variable
    }
    });
}

/**
 * Realiza retenciones a los movimientos seleccionados haciendo una peticion ajax al modulo de util_cv.retener()
 * @param {String} tipo Tipo de retencion a devolver
 * @returns {Array|Boolean}
 */
function obtenerMovimientosSeleccionadosDevolucion(tipo){

    if(['iva', 'gnc', 'iva-gnc'].indexOf(tipo) === -1){
        console.log("No es valido el tipo de retencion.>>>"+tipo);
        //console.error("No es valido el tipo de retencion.");
        //mostrarErrorTipoSeleccionados();

        return false;
    }

    var listado = tabla.rows().nodes();
    var movimientos = [];

    for(var i = 0; i < listado.length; i++){
        var itemCheckbox = (tabla.rows().nodes())[i].firstChild.childNodes[0];
        var idMovRetencion = $(tabla.rows().nodes()[i]).find("td#trow-btn-devolver-"+ tipo +">boton[data-accion=devolver]").attr("data-id-move");
        var btnDevolverDeshabilitado = $(tabla.rows().nodes()[i]).find("td#trow-btn-devolver-"+ tipo +">boton[data-accion=devolver]").attr("disabled");

        if(itemCheckbox.checked === true && (btnDevolverDeshabilitado === "disabled" || idMovRetencion === "")){
           //Mostrar error y retornar false
           var id_move = $((tabla.rows().nodes())[i].firstChild.childNodes[0]).attr("data-selector-id-move");
           mostrarModalError("¡Movimiento seleccionado sin retención!", "El movimiento con el ID: " + id_move + " no tiene retención de "+ ((tipo === 'iva')?'IVA':'Ganancia') +" o ya fué reversada");

           return false;

        }else if(itemCheckbox.checked === true && !isNaN(parseInt(idMovRetencion))){
           movimientos.push(idMovRetencion);
        }
    }

    if(movimientos.length === 0){
        mostrarModalError("¡No hay movimientos seleccionados!","Seleccione uno o más movimientos para realizar la operación");
        return false;
    }

    return movimientos;
}

/**
 * Valida los datos para realizar la operacion de devolucion y muestra el modal de confirmacion
 * @param {object} elemento DOM que contiene los atributos data-* que llama a la funcion 
 * @returns {void|Boolean}
 */
function confirmarDevolucionSeleccionados(elemento){
    tipoSeleccionadosDevolucion = elemento.getAttribute("data-tipo");
    movimientosSeleccionadosDevolucion = obtenerMovimientosSeleccionadosDevolucion(tipoSeleccionadosDevolucion);
    
    if(movimientosSeleccionadosDevolucion === false){
        console.log("Alguno de los movimientos seleccionados no tienen una retencion para reversar.");
        console.log("Movimientos:"+JSON.stringify(movimientosSeleccionadosDevolucion));
        return false;
    }

    $("#modal-devoluciones-tipo>span").text(tipoSeleccionadosDevolucion === 'iva'? 'IVA' : 'Ganancias');
    $("#modal-devoluciones-cantidad-moves>span").text(movimientosSeleccionadosDevolucion.length);
    $("#modal-devoluciones-monto-base>span").text(obtenerMontoBaseSeleccionados());
    $("#modal-devoluciones-monto-retenido>span").text(obtenerMontoRetencionSeleccionados(tipoSeleccionadosDevolucion));
    mostrarModal("#modal-devoluciones");
}

/**
 * Realiza las devoluciones a los movimientos de retencion seleccionados haciendo una peticion ajax al modulo de util_cv.devolver()
 * @param {Array} movimientos Listado de id_moves (con id_mp 50004, 50005) a devolver.
 * @param {type} tipo Tipo de retencion a devolver: iva o gnc.
 * @returns {void}
 */
function devolverSeleccionados(){
    //movimientos = obtenerMovimientosSeleccionadosDevolucion();

    if(movimientosSeleccionadosDevolucion == false || tipoSeleccionadosDevolucion == false){
        //Mostrar cartel de error
        mostrarModalError("No se pudo procesar la operación", "Intentelo nuevamente o comuniquese con el Área de Sistemas");
        console.error("No se puedo realizar la devolucion de los movimientos seleccionados por que no se encuentra definida alguna de las variables requeridas.")
        return false;
    }

    var datos = {"id-moves":movimientosSeleccionadosDevolucion, "devolver":tipoSeleccionadosDevolucion};
    var modulo = document.querySelector("boton[data-tipo-btn^=btn-devolver-seleccionados-]").attributes["name"].value;
    //movesAjax = JSON.stringify(movimientos);
   $.ajax({
    //data: {"prueba":"2", "nav":"util_cv.devolver", "data":"id-moves="+movesAjax+"&devolver="+tipo},
    data: {"prueba":"2", "nav":modulo, "datos":JSON.stringify(datos)},
    url: url_servidor,
    type: 'post',
    beforeSend: function () {
        console.log("ENVIADOS LOS MOVIMIENTOS SELECCIONADOS PARA DEVOLUCION: "+JSON.stringify(datos));
        $("body").css("cursor", "progress");
        cerrarModal("#modal-devoluciones");
        mostrarModal("#modal-devoluciones-procesando", false);
        destildarTodosLosItems();
    },
    success: function (response, data, xml) {
        response = response.replace("<json>", "");
        response = response.replace("<\/json>", "");
        respuesta = JSON.parse(response);
        console.log(respuesta);
        cerrarModal("#modal-devoluciones-procesando");

        if(respuesta.hasOwnProperty('estado') && respuesta.estado === "1"){
            datos = respuesta.datos;
            for (var item of datos) {
                actualizarDatosDevolucion(item);
            }
            console.log("Se procesaron las devoluciones seleccionadas de manera correcta.");
            mostrarOKProcesamiento();

        }else{
            console.log("No se procesaron las devoluciones seleccionadas.");
            mostrarModalError("¡No se pudo procesar las retenciones!", "Vuelva a intentarlo o comuniquese con el Área de Sistemas");
            //$('.modal-card-body').append("No se pudo procesar la retención.");
        }  
        //mostrarModal();
        $("body").css("cursor", "default");
    },
    error: function () {
        cerrarModal("#modal-retenciones-procesando");
        console.error("Error en la respuesta de la solicitud ajax al solicitar devoluciones de movimientos seleccionados.");
        mostrarModalError("¡Error al solicitar las devoluciones!", "Vuelva a intentarlo o comuniquese con el Área de Sistemas");
        $("body").css("cursor", "default");
    }
    });
}

/**
 * Obtiene los datos de movimiento junto con sus retenciones y los muestra en el modal detalles de movimiento
 * @param {object} elemento DOM que dispara el llamado a la funcion
 * @returns {void|Boolean}
 */
function verDetallesMove(elemento){
    var idMove =  $(elemento).attr("data-id-move");
    var modulo = $(elemento).attr("name");

    if(idMove == false){
        //Mostrar cartel de error
        mostrarModalError("No se puede realizar la operación", "Intentelo nuevamente o comuniquese con el Área de Sistemas");
        console.error("No se puede realizar la consulta del movimiento seleccionado por que no se encuentra definida alguna de las variables requeridas.");
        return false;
    }
    var datos = {"id-move" : idMove};
    movesAjax = JSON.stringify(idMove);

   $.ajax({
    //data: {"prueba":"3", "nav":modulo, "data":"id-move="+movesAjax},
    data: {"prueba":"3", "nav":modulo, "datos": JSON.stringify(datos)},
    url: url_servidor,
    type: 'post',
    beforeSend: function () {
        console.log("Enviado el id-movimiento para coonsultar: " + JSON.stringify(datos));
        $("body").css("cursor", "progress");
        $(elemento).addClass('is-loading');
    },
    success: function (response, data, xml) {
        response = response.replace("<json>", "");
        response = response.replace("<\/json>", "");
        respuesta = JSON.parse(response);
        console.log(respuesta);
        //Sacar icono btn procesando
        limpiarModalDetallesMovimiento();

        if(respuesta.hasOwnProperty('estado') && respuesta.estado === "1"){
            datos = respuesta.datos;
            console.log("Se procesaron los datos del movimiento de manera correcta.");
            cargarModalDetallesMovimiento(datos);
            mostrarModal("#modal-movimiento-detalle");
            
        }else{
            console.log("No se procesaron los datos del movimiento.");
            mostrarModalError("¡No se pudo los datos del movimiento!", "Vuelva a intentarlo o comuniquese con el Área de Sistemas");
        }
        $("body").css("cursor", "default");
        $(elemento).removeClass('is-loading');
    },
    error: function () {
        console.error("Error en la respuesta de la solicitud ajax al solicitar devoluciones de movimientos seleccionados.");
        mostrarModalError("¡Error al obtener la información!", "Vuelva a intentarlo o comuniquese con el Área de Sistemas");
        $("body").css("cursor", "default");
        $(elemento).removeClass('is-loading');
    }
    });
}

/**
 * Borra los campos del modal detalles de movimiento
 * @returns {void}
 */
function limpiarModalDetallesMovimiento(){
    //detalles-movimiento
    $("#modal-movimiento-detalle #datos-movimiento #id-move>span").text('----');
    $("#modal-movimiento-detalle #datos-movimiento #fecha>span").text('----');
    $("#modal-movimiento-detalle #datos-movimiento #razon-social>span").text('----');
    $("#modal-movimiento-detalle #datos-movimiento #nombre>span").text('----');
    $("#modal-movimiento-detalle #datos-movimiento #cuit>span").text('----');
    $("#modal-movimiento-detalle #datos-movimiento #idm>span").text('----');
    $("#modal-movimiento-detalle #datos-movimiento #monto-base>span").text('----');
    //retencion iva
    $("#modal-movimiento-detalle #datos-retencion-iva #fecha>span").text('----');
    $("#modal-movimiento-detalle #datos-retencion-iva #id-move-retencion>span").text('----');
    $("#modal-movimiento-detalle #datos-retencion-iva #alicuota>span").text('----');
    $("#modal-movimiento-detalle #datos-retencion-iva #monto-retenido>span").text('----');
    //retencion gnc
    $("#modal-movimiento-detalle #datos-retencion-gnc #fecha>span").text('----');
    $("#modal-movimiento-detalle #datos-retencion-gnc #id-move-retencion>span").text('----');
    $("#modal-movimiento-detalle #datos-retencion-gnc #alicuota>span").text('----');
    $("#modal-movimiento-detalle #datos-retencion-gnc #monto-retenido>span").text('----');
    //devolucion iva        
    $("#modal-movimiento-detalle #datos-devolucion-iva #fecha>span").text('----');
    $("#modal-movimiento-detalle #datos-devolucion-iva #id-move-devolucion>span").text('----');
    //devolucion gnc
    $("#modal-movimiento-detalle #datos-devolucion-gnc #fecha>span").text('----');
    $("#modal-movimiento-detalle #datos-devolucion-gnc #id-move-devolucion>span").text('----');
}

/**
 * Carga los datos al modal de detalles de movimiento
 * @param {object} datos Objeto JS que contiene los datos a mostrar en el modal
 * @returns {void}
 */
function cargarModalDetallesMovimiento(datos){
     //detalles-movimiento
    $("#modal-movimiento-detalle #datos-movimiento #id-move>span").text(datos.movimiento.id_move);
    $("#modal-movimiento-detalle #datos-movimiento #fecha>span").text(new Date(datos.movimiento.fecha).toLocaleString());
    $("#modal-movimiento-detalle #datos-movimiento #razon-social>span").text(datos.movimiento.razon_social);
    $("#modal-movimiento-detalle #datos-movimiento #nombre>span").text(datos.movimiento.nombre);
    $("#modal-movimiento-detalle #datos-movimiento #cuit>span").text(formatearCUIT(datos.movimiento.documento));
    $("#modal-movimiento-detalle #datos-movimiento #idm>span").text(datos.movimiento.idm);
    $("#modal-movimiento-detalle #datos-movimiento #monto-base>span").text('$'+parseFloat(datos.movimiento.monto_base).toFixed(2).toLocaleString('es-AR'));

    if(datos.retencion_iva !== null){
        //retencion iva
        $("#modal-movimiento-detalle #datos-retencion-iva #fecha>span").text(new Date(datos.retencion_iva.fecha_gen).toLocaleString());
        $("#modal-movimiento-detalle #datos-retencion-iva #id-move-retencion>span").text(datos.retencion_iva.id_moves_retencion);
        $("#modal-movimiento-detalle #datos-retencion-iva #alicuota>span").text('%'+datos.retencion_iva.porcentaje_monto);
        $("#modal-movimiento-detalle #datos-retencion-iva #monto-retenido>span").text('$'+parseFloat(datos.retencion_iva.monto_retenido).toFixed(2).toLocaleString("es-AR"));

        if(datos.devolucion_iva !== null){
            //devolucion iva        
            $("#modal-movimiento-detalle #datos-devolucion-iva #fecha>span").text(new Date(datos.devolucion_iva.fecha_devolucion).toLocaleString());
            $("#modal-movimiento-detalle #datos-devolucion-iva #id-move-devolucion>span").text(datos.devolucion_iva.id_moves_devolucion);
        }

    }

    if(datos.retencion_gnc !== null){
        //retencion gnc
        $("#modal-movimiento-detalle #datos-retencion-gnc #fecha>span").text(new Date(datos.retencion_gnc.fecha_gen).toLocaleString());
        $("#modal-movimiento-detalle #datos-retencion-gnc #id-move-retencion>span").text(datos.retencion_gnc.id_moves_retencion);
        $("#modal-movimiento-detalle #datos-retencion-gnc #alicuota>span").text('%'+datos.retencion_gnc.porcentaje_monto);
        $("#modal-movimiento-detalle #datos-retencion-gnc #monto-retenido>span").text('$'+parseFloat(datos.retencion_gnc.monto_retenido).toFixed(2).toLocaleString("es-AR"));

        if(datos.devolucion_gnc !== null){
            //devolucion gnc
            $("#modal-movimiento-detalle #datos-devolucion-gnc #fecha>span").text(new Date(datos.devolucion_gnc.fecha_devolucion).toLocaleString());
            $("#modal-movimiento-detalle #datos-devolucion-gnc #id-move-devolucion>span").text(datos.devolucion_gnc.id_moves_devolucion);
        }
    }
}

/**
 * Formatea un nro de CUIT con los guiones
 * @param {string} nro Numero de CUIT
 * @returns {String|Boolean}
 */
function formatearCUIT(nro){
    if(typeof(nro) === 'string' && nro.length === 11){
        return nro.substring(0,2)+'-'+nro.substring(2,10)+'-'+nro.substring(10);
    }

    return false;
}

/**
 * Cambia de color una fila de tabla de datos indistitamente que clases tenga
 * @param {DOMElement} fila
 * @returns {void}
 */
function destacarFila(fila){
    $("tr#"+fila.childNodes[0].firstElementChild.attributes["data-selector-id-move"].value+">td").toggleClass('selector-fila');
    $("tr#"+fila.childNodes[0].firstElementChild.attributes["data-selector-id-move"].value+">td[id$='-iva']").toggleClass('td-datos-iva');
    $("tr#"+fila.childNodes[0].firstElementChild.attributes["data-selector-id-move"].value+">td[id$='-gnc']").toggleClass('td-datos-gnc');
}

/**
 * Colorea la fila con el color css de la clase 'selector-fila'
 * @param {DOMElement} fila
 * @returns {void}
 */
function colorearFila(fila){
    //Se utiliza nodos puro pq jquery accesede solo a lo que esta en vista-
    for(var i = 0; i < fila.childNodes.length; i++){
        var celdaClases = fila.childNodes[i].classList;
        celdaClases.add('selector-fila');
        
        if(celdaClases.contains('td-datos-iva')){
            celdaClases.remove('td-datos-iva');
        }
        
        if(celdaClases.contains('td-datos-gnc')){
            celdaClases.remove('td-datos-gnc');
        }
    }
}

/**
 * Quita el color de la fila seteado por el css de la clase 'selector-fila'
 * @param {DOMElement} fila
 * @returns {void}
 */
function descolorearFila(fila){
    for(var i = 0; i < fila.childNodes.length; i++){
        var celda = fila.childNodes[i];
        celda.classList.remove('selector-fila');
        
        if(/^trow\-\S+\-iva$/.test(celda.getAttribute('id'))){
            //si tiene el id de iva
            celda.classList.add('td-datos-iva');
        }
        if(/^trow\-\S+\-gnc$/.test(celda.getAttribute('id'))){
            //si tiene el id de celda gnc
            celda.classList.add('td-datos-gnc');
        }
    }
}

/**
 * Establece el color de fila segun el estado del checkbox de seleccion de la fila
 * @param {DOMElement} fila
 * @returns {void}
 */
function normalizarFila(fila){
    //console.log("(!)DEBUG: interno_util_cv.js > marcarFila()>"+fila);
    var checkbox = fila.childNodes[0].firstElementChild;
    if(checkbox.checked){
        colorearFila(fila);
    }else{
        descolorearFila(fila);
    }
}