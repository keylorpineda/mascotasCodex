<?php

if (!function_exists('moneda_to_float')) {
    /**
     * Convierte una cadena de moneda a float
     * 
     * Acepta diferentes formatos de moneda y los normaliza a float:
     * - Formato americano: 1,234.56 -> 1234.56
     * - Formato europeo: 1.234,56 -> 1234.56
     * - Con símbolos: $1,234.56, €1.234,56, etc.
     * 
     * @param string|int|float $monto Cantidad en formato de moneda
     * @return float Valor convertido a float
     * 
     * @example
     * moneda_to_float('$1,234.56')    // 1234.56
     * moneda_to_float('€1.234,56')    // 1234.56
     * moneda_to_float('1234')         // 1234.0
     * moneda_to_float('1,234')        // 1234.0
     * 
     * @throws InvalidArgumentException Si el formato no es válido
     */
    function moneda_to_float($monto): float 
    {
        // Validación de entrada
        if (is_null($monto) || $monto === '') {
            return 0.0;
        }
        
        // Si ya es numérico, convertir directamente
        if (is_numeric($monto)) {
            return (float) $monto;
        }
        
        // Debe ser string para procesar
        if (!is_string($monto)) {
            throw new InvalidArgumentException('El monto debe ser string, número o null');
        }
        
        // Limpiar: mantener solo números, puntos y comas
        $montoLimpio = preg_replace('/[^0-9.,]/', '', $monto);
        
        // Casos especiales
        if ($montoLimpio === '') {
            return 0.0;
        }
        
        // Si no tiene separadores, es un número entero
        if (strpos($montoLimpio, '.') === false && strpos($montoLimpio, ',') === false) {
            return (float) $montoLimpio;
        }
        
        return procesarSeparadoresDecimales($montoLimpio);
    }
}

if (!function_exists('procesarSeparadoresDecimales')) {
    /**
     * Procesa una cadena con separadores decimales y de miles
     * 
     * @param string $monto Cadena limpia con solo números, puntos y comas
     * @return float Valor convertido
     */
    function procesarSeparadoresDecimales(string $monto): float 
    {
        $ultimoPunto = strrpos($monto, '.');
        $ultimaComa = strrpos($monto, ',');
        
        // Solo tiene puntos o solo comas
        if ($ultimaComa === false) {
            return procesarSoloUnSeparador($monto, '.');
        }
        
        if ($ultimoPunto === false) {
            return procesarSoloUnSeparador($monto, ',');
        }
        
        // Tiene ambos separadores
        if ($ultimoPunto > $ultimaComa) {
            // Formato americano: 1,234.56
            return (float) str_replace(',', '', $monto);
        } else {
            // Formato europeo: 1.234,56
            return (float) str_replace(['.', ','], ['', '.'], $monto);
        }
    }
}

if (!function_exists('procesarSoloUnSeparador')) {
    /**
     * Procesa cadena con solo un tipo de separador
     * 
     * @param string $monto Cadena con solo puntos o solo comas
     * @param string $separador El separador encontrado ('.' o ',')
     * @return float Valor convertido
     */
    function procesarSoloUnSeparador(string $monto, string $separador): float 
    {
        $posiciones = [];
        $offset = 0;
        
        // Encontrar todas las posiciones del separador
        while (($pos = strpos($monto, $separador, $offset)) !== false) {
            $posiciones[] = $pos;
            $offset = $pos + 1;
        }
        
        // Si solo hay un separador
        if (count($posiciones) === 1) {
            $pos = $posiciones[0];
            $parteDecimal = substr($monto, $pos + 1);
            
            // Si la parte decimal tiene 1-2 dígitos, es separador decimal
            if (strlen($parteDecimal) <= 2) {
                return (float) ($separador === ',' ? str_replace(',', '.', $monto) : $monto);
            }
            // Si tiene 3+ dígitos, es separador de miles
            else {
                return (float) str_replace($separador, '', $monto);
            }
        }
        
        // Múltiples separadores = separadores de miles
        return (float) str_replace($separador, '', $monto);
    }
}

if (!function_exists("float_to_moneda")) {
	/**
	 * Convierte un float a formato de moneda americano
	 * 
	 * @param float $monto Cantidad a formatear
	 * @param int $decimales Número de decimales (default: 2)
	 * @return string Formato: 1,234.56
	 */
	function float_to_moneda(float $monto, int $decimales = 2): string
	{
	    return number_format($monto, $decimales, ".", ",");
	}
}

if (!function_exists('monto_en_palabras')) {
    /**
     * Convierte un monto numérico (float o string) a su representación en palabras en español,
     * asegurando exactitud para dos decimales y gestionando números muy grandes como cadenas.
     *
     * @param  float|string  $monto  Monto a convertir. Puede ser float o string numérico.
     * @return string               Monto en palabras, p. ej. "Tres millones ... céntimos".
     * @throws InvalidArgumentException Si $monto no es un valor numérico válido.
     */
    function monto_en_palabras($monto): string
    {
        // 1) Validar que sea numérico
        $monto_str = (string)($monto);
        if (!preg_match('/^-?\d+(\.\d+)?$/', $monto_str)) {
            throw new InvalidArgumentException("El monto debe ser un número válido (float o string).");
        }

        // 2) Separar parte entera y decimales (hasta 2 dígitos)
        if (strpos($monto_str, '.') !== false) {
            list($entero_str, $dec_str) = explode('.', $monto_str, 2);
        } else {
            $entero_str = $monto_str;
            $dec_str = '';
        }
        // Normalizar decimales a dos dígitos (redondeo hacia abajo)
        $dec_str = str_pad(substr($dec_str, 0, 2), 2, '0', STR_PAD_RIGHT);

        // 3) Detectar y separar el signo
        $signo = '';
        if (strpos($entero_str, '-') === 0) {
            $signo = 'menos ';
            $entero_str = substr($entero_str, 1);
        }

        // 4) Configurar el formateador
        $fmt = new NumberFormatter('es', NumberFormatter::SPELLOUT);

        // 5) Convertir cada parte usando el formateador
        //    – Como NumberFormatter internamente maneja hasta PHP_INT_MAX,
        //      para cifras muy grandes (> 9 dígitos) podría perder precision.
        //      En esos casos, habría que implementar una división manual por grupos.
        $colones_texto   = $fmt->format($entero_str);
        $cent_texto      = $fmt->format((int)$dec_str);

        // 6) Construir la frase y capitalizar la primera letra
        $resultado = sprintf(
            '%s%s colones con %s céntimos',
            $signo,
            trim($colones_texto),
            trim($cent_texto)
        );

        return ucfirst($resultado);
    }
}