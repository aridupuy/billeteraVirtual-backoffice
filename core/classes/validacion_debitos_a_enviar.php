<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of validador_debitos_a_enviar
 *
 * @author arieldupuy
 */
class validacion_debitos_a_enviar extends Validacion_sistema{
    //put your code here
    public function ejecutar() {
        developer_log("Ejecutando: ".__CLASS__);

        $rs_hoy= Debito_cbu::select_debitos_validacion_hoy();
        $rs_mañana= Debito_cbu::select_debitos_validacion_mañana();
        $view=new View();
        $table1=new Table($rs_hoy, 1, $rs_hoy->rowCount());
        $table2=new Table($rs_mañana, 1, $rs_mañana->rowCount());
        if($rs_hoy->rowCount()>0 OR $rs_mañana->rowCount()>0){
            $div=$view->createElement("div","Estado de debitos para enviar de Hoy(fecha_enviar < a 2 dias habiles), registros encontrados: ".$rs_hoy->rowCount());
            $div2=$view->createElement("div","Estado de debitos para enviar Para la proxima corrida (fecha_enviar < a 2 dias habiles), registros encontrados: ".$rs_mañana->rowCount());
            $div->appendChild($view->importNode($table1->documentElement,true));
            $div2->appendChild($view->importNode($table2->documentElement,true));
            $view->appendChild($div);
            $view->appendChild($div2);
    //        echo $view->saveHTML();
            Gestor_de_correo::enviar(Gestor_de_correo::MAIL_COBRODIGITAL_INFO, "sistemas@cobrodigital.com", __CLASS__, $view->saveHTML());
            Gestor_de_correo::enviar(Gestor_de_correo::MAIL_COBRODIGITAL_INFO, "doviedo@cobrodigital.com", __CLASS__, $view->saveHTML());
            Gestor_de_correo::enviar(Gestor_de_correo::MAIL_COBRODIGITAL_INFO, "allami@cobrodigital.com", __CLASS__, $view->saveHTML());
        }
        $this->next()->ejecutar();
        
    }

}
