<?php

if (!function_exists("is_email")) {
    function is_email(string|array $value): bool
    {
        $value = is_string($value) ? [$value] : $value;
        
        return !empty($value) && array_reduce(
            $value,
            static fn(bool $valid, string $email): bool => 
                $valid 
                && filter_var($email, FILTER_VALIDATE_EMAIL) !== false
                && filter_var($email, FILTER_SANITIZE_EMAIL) === $email
                && strlen($email) <= 254,
            true
        );
    }
}

if (!function_exists("normalize_text")) {
    /**
     * Normaliza texto removiendo acentos y caracteres especiales
     * Soporta múltiples idiomas y caracteres Unicode
     */
    function normalize_text(
        string $text, 
        bool $allowUnderscores = true, 
        bool $allowDashes = true, 
        bool $allowSpaces = true,
        bool $preserveCase = true
    ): string {
        // Método 1: Usando transliteración Unicode (recomendado)
        if (function_exists('transliterator_transliterate')) {
            $text = transliterator_transliterate('Any-Latin; Latin-ASCII', $text);
        } else {
            $text = removeAccentsManual($text);
        }
        
        // Construir patrón de caracteres permitidos
        $allowed = 'a-zA-Z0-9';
        if ($allowSpaces) $allowed .= '\s';
        if ($allowDashes) $allowed .= '\-';
        if ($allowUnderscores) $allowed .= '_';
        
        // Remover caracteres no permitidos
        $text = preg_replace('/[^' . $allowed . ']/u', '', $text);
        
        // Normalizar espacios múltiples
        if ($allowSpaces) {
            $text = preg_replace('/\s+/u', ' ', $text);
        }
        
        return trim($text);
    }
}

if (!function_exists("remove_accents_manual")) {
    /**
     * Remueve acentos usando mapeo manual (fallback)
     */
    function remove_accents_manual(string $text): string
    {
        $accents = [
            // Vocales con acentos (incluyendo las originales de tu función)
            'á' => 'a', 'à' => 'a', 'ä' => 'a', 'â' => 'a', 'ā' => 'a', 'ã' => 'a',
            'é' => 'e', 'è' => 'e', 'ë' => 'e', 'ê' => 'e', 'ē' => 'e',
            'í' => 'i', 'ì' => 'i', 'ï' => 'i', 'î' => 'i', 'ī' => 'i',
            'ó' => 'o', 'ò' => 'o', 'ö' => 'o', 'ô' => 'o', 'ō' => 'o', 'õ' => 'o',
            'ú' => 'u', 'ù' => 'u', 'ü' => 'u', 'û' => 'u', 'ū' => 'u',
            
            // Mayúsculas
            'Á' => 'A', 'À' => 'A', 'Ä' => 'A', 'Â' => 'A', 'Ā' => 'A', 'Ã' => 'A',
            'É' => 'E', 'È' => 'E', 'Ë' => 'E', 'Ê' => 'E', 'Ē' => 'E',
            'Í' => 'I', 'Ì' => 'I', 'Ï' => 'I', 'Î' => 'I', 'Ī' => 'I',
            'Ó' => 'O', 'Ò' => 'O', 'Ö' => 'O', 'Ô' => 'O', 'Ō' => 'O', 'Õ' => 'O',
            'Ú' => 'U', 'Ù' => 'U', 'Ü' => 'U', 'Û' => 'U', 'Ū' => 'U',
            
            // ñ/Ñ como en tu función original
            'ñ' => 'n', 'Ñ' => 'N',
            
            // Otros caracteres especiales
            'ç' => 'c', 'Ç' => 'C',
            'ß' => 'ss',
            'æ' => 'ae', 'Æ' => 'AE',
            'œ' => 'oe', 'Œ' => 'OE',
        ];
        
        return str_replace(array_keys($accents), array_values($accents), $text);
    }
}

if (!function_exists("quitar_acentos")) {
    /**
     * FUNCIÓN COMPATIBLE - Mantiene la misma interfaz que tu función original
     * Pero utiliza internamente los métodos mejorados
     * 
     * @param string $string El texto a procesar
     * @param bool $allowUnderscores Permitir guiones bajos
     * @param bool $allowDashes Permitir guiones medios  
     * @param bool $allowSpaces Permitir espacios
     * @return string El texto procesado
     */
    function quitar_acentos(string $string, bool $allowUnderscores = true, bool $allowDashes = true, bool $allowSpaces = true): string
    {
        // Usa la función mejorada internamente pero mantiene el comportamiento original
        return normalize_text($string, $allowUnderscores, $allowDashes, $allowSpaces, true);
    }
}

if (!function_exists("str_to_title_case")) {
    /**
     * Convierte texto a Title Case (compatible con la función anterior)
     */
    function str_to_title_case(string $str): string
    {
        return preg_replace_callback(
            '/\b\p{L}+/u', 
            fn($match) => mb_convert_case($match[0], MB_CASE_TITLE, "UTF-8"),
            $str
        );
    }
}

if (!function_exists("text_to_slug")) {
    /**
     * Convierte texto a formato slug (URL-friendly)
     */
    function text_to_slug(string $text, string $separator = '-'): string
    {
        $slug = normalize_text($text, false, false, true, false);
        $slug = strtolower($slug);
        $slug = preg_replace('/\s+/', $separator, $slug);
        $slug = preg_replace('/[' . preg_quote($separator) . ']+/', $separator, $slug);
        return trim($slug, $separator);
    }
}

if (!function_exists("strToCapitalize")) {
    /**
     * Versión simplificada usando mb_convert_case directamente
     * Puede ser menos precisa con algunas excepciones de idioma
     * 
     * @param string $str El string a convertir
     * @return string El string convertido a Title Case
     */
    function strToCapitalize(string $str): string
    {
        return mb_convert_case($str, MB_CASE_TITLE, "UTF-8");
    }
}

if (!function_exists("random_str")) {
	function random_str(int $length = 12, string $characters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'): string
	{
	    $charactersLength = strlen($characters);
	    $randomStr = '';
	    for ($i = 0; $i < $length; $i++) {
	        $randomStr .= $characters[rand(0, $charactersLength - 1)];
	    }
	    return $randomStr;
	}
}