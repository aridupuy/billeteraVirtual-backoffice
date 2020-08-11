<?php
class Landing_boton_de_pago 
{
	private $boleta;
	private $marchand;
	public $errores;
        public $valores_variables;
        const FORMATO_FECHA_POST='Ymd';
	const IDENTIFICADOR='bopa_idcomercio';
	const IMPORTE='bopa_importe';
	const VENCIMIENTO='bopa_fvto';
	const DETALLE='txi_detalle';
	const NOMBRE='bopa_nombre';
	const APELLIDO='bopa_apellido';
	const CORREO='bopa_email';
	const DOCUMENTO='txi_documento';
	const DIRECCION='txi_direccion';
        public function get_boleta() {
            return $this->boleta;
        }

        public function get_marchand() {
            return $this->marchand;
        }

        public function set_boleta($boleta) {
            $this->boleta = $boleta;
            return $this;
        }

        public function set_marchand($marchand) {
            $this->marchand = $marchand;
            return $this;
        }

        public function procesar($post)
	{	
		error_log(json_encode($post));
		$respuesta=false;
		list($array,$valores_variables)=$this->preparar_post($post);
                $this->valores_variables=$valores_variables;
		list($validez,$mensaje)=$this->validar_post($array);
		if(!$validez){
			$this->errores=$mensaje;
			return false;
		}
		$mercalpha=$post[self::IDENTIFICADOR];
		$importe=$post[self::IMPORTE];
		$vencimiento=$post[self::VENCIMIENTO];
		$vencimiento=Datetime::createFromFormat(self::FORMATO_FECHA_POST,$array[self::VENCIMIENTO]);
		$vencimiento=$vencimiento->format('d/m/Y');
		$concepto=utf8_encode($post[self::DETALLE]);
                if(isset($post[self::NOMBRE]))
                    $datos[Boleta_comprador::NOMBRE]=$post[self::NOMBRE];
                else
                    $datos[Boleta_comprador::NOMBRE]="Sin Nombre";
                if(isset($post[self::APELLIDO]))
                    $datos[Boleta_comprador::APELLIDO]=$post[self::APELLIDO];
                else
                    $datos[Boleta_comprador::APELLIDO]="Sin Apellido";
                if(isset($post[self::CORREO]))
                    $datos[Boleta_comprador::CORREO]=$post[self::CORREO];
                else
                    $datos[Boleta_comprador::CORREO]="Sin Correo";
                if(isset($post[self::CORREO]))
                    $datos["bopa_apellido"]=$post[self::CORREO];
                else
                    $datos["bopa_apellido"]="Sin Correo";
		if(isset($post[self::DOCUMENTO])){
			$datos[Boleta_comprador::DOCUMENTO]=$post[self::DOCUMENTO];
		}
		else  
                    $datos[Boleta_comprador::DOCUMENTO]="";
		if(isset($post[self::DIRECCION])){
			$datos[Boleta_comprador::DIRECCION]=$post[self::DIRECCION];
		}
		else{
			$datos[Boleta_comprador::DIRECCION]="";
		}
		$recordset=Marchand::select(array('mercalpha'=>$mercalpha));
		if(!$recordset OR $recordset->RowCount()==0){
			$this->errores='El código de comercio no es correcto. ';
			return false;
		}
		$this->marchand=new Marchand($recordset->FetchRow());
		$this->boleta=new Boleta_comprador();
		try {
			$respuesta=$this->boleta->crear($this->marchand->get_id_marchand(), $datos, array($importe),array($vencimiento),$concepto,$valores_variables);
				
		} catch (Exception $e) {
			$this->errores=$e->getMessage();
		}
		if($respuesta) return true;
		if($this->errores=='') 
			$this->errores='Ha ocurrido un error.';
		return false;
	}
	private function preparar_post($post)
	{
		//var_dump($post);
		if(isset($post[self::IDENTIFICADOR]))
			$post[self::IDENTIFICADOR]=strtoupper($post[self::IDENTIFICADOR]);
		if(isset($post[self::DOCUMENTO])){
			$post[self::DOCUMENTO]=str_replace('-', '', $post[self::DOCUMENTO]);
			$post[self::DOCUMENTO]=str_replace('.', '', $post[self::DOCUMENTO]);
		}
		if(isset($post[self::VENCIMIENTO]) AND !Datetime::createFromFormat(self::FORMATO_FECHA_POST, $post[self::VENCIMIENTO])){
			$respuesta=interpretar_fecha_relativa($post[self::VENCIMIENTO]);
			if($respuesta!==false) $post[self::VENCIMIENTO]=$respuesta->format(self::FORMATO_FECHA_POST);
			
		}

		foreach ($post as $key => $value) {
			$post[$key]=trim($value);
		}
		if(!isset($post[self::VENCIMIENTO]) OR $post[self::VENCIMIENTO]==''){
                        $validez=true;
                        $datetime=new DateTime("now");
                        $datetime->add(new DateInterval("P2D"));
                        $fecha=$datetime->format(self::FORMATO_FECHA_POST);
                        $post[self::VENCIMIENTO]=$fecha;
                        error_log(json_encode($post));
                }
                if(isset($post["bopa_labelto"])){
                    $columnas = explode(" ", $post["bopa_labelto"]);
                    $valores = explode(",", $post["bopa_thelabel"]);
                    foreach ( $columnas as $c=>$columna){
                        $valores_mostrables[$valores[$c]]=$post[$columna];
                       	unset($post[$columna]);
                    }
                }
                foreach ($post as $clave=>$value){
                    if(substr($clave, 0,4)=="txi_"){
                        $valores_variables[str_replace("txi_","", $clave)]=$value;
                    }
                    if(substr($clave, 0,5)=="bopa_" and !strstr($clave,"url") 
		    and !in_array($clave,array("bopa_resend","bopa_idcomercio","bopa_genscript","bopa_labelto","bopa_thelabel","bopa_reques","bopa_test"))){
			if($clave=="bopa_fvto"){
				$fecha=Datetime::createFromFormat("Ymd",$value);
				$value=$fecha->format("d/m/Y");
			}
                        $valores_variables[str_replace("bopa_","", $clave)]=$value;
                    }
                }
                $valores_variables=array_merge($valores_mostrables,$valores_variables);
//		$valores_variables=$valores_mostrables;
		return array($post,$valores_variables);
	}
	private function validar_post($post)
	{
		$validez=true;
		$mensaje='';
//                var_dump($post);
		if(!isset($post[self::IDENTIFICADOR]) OR $post[self::IDENTIFICADOR]==''){
			$validez=false;
			$mensaje.="Debe enviar el campo '".self::IDENTIFICADOR."'. ";
		}
		elseif(!validar_mercalpha($post[self::IDENTIFICADOR])){
			$validez=false;
			$mensaje.="El campo '".self::IDENTIFICADOR."' no es correcto. ";
		}
		if(!isset($post[self::IMPORTE]) OR $post[self::IMPORTE]==''){
			$validez=false;
			$mensaje.="Debe enviar el campo '".self::IMPORTE."'. ";
		}
		elseif((!is_numeric($post[self::IMPORTE]) OR $post[self::IMPORTE]<0) OR $post[self::IMPORTE]>99999.99) {
			$validez=false;
			$mensaje.="El formato del campo '".self::IMPORTE."' no es correcto. ";
		}
		if(!isset($post[self::VENCIMIENTO]) OR $post[self::VENCIMIENTO]==''){
			$validez=false;
			$mensaje.="Debe enviar el campo '".self::VENCIMIENTO."'. ";
		}
		elseif(!Datetime::createFromFormat(self::FORMATO_FECHA_POST, $post[self::VENCIMIENTO])){
			$validez=false;
			$mensaje.="El formato del campo '".self::VENCIMIENTO."' no es correcto. ";	
		}
		if(!isset($post[self::DETALLE]) OR $post[self::DETALLE]==''){
			$validez=true;
			$mensaje.="Debe enviar el campo '".self::DETALLE."'. ";
		}
		if(!isset($post[self::NOMBRE]) OR $post[self::NOMBRE]==''){
			$validez=true;
			$mensaje.="Debe enviar el campo '".self::NOMBRE."'. ";
		}
		if(!isset($post[self::APELLIDO]) OR $post[self::APELLIDO]==''){
			$validez=true;
			$mensaje.="Debe enviar el campo '".self::APELLIDO."'. ";
		}
		if(!isset($post[self::CORREO]) OR $post[self::CORREO]==''){
			$validez=true;
			$mensaje.="Debe enviar el campo '".self::CORREO."'. ";
		}
		elseif(!validar_correo($post[self::CORREO])){
			$validez=true;
			$mensaje.="El formato del campo '".self::CORREO."' no es correcto. ";
		}
		if(!isset($post[self::DOCUMENTO]) OR $post[self::DOCUMENTO]==''){
			$validez=true;
			$mensaje.="Debe enviar el campo '".self::DOCUMENTO."'. ";
		}
		elseif(!validar_cuit($post[self::DOCUMENTO]) AND !validar_dni($post[self::DOCUMENTO])) {
			$validez=true;
			$mensaje.="El formato del campo '".self::DOCUMENTO."' es correcto. ";
		}
		if(!isset($post[self::DIRECCION]) OR $post[self::DIRECCION]==''){
			$validez=true;
			$mensaje.="Debe enviar el campo '".self::DIRECCION."'. ";
		}

		return array($validez, $mensaje);
	}
	public function mostrar_boleta($boleta=null)
	{
            if($boleta==null){
                return $boleta->get_boleta_html();
            }
            else
                return $this->boleta->bolemarchand->get_boleta_html();
	}
        public function obtener_barcode(){
            return $this->boleta->barcode_1->get_barcode();
        }
        public function obtener_monto(){
            return $this->boleta->barcode_1->get_monto();
        }
        public function obtener_id_boleta(){
            return $this->boleta->barcode_1->get_id_boletamarchand();
        }
}
