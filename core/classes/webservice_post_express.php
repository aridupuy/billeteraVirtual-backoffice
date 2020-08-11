<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
class Webservice_post_express extends Webservice{
    protected  $post;
    public function ejecutar($array){
        $this->adjuntar_mensaje_para_usuario("iniciando postExpress para marchad: ".self::$id_marchand);
        try{
            $this->post=new Post_express(self::$marchand);
            if(($id_afuturo_run=$this->verificar_bloqueo())!=false){
                Afuturo_run::eliminar_registro($id_afuturo_run);
            }
            if($this->post->ejecutar()){
                $this->adjuntar_mensaje_para_usuario("Ejecucion de postExpress correcta");
                $this->respuesta_ejecucion= self::RESPUESTA_EJECUCION_CORRECTA;
            }
        }catch (Exception $e){
            error_log("POST ERROR".$e->getMessage());
            $this->adjuntar_mensaje_para_usuario("POST ERROR".$e->getMessage());
            $this->respuesta_ejecucion= self::RESPUESTA_EJECUCION_INCORRECTA;
            Model::FailTrans();
            return ;   
        }
    }
    protected function verificar_bloqueo(){
        $rs= Afuturo_run::select_bloqueo(self::$id_marchand);
        if(!$rs or $rs->rowCount()==0){
            return false;
        }
        $row=$rs->fetchRow();
        return $row["id_afuturo_run"];
    }

}
