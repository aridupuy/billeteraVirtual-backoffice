<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of Validacion_debitos
 *
 * @author arieldupuy
 */
class Validacion_costeos  extends Validacion_sistema{
    //put your code here
    public function ejecutar() {
        developer_log("Ejecutando: ".__CLASS__);
        developer_log("Consultando costeo de debitos");
        $rs_debitos=Sabana::select_estado_costeo_debitos();
        developer_log("Consultando costeo de efectivo");
        $rs_efectivo=Sabana::select_estado_costeo_efectivo();
        $view=new View();
        developer_log("creando tabla de debitos");
        $table1=new Table($rs_debitos, 1, $rs_debitos->rowCount());
        developer_log("creando tabla de efectivo");
        $table2=new Table($rs_efectivo, 1, $rs_efectivo->rowCount());
        $sum1=$sum2=0;
        foreach ($rs_debitos as $row){
            $sum1+=$row["cantidad"];
        }
        $rs_debitos->move(0);
        foreach ($rs_efectivo as $row){
            $sum2+=$row["count"];
        }
        if($sum1>0 )
        $rs_efectivo->move(0);
        $div=$view->createElement("div","estado de debitos Hasta ahora, registros costeados: ".$sum1);
        $div2=$view->createElement("div","estado de efectivo Hasta ahora, registros costeados: ".$sum2);
        $div->appendChild($view->importNode($table1->documentElement,true));
        $div2->appendChild($view->importNode($table2->documentElement,true));
        $view->appendChild($div);
        $view->appendChild($div2);
//        echo $view->saveHTML();
        Gestor_de_correo::enviar(Gestor_de_correo::MAIL_COBRODIGITAL_INFO, "sistemas@cobrodigital.com", __CLASS__, $view->saveHTML());
        Gestor_de_correo::enviar(Gestor_de_correo::MAIL_COBRODIGITAL_INFO, "doviedo@cobrodigital.com", __CLASS__, $view->saveHTML());
	Gestor_de_correo::enviar(Gestor_de_correo::MAIL_COBRODIGITAL_INFO, "allami@cobrodigital.com", __CLASS__, $view->saveHTML());
        $this->next()->ejecutar();
    }
    
  
}
