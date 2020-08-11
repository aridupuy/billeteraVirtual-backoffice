<?php
class Webservice_obtener_saldo extends Webservice {
use Trait_detalle_saldo;
    public function ejecutar($array) {
        return $this->detallar($array);
    }

}
