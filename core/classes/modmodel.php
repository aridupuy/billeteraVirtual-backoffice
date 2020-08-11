<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of modModel
 *
 * @author juan
 */
class Modmodel {
    //put your code here
    public function store_Model() {

        $_SESSION["viewmodel"] = serialize($this);
    }

        public function update() {
            $this->store_Model();
        }
     
        public function get_Model() {
        return unserialize($_SESSION["viewmodel"]);
    }
}
