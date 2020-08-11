<?php

abstract class Validacion_sistema {

    //patron chain of responsability
    private $siguiente = null;
    //funcion run arranca y finaliza el encadenamiento
    protected function run() {
        //ejecuta la funcion ejecutar del objeto actual;
        $this->ejecutar();
        if ($this->next() != false)
        //ejecuta el metodo run del siguiente objeto, recursividad
            $this->next()->run();
    }

    abstract function ejecutar();

    protected function next() {
        if ($this->siguiente != null)
            return $this->siguiente;
        throw new Exception("Termina cadena");
        return false;
    }
    protected function setNext($next) {
        $this->siguiente=$next;
        return $this->next();
    }

    protected function encadenar() {
//        var_dump(new ReflectionClass(__CLASS__));
//        ->isSubclassOf($class));
        //busca las clases que son hijas de mi
        $archs = scandir(__DIR__);
        $clase_anterior = null;
        developer_log("Encadenando");
        foreach ($archs as $archivo) {
            //trucho pero optimo
            if (substr($archivo, 0, strlen("Validacion_")) == "validacion_") {
                $clase = ucfirst(substr($archivo, 0, strlen($archivo) - strlen(".php")));
                developer_log($clase);
                if ($clase != '') {
                    $reflector = new ReflectionClass($clase);
                    //valido que la clase por mas que se llame "validador_" sea extendida de 
                    //la la clase abstracta Validacion_sistema
                    if ($clase != false and $reflector->isSubclassOf(__CLASS__)) {
                        $objeto = new $clase();
                        //encadeno
                        if ($clase_anterior != null) {
                            $clase_anterior->siguiente = $objeto;
                        } else {
                            $this->siguiente = $objeto;
                        }
                        $clase_anterior = $objeto;
                    } else {
                        unset($archivo);
                    }
                }
            }
        }
    }

}
