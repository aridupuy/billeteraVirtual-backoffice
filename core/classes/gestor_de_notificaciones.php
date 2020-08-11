<?php

class Gestor_de_notificaciones {
    const ACTIVAR_DEBUG=true;
    const URL="https://gcm-http.googleapis.com/gcm/send";
    const KEY='key=AAAAgWDFCNY:APA91bF1ejbpGOyi8WmCuS7a1xB6GnECsk-5EHh3WLB6EoIkaRcQdiptOuQ1QK_GvJIp1Hjm_8zOA99vL8ripcZi_100u63Yt88cZIY9bn_pe7u1XsagPid38CG_F2yIRic2_-O_hoYJ';
    const ID_AUTHSTAT_ACTIVO=990;
    const ID_AUTH=30;
    const VERDADERO=1;
    const FALSO=0;
    const DEFAULT_LEIDO=0;
    const DEFAULT_NIVEL=0;
    protected static $cuerpo='{ 
                                    "priority" : "high",
                                    "delay_while_idle" : true,
                                    "data": {
                                            "titulo": "",
                                            "cuerpo": "",
                                            "activity" : ""
                                    },
                                    "to" : ""
                                }';
    public static function notificar($id_marchand,$mensaje,$titulo="Cobro Digital",$actividad="Main.Main",$nivel=self::DEFAULT_NIVEL){
        if (self::ACTIVAR_DEBUG)
            developer_log("Notificando al IDM: ".$id_marchand);
        $recordset= Dispositivo::select(array("id_marchand"=>$id_marchand));
        $postdata= json_decode(self::$cuerpo,true);
        foreach ($recordset as $row){
            if (self::ACTIVAR_DEBUG)
                developer_log("Dispositivo recuperado IDM:".$id_marchand." | TIPO:".$row['tipo']);
            $postdata["data"]["titulo"]=$titulo;
            $postdata["data"]["cuerpo"]=$mensaje;
            $postdata["data"]["nivel"]=$nivel;
            $postdata["data"]["activity"]=$actividad;
            $postdata["to"]=$row["token"];
            if(!self::enviar($postdata))
                Throw new Exception("Ah ocurrido un error al enviar la notificacion.");
             if (self::ACTIVAR_DEBUG)
                developer_log("La notificacion ha sido enviada.");
            }
        return true;
    }
    private static function enviar($postdata){
        $json_envio = json_encode($postdata);
        $headers=array(
            'Content-Type' => 'application/JSON',
            'Authorization' => self::KEY
        );
        $opts = array('http' =>
                                array(
                                    'method'  => 'POST',
                                    'header'  => self::preparar_cabeceras($headers),
                                    'content' => $json_envio
                                )
                    );
        $context = stream_context_create($opts);
        $result = file_get_contents(self::URL, false, $context);
        $result= json_decode($result,true);
        if($result['success']===self::VERDADERO AND $result['failure']===self::FALSO ){
            developer_log("Notificacion Correctamente enviada a google.");
            return true;
        }
        developer_log("Notificacion no pudo ser enviada a google.");
        return false;
    }

    private static function preparar_cabeceras($headers) {
      $array = array();
      foreach ($headers as $key => $header) {
        if (is_int($key)) {
          $array[] = $header;
        } else {
          $array[] = $key.': '.$header;
        }
      }

      return implode("\r\n", $array);
    }
    
    public static function guardar_notificacion($id_marchand,$mensaje,$titulo="Cobro Digital",$actividad="Main.Main",DateInterval $duracion=null,$nivel){
        if (self::ACTIVAR_DEBUG)
            developer_log("Guardando notificacion.");
        if($duracion===null){
            $duracion=new DateInterval("P1D");
        }
        $noti_marchand=new Noti_marchand();
        $noti_marchand->set_notificacion($mensaje);
        $noti_marchand->set_id_marchand($id_marchand);
        $noti_marchand->set_id_authstat(self::ID_AUTHSTAT_ACTIVO);
        $noti_marchand->set_fecha_ini((new DateTime("now"))->format("Y-m-d"));
        $fecha_fin=new DateTime("now");
        $fecha_fin->add($duracion);
        $noti_marchand->set_fecha_fin($fecha_fin->format("Y-m-d"));
        $noti_marchand->set_id_auth(self::ID_AUTH);
        $noti_marchand->set_leido(self::DEFAULT_LEIDO);
        $noti_marchand->set_nivel($nivel);
        $noti_marchand->set_titulo($titulo);
        $noti_marchand->set_actividad($actividad);
        if(!$noti_marchand->set()){
            Throw new Exception("Error al guardar notificacion");
        }
        if (self::ACTIVAR_DEBUG)
            developer_log("Notificacion guardada correctamente.");
        return true;
    }
}
