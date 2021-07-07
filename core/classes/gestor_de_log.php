<?php

// namespace Classes;
class Gestor_de_log {

    private static $last_log = array(); # Ultimos mensajes logeado en bdd. Visible al usuario solo los level 1000

    const LOGLEVEL_WEBSERVICE = '20';
    const LOGLEVEL_MICROSITIO = '22';
    private static $last_ulog=array();
    public static function ultimos_ulogs($cantidad=5)
	{
		$ultimo=count(self::$last_ulog)-1;
		return self::$last_ulog[$ultimo];
	}

    public static function ultimos_logs($cantidad = 5, $separador = "") {
        $ultimo = count(self::$last_log) - 1;
        if ($ultimo == -1)
            return false;
        $string = '';
        for ($i = 0; $i < $cantidad; $i++) {
            if (isset(self::$last_log[$ultimo - $i]))
                $string .= $separador . substr(self::$last_log[$ultimo - $i], 0, 130) . htmlentities("\n");
        }
        return $string;
    }

    public static function set_exception($mensaje) {

        return self::set($mensaje, 0, 1, 'Excepciones del sistema.');
    }

    public static function set_intrusion($mensaje) {

        return self::set($mensaje, 0, 2, 'Intrusion.');
    }

    public static function set($mensaje, $resultado = 0, $loglevel = 1000, $dbmenso = 'Mensaje del desarrollador.') {
        if (CONSOLA)
            developer_log($mensaje);
        # Mensajes de log definidos por el desarrollador
        $ulog = new Ulog();
        $ulog->set_id_entidad(1);

        if (Application::$usuario AND get_class(Application::$usuario) == 'Auth') {

            $ulog->set_id_auth(Application::$usuario->get_id());
            $ulog->set_id_marchand('1');
            $ulog->set_id_usumarchand('1');
            $ulog->set_id_authcode(1);
        }

        $ulog->set_mensaje($mensaje);
        $ulog->set_loglevel($loglevel);

        $ulog->set_fecha('now()');

        $ulog->set_sesion('72'); #What?
        #$ulog->set_iddoc(); #What?
        if ($resultado)
            $ulog->set_transaccion_correcta('1');
        else
            $ulog->set_transaccion_correcta('0');
        $ulog->set_dbmenso($dbmenso);
        $ulog->set_id_clima('1');
        $ulog->set_id_normalizado('70');
        $ulog->set_id_msglog(1); #What?
        $ulog->set_vec_data('');

        if (ACTIVAR_LOG_APACHE_DEV_LOG)
            developer_log($ulog->get_mensaje());

        self::$last_log[] = $ulog->get_mensaje();
        if (defined("SOLO_REPLICA") and SOLO_REPLICA === 'true') {
            return true;
        }
        if ($ulog->set())
            return $ulog;
        return false;
    }

    public static function set_webservice($id_marchand, $mensaje, $resultado, $dbmenso, $duracion) {
        if (CONSOLA)
            developer_log($mensaje);
        # Mensajes de log definidos por el desarrollador

        $ulog = new Ulog();
        $ulog->set_id_entidad(1);

        $ulog->set_id_auth('0');
        $ulog->set_id_marchand($id_marchand);
        $ulog->set_id_usumarchand('1');
        $ulog->set_id_authcode(Authcode::USUARIO_EXTERNO);

        $ulog->set_mensaje($mensaje);
        $ulog->set_loglevel(self::LOGLEVEL_WEBSERVICE);

        $ulog->set_fecha('now()');

        $ulog->set_sesion('72'); #What?
        #$ulog->set_iddoc(); #What?
        if ($resultado)
            $ulog->set_transaccion_correcta('1');
        else
            $ulog->set_transaccion_correcta('0');
        $ulog->set_dbmenso($dbmenso);
        $ulog->set_id_clima('1');
        $ulog->set_id_normalizado('70');
        $ulog->set_id_msglog(1); #What?
        $ulog->set_vec_data('');

        if (ACTIVAR_LOG_APACHE_DEV_LOG)
            developer_log($ulog->get_mensaje());

        if ($duracion) {
            $duracion = convertir_microtime_a_datetime($duracion);
            if ($duracion) {
                $ulog->set_duracion($duracion->format(FORMATO_TIEMPO_POSTGRES));
            }
        }
        if (SOLO_REPLICA === 'true') {
            return true;
        }
        if ($ulog->set())
            return $ulog;
        return false;
    }

    public static function set_micrositio($id_marchand, $id_climarchand, $mensaje, $dbmenso) {
        if (CONSOLA)
            developer_log($mensaje);
        # Mensajes de log definidos por el desarrollador

        $ulog = new Ulog();
        $ulog->set_id_entidad(1);

        $ulog->set_id_auth('0');
        $ulog->set_id_marchand($id_marchand);
        $ulog->set_id_usumarchand('1');
        $ulog->set_id_authcode(1);
        $ulog->set_iddoc($id_climarchand);
        $ulog->set_id_entidad(Entidad::ESTRUCTURA_CLIENTES);
        $ulog->set_mensaje($mensaje);
        $ulog->set_loglevel(self::LOGLEVEL_MICROSITIO);

        $ulog->set_fecha('now()');

        $ulog->set_sesion('72'); #What?
        $ulog->set_transaccion_correcta('1');
        $ulog->set_dbmenso($dbmenso);
        $ulog->set_id_clima('1');
        $ulog->set_id_normalizado('70');
        $ulog->set_id_msglog(1); #What?
        $ulog->set_vec_data('');

        if (ACTIVAR_LOG_APACHE_DEV_LOG)
            developer_log($ulog->get_mensaje());

        if ($ulog->set())
            return $ulog;
        return false;
    }

    public static function set_auto($operacion, $tabla, $id_tabla, $parametros, $resultado, $duracion) {
        # Mensajes de log auto generados
        if ($tabla == 'ho_ulog') {
            if (ACTIVAR_LOG_EXT_APACHE_DE_CONSULTAS_SQL)
                developer_log('Log evitado: recursividad en la tabla ' . $tabla);
            return true;
        }
        if ($tabla == 'cd_instancias' OR $tabla == 'cd_tarea') {
            if (ACTIVAR_LOG_EXT_APACHE_DE_CONSULTAS_SQL)
                developer_log('Log evitado: No se logea la actividad en la tabla instancias ni en tarea.');
            return true;
        }

        if ($operacion == 'SELECT' AND ! ACTIVAR_LOG_SQL_SELECT)
            return true;
        if ($operacion == 'UPDATE' AND ! ACTIVAR_LOG_SQL_UPDATE)
            return true;
        if ($operacion == 'INSERT' AND ! ACTIVAR_LOG_SQL_INSERT)
            return true;

        $ulog = new Ulog();
        $ulog->set_id_entidad(self::traducir_entidad($tabla));
        if (Application::$usuario AND get_class(Application::$usuario) == 'Auth') {

            $ulog->set_id_auth(Application::$usuario->get_id());
            $ulog->set_id_marchand('1');
            $ulog->set_id_usumarchand('1');
            $ulog->set_id_authcode(1);
        }
        switch ($operacion) {
            case 'SELECT':
                $ulog->set_loglevel('10');
                break;
            case 'INSERT':
                $ulog->set_loglevel('100');
                break;
            case 'UPDATE':
                $ulog->set_loglevel('100');
                break;
        }

        $ulog->set_mensaje(strtolower($tabla . '.' . $operacion));
        $duracion = convertir_microtime_a_datetime($duracion);
        $ulog->set_duracion($duracion->format(FORMATO_TIEMPO_POSTGRES));

        if ($resultado)
            $ulog->set_transaccion_correcta('1');
        else
            $ulog->set_transaccion_correcta('0');

        $ulog->set_fecha('now()');

        $ulog->set_sesion('72'); #What?
        if (isset($parametros[$id_tabla]))
            $ulog->set_iddoc($parametros[$id_tabla]);

        $ulog->set_dbmenso(self::hacer_dbmenso($parametros));
        $ulog->set_id_clima('1');
        $ulog->set_id_normalizado('70');
        $ulog->set_id_msglog(self::traducir_msglog($tabla));
        $ulog->set_vec_data(''); # Setear parametros

        if (ACTIVAR_LOG_EXT_APACHE_DE_CONSULTAS_SQL)
            developer_log($ulog->get_mensaje());
        # Lo comento, el usuario no debe ver mensajes de nivel 100 o 10
        #self::$last_log[]=$mensaje;
//        if (SOLO_REPLICA === 'true') {
//            return true;
//        } else {
//            //developer_log("master");
//        }
        if ($ulog->set())
            return $ulog;
        return false;
    }

    private static function traducir_entidad($tabla) {

        if ($tabla == 'ho_entidad')
            return 1;
        $result = Entidad::select(array('entidad' => $tabla));
        if (!$result OR ! $result->RowCount()) {
            $variables['entidad'] = $tabla;
            $variables['dentidad'] = '100';
            $variables['descripcion'] = 'Tabla ' . $tabla;
            $variables['is_productable'] = '0';

            $entidad = new Entidad($variables);
            if ($entidad->set())
                return $entidad->get_id();
            else
                return false;
        }


        $row = $result->FetchRow();
        $entidad = new Entidad($row);
        return $entidad->get_id();
    }

    private static function traducir_msglog($tabla) {
        # Si no existe un msglog autogenerado, se crea y retorna el id ??
        return 1;
    }

    private static function hacer_dbmenso($array) {
        $string = '';
        if ($array) {
            $string .= "\n";
            foreach ($array as $clave => $valor):
                $string .= $clave . ' = ' . $valor . "\n";
            endforeach;
        }
        return $string;
    }

}
