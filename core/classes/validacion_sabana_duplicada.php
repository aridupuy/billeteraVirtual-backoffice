<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of validacion_sabana_duplicada
 *
 * @author arieldupuy
 */
class Validacion_sabana_duplicada extends Validacion_sistema{
   
    
    public function ejecutar() {
        developer_log("Ejecutando: ".__CLASS__);
        $rs= Sabana::select_sabana_duplicada();
        $view=new View();
//        $this->next()->ejecutar();
        if($rs->rowCount()>0){
            $table=new Table($rs, 1,$rs->rowCount());
            $div=$view->createElement("div");
            $div->appendChild($view->createElement("span","Se encontraron ".$rs->rowCount()." Sabanas duplicadas hasta ahora"));
            $view->appendChild($div);
            $view->appendChild($view->createElement("br"));
            $view->appendChild($view->importNode($table->documentElement,true));
            Gestor_de_correo::enviar(Gestor_de_correo::MAIL_COBRODIGITAL_INFO, "sistemas@cobrodigital.com", __CLASS__, $view->saveHTML());
            Gestor_de_correo::enviar(Gestor_de_correo::MAIL_COBRODIGITAL_INFO, "doviedo@cobrodigital.com", __CLASS__, $view->saveHTML());
            Gestor_de_correo::enviar(Gestor_de_correo::MAIL_COBRODIGITAL_INFO, "allami@cobrodigital.com", __CLASS__, $view->saveHTML());
        }
        return $this->next()->run();
    }

}
