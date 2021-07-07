<section class="form-box form-contacto">
	<div class="contenedor-form">
		<div class="form-left">
			<h1 class="titulo-form">
				<span class="bold">¡Hola!</span><br/>
				Envianos tu consulta
			</h1>
			<h5>Completá el formulario con todos tus datos. Nuestros representantes se contactarán con vos a la brevedad.</h5>
		</div>
		

            <form action="" method="post" id="miFormulario" class="contacto_form">
			<fieldset>
				<label for="nombre">Nombre</label>
				<input type="text" id="nombre_contacto" name="nombre" placeholder="">
				<span class="error">Completar campo</span>
			</fieldset>
			<fieldset>
				<label for="apellido">Apellido</label>
				<input type="text" id="apellido_contacto" name="apellido" placeholder="">
				<span class="error">Completar campo</span>
			</fieldset>
			<fieldset>
				<label for="email">Email</label>
				<input type="email" id="email_contacto" name="email" placeholder="">
				<span class="error">Completar campo</span>
			</fieldset>		
			<fieldset>
				<label for="telefono">Teléfono</label>
				<input type="text" id="telefono_contacto" name="telefono" placeholder="">
				<span class="error">Completar campo</span>
			</fieldset>	
			<fieldset class="full">
				<label for="empresa">Empresa</label>
				<input type="text" id="empresa_contacto" name="empresa" placeholder="">
				<span class="error">Completar campo</span>
			</fieldset>				
			<fieldset class="full">
				<label for="mensaje">Mensaje</label>
				<textarea type="text" id="mensaje_contacto" name="mensaje" cols="60" rows="4" placeholder=""></textarea>
                                <span class="error">Completar campo</span>
			</fieldset>
			<div class="form-bottom">
				<div class="obligatorios">Todos los campos son obligatorios. </div>
				<div class="btn btn-enviar contacto" type="submit" id="boton-enviar" name="contacto_landing" value="main_controller.contacto_mail_front_landing">Enviar</div>
				<div class="success">¡Tu mensaje fue enviado! Te contactaremos a la brevedad</div>
			</div>		
			</form>
			

			<div class="icono-form"><img src="assets/img/icono-contacto.svg" alt="Contacto"></div>
			<div class="cerrar-form"><img src="assets/img/icono-cerrar.svg" alt="Cerrar"></div>
	</div>
</section>



<script>
$(".contacto").click(function(){
    var error=false;
    $(".error").hide();
    $('.contacto_form input[type=text] , .contacto_form  input[type=email] , .contacto_form textarea').each(function(){
        console.log("error");
        if($(this).val()=="" || $(this).val()==undefined){
            $(this).siblings(".error").show();
             error=true;
             console.log(this);
        }
        
    })
    if(error){
        console.log("sale por aca");
        return false;
    }
    var data = $("#miFormulario").serialize();
    link_reemplazo("main_controller.contacto_mail_front_contacto_landing","","reemplazo",data,function(){
	$('.contacto_form input[type=text] , .contacto_form  input[type=email] , .contacto_form textarea').each(function(){
         $(this).val("");
      });
        $(".success").show();
    });
});

var url_servidor = '/';
function link_reemplazo(nav, id, id_elemento_a_reemplazar,datos, fx,data=true) {
    if (datos) {
        var parametros = {
            "nav": nav,
            "id": id,
            "data": datos
        };
    } else {
        var parametros = {
            "nav": nav,
            "id": id,
            "data": $("#miFormulario").serialize()
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
            if (fx) {
                fx();
            }

        },

        error: function () {
            $("#miFormulario").html(mensaje_error);
        }
    });
}


</script>
