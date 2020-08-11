<?php
// namespace Classes;
# Arreglar trycatches
class Gestor_de_correo{
    const MAIL_COBRODIGITAL_INFO='info@cobrodigital.com';
    const MAIL_COBRODIGITAL_NORESPONDER='noresponder@cobrodigital.com';
    const MAIL_COBRODIGITAL_ATENCION_AL_CLIENTE='atencionalcliente@cobrodigital.com';
    const MAIL_DESARROLLO='wscarano@cobrodigital.com';
    const VALIDAR_CORREO=false;
    #const SERVIDOR_HOST='10.100.10.53';
    const SERVIDOR_HOST='132.147.160.4'; //'10.132.254.222' Obsoleta
    const SERVIDOR_PORT='25';
#    const SERVIDOR_USER='noresponder';
    const SERVIDOR_USER='noresponder';
    const SERVIDOR_PASS='cuandoveo1pajaronoveolabandada';
    const SERVIDOR_AUTH=false;
    const ACTIVAR_TEST=false;
    public static function enviar($emisor, $destinatario, $asunto, $mensaje,$file_path=false)
    {
        if(self::ACTIVAR_TEST)
            return true;
        if(self::VALIDAR_CORREO AND !validar_correo($destinatario)) return false;
        if(self::VALIDAR_CORREO AND !validar_correo($emisor)) return false;
        # Si tiene archivos adjuntos
        //if($file_path) 
            return self::enviar_con_adjunto($emisor, $destinatario, $asunto, $mensaje,$file_path);
        # Si no tiene archivos adjuntos
        $cabeceras  = 'MIME-Version: 1.0' . "\r\n";
        $cabeceras .= 'Content-type: text/html; charset=UTF-8' . "\r\n";
        $cabeceras .= 'FROM: '.$emisor . "\r\n";
        $cabeceras.= "BCC:".$emisor . "\r\n";
        if(!mb_detect_encoding($mensaje, 'UTF-8', true))
            $mensaje=utf8_encode($mensaje);
        $mensaje = wordwrap($mensaje, 70, "\r\n");
        if(mail($destinatario, $asunto, $mensaje,$cabeceras)){
          if(ACTIVAR_LOG_APACHE_DE_CORREO) error_log('Correo correctamente enviado a: '.$destinatario.'. ');
          return true;
        }
        if(ACTIVAR_LOG_APACHE_DE_CORREO) error_log('Ha ocurrido un error al intentar enviar un correo a: '.$destinatario.'. ');
        return false;
    }
    private static function enviar_con_adjunto($emisor, $destinatario, $asunto, $mensaje,$file_path){
        if(self::VALIDAR_CORREO AND !validar_correo($emisor)) return false;
        if(self::VALIDAR_CORREO AND !validar_correo($destinatario)) return false;
//        if(!is_file($file_path)) {
//            if(ACTIVAR_LOG_APACHE_DE_CORREO){
//                error_log('No existe el archivo: '.$file_path);
//            }
//            return false;
//        }
        if(!@include_once PATH_PUBLIC.'PHPMailer/class.phpmailer.php'){
            error_log('Fallo al abrir la clase PHPMailer');
            return false;
        }
    
        
        if(!@include_once PATH_PUBLIC.'PHPMailer/class.smtp.php'){
            error_log('Fallo al abrir la clase smtp');
            return false;
        }
        $email = new PHPMailer();
        $cabeceras  = 'MIME-Version: 1.0' . "\r\n";
        $cabeceras .= 'Content-type: text/html; charset=UTF-8' . "\r\n";
        $cabeceras .= 'FROM: '.$emisor . "\r\n";
        $cabeceras.= "BCC:".$emisor . "\r\n";
        if(!mb_detect_encoding($mensaje, 'UTF-8', true))
            $mensaje=utf8_encode($mensaje);
        $mensaje = wordwrap($mensaje, 70, "\r\n");
     //   $email->SMTPDebug = 3;
        $email->isSMTP();
        $email->Host      =self::SERVIDOR_HOST;
        $email->Port      =  self::SERVIDOR_PORT; 
        $email->Username      =self::SERVIDOR_USER;
        $email->Password      =self::SERVIDOR_PASS;
        $email->From      = $emisor;
        $email->FromName  = COBRO_DIGITAL;
        $email->Body=$mensaje;
//        $email->msgHTML($mensaje);
        $email->ContentType="text/html";
        $email->CharSet ='UTF-8';
        $email->Subject   = $asunto;
        $email->isHTML(true);
        $email->SMTPAuth = self::SERVIDOR_AUTH;  
//        $email->SMTPDebug = 2;
//        $email->Body= $mensaje;
        $email->AddAddress($destinatario);
        if($file_path){
            $email->AddAttachment( $file_path , basename($file_path) );
        }
        
        if($email->Send()){
	   error_log("EMAIL SALE BIEN");
            if(ACTIVAR_LOG_APACHE_DE_CORREO) error_log('Correo correctamente enviado a: '.$destinatario.' con un archivo adjunto. ');
            return true;
        }else{
	    error_log("EMAIL SALE MAL");
            if(ACTIVAR_LOG_APACHE_DE_CORREO){ 
                $mesErr= "Ha ocurrido un error al intentar enviar un correo a: ".$destinatario;
                $mesErr.= $email->ErrorInfo;  
                error_log($mesErr);
                    
            }
                    return false ;
        }
        if(ACTIVAR_LOG_APACHE_DE_CORREO) error_log('Ha ocurrido un error al intentar enviar un correo a: '.$destinatario.' con un archivo adjunto. ');
        return false;

    }

}
