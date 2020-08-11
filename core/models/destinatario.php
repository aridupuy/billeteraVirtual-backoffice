<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of destinatario
 *
 * @author ariel
 */
class Destinatario extends Model{
    //put your code here
        public static $id_tabla = "id_destinatario";
        private $id_destinatario;
        private $cuit;
        private $cvu;
        private $cbu;
        private $alias;
        private $nombre;
        private $apellido;
        private $referencia;
        private $id_cuenta;
        private $email;
        
        public function get_email() {
            return $this->email;
        }

        public function set_email($email) {
            $this->email = $email;
            return $this;
        }

                public function get_id_destinatario() {
            return $this->id_destinatario;
        }

        public function get_cuit() {
            return $this->cuit;
        }

        public function get_cvu() {
            return $this->cvu;
        }

        public function get_cbu() {
            return $this->cbu;
        }

        public function get_alias() {
            return $this->alias;
        }

        public function get_nombre() {
            return $this->nombre;
        }

        public function get_apellido() {
            return $this->apellido;
        }

        public function get_referencia() {
            return $this->referencia;
        }

        public function get_id_cuenta() {
            return $this->id_cuenta;
        }

        public function set_id_destinatario($id_destinatario) {
            $this->id_destinatario = $id_destinatario;
            return $this;
        }

        public function set_cuit($cuit) {
            $this->cuit = $cuit;
            return $this;
        }

        public function set_cvu($cvu) {
            $this->cvu = $cvu;
            return $this;
        }

        public function set_cbu($cbu) {
            $this->cbu = $cbu;
            return $this;
        }

        public function set_alias($alias) {
            $this->alias = $alias;
            return $this;
        }

        public function set_nombre($nombre) {
            $this->nombre = $nombre;
            return $this;
        }

        public function set_apellido($apellido) {
            $this->apellido = $apellido;
            return $this;
        }

        public function set_referencia($referencia) {
            $this->referencia = $referencia;
            return $this;
        }

        public function set_id_cuenta($id_cuenta) {
            $this->id_cuenta = $id_cuenta;
            return $this;
        }


}
