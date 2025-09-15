<?php
namespace App\Services;

use DateTime;

class RecaptchaService
{
    public $RESPONSE_CAPTCHA;
    public function validar_captcha(string $TOKEN): booL
    {
        $cu = curl_init();
        curl_setopt($cu, CURLOPT_URL, CAPTCHA_RUTA_VALIDAR_TOKEN);
        curl_setopt($cu, CURLOPT_POST, 1);
        curl_setopt($cu, CURLOPT_POSTFIELDS, http_build_query(['secret' => CAPTCHA_CLAVE_PRIVADA, 'response' => $TOKEN]));
        curl_setopt($cu, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($cu);
        curl_close($cu);
        $this->RESPONSE_CAPTCHA = $datos = json_decode($response, true);
        return $datos['success'] == 1 && $datos['score'] >= 0.5;
    }
}