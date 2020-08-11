<?php

class Validacion_tareas extends Validacion_sistema {

    public function ejecutar() {
        developer_log("Ejecutando: ".__CLASS__);
       
        $rs = Tarea::select_tareas_ocupadas();
        $bloqueos = 0;
        $desbloqueos = 0;
        $correctos = 0;
        //esta para probar que realmente avisa
        Model::StartTrans();
        foreach ($rs as $row) {
            $tarea = new Tarea($row);
            $pid = $tarea->get_pid();
            if ($pid != null) {
                developer_log("verificando pid: $pid");
                if (file_exists("/proc/" . $pid)) {
//            echo "existe"."/proc/" . $pid." Continuo....";
                    $correctos++;
                } else {
                    $tarea->set_ocupado("false");
                    $tarea->set_pid("null");
                    $tarea->set_ejecucion_correcta("false");
                    $tarea->set_mensaje("Salio con error Desbloqueado...");
                    if ($tarea->set()) {
                        $desbloqueos++;
                        Gestor_de_correo::enviar(Gestor_de_correo::MAIL_COBRODIGITAL_NORESPONDER, "sistemas@cobrodigital.com", "Tarea Desbloqueada: " . $tarea->get_comando(), "La tarea: " . $tarea->get_comando() . " tuvo que ser desbloqueada en el server".NOMBRE_SERVER."ya que el pid:" . $tarea->get_pid() . "  no estaba corriendo.");
                    } else {
                        $bloqueos++;
                        Gestor_de_correo::enviar(Gestor_de_correo::MAIL_COBRODIGITAL_NORESPONDER, "sistemas@cobrodigital.com", "Tarea Bloqueada: " . $tarea->get_comando(), "se intento desbloquear la tarea pero no se pudo. Por favor revise manualmente.");
                    }
                }
            }
        }
        Model::FailTrans();
        Model::CompleteTrans();
        if($desbloqueos>0 or $bloqueos>0 or $correctos > 0){
            Gestor_de_correo::enviar(Gestor_de_correo::MAIL_COBRODIGITAL_NORESPONDER, "sistemas@cobrodigital.com","Informe de tareas", 
            'Termina proceso cantidad desbloqueada: ' . $desbloqueos . ", "
            . " Cantidad que no pudo ser desbloqueada " . $bloqueos . " "
            . " Cantidad de procesos correctamente corriendo " . $correctos
            . " de un total de " . $rs->rowCount());
              Gestor_de_correo::enviar(Gestor_de_correo::MAIL_COBRODIGITAL_NORESPONDER, "doviedo@cobrodigital.com","Informe de tareas", 
             'Termina proceso cantidad desbloqueada: ' . $desbloqueos . ", "
            . " Cantidad que no pudo ser desbloqueada " . $bloqueos . " "
            . " Cantidad de procesos correctamente corriendo " . $correctos
            . " de un total de " . $rs->rowCount());
              Gestor_de_correo::enviar(Gestor_de_correo::MAIL_COBRODIGITAL_NORESPONDER, "allami@cobrodigital.com","Informe de tareas", 
             'Termina proceso cantidad desbloqueada: ' . $desbloqueos . ", "
            . " Cantidad que no pudo ser desbloqueada " . $bloqueos . " "
            . " Cantidad de procesos correctamente corriendo " . $correctos
            . " de un total de " . $rs->rowCount());
        }
        return $this->next()->run();
    }

}
