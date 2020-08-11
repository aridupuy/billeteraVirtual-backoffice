<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of webservice_nopagados
 *
 * @author ariel
 */
class webservice_nopagados extends Webservice_post_express {

    //put your code here
    public function ejecutar($array) {
        $this->adjuntar_mensaje_para_usuario("iniciando postExpress (vencidos) para marchad: ".self::$id_marchand);
        try {
            $this->post = new Post_express_vencidos(self::$marchand);
            if(($id_afuturo_run=$this->verificar_bloqueo())!=false){
                Afuturo_run::eliminar_registro($id_afuturo_run);
            }
            if ($this->post->ejecutar()) {
                $this->adjuntar_mensaje_para_usuario("Ejecucion de postExpress correcta");
                $this->respuesta_ejecucion = self::RESPUESTA_EJECUCION_CORRECTA;
            }
        } catch (Exception $e) {
            error_log("POST ERROR" . $e->getMessage());
            $this->adjuntar_mensaje_para_usuario("POST ERROR" . $e->getMessage());
            $this->respuesta_ejecucion = self::RESPUESTA_EJECUCION_INCORRECTA;
            Model::FailTrans();
            return;
        }
//        var_dump($respuesta);
    }

}
