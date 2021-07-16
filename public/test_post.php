<?php

$archivo = "/home/ariel/Descargas/PadronRGSRet082019.zip";
//$zip = new ZipArchive;
//$zip->open($archivo);
//$archivo_descomprimido="/home/ariel/archivos/PadronRGSRet082019.txt";
//$zip->extractTo($archivo_descomprimido);
//$zip->close();
$connection = ssh2_connect('172.20.10.105', 22);

        } else {
            error_log("cargando archivo");
            if (!ssh2_exec($connection, "psql -Unegro laura_2017_07_23 -c  \"insert into cd_sujeto_retencion (cuit, fecha_gen, agente, id_authstat, regimen, letra_alicuota) select cuit::numeric, now(),'ARBA' , 1, regimen, grupo from prueba_colo where grupo <> '25' \"")) {
                error_log("error al ejecutar insert sujeto_retencion");
            } else
            if (!ssh2_exec($connection, "psql -Unegro laura_2017_07_23 -c  \" insert into cd_alicuota (porcentaje, letra, id_authstat, fecha_gen, fecha_desde, fecha_hasta) select distinct replace(alicuota, ',', '.')::numeric, grupo, 1, now(), to_date(fecha_desde, 'ddmmyyyy'),  to_date(fecha_hasta, 'ddmmyyyy') from prueba_colo where grupo <> '25' \"")) {
                error_log("error al ejecutar insert alicuota");
            }
            else{
                error_log("Ejecutado correctamente");
            }
        }
    } else {
        error_log("Archivo no enviado");
    }
} else {
    error_log("Error al autenticar");
}
error_log("termine");
