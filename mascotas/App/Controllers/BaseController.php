<?php
namespace App\Controllers;
use App\Core\Request\RequestEngine;

class BaseController extends RequestEngine
{
    public function __construct(
        protected ?RequestEngine $request = null,
        protected array $origins = []
    ) {
        parent::__construct();
        helper("alertas_helper, fechas_helper");
        
        // Si no se pasa un request, usar la instancia actual
        $this->request = $this->request ?? $this;
        
        if (function_exists("validar_origen_peticion")) {
            validar_origen_peticion($this->origins);
        }
    }
}