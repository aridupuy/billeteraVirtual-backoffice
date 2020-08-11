<?php

// namespace Models;
class Ulog extends Model {

    const FORMATO_FECHA = 'Y-m-d H:i:s.u-**';
    const LOGLEVEL_USUARIO = 1000;

    public static $prefijo_tabla = 'ho_';
    public static $id_tabla = 'id_ulog';
    public static $secuencia = 'sq_ulog';
    private $id_ulog;
    private $id_entidad;
    private $id_auth;
    private $mensaje;
    private $fecha;
    private $id_authcode;
    private $sesion;
    private $iddoc;
    private $dbmenso;
    private $id_marchand;
    private $loglevel;
    private $id_clima;
    private $id_usumarchand;
    private $id_normalizado;
    private $id_msglog;
    private $vec_data;
    private $duracion;
    private $transaccion_correcta;

    public function get_id_ulog() {
        return $this->id_ulog;
    }

    public function get_id_entidad() {
        return $this->id_entidad;
    }

    public function get_id_auth() {
        return $this->id_auth;
    }

    public function get_mensaje() {
        return $this->mensaje;
    }

    public function get_fecha() {
        return $this->fecha;
    }

    public function get_id_authcode() {
        return $this->id_authcode;
    }

    public function get_sesion() {
        return $this->sesion;
    }

    public function get_iddoc() {
        return $this->iddoc;
    }

    public function get_dbmenso() {
        return $this->dbmenso;
    }

    public function get_id_marchand() {
        return $this->id_marchand;
    }

    public function get_loglevel() {
        return $this->loglevel;
    }

    public function get_id_clima() {
        return $this->id_clima;
    }

    public function get_id_usumarchand() {
        return $this->id_usumarchand;
    }

    public function get_id_normalizado() {
        return $this->id_normalizado;
    }

    public function get_id_msglog() {
        return $this->id_msglog;
    }

    public function get_vec_data() {
        return $this->vec_data;
    }

    public function get_duracion() {
        return $this->duracion;
    }

    public function get_transaccion_correcta() {
        return $this->transaccion_correcta;
    }

    public function set_id_ulog($variable) {
        $this->id_ulog = $variable;
        return $this->id_ulog;
    }

    public function set_id_entidad($variable) {
        $this->id_entidad = $variable;
        return $this->id_entidad;
    }

    public function set_id_auth($variable) {
        $this->id_auth = $variable;
        return $this->id_auth;
    }

    public function set_mensaje($variable) {
        $this->mensaje = $variable;
        return $this->mensaje;
    }

    public function set_fecha($variable) {
        $this->fecha = $variable;
        return $this->fecha;
    }

    public function set_id_authcode($variable) {
        $this->id_authcode = $variable;
        return $this->id_authcode;
    }

    public function set_sesion($variable) {
        $this->sesion = $variable;
        return $this->sesion;
    }

    public function set_iddoc($variable) {
        $this->iddoc = $variable;
        return $this->iddoc;
    }

    public function set_dbmenso($variable) {
        $this->dbmenso = $variable;
        return $this->dbmenso;
    }

    public function set_id_marchand($variable) {
        $this->id_marchand = $variable;
        return $this->id_marchand;
    }

    public function set_loglevel($variable) {
        $this->loglevel = $variable;
        return $this->loglevel;
    }

    public function set_id_clima($variable) {
        $this->id_clima = $variable;
        return $this->id_clima;
    }

    public function set_id_usumarchand($variable) {
        $this->id_usumarchand = $variable;
        return $this->id_usumarchand;
    }

    public function set_id_normalizado($variable) {
        $this->id_normalizado = $variable;
        return $this->id_normalizado;
    }

    public function set_id_msglog($variable) {
        $this->id_msglog = $variable;
        return $this->id_msglog;
    }

    public function set_vec_data($variable) {
        $this->vec_data = $variable;
        return $this->vec_data;
    }

    public function set_duracion($variable) {
        $this->duracion = $variable;
        return $this->duracion;
    }

    public function set_transaccion_correcta($variable) {
        $this->transaccion_correcta = $variable;
        return $this->transaccion_correcta;
    }

    public static function select_min($variables = null) {
        $filtros = self::preparar_filtros($variables);
        $sql = "	SELECT A.fecha,A.mensaje
						FROM ho_ulog A LEFT JOIN ho_entidad B
						ON A.id_entidad=B.id_entidad
						$filtros
						ORDER BY A.fecha DESC";
        return self::execute_select($sql, $variables);
    }

    public static function select_actividad_id_usumarchand($id_usumarchand) {
        $variables['id_usumarchand'] = $id_usumarchand;
        $variables['loglevel'] = self::LOGLEVEL_USUARIO;
        $variables['id_authcode'] = Authcode::USUARIO_EXTERNO;
        $sql = "	SELECT fecha, mensaje
						FROM ho_ulog
						WHERE id_usumarchand=?
						AND loglevel=?
						AND id_authcode=?
						ORDER BY fecha DESC";
        return self::execute_select($sql, $variables);
    }

    # Usada en Util_xviii 

    public static function select_monitor($variables) {
        $where = ' WHERE true ';
        $filtros = array();
        $where .= " AND A.id_normalizado=70 ";

        if (isset($variables['fecha_desde'])) {
            $where .= ' AND A.fecha>= ? ';
            $filtros['fecha'] = $variables['fecha_desde'];
        }
        if (isset($variables['fecha_hasta'])) {
            $where .= " AND A.fecha<=? ";
            $filtros[] = $variables['fecha_hasta'];
        }
        if (isset($variables['duracion_desde'])) {
            $where .= ' AND A.duracion>=?';
            $filtros['duracion'] = $variables['duracion_desde'];
        }
        if (isset($variables['entidad'])) {
            $where .= ' AND B.entidad=? ';
            $filtros['entidad'] = strtolower($variables['entidad']);
        }
        if (isset($variables['id'])) {
            $where .= ' AND A.iddoc=?';
            $filtros['iddoc'] = $variables['id'];
        }
        if (isset($variables['id_auth'])) {
            $where .= ' AND A.id_auth=?';
            $filtros['id_auth'] = $variables['id_auth'];
        }
        if (isset($variables['id_authcode'])) {
            $where .= ' AND A.id_authcode=?';
            $filtros['id_authcode'] = $variables['id_authcode'];
        }
        if (isset($variables['ejecucion'])) {
            $where .= ' AND A.transaccion_correcta=?';
            $filtros['transaccion_correcta'] = $variables['ejecucion'];
        }
        if (isset($variables['loglevel'])) {
            $where .= ' AND A.loglevel=?';
            $filtros['loglevel'] = $variables['loglevel'];
        }
        if (isset($variables['operacion'])) {
            $where .= ' AND A.mensaje=?';
            $filtros['mensaje'] = strtoupper($variables['operacion']);
        }

        $sql = "	SELECT A.fecha, A.duracion, A.mensaje, B.entidad, A.iddoc, A.id_auth, A.id_usumarchand, A.id_authcode, A.transaccion_correcta
				FROM ho_ulog A
				LEFT JOIN ho_entidad B
				ON A.id_entidad=B.id_entidad
				$where
				ORDER BY A.fecha DESC";
        return self::execute_select($sql, $variables);
    }

    # Usada en Util_xviii

    public static function select_modulos_por_uso() {
        $sql = "	SELECT B.entidad as Modulo, count(*) as Usos
				FROM ho_ulog A LEFT JOIN ho_entidad B
				ON A.id_entidad=B.id_entidad
				WHERE B.entidad LIKE 'util_%'
				OR B.entidad LIKE 'mod_%'
				OR B.entidad LIKE 'meta_controller'
				GROUP BY 1
				ORDER BY 2 DESC
				";
        return self::execute_select($sql);
    }

    # Usada en Util_xviii

    public static function select_modulos_por_tiempo() {
        $sql = "	SELECT B.entidad as Modulo, sum(A.duracion) as Total, (sum(A.duracion)/count(*)) as Promedio
				FROM ho_ulog A LEFT JOIN ho_entidad B
				ON A.id_entidad=B.id_entidad
				WHERE B.entidad LIKE 'util_%'
				OR B.entidad LIKE 'mod_%'
				OR B.entidad LIKE 'meta_controller'
				GROUP BY 1
				ORDER BY 2 DESC
				";
        return self::execute_select($sql);
    }

    public static function select_actividad_id_climarchand($id_climarchand, Datetime $desde, Datetime $hasta, $loglevel = false, $limit = false) {
        $variables = array();
        $where = '';
        $variables['id_entidad'] = Entidad::ESTRUCTURA_CLIENTES;
        $variables['iddoc'] = $id_climarchand;
        $variables[] = $desde->format(FORMATO_FECHA_POSTGRES);
        $variables[] = $hasta->format(FORMATO_FECHA_POSTGRES);
        if ($loglevel) {
            $variables['loglevel'] = $loglevel;
            $where = " AND loglevel=? ";
        }
        $sql = "	SELECT fecha, mensaje
    			FROM ho_ulog 
    			WHERE id_entidad=?
    			AND iddoc=?
    			AND fecha >=?
    			AND fecha  <=?
    			$where
    			ORDER BY id_ulog DESC
    			";
        return self::execute_select($sql, $variables, $limit);
    }

}
