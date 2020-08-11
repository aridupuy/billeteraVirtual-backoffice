<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of ExceptionApiGire
 *
 * @author ariel
 */
class ExceptionApiGire extends Exception{
    //put your code here
    public function __construct( $message = "",$codigo_error) {
        $this->code=$codigo_error;
        $this->message=$message;
    }
}
