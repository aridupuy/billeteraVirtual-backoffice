<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of Bind_event_manager
 *
 * @author ariel
 */
//https://app.buenbit.com/dashboard/operar/compra/daiusd
class Bind_event_manager {
    //put your code here
    protected $variables;
    public function __construct($post,$get,$input) {
        $array= json_decode($input,true);
        $this->variables = array_merge($post, $get,$array);
        
    }
    /***
     * eventos aceptados.
     * TRANSFER 
     * DEBIN_SUBSCRIPTION 
     * TRANSFER_CVU
     * DEBIN
     * 
    * */
     
    public function execute($event){
        try{
            $resp=[];
            Bind_event::set_manager($this);
            switch ("/".basename($event)){
                /*transferencia cvu recibida*/
                case "/transfcvurec":
                    $resp=Bind_event::factory("transfcvu")->run();
                    break;
                /*transferencia cvu reversada*/
                case "/transfcvurev":
                    $resp=Bind_event::factory("transfcvurev")->run();
                    break;
                /*transferencia debin aceptado*/
                case "/debinok":
                    $resp=Bind_event::factory("debinaceptado")->run();
                    break;
                    /*transferencia debin acreditado*/
                case "/debinacr":
                    $resp=Bind_event::factory("debinacreditado")->run();
                    break;
                    /*transferencia debin rechazado*/
                case "/debinrch":
                    $resp=Bind_event::factory("debinarechazado")->run();
                    break;

            }
            return $this->responder($resp);
        } catch (Exception $e){
            developer_log($e->getMessage());
            return null;
        }
    }
    private function responder($resp){
        return json_encode($resp);
    }
    public function get_variables(){
        return $this->variables;
    }
    
}
