<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of Bind_consulta_interface
 *
 * @author ariel
 */
interface Bind_consulta_interface {
   function consultar();
   function consultar_una(...$params);
}
