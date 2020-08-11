<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of encriptador_pei
 *
 * @author ariel
 */
class Encriptador_pei {
    //put your code here
    private static $claveEncriptacion = "130BCF1E06DE5282D6B8E9D68D5E7F4211569D7C8754205FF3F9369E22CDAFA2";
    const APLICATIVO=PATH_PUBLIC."/encriptador_pei/JavaApplication1.jar";
    public static function encriptar($texto){
        $command="java -jar ".self::APLICATIVO." '$texto' '".self::$claveEncriptacion."' ";
//        var_dump($command);
        $output = array();
        $return_var = 0;
        exec($command, $output, $return_var);
        return ($output[0]);
        
    }
}
