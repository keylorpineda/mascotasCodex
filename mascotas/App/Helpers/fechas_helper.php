<?php

if (!function_exists("create_date")) {
	function create_date(string $fecha, ?array $formatos_personalizados = null): DateTime
	{
	    // Ordenar formatos por especificidad (más específicos primero)
	    // y agrupar por tipo para evitar ambigüedades
	    $grupos_formatos = [
	        // ISO 8601 y formatos inequívocos primero
	    	'iso_y_inequivocos' => [
	    	    'Y-m-d\TH:i:s',
	    	    'Y-m-d\TH:i:s.u',
	    	    'Y-m-d\TH:i:s.v',
	    	    'Y-m-d\TH:i',
	    	    'Y-m-d H:i:s',
	    	    'Y-m-d H:i:s.u',
	    	    'Y-m-d H:i:s.v',
	    	    'Y-m-d H:i',
	    	    'Y-m-d',
	    	],
	        
	        // Formatos con año de 4 dígitos al inicio (inequívocos)
	        'año_inicio' => [
	            'Y/m/d H:i:s',
	            'Y/m/d H:i',
	            'Y/m/d',
	            'Y.m.d H:i:s',
	            'Y.m.d H:i',
	            'Y.m.d',
	            'Y m d H:i:s',
	            'Y m d H:i',
	            'Y m d',
	        ],
	        
	        // Solo tiempo (si la fecha contiene solo tiempo)
	        'solo_tiempo' => [
	            'H:i:s',
	            'H:i',
	            'h:i:s a',
	            'h:i a',
	        ],
	        
	        // Formatos potencialmente ambiguos (día/mes vs mes/día)
	        // Preferir día/mes (formato europeo) sobre mes/día (formato US)
	        'ambiguos_dia_mes' => [
	            'd/m/Y H:i:s',
	            'd/m/Y H:i',
	            'd/m/Y',
	            'd-m-Y H:i:s',
	            'd-m-Y H:i',
	            'd-m-Y',
	            'd.m.Y H:i:s',
	            'd.m.Y H:i',
	            'd.m.Y',
	            'd m Y H:i:s',
	            'd m Y H:i',
	            'd m Y',
	        ],
	        
	        'ambiguos_mes_dia' => [
	            'm/d/Y H:i:s',
	            'm/d/Y H:i',
	            'm/d/Y',
	            'm-d-Y H:i:s',
	            'm-d-Y H:i',
	            'm-d-Y',
	            'm.d.Y H:i:s',
	            'm.d.Y H:i',
	            'm.d.Y',
	            'm d Y H:i:s',
	            'm d Y H:i',
	            'm d Y',
	        ],
	        
	        // Formatos con AM/PM
	        'am_pm' => [
	            'Y-m-d h:i:s a',
	            'Y/m/d h:i:s a',
	            'd/m/Y h:i:s a',
	            'd-m-Y h:i:s a',
	            'm/d/Y h:i:s a',
	            'm-d-Y h:i:s a',
	            'Y.m.d h:i:s a',
	            'd.m.Y h:i:s a',
	            'm.d.Y h:i:s a',
	        ],
	    ];
	    
	    // Si se proporcionan formatos personalizados, usarlos primero
	    if ($formatos_personalizados !== null) {
	        $grupos_formatos = ['personalizados' => $formatos_personalizados] + $grupos_formatos;
	    }
	    
	    // Intentar parsear con cada grupo de formatos
	    foreach ($grupos_formatos as $grupo => $formatos) {
	        foreach ($formatos as $formato) {
	            $datetime = DateTime::createFromFormat($formato, $fecha);
	            
	            if ($datetime !== false) {
	                // Validación adicional: verificar que la fecha parseada 
	                // coincida con la entrada original
	                if (validar_fecha_parseada($datetime, $formato, $fecha)) {
	                    // Para formatos ambiguos, hacer validación adicional
	                    if (in_array($grupo, ['ambiguos_dia_mes', 'ambiguos_mes_dia'])) {
	                        if (es_fecha_logica($datetime)) {
	                            return $datetime;
	                        }
	                        continue; // Si no es lógica, continuar con otros formatos
	                    }
	                    
	                    return $datetime;
	                }
	            }
	        }
	    }
	    
	    throw new InvalidArgumentException("No se pudo parsear la fecha: '{$fecha}'");
	}

	function validar_fecha_parseada(DateTime $datetime, string $formato, string $fecha_original): bool
	{
	    // Verificar que al formatear la fecha parseada obtenemos la fecha original
	    // Esto ayuda a detectar fechas inválidas como "30 de febrero"
	    try {
	        $fecha_reformateada = $datetime->format($formato);
	        
	        // Para formatos con espacios o caracteres especiales, normalizar
	        $fecha_normalizada = normalizar_fecha($fecha_original);
	        $reformateada_normalizada = normalizar_fecha($fecha_reformateada);
	        
	        return $fecha_normalizada === $reformateada_normalizada;
	    } catch (Exception $e) {
	        return false;
	    }
	}

	function normalizar_fecha(string $fecha): string
	{
	    // Normalizar espacios múltiples y trim
	    return trim(preg_replace('/\s+/', ' ', $fecha));
	}

	function es_fecha_logica(DateTime $datetime): bool
	{
	    $año = (int)$datetime->format('Y');
	    $mes = (int)$datetime->format('m');
	    $dia = (int)$datetime->format('d');
	    
	    // Validaciones básicas de lógica de fechas
	    return $año >= 1900 && $año <= 2100 && 
	           $mes >= 1 && $mes <= 12 && 
	           $dia >= 1 && $dia <= 31;
	}

	// Función auxiliar para casos donde conoces el contexto
	function create_date_con_contexto(string $fecha, string $contexto = 'europeo'): DateTime
	{
	    $formatos_por_contexto = [
	        'europeo' => ['d/m/Y', 'd-m-Y', 'd.m.Y', 'Y-m-d'],
	        'americano' => ['m/d/Y', 'm-d-Y', 'm.d.Y', 'Y-m-d'],
	        'iso' => ['Y-m-d', 'Y-m-d\TH:i:s', 'Y-m-d H:i:s'],
	    ];
	    
	    $formatos = $formatos_por_contexto[$contexto] ?? $formatos_por_contexto['europeo'];
	    return create_date($fecha, $formatos);
	}
} 

if (!function_exists("is_date")) {
	function is_date(string $fecha, string $formato = 'Y-m-d H:i:s'): bool
	{
	    $d = DateTime::createFromFormat($formato, $fecha);
	    // La fecha es válida si:
	    // 1. DateTime::createFromFormat() no devuelve false
	    // 2. La fecha después de ser formateada es igual a la fecha original
	    return $d && $d->format($formato) == $fecha;
	}
}

if (!function_exists("es_n_o_mas_dias_posterior")) {
	function es_n_o_mas_dias_posterior(string $fecha1, string $fecha2, int $dias): bool
	{
	    // Convertir las fechas a tiempo Unix
	    $timestamp_fecha1 = strtotime($fecha1);
	    $timestamp_fecha2 = strtotime($fecha2);
	    // Calcular la diferencia en segundos
	    $diferencia_en_segundos = $timestamp_fecha2 - $timestamp_fecha1;
	    // Convertir la diferencia de segundos a días
	    $diferencia_en_dias = ( $diferencia_en_segundos / (60 * 60 * 24) ) - 1;
	    // Verificar si la diferencia es de n días o más
	    return ($diferencia_en_dias >= $dias);
	}
}

if (!function_exists("convertir_en_fecha_larga")) {
	function convertir_en_fecha_larga(string $fecha, string $formato): string
	{
	    // Intentar parsear la fecha usando tu función robusta existente
	    try {
	        $fechaObj = create_date($fecha);
	    } catch (Exception $e) {
	        // Fallback: mantener compatibilidad con formato Y-m-d original
	        $fechaObj = DateTime::createFromFormat('Y-m-d', $fecha);
	        if (!$fechaObj) { 
	            return "Fecha inválida. Error: {$e->getMessage()}";
	        }
	    }
	    
	    // Definir nombres en español
	    $dias = [
	        'domingo',
	        'lunes', 
	        'martes',
	        'miércoles',
	        'jueves',
	        'viernes',
	        'sábado'
	    ];
	    
	    $dias_cortos = [
	        'dom',
	        'lun',
	        'mar',
	        'mié',
	        'jue',
	        'vie',
	        'sáb'
	    ];
	    
	    $meses = [
	        1 => 'enero',
	        'febrero',
	        'marzo',
	        'abril',
	        'mayo',
	        'junio',
	        'julio',
	        'agosto',
	        'septiembre',
	        'octubre',
	        'noviembre',
	        'diciembre'
	    ];
	    
	    $meses_cortos = [
	        1 => 'ene',
	        'feb',
	        'mar',
	        'abr',
	        'may',
	        'jun',
	        'jul',
	        'ago',
	        'sep',
	        'oct',
	        'nov',
	        'dic'
	    ];
	    
	    // Extraer valores individuales
	    $diaSemana = $dias[$fechaObj->format('w')];
	    $diaNumero = $fechaObj->format('d'); // Quitar padding innecesario
	    $mesNombre = $meses[(int)$fechaObj->format('m')];
	    $anio = $fechaObj->format('Y');
	    
	    // Nuevas funcionalidades manteniendo compatibilidad
	    $reemplazos = [
	        // === NUEVAS FUNCIONALIDADES ===
	        
	        // Días
	        'DIA_CORTO' => $dias_cortos[$fechaObj->format('w')],  // 'mar'
	        'DIA_MAYUS' => strtoupper($diaSemana),                // 'MARTES'
	        'DIA_CORTO_MAYUS' => strtoupper($dias_cortos[$fechaObj->format('w')]), // 'MAR'
	        'DIA_TITULO' => ucfirst($diaSemana),                  // 'Martes'
	        
	        // Fechas numéricas
	        'FECHA_SIN_CERO' => $fechaObj->format('j'),          // '4' (sin padding)
	        'FECHA_ORDINAL' => obtener_ordinal($fechaObj->format('j')), // '4to'
	        
	        // Meses
	        'MES_CORTO' => $meses_cortos[(int)$fechaObj->format('m')], // 'feb'
	        'MES_MAYUS' => strtoupper($mesNombre),               // 'FEBRERO'
	        'MES_CORTO_MAYUS' => strtoupper($meses_cortos[(int)$fechaObj->format('m')]), // 'FEB'
	        'MES_TITULO' => ucfirst($mesNombre),                 // 'Febrero'
	        'MES_NUMERO' => $fechaObj->format('m'),              // '02'
	        'MES_NUMERO_SIN_CERO' => $fechaObj->format('n'),     // '2'
	        
	        // Años
	        'AÑO_CORTO' => $fechaObj->format('y'),               // '25'
	        'SIGLO' => obtener_siglo($fechaObj->format('Y')),    // 'XXI'
	        
	        // Tiempo (si la fecha incluye hora)
	        'HORA' => $fechaObj->format('H'),                    // '14'
	        'HORA_12' => $fechaObj->format('h'),                 // '02'
	        'MINUTOS' => $fechaObj->format('i'),                 // '30'
	        'SEGUNDOS' => $fechaObj->format('s'),                // '45'
	        'MERIDIANO' => $fechaObj->format('A'),               // 'AM'/'PM'
	        'MERIDIANO_MINUSC' => strtolower($fechaObj->format('A')), // 'am'/'pm'
	        
	        // Información adicional
	        'SEMANA_AÑO' => $fechaObj->format('W'),              // '07' (semana del año)
	        'DIA_AÑO' => $fechaObj->format('z'),                 // '047' (día del año)
	        'TRIMESTRE' => obtener_trimestre($fechaObj->format('n')), // '1er trimestre'
	        'ESTACION' => obtener_estacion($fechaObj->format('n')), // 'verano'
	        
	        // === COMPATIBILIDAD ORIGINAL === 
	        'DIA' => $diaSemana,           // 'martes'
	        'FECHA' => $diaNumero,         // '04' 
	        'MES' => $mesNombre,           // 'febrero'
	        'AÑO' => $anio,                // '2025'
	    ];
	    
	    // Reemplazar en el formato
	    $resultado = str_replace(
	        array_keys($reemplazos),
	        array_values($reemplazos),
	        $formato
	    );
	    
	    return $resultado;
	}

	// === FUNCIONES AUXILIARES ===

	function obtener_ordinal(int $numero): string
	{
	    $ordinales = [
	        1 => '1ro', 2 => '2do', 3 => '3ro', 4 => '4to', 5 => '5to',
	        6 => '6to', 7 => '7mo', 8 => '8vo', 9 => '9no', 10 => '10mo',
	        11 => '11vo', 12 => '12vo', 13 => '13vo', 14 => '14vo', 15 => '15vo',
	        16 => '16vo', 17 => '17vo', 18 => '18vo', 19 => '19vo', 20 => '20vo',
	        21 => '21vo', 22 => '22vo', 23 => '23vo', 24 => '24vo', 25 => '25vo',
	        26 => '26vo', 27 => '27vo', 28 => '28vo', 29 => '29vo', 30 => '30vo',
	        31 => '31vo'
	    ];
	    
	    return $ordinales[$numero] ?? $numero . 'vo';
	}

	function obtener_siglo(int $año): string
	{
	    $siglo = ceil($año / 100);
	    
	    $romanos = [
	        19 => 'XIX', 20 => 'XX', 21 => 'XXI', 22 => 'XXII', 23 => 'XXIII'
	    ];
	    
	    return $romanos[$siglo] ?? 'S' . $siglo;
	}

	function obtener_trimestre(int $mes): string
	{
	    $trimestres = [
	        1 => '1er trimestre', 2 => '1er trimestre', 3 => '1er trimestre',
	        4 => '2do trimestre', 5 => '2do trimestre', 6 => '2do trimestre', 
	        7 => '3er trimestre', 8 => '3er trimestre', 9 => '3er trimestre',
	        10 => '4to trimestre', 11 => '4to trimestre', 12 => '4to trimestre'
	    ];
	    
	    return $trimestres[$mes];
	}

	function obtener_estacion(int $mes, string $region = 'CR'): string
	{
	    $modelos = [
	        'CR' => [ // Costa Rica
	            'verano' => [12, 1, 2, 3, 4],
	            'invierno' => [5, 6, 7, 8, 9, 10, 11],
	        ],
	        'EU' => [ // Europa / hemisferio norte
	            'invierno' => [12, 1, 2],
	            'primavera' => [3, 4, 5],
	            'verano' => [6, 7, 8],
	            'otoño' => [9, 10, 11],
	        ]
	    ];

	    foreach ($modelos[$region] ?? [] as $nombre => $meses) {
	        if (in_array($mes, $meses)) return $nombre;
	    }

	    return 'desconocida';
	}
}

if (!function_exists("between_date")) {
	function between_date($fecha_validar, $fecha_rige, $fecha_vence) {
	    $fecha_validar = strtotime(create_date($fecha_validar)->format("Y-m-d"));
	    $fecha_rige = strtotime(create_date($fecha_rige)->format("Y-m-d"));
	    $fecha_vence = strtotime(create_date($fecha_vence)->format("Y-m-d"));

	    return ($fecha_validar >= $fecha_rige && $fecha_validar <= $fecha_vence);
	}
}