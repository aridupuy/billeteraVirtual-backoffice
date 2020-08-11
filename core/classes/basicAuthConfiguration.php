<?php

class BasicAuthConfiguration extends Configuration
{

    private $username = '10Cobro!';
    private $password = '20Cobro!';

    public function __construct($username, $password, $baseUrl = null)
    {
        parent::__construct($baseUrl);
        $this->username = $username;
        $this->password = $password;
    }

    public function getAuthenticationHeader()
    {
        return "Basic " . $this->encodeBase64();
    }

    private function encodeBase64()
    {
        $userPass = $this->username . ":" . $this->password;
        return base64_encode($userPass);
    }

}
