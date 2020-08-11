<?php

class Calendario extends View
{
    const ACTIVAR_DEBUG=false;
    const FECHA='fecha';
    const TITULO='titulo';
    const CUERPO='cuerpo';
    const PIE='pie';

    const CLASE_CALENDARIO='calendario';
    const CLASE_MES_PAR='mp';
    const CLASE_MES_IMPAR='mi';
    const CLASE_SEMANA='semana';
    const CLASE_TITULO_DIA='titulo_dia';
    const CLASE_DIA='dia'; 
    const CLASE_DIA_HOY='hoy'; 
    const CLASE_DIA_VACIO='evento';
    const CLASE_EVEMTO='evento';
    const CLASE_NUMERO_DIA='numero_dia';
    private $con_titulo=false;
    public function __construct(Datetime $desde,Datetime $hasta=null, $agregar_padding=true)
    {
        if($hasta==null) $hasta=clone $desde;
        parent::__construct();
        if(self::ACTIVAR_DEBUG) developer_log('Creando calendario vacio.');
        $div_calendario = $this->createElement('div');
        $div_calendario->setAttribute('id','calendario');
        $div_calendario->setAttribute('class',self::CLASE_CALENDARIO);
        $div_calendario->setIdAttribute('id',true);
        $this->appendChild($div_calendario);
        $css=new View();
        $css->loadHTMLFile(PATH_CORE.'css/calendario.css');
        $div_calendario->appendChild($this->importNode($css->documentElement,true));
        if($hasta>=$desde) 
            $this->crear($desde,$hasta,$agregar_padding);
        return $this;
    }
    private function crear(Datetime $desde,Datetime $hasta, $agregar_padding)
    {
        $fecha=clone $desde;
        $div_calendario=$this->getElementById('calendario');
        $this->poner_dias_titulo($desde, $agregar_padding);
        $contador=0;
        $hoy=new Datetime('today');
        while ( $fecha<= $hasta) {

            if($contador==0 OR $contador%7==0){
                $div_semana=$this->createElement('div');
                if($contador==0){
                    if($agregar_padding){
                        $agregado=$this->agregar_padding($div_semana, $fecha, false);
                        $contador=$contador+$agregado;
                    }
                }
                $div_semana->setAttribute('class',self::CLASE_SEMANA);
                $div_calendario->appendChild($div_semana);
            }
            $div_dia=$this->createElement('div');
            $div_dia->setAttribute('id',$fecha->format('Ymd'));
            $mes=$fecha->format('m');
            if($mes % 2==0) $clase=self::CLASE_DIA.' '.self::CLASE_MES_PAR;
            else $clase=self::CLASE_DIA.' '.self::CLASE_MES_IMPAR;
            if($hoy->format('Ymd')==$fecha->format('Ymd'))
                $clase.=' '.self::CLASE_DIA_HOY;
            $div_dia->setAttribute('class',$clase);
            $div_dia->setIdAttribute('id',true);
            $div_dia->setAttribute('title',$fecha->format('j \d\e M \d\e Y'));
            $div_numero_dia=$this->createElement('div',$fecha->format('j'));
            $div_numero_dia->setAttribute('class',self::CLASE_NUMERO_DIA);
            $div_dia->appendChild($div_numero_dia);
            $div_semana->appendChild($div_dia);
            if($fecha->format('d')=='1')
                $div_numero_dia->appendChild($this->createTextNode (" ".$this->meses_espanol ($fecha->format('m'))." ".$fecha->format('Y')));
            $fecha=$fecha->add(new DateInterval('P1D'));
            $contador++;
        }
        
        # NO FUNCIONA CUANDO SE ROTA EL CALENDARIO!!!
        $this->agregar_padding($div_semana,$fecha,true);
        return true;

    }
    private function agregar_padding($div_semana, DateTime $fecha, $padding_final=false)
    {
        $dia_semana=$fecha->format('N');
        if(!$padding_final){
            $inicio=1;
            $fin=$dia_semana;
        }
        else{
            $inicio=$dia_semana;
            if($inicio==1) $fin=$inicio;
            else $fin=8;
        }
        for($i=$inicio;$i<$fin;$i++){
            $div_vacio=$this->createElement('div');
            $div_vacio->setAttribute('class', self::CLASE_DIA);
            $div_semana->appendChild($div_vacio);
        }
        return $fin-$inicio;
    }
    private function poner_dias_titulo(DateTime $desde, $agregar_padding)
    {
        $dias=array('Lunes','Martes','Miércoles','Jueves','Viernes','Sábado','Domingo');
        if(!$agregar_padding){
            $dias_aux=array();
            for ($i=0; $i < 7; $i++) { 
                $dias_aux[]=$dias[($desde->format('N')-1+$i) % 7];
            }
            $dias=$dias_aux;
            unset($dias_aux);
        }
        $calendario=$this->getElementById('calendario');
        foreach ($dias as $dia) {
            $div=$this->createElement('div',$dia);
            $div->setAttribute('class',self::CLASE_TITULO_DIA);
            $calendario->appendChild($div);
        }
    }
    private function meses_espanol($mes)
    {
        $meses=array('Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre');
        return $meses[$mes-1];
    }
    public function crear_referencia($clase,$titulo)
    {
        $referencia= $this->createElement('div');
        $referencia->setAttribute('class', 'referencia');
        $color= $this->createElement('div');
        $color->setAttribute('class', $clase);
        $referencia->appendChild($color);
        $span= $this->createElement('span',$titulo);
        $referencia->appendChild($span);
        return $referencia;
    }
    public function crear_referencia_con_checkbox($variables,$clase,$titulo,$id)
    {
        $referencia= $this->createElement('div');
        $referencia->setAttribute('class', 'referencia');
        $color= $this->createElement('div');
        $color->setAttribute('class', $clase);
        if(isset($variables[$id]) AND $variables[$id]==1)
           $this->crear_checkbox($color, $id, 1) ;
        else
            $this->crear_checkbox($color, $id, 0) ;
        $referencia->appendChild($color);
        $span= $this->createElement('span',$titulo);
        $referencia->appendChild($span);
        return $referencia;
    }
    private function crear_checkbox($elemento, $titulo,$value)
    {
        $ck=$this->createElement('input');
        $ck->setAttribute('type', 'checkbox');
        $ck->setAttribute('name', $titulo);
        $ck->setAttribute('id', $titulo);
        $elemento->appendChild($ck);
        if($value==1){
            $ck->setAttribute('checked', 'checked');
            $ck->setAttribute('value','1');
            
        }
        else{
                $ck->setAttribute('value','0');
        }
        $ck->setAttribute('style', 'background-position: -1px -151px;
            width: 19px;
            height: 19px;');
    }
}