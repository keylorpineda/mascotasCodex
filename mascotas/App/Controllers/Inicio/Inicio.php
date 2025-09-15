<?php
namespace App\Controllers\Inicio;

use App\Controllers\BaseController;

class Inicio extends BaseController
{
	public function inicio()
	{
		return view("inicio/inicio");
	}

}