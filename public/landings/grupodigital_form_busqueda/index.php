<!DOCTYPE html>
<html>
<head>
	<meta charset="UTF-8">
	<title>Grupo Digital</title>
	<meta property="og:title" content="Grupo Digital" />
	<meta property="og:description" content="Infinitas posibilidades" />
	<meta property="og:image" content="http://grupodigital.com.ar/ogimage.jpg" />
	<meta property="og:url" content="http://grupodigital.com.ar/" />
	
	<meta name="description" content="Infinitas posibilidades">
	<meta name="viewport" content="width=device-width,  user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0"/>
	<meta name="keywords" content="">

	<!-- CSS -->		
	<link rel="stylesheet" href="assets/css/style.css">

	<!-- JS -->
	<script src="https://code.jquery.com/jquery-latest.js"></script>
	<script type="text/javascript" src="assets/js/prefixfree.min.js"></script>
	<script type="text/javascript" src="assets/js/global.js"></script>

	<!-- Fuentes -->
	<link href="https://fonts.googleapis.com/css?family=Work+Sans:400,500,600,700&display=swap" rel="stylesheet">

	<!-- Favicon -->
	<link rel="apple-touch-icon" sizes="152x152" href="assets/favicon/apple-touch-icon.png">
	<link rel="icon" type="image/png" sizes="32x32" href="assets/favicon/favicon-32x32.png">
	<link rel="icon" type="image/png" sizes="16x16" href="assets/favicon/favicon-16x16.png">
	<link rel="manifest" href="assets/favicon/site.webmanifest">
	<link rel="mask-icon" href="assets/favicon/safari-pinned-tab.svg" color="#012d41">
	<link rel="shortcut icon" href="assets/favicon/favicon.ico">
	<meta name="msapplication-TileColor" content="#012d41">
	<meta name="msapplication-config" content="assets/favicon/browserconfig.xml">
	<meta name="theme-color" content="#012d41">
	
</head>
<body>
	
	<div class="contenido">
		<div class="contenedor-form">
			<div class="form-header">
				<div class="logo"><a href="http://grupodigital.com.ar/"><img src="assets/img/logo_grupodigital.png" alt="Grupo_Digital"></a></div>
				<div class="titulo-form">Trabajá con nosotros</div>
			</div>

			<form action="/grupo/procesar.php" class="formulario" method="post" enctype="multipart/form-data">
				<fieldset>
					<input type="text" id="nombre" name="nombre" placeholder="Nombre Completo" value="<?= $nombre ?>">
					<span class="error"><?= $nombre_error ?></span>
				</fieldset>
				<fieldset>
					<input type="email" id="email" name="email" placeholder="E-mail de Contacto" value="<?= $email ?>">
					<span class="error"><?= $email_error ?></span>
				</fieldset>
				<fieldset class="half">
					<input type="text" id="puesto" name="puesto" placeholder="Puesto al que aplicás" value="<?= $puesto ?>">
					<span class="error"><?= $puesto_error ?></span>
				</fieldset>	
				<fieldset class="half">
					<input type="file"  name="cv" class="carga-cv" id="cv" />
					<label class="falso-cv" for="cv"><span>Cargá tu CV</span><img class="icono-upload" src="assets/img/icono-carga.svg"></label>
					
					<span class="error"><?= $cv_error ?></span>
				</fieldset>								
				<fieldset>
					<textarea type="text" id="mensaje" name="mensaje" cols="60" rows="4" placeholder="Mensaje"><?= $mensaje ?></textarea>
				</fieldset>
				<input class="btn-enviar" type="submit" id="boton-enviar" value="Enviar">
				<div class="success"><?= $success; ?></div>
			</form>
		</div>		
	</div>

	<div class="redes">
		<div class="red"><a href="https://www.instagram.com/somosgrupodigital/" target="blank"><img src="assets/img/icono-ig.svg"></a></div>
		<div class="red"><a href="https://www.facebook.com/somosgrupodigital" target="blank"><img src="assets/img/icono-fb.svg"></a></div>
		<div class="red"><a href="https://www.linkedin.com/company/somos-grupo-digital" target="blank"><img src="assets/img/icono-li.svg"></a></div>
	</div>
</body>
</html>


<script>
$(".carga-cv").change(function(){
	var a=$(".carga-cv").val().substring($(".carga-cv").val().lastIndexOf('\\')+1);
	$(".falso-cv>span").html(a);
})
</script>
