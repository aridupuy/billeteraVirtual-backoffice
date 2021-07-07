<section class="form-box form-asociarse">
	<div class="contenedor-form">
		<div class="form-left">
			<h1 class="titulo-form rojo">
				<span class="bold">¡Hola!</span><br/>
				Falta poco para poder asociarte
			</h1>
			<h5>Para comenzar con el alta de tu empresa, completá el siguiente formulario con todos tus datos. Un representante de Cobro Digital te contactará  a la brevedad.</h5>
		</div>
		

            <form action="/" method="post" id="miFormulario2" class="asociar_form">
			<fieldset>
				<label for="nombre">Nombre</label>
				<input type="text" id="nombre" name="nombre" placeholder="">
				<span class="error">Completar campo</span>
			</fieldset>
			<fieldset>
				<label for="apellido">Apellido</label>
				<input type="text" id="apellido" name="apellido" placeholder="">
				<span class="error">Completar campo</span>
			</fieldset>
			<fieldset>
				<label for="email">Email</label>
				<input type="email" id="email" name="email" placeholder="">
				<span class="error">Completar campo</span>
			</fieldset>		
			<fieldset>
				<label for="telefono">Teléfono</label>
				<input type="text" id="telefono" name="telefono" placeholder="">
				<span class="error">Completar campo</span>
			</fieldset>	
			<fieldset>
				<label for="empresa">Empresa</label>
				<input type="text" id="empresa" name="empresa" placeholder="">
				<span class="error">Completar campo</span>
			</fieldset>	
			<fieldset>
				<label for="cuit">CUIT</label>
				<input type="text" id="cuit" name="cuit" placeholder="">
				<span class="error">Completar campo</span>
			</fieldset>				
			<fieldset class="full">
				<label for="mensaje">Mensaje</label>
				<textarea type="text" id="mensaje" name="mensaje" cols="60" rows="4" placeholder=""></textarea>
                                <span class="error">Completar campo</span>
			</fieldset>
                        <div class="form-bottom">
				<div class="obligatorios">Todos los campos son obligatorios. </div>
                                <div class="btn btn-enviar asociar" type="submit" id="boton-enviar" name="asociar" value="main_controller.contacto_mail_front_asociar">Enviar</div>
				<div class="success">¡Tu mensaje fue enviado! Te contactaremos a la brevedad</div>
			</div>	
                    
			</form>
			
            <div style="display: none" id="reemplazo"></div>
			<div class="icono-form" style="width:55px;"><img src="assets/img/icono-alta.svg" alt="Contacto"></div>
			<div class="cerrar-form"><img src="assets/img/icono-cerrar.svg" alt="Cerrar"></div>
	</div>
</section>


<script>
$(".asociar").click(function(){
    var error=false;
    $(".error").hide();
    $('.asociar_form input[type=text] , .asociar_form  input[type=email] , .asociar_form textarea').each(function(){
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
    link_reemplazo2("main_controller.contacto_mail_front_asociar","","reemplazo",function(){
      $('.asociar_form input[type=text] , .asociar_form  input[type=email] , .asociar_form textarea').each(function(){
          $(this).val("");
      });
        $(".success").show();
    });
});

var url_servidor = '/';
function link_reemplazo2(nav, id, id_elemento_a_reemplazar, fx,data=true) {
//    console.log($("#miFormulario2").serialize());
    if (data) {
        var parametros = {
            "nav": nav,
            "id": id,
            "data": $("#miFormulario2").serialize()
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
            if (fx) {
                fx();
            }

        },

        error: function () {
            $("#miFormulario2").html(mensaje_error);
        }
    });
}


</script>
