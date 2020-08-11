<?php
class Service_detalle_saldo extends Device_service{
    use Trait_detalle_saldo;
    public function ejecutar($array) {
        return $this->detallar($array);
        
    }

}