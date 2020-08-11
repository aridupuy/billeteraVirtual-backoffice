<?php

class Webservice_meta extends Webservice{

	public function ejecutar($array)
	{
		$error=false;
		foreach($array as $key => $aux){
			if(!is_numeric($key)){
				$error=true;
			}
			if(!is_array($aux)){
				$error=true;
			}
		}
		if($error){
			$this->adjuntar_mensaje_para_usuario("Los índices deben ser todos numéricos y los valores deben ser vectores. ");
			$this->respuesta_ejecucion=self::RESPUESTA_EJECUCION_INCORRECTA;
			return false;
		}
		foreach ($array as $key => $sub_parametros_de_entrada) {
            $sub_parametros_de_entrada[Webservice::PARAMETRO_MERCALPHA]=self::$marchand->get_mercalpha();
            $sub_parametros_de_entrada[Webservice::PARAMETRO_SID]=false;
            $sub_parametros_de_salida=Webservice::fabrica($sub_parametros_de_entrada);
            $this->adjuntar_dato_para_usuario($sub_parametros_de_salida);
		}
        $this->adjuntar_mensaje_para_usuario("Meta-Método ejecutado. ");
		$this->respuesta_ejecucion=self::RESPUESTA_EJECUCION_CORRECTA;
		return true;
	}	
}