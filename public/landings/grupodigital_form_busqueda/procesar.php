<?php 

// define variables and set to empty values
$nombre_error = $email_error = $puesto_error = $cv_error = "";
$nombre = $email = $puesto = $cv = $mensaje = $success = "";

define("PATH_PUBLIC","/home/relc/PRODcdi/apps/externo/grupodigital_form_busqueda/");
define('COBRO_DIGITAL', 'Cobro Digital ©');
require_once("gestor_de_correo.php");

//form is submitted with POST method
if ($_SERVER["REQUEST_METHOD"] == "POST") {
	

	// 1 - NOMBRE
	if (empty($_POST["nombre"])) {
		$nombre_error = "Nombre es un campo obligatorio";
	} else {
		$nombre = test_input($_POST["nombre"]);
	// check if name only contains letters and whitespace
		if (!preg_match("/^[a-zA-Z ]*$/",$nombre)) {
			$nombre_error = "Solo están permitidos letras y espacios"; 
		}
	}

	// 2 - EMAIL
	if (empty($_POST["email"])) {
		$email_error = "Email es un campo obligatorio";
	} else {
		$email = test_input($_POST["email"]);
	// check if e-mail address is well-formed
		if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
			$email_error = "Formato de email inválido"; 
		}
	}

	// 3 - PUESTO
	if (empty($_POST["puesto"])) {
		$puesto_error = "Puesto es un campo obligatorio";
	} else {
		$puesto = test_input($_POST["puesto"]);
	}

	// 4 - CV
	if( empty($_FILES['cv']['tmp_name']) || !is_uploaded_file($_FILES['cv']['tmp_name']))
		{
		   $cv_error = "CV es un campo obligatorio";
		} else {
			$cv = test_input($_FILES['cv']['name']);
		}

	// 6 - MENSAJE
	if (empty($_POST["mensaje"])) {
		$mensaje = "";
	} else {
		$mensaje = test_input($_POST["mensaje"]);
	}
//	$archivo = file_get_contents($_FILE["cv"]["tmp_name"]);
//	file_put_contents("/home/relc/Dinamico/cdexports/".$_FILE["cv"]["name"],$archivo);
	if(move_uploaded_file($_FILES["cv"]["tmp_name"], "/home/relc/Dinamico/cdexports/".$_FILES["cv"]["name"])){
 //               return $nombre;
		error_log("archivo movido");
        }
	else 
		error_log("error al mover el archivo a "."/home/relc/Dinamico/cdexports/".$_FILES["cv"]["name"]);
	if ($nombre_error == '' and $email_error == '' and $puesto_error == '' and $cv_error == '' ){
		$mensaje_body = '';
		unset($_POST['submit']);
		foreach ($_POST as $key => $value){
			if ($key != 'check_list') {
				$mensaje_body .=  "$key: $value<br/>";
			}
		}
		$subject = "Nueva postulación en Grupo Digital - $puesto - $nombre";
		$success = "Tu postulación fue enviada correctamente ¡Gracias por sumarte!";
		if(gestor_de_correo::enviar("sistemas@cobrodigital.com", "rrhh@grupodigital.com.ar", $subject, $mensaje_body,"/home/relc/Dinamico/cdexports/".$_FILES["cv"]["name"])){
			error_log("Correcto");
		}
		else 
			error_log("Error");
	}

}

function test_input($data) {
	$data = trim($data);
	$data = stripslashes($data);
	$data = htmlspecialchars($data);
	return $data;
}
include("index.php");
