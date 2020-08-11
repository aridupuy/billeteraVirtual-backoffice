<?php

class Landing_proveedor {

    private $state;
    private $proveedorPendiente;
    public $viewName;
    private $cliente;
    public function get_proveedor_pendiente(){
        return $this->proveedorPendiente;
    }

    public function set_view($nombreDeView) {
        $this->viewName = $nombreDeView;
    }
    public function get_view(){
        return $this->viewName;
    }
    public function set_noview() {
        $this->viewName = '';
    }

    public function get_mercalpha() {
        if (!isset($this->cliente)) {
            return "cliente no seteado ";
        }
        return $this->cliente->marchand->get_mercalpha();
    }

    public function __construct($id) {
        /* recupera el proveedor pendiente y setea el 
         * estado con el estado correspondiente state pattern
         */
//        $record_set = Proveedor_pendiente::select_min($id);
        $record_set = Pago_proveedor::datos_pago($id);
        developer_log("id de proveedor pendiente=$id");
        $row = $record_set->fetchRow();
        //$this->proveedorPendiente = new Proveedor_pendiente($row);
        // obtiene el estado para esa row 
        // todo el procesamiento se delega en el estado.
        $estado = $this->obtener_estado($row);
        //$estado = $this->obtener_estado($this->proveedorPendiente);
        $this->set_state($estado);
        $this->state->procesar($this);
    }

    public function debug() {
        $response = get_class($this) . "->" . get_class($this->state);
        return $response;
    }

    private function obtener_estado(Proveedor_pendiente $proveedor) {
        /*
         * obtiene el estado a partir de la fecha a ver si vencio.
         * si esta vencido , si ya respondio o si esta correcto 
         */
        $acepto = $proveedor->get_acepto();
        $fecha_gen = DateTime::createFromFormat('Y-m-d', $proveedor->get_fecha_gen());
        $class = $this->estadoSegunData($acepto, $fecha_gen);
        return $class;
    }

    public function obtener_pantalla() {
        developer_log("######### " . $this->viewName);
        return $this->viewName;
    }

    public function acepto() {
        $this->updateAcepto(true);
        $this->state->acepto($this->proveedorPendiente);
    }

    public function showView(View $view) {
        error_log($this->viewName);
	$view->load($this->viewName);
        
    }
    public function mostrar(View $view){
        echo $view->saveHTML();
    }
    public function rechazo(Landing_proveedor $landing) {
        $this->updateAcepto(false);
        $this->state->rechazo($landing);
    }

    private function set_state($a) {
        developer_log("set stateTo:");
        $this->state = $a;
    }

    private function estadoSegunData($acepto, $fecha) {

        $fecha_ven = clone $fecha;
        $today = new DateTime('now');
        $fecha_ven->add(new DateInterval('P6D'));

        while (true) {
            if ($acepto === null) { //nunca entro 
                if ($this->vencio($today, $fecha_ven)) { // nunca entro  y esta vencido 
                    developer_log("ESTADO:VENCIDO");
                    $response = new Landing_proveedor_vencido($this);
                } else { // no esta vencido
                    developer_log("ESTADO:CORRECTO");
                    $response = new Landing_proveedor_correcto($this);
                }
                break;
            }
            // antes acepto o no acepto pero ya fue por el reverso  
            developer_log("ESTADO:PROCESADO");
            $response = new Landing_proveedor_procesado($this);
            break;
        }
        return $response;
    }

    private function vencio($today, $fecha_ven) {
        return $today->format('Y-m-d') > $fecha_ven->format('Y-m-d');
    }

    private function updateAcepto($acepto) {
        $this->proveedorPendiente->set_acepto($acepto);
        $this->proveedorPendiente->set();
    }

    private function set_cliente($cliente) {
        $this->cliente = $cliente;
    }

    public function putData($view, $model, $p) {
        $this->state->putData($view, $model, $p);
    }

    public function get_row() {
        return $this->proveedorPendiente;
    }

    public function cliente() {
        return $this->cliente;
    }

    public function get_id() {
        return $this->proveedorPendiente->get_id_move();
    }

    public function get_concepto() {
        return $this->proveedorPendiente->get_concepto();
    }

    public function get_cbu() {
        return $this->proveedorPendiente->get_cuil();
    }

    public function get_mail() {
        return $this->proveedorPendiente->get_mail();
    }

    public function get_id_marchand() {
        return $this->proveedorPendiente->get_id_marchand();
    }

    public function getNombreProveedor() {
        return $this->proveedorPendiente->nombre_completo();
    }

    public function set_acepto($boolean) {
        $this->proveedorPendiente->set_acepto($boolean);
        $this->proveedorPendiente->set();
    }

    public function set_proveedor() {
        $this->proveedorPendiente->set();
    }

    public function get_move_referencia() {
        return $this->proveedorPendiente->get_id_move();
    }

    public function get_monto() {
        return $this->proveedorPendiente->get_monto();
    }

}
