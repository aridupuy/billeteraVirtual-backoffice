<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of bind_consulta_moves
 *
 * @author ariel
 */
class Bind_consulta_moves  extends Bind_consulta_cuenta implements Bind_consulta_interface{
    private $moves;
    
    //put your code here

        //var_dump($this->moves);
    
    protected function get_moves(){
        return $this->moves;
    }
    public function consultar() {
        parent::consultar();
        foreach ($this->cuentas as $vista => $cuentas){
            foreach ($cuentas as $id_cuenta=>$cuenta){
                
                $this->moves[$vista][$id_cuenta]=$this->consultar_una($id_cuenta,$vista);
            }
        }
        return $this->get_moves();
        //$this->llamado_api(self::URL,array(),"GET");
    }

    public function consultar_una(...$params) {
        $res = $this->llamado_api(self::URL."/$params[0]/$params[1]/transactions",array(),"GET");
        return json_decode($res,true);
    }
   

}
