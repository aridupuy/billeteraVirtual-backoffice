
<?php
// namespace Models;
abstract class Model {
    protected static $conexion; # Conexion a la base de datos # ES PRIVATE!!
    public static $prefijo_tabla='ef_'; # Re-escribir en caso que haga falta
    public static $id_tabla='id'; # Re-escribir en caso que haga falta
    public static $secuencia='paratodo'; #Secuencia de generacion de Ids # Re-escribir en caso que haga falta
    public static $transaccion_actual=0;
    const PREFIJO_GETTERS='get_';
    const PREFIJO_SETTERS='set_';
    public static $transaction_mode="READ UNCOMMITTED";

    final public function __construct($variables=false){

        if($variables) $this->init($variables);
        self::$conexion=self::singleton();
        return $this;
    }

    final public static function singleton() {
        if (!isset(self::$conexion)) {
            $DB = NewADOConnection(DATABASE_ENGINE);
            try {
                $resultado=$DB->Connect(DATABASE_HOST.":".DATABASE_PORT, DATABASE_USERNAME, DATABASE_USERPASS,DATABASE_NAME);
            } catch (Exception $e) {
                $resultado=false;
            }
            if($resultado AND false) developer_log('Conexion establecida con la base de datos.');
            if(!$resultado AND ACTIVAR_LOG_APACHE_LOGIN) developer_log('Fallo al establecerse la conexion con la base de datos.');
            if(!$resultado) exit();
            $DB->SetCharSet('utf8');
            
            self::$conexion=$DB;

        }
        return self::$conexion;
    }

    final public function set_id($id){ return $this->setId($id);}

    final public function get_id(){return $this->getId();}

    final public function getId(){
        $id_tabla = strtolower(static::$id_tabla);
        $metodo=self::PREFIJO_GETTERS.$id_tabla;
        if(method_exists($this,$metodo))
            return $this->$metodo();
        return false;
    }

    final public function setId($id){
        $id_tabla = strtolower(static::$id_tabla);
        $metodo=self::PREFIJO_SETTERS.$id_tabla;
        if(method_exists($this,$metodo))
            return $this->$metodo($id);
        return false;
    }

    final private function init($variables){
        # DEJAR EN PRIVATE!
        foreach($variables as $propiedad=>$valor):
          $method=self::PREFIJO_SETTERS.ucfirst($propiedad);
          if(method_exists($this, $method) && $valor!=='')
          $this->$method($valor);
        endforeach;
        return true;
    }

    public function get($id){
        if(!is_numeric($id)) return false;

        $sql="  SELECT *
                FROM ".static::$prefijo_tabla.strtolower(get_class($this))."
                WHERE ".static::$id_tabla."= ?
                ";
        $result=$this->execute_select($sql,$id);
        if($result AND $result->rowCount()==1) { return $this->init($result->GetRowAssoc(false)); }
        
        return false;
    }

    final public function parametros(){
        $parametros=array();
        $metodos=get_class_methods(get_class($this));
        foreach($metodos as $metodo):
            $atributo=explode(self::PREFIJO_GETTERS, $metodo);
            if(isset($atributo[1]) && $atributo[1]!==''){
                $atributo=strtolower($atributo[1]);
                
               if($this->$metodo()!==null && $this->$metodo()!=false) $parametros[$atributo]=$this->$metodo();

            }
        endforeach;
        unset($parametros['conexion']);

        return $parametros;
    }

    public function set(){

        $parametros=$this->parametros();
        $id_tabla=static::$id_tabla;
        $resultado=false;
        if(!$this->getId())
        {
            $parametros[$id_tabla]=$this->generar_id();
            if($parametros[$id_tabla]!==false)
            {   
                $this->setId($parametros[$id_tabla]);
                $parametros['id']=$this->get_id();
                $resultado= $this->execute_insert($parametros);
                if(!$resultado) $this->setId('');
            }
        }
        else
        {
            foreach($parametros as $clave=>$valor){
                if($valor!==0)
                    $parametros[$clave]=trim($valor);
                else
                    $parametros[$clave]=$valor;
            }
            $resultado= $this->execute_update($parametros);
        }
        if($resultado) return $resultado;
        return false;
    }

    public static function execute_select($sql,$variables=false,$limit=-1,$offset=-1){
        self::StartTrans();
        try{
            $tabla=strtolower(static::$prefijo_tabla.get_called_class());
            $sql=utf8_encode(quitar_saltos_de_linea($sql));
            if(ACTIVAR_LOG_APACHE_DE_CONSULTAS_SQL) 
                developer_log($sql);
            
            $tiempo_inicio=microtime(true);
            if($limit==-1) $limit=$GLOBALS['MAXIMO_REGISTROS_POR_CONSULTA'];
            if(substr($sql, 0,6)=="DELETE") $limit=-1;
            if(!$variables) $variables=false;
            $result=self::$conexion->SelectLimit($sql,$limit,$offset,$variables);
            $duracion=microtime(true)-$tiempo_inicio;
            if($result) $resultado=true; # No fallo la consulta, pero puede estar vacia
            else $resultado=false; # Fallo la consulta
            if(defined("SOLO_REPLICA") and SOLO_REPLICA!=='true')
            if($resultado) $resultado=Gestor_de_log::set_auto('SELECT', $tabla,static::$id_tabla,null, $resultado,$duracion);

        }
        catch(Exception $e){
            $resultado=false;
        }
        self::CompleteTrans();   
        if($resultado){
            return $result;
        }
        return false;
    }

    protected static function execute_update($parametros,$where=null){
        if( defined("SOLO_REPLICA") and SOLO_REPLICA=='true')
            throw new Exception("Solo el master puede hacer updates");
        self::StartTrans();
        try {
            $tabla=strtolower(static::$prefijo_tabla.get_called_class());
            if(isset($parametros[static::$id_tabla])){
                $id=$parametros[static::$id_tabla];
                unset($parametros[static::$id_tabla]);
                if($where==null) $where=static::$id_tabla."={$id}";
            }
//            var_dump($parametros);
            if($where==null) return false; # Sin where y sin Id, no parece una buena idea
            if(ACTIVAR_LOG_APACHE_DE_CONSULTAS_SQL) 
                developer_log('UPDATE '.$tabla.' WHERE '.$where, 0);
            $tiempo_inicio=microtime(true);
            
            $result=self::$conexion->AutoExecute($tabla, $parametros, 'UPDATE',$where);
            if($result) $resultado=true;
            else $resultado=false;
            $duracion=microtime(true)-$tiempo_inicio;

            if(isset($id)) $parametros[static::$id_tabla]=$id;
            if($resultado) $resultado=Gestor_de_log::set_auto('UPDATE',$tabla,static::$id_tabla,$parametros, $resultado,$duracion);
            
        } catch (Exception $e) {
            $resultado=false;
        }
        self::CompleteTrans();

        if($resultado){
            return $result;
        }
        return false;
    }

    protected static function execute_insert($parametros){
        # Retorna true en exito, false en fracaso.
        if( defined("SOLO_REPLICA") and SOLO_REPLICA=='true')
            throw new Exception("Solo el master puede hacer inserts");
        self::StartTrans();
        try {
            $tabla=strtolower(static::$prefijo_tabla.get_called_class());
            if(ACTIVAR_LOG_APACHE_DE_CONSULTAS_SQL) 
                developer_log('INSERT '.$tabla.' VALUES '.static::$id_tabla.'='.$parametros['id'], 0);
            $tiempo_inicio=microtime(true);
            $result=self::$conexion->AutoExecute($tabla, $parametros, 'INSERT');
            if($result) $resultado=true;
            else $resultado=false;
            $duracion=microtime(true)-$tiempo_inicio;
            if($resultado) $resultado=Gestor_de_log::set_auto('INSERT',$tabla,static::$id_tabla,$parametros, $resultado,$duracion);
            
        } catch (Exception $e) {
            $resultado=false;
        }
        self::CompleteTrans();
        if($resultado){
            return $result;
        }
        return false;     
    }
    final protected static function addEmptyOption($result,$content='Escoja una opción'){
        $resultAux=array();

        if(count($result)>=2) {
            $resultAux[]=array('value'=>'','content'=>$content);
            return array_merge($resultAux,$result);
        }

        if(count($result)==1){
            return $result;
        }

        $resultAux[]=array('value'=>'','content'=>$content);
        return $resultAux;
    }

    final public static function StartTrans(){
        self::$transaccion_actual++;
        if(ACTIVAR_LOG_TRANSACCIONES) developer_log(self::$transaccion_actual.' | Comienza una transaccion.');
        return self::$conexion->StartTrans();
    }

    final public static function HasFailedTrans(){
        #######if(ACTIVAR_LOG_TRANSACCIONES) developer_log('Verifica si hay transacciones fallidas.');
        return self::$conexion->HasFailedTrans();
    }
    
    final public static function CompleteTrans(){
        self::$transaccion_actual--;
        if(ACTIVAR_LOG_TRANSACCIONES) developer_log(self::$transaccion_actual.' | Completa una transaccion.');
        return self::$conexion->CompleteTrans();
    }
    final public static function SavePoint($nombre='mi_savepoint')
    {
        return self::$conexion->Execute("SAVEPOINT {$nombre};");
    }
    final public static function RollbackTo($nombre='mi_savepoint')
    {
        return self::$conexion->Execute("ROLLBACK TO {$nombre};");
    }
    final public static function getTrans(){

        return self::$conexion->_transOK;
    }

    final public static function FailTrans(){
        
        if(ACTIVAR_LOG_TRANSACCIONES) developer_log(self::$transaccion_actual.' | Falla una transaccion.');
        return self::$conexion->FailTrans();
    }

    final public static function RollbackTrans(){
        if(ACTIVAR_LOG_TRANSACCIONES) developer_log('Cancela una transaccion.');
        return self::$conexion->RollbackTrans();
    }

    final public function generar_id(){
        $tabla=strtolower(static::$prefijo_tabla.get_class($this));
        $id_tabla=static::$id_tabla;
        if(static::$secuencia===GENERAR_ID_ALEATORIO) return $this->generar_id_aleatorio($id_tabla,$tabla);
        if(static::$secuencia===GENERAR_ID_MAXIMO) return $this->generar_id_maximo($id_tabla,$tabla);
        else return $this->generar_id_secuencial();
    }

    final private function generar_id_aleatorio($id_tabla,$tabla) {
        // Funcion recursiva heredada de SDIN
        # Podria mejorarse y con un solo select conseguir el id
        # Falta verificar el overflow. Si el campo esta completo, ejecuta indefinidamente
        $i=$rac='';
        for ($i=0; $i<rand(4,9) ;$i++) {
            $rac.=chr(rand(49,57));
        }
        $ars=$this->execute_select("SELECT $id_tabla as xdatop FROM $tabla WHERE $id_tabla= ? ",$rac);
        $a=$ars->FetchRow();
        if ($rac!=$a['xdatop'])
        return ($rac);
        else
        return (self::generar_id_aleatorio($id_tabla,$tabla));
    }

    final private function generar_id_maximo($id_tabla,$tabla) {
        # Genera el siguiente ID, tomando el mayor
        $result=$this->execute_select("SELECT max($id_tabla) FROM $tabla");
        # Falta verificar el Overflow
        if($result AND $result->RowCount()){
            $row=$result->FetchRow();
            return $row[0]+1;
        }
        else return false;
    }

    final private function generar_id_secuencial(){

        $sql="SELECT nextval('".static::$secuencia."')";
        $result=$this->execute_select($sql);
        # Falta verificar el Overflow
        if($result AND $result->rowCount()) {
            $row=$result->FetchRow();
            return $row['nextval'];
        }
        return false;
    }

    public static function select($variables=false){
        $filtros=self::preparar_filtros($variables);
        $tabla = static::$prefijo_tabla.strtolower((get_called_class())); 
        $id_tabla = strtolower(static::$id_tabla); 
        $sql="  SELECT *
                FROM $tabla
                $filtros
                ORDER BY $id_tabla DESC 
                ";

        $result=self::execute_select($sql,$variables);    
        if($result) { return $result; }
        return false;
    }

    protected static function preparar_filtros($variables=false){
	unset($variables['dataTable_length']);
        # Deprecar esta funcion, se puede hacer tranquilamente con ADOdb selectlimit
        $filtros='';
        if($variables){
            $filtros='WHERE true ';
            foreach ($variables as $clave => $valor):
                $clave=strtolower($clave);
                $filtros .=" AND ".$clave."= ? ";    
            endforeach;
        }
        return $filtros;
    }

    final public function imprimir(){

        $string='<br/><br/><br/>';
        $string.=strtoupper(get_called_class());
        $string.='<br/>';
        $metodos=get_class_methods($this);
        foreach ($metodos as $metodo) {
            if(!strncmp($metodo,self::PREFIJO_GETTERS,4)){
                $atributo=substr($metodo,strlen(self::PREFIJO_GETTERS));
                $string.=$atributo;
                $string.='=';
                $string.=$this->$metodo();
                $string.='<br/>';
            }
        }

        return $string;
        exit();
    }
    
    final public function save_log($mensaje){
        $this->CompleteTrans();
        $this->StartTrans();
        $date = new DateTime("now");
        $date = $date->format("Y-m-d H:i:s");
        $this->set__log($mensaje ." " . json_encode($date));
        $this->set();
        Model::CompleteTrans();
        return true;
    }

    public static function fallar_transacciones_pendientes($dejar_vivas=0)
    {
        $j=self::$transaccion_actual-$dejar_vivas;
        for ($i=1; $i <= $j; $i++) { 
            self::FailTrans();
            self::CompleteTrans();
        }
        return true;
    }
    public static function do_not_use_this_method($sql,$variables=false,$limit=-1)
    {
        return self::execute_select($sql.' '.self::preparar_filtros($variables),$variables,$limit);
    }
    
    public static function execute($sql)
    {
        return self::$conexion->execute($sql);
    }
    public static function get_cantidad_de_transacciones(){
        return self::$transaccion_actual;
    }
    public static function setTransacctionMode($transaction){
  //      self::$transaction_mode=$transaction;
//        self::$conexion->setTransactionMode(self::$transaction_mode);
    }
    public static function select_stat_activity(){
        $sql="select count(*) as cantidad from pg_stat_activity";
        return self::execute_select($sql);
    }
}
