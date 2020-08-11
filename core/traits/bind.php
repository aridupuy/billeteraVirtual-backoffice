<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of bind
 *
 * @author ariel
 */
abstract class Bind implements Bind_interface {

    const URL_LOGIN = "https://sandbox.bind.com.ar/v1/login/jwt";

    /* se implementa en bind_login */

    public $token;

    public function llamado_api($url, $parametros, $method = "POST") {
        return $this->llamado_api_protected($url, $parametros, $method);
    }

    protected function llamado_api_protected($url, $parametros, $method = "post") {
        $curl = curl_init();
        $headers = array(
            "cache-control: no-cache",
            "content-type: application/json",
        );
        if (isset($this->token) and $this->token != null) {
            $headers[] = "authorization: JWT " . $this->token;
        }
        //"authorization: JWT eyJhbGciOiJIUzUxMiJ9.eyJzdWIiOiJ3YURlRmJ2emgydmd5QWJmdmFEWEVCV3lXMmtOcmtGeXJsSjBiS0pBZjI4PSIsImNyZWF0ZWQiOjE1OTQyNDI0MjIxNzMsIm5hbWUiOiJDb2JybyBEaWdpdGFsIFNSTCIsImV4cCI6MTU5NDI3MTIyMn0.q-iSN2TydlAkUEY98mB0Xb97UNhqaIaP--uwKRAl35qwydCAzpx6mTMlP3KNgbnbq1fZxBhl4hudQtJWRKy2JA"
        curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_POSTFIELDS => json_encode($parametros),
            CURLOPT_HTTPHEADER => $headers,
        ));

        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);

        if ($err) {
            return false;
            // "cURL Error #:" . $err;
        } else {
            return $response;
        }
    }

}
