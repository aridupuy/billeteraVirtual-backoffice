<?php 

abstract Class Boleta
{
	const ACTIVAR_DEBUG=true;
	public $bolemarchand=false;    #Objeto #NO_OPTIMIZAR
    public static $marchand=false;               #Objeto #OPTIMIZAR
    public static $trixgroup=false;        #Objeto #OPTIMIZAR
    public static $sc=false;                #Objeto #OPTIMIZAR
	public $barcode_1=false;     #Objeto #NO_OPTIMIZAR
    public $barcode_2=false;     #Objeto #NO_OPTIMIZAR
    public $barcode_3=false;     #Objeto #NO_OPTIMIZAR
    public $barcode_4=false;     #Objeto #NO_OPTIMIZAR

    const DUMMY_GENSCRIPT='Boleta(Trait)';
    const DUMMY_GENUSU=1;
    const DUMMY_IS_POSTED=0;
    const DUMMY_XML_BOLETA='<deprecated/>';
    const DUMMY_ITEM_PAGO='';
    const DUMMY_TIPO_PAGO='';

    ##### REEMPLAZOS #####
    const PATRON_INICIO="\\{\\{";
    const PATRON_FINAL="\\}\\}";
    ##### REEMPLAZOS #####
    const NOMBRE_MARCHAND="NOMBRE";
    const RAZON_SOCIAL="RAZON_SOCIAL";
    const CUIT="CUIT";
    const TELEFONO="TELEFONO";
    const DIRECCION="DIRECCION";
    const NRO_BOLETA="NRO_BOLETA";
    const CONCEPTO="CONCEPTO";
    const DETALLE="DETALLE";
    const FECHA="FECHA";
    const FECHA_MES_ESP="FECHA_MES_ESP";
    const LOGO="LOGO";
    const SERVICIO = "SERVICIO";
    const TIPO_PAGO = "TIPO_PAGO";
    const ID_TRANS="ID";
    const ID_CLIENTE="ID_CLIENTE";
    const NOMBRE_CLIENTE="NOMBRE_CLIENTE";
    const BARCODE_1="CODIGO_DE_BARRAS_1";
    const FECHA_VENCIMIENTO_1="FECHA_VENCIMIENTO_1";
    const MONTO_1="MONTO_1";
    const BARCODE_2="CODIGO_DE_BARRAS_2";
    const FECHA_VENCIMIENTO_2="FECHA_VENCIMIENTO_2";
    const MONTO_2="MONTO_2";
    const BARCODE_3="CODIGO_DE_BARRAS_3";
    const FECHA_VENCIMIENTO_3="FECHA_VENCIMIENTO_3";
    const MONTO_3="MONTO_3";
    const BARCODE_4="CODIGO_DE_BARRAS_4";
    const FECHA_VENCIMIENTO_4="FECHA_VENCIMIENTO_4";
    const MONTO_4="MONTO_4";
    const CODIGO_ELECTRONICO='CODIGO_ELECTRONICO';
    ##### REEMPLAZOS #####
    
    protected static function optimizar_marchand($id_marchand)
    {
        if(!self::$marchand OR self::$marchand->get_id()!==$id_marchand){
            self::$marchand=new Marchand();
            self::$marchand->get($id_marchand);
        }
        return self::$marchand;
    }
    protected function optimizar_trixgroup(Trix $trix)
    {
        if(!self::$trixgroup OR self::$trixgroup->get_id()!==$trix->get_id_trixgroup()){
            $trixgroups=Trixgroup::Select(array('id_trixgroup'=>$trix->get_id_trixgroup()));
            if(!$trixgroups OR $trixgroups->RowCount()!=1){
                return false;   
            }
            self::$trixgroup=new Trixgroup($trixgroups->FetchRow());
        }
        return self::$trixgroup;
    }
    protected function optimizar_sc($id_sc)
    {
        if(!self::$sc OR self::$sc->get_id()!==$id_sc) {
            self::$sc=new Sc();
            self::$sc->get($id_sc);
        }
        return self::$sc;
    }
    protected static function obtener_mes_español($fecha){
        $meses = array("Enero","Febrero","Marzo","Abril","Mayo","Junio","Julio","Agosto","Septiembre","Octubre","Noviembre","Diciembre");
        $fecha=date('d/m/Y',strtotime($fecha));
        $fecha=DateTime::createFromFormat("d/m/Y", $fecha);
        return $meses[(int)$fecha->format("m")-1]." ".$fecha->format("Y");
    }
    protected static function cargar_reemplazos(Bolemarchand $bolemarchand, Barcode $barcode_1, $barcode_2=null, $barcode_3=null, $barcode_4=null, $detalle=null, $id_trans=null, $servicio=null, $tipo_pago=null)
    {
        if(!(self::$marchand=self::optimizar_marchand($bolemarchand->get_id_marchand()))) {
            if(self::ACTIVAR_DEBUG) developer_log('Ha ocurrido un error. No se pudo obtener el Marchand(3).');
            return false;
        }
        $reemplazos=array(
            self::SERVICIO=>$servicio,
            self::TIPO_PAGO=>$tipo_pago,
            self::ID_TRANS=>$id_trans,
            self::NOMBRE_MARCHAND=>self::$marchand->get_apellido_rs(), 
            self::RAZON_SOCIAL=>self::$marchand->get_apellido_rs(),
            self::CUIT=>self::$marchand->get_documento(),
            self::DIRECCION=>self::$marchand->get_gr_calle().' '.self::$marchand->get_gr_numero(),
            self::TELEFONO=>self::$marchand->get_telefonos(),
            self::CONCEPTO=>$bolemarchand->get_boleta_concepto(),
            self::NRO_BOLETA=>$bolemarchand->get_nroboleta(), 
            self::FECHA=>date('d/m/Y',strtotime($bolemarchand->get_emitida())), 
            self::FECHA_MES_ESP=>self::obtener_mes_español($bolemarchand->get_emitida()), 
            self::LOGO=>"<img height='200px' src='".URL_LOGO.self::$marchand->get_mlogo()."'>", 
            self::BARCODE_1=>$barcode_1->get_barcode(), 
            self::FECHA_VENCIMIENTO_1=>date('d/m/Y',strtotime($barcode_1->get_fecha_vto())), 
            self::MONTO_1=>$barcode_1->get_monto(),
            self::CODIGO_ELECTRONICO=>$barcode_1->get_pmc19()
            );
        
        if($detalle!=null){
            $reemplazos[self::DETALLE]=$detalle;
        }
        else{
            $reemplazos[self::DETALLE]="";   
        }
        if($barcode_2!=null){
            $reemplazos[self::BARCODE_2]=$barcode_2->get_barcode(); 
            $reemplazos[self::FECHA_VENCIMIENTO_2]=date('d/m/Y',strtotime($barcode_2->get_fecha_vto()));
            $reemplazos[self::MONTO_2]=$barcode_2->get_monto();
        }
        else{
            $reemplazos[self::BARCODE_2]=""; 
            $reemplazos[self::FECHA_VENCIMIENTO_2]="";
            $reemplazos[self::MONTO_2]="";   
        }

        if($barcode_3!=null){
            $reemplazos[self::BARCODE_3]=$barcode_3->get_barcode();
            $reemplazos[self::FECHA_VENCIMIENTO_3]=date('d/m/Y',strtotime($barcode_3->get_fecha_vto()));
            $reemplazos[self::MONTO_3]=$barcode_3->get_monto();
        }
        else{
            $reemplazos[self::BARCODE_3]=""; 
            $reemplazos[self::FECHA_VENCIMIENTO_3]="";
            $reemplazos[self::MONTO_3]="";   
        }
        if($barcode_4!=null){
            $reemplazos[self::BARCODE_4]=$barcode_4->get_barcode();
            $reemplazos[self::FECHA_VENCIMIENTO_4]=date('d/m/Y',strtotime($barcode_4->get_fecha_vto()));
            $reemplazos[self::MONTO_4]=$barcode_4->get_monto();
        }
        else{
            $reemplazos[self::BARCODE_4]=""; 
            $reemplazos[self::FECHA_VENCIMIENTO_4]="";
            $reemplazos[self::MONTO_4]="";   
        }
        return $reemplazos;
    }
    protected static function reemplazar_paquetes($string)
    {
        $patrones=array();
        $reemplazos=array();
        libxml_use_internal_errors(true);
        $paquetes_html=new DOMDocument('1.0', 'utf-8');
        $paquetes_html->loadHTMLFile(PATH_EXTERNO.'views/paquetes.html');
        libxml_clear_errors();
        $paquetes=$paquetes_html->getElementById('paquetes');
        foreach ($paquetes->childNodes as $paquete) {
            if(get_class($paquete)=='DOMElement'){
                if($paquete->getAttribute('class')=='paquete' AND $paquete->hasAttribute('id')){
                    $patron=strtoupper($paquete->getAttribute('id'));
                    $patrones[]='/'.self::PATRON_INICIO.'\b'.$patron.'\b'.self::PATRON_FINAL.'/';
                    $reemplazos[]=$paquetes_html->saveXML($paquete);

                }
            }
        }
        return preg_replace($patrones, $reemplazos, $string);
    }
    protected static function reemplazar($string, $patron_reemplazo)
    {
        $patrones=array();
        $reemplazos=array();
        foreach ($patron_reemplazo as $patron => $reemplazo) {
            $patrones[]='/'.self::PATRON_INICIO.'\b'.$patron.'\b'.self::PATRON_FINAL.'/';
            $reemplazos[]=$reemplazo;
        }
        return preg_replace($patrones, $reemplazos, $string);
    }
    public  function obtener_desde_bolemarchand(Bolemarchand $bolemarchand){
        $rs= Barcode::select(array("id_boletamarchand"=>$bolemarchand->get_id()));
//        var_dump($bolemarchand->get_id());
        $this->bolemarchand=$bolemarchand;
        foreach ($rs as $cont=>$row){
            $barcode=new Barcode($row);
            switch ($barcode->get_id_tipopago()){
                case "100":
                case "501":
                    $this->barcode_1=$barcode;
                    break;
                case "502":
                     $this->barcode_2=$barcode;
                    break;
                case "503":
                     $this->barcode_3=$barcode;
                    break;
                case "504":
                    $this->barcode_4=$barcode;
                    break;  
            }
        }
        return $this;
    }
}
