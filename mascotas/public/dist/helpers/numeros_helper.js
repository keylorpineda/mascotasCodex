/**
 * Verifica si un valor es un string que representa un número
 * @param {any} value - El valor a verificar
 * @returns {boolean} true si es un string numérico válido
 * 
 * @example
 * isNumberString('123.45'); // true
 * isNumberString('1.23e-4'); // true
 * isNumberString('abc'); // false
 * isNumberString(''); // false
 */
const isNumberString = (value) => {
  // Verificar que sea string y no esté vacío
  if (typeof value !== 'string' || value.trim() === '') {
    return false;
  }
  
  // Regex mejorada para números con notación científica
  const numberRegex = /^[+-]?(\d+(\.\d*)?|\.\d+)([eE][+-]?\d+)?$/;
  return numberRegex.test(value.trim());
};

/**
 * Verifica si un valor es un float (número decimal)
 * @param {any} value - El valor a verificar
 * @returns {boolean} true si es un número decimal
 * 
 * @example
 * isFloat(123.45); // true
 * isFloat('123.45'); // true
 * isFloat(123); // false
 * isFloat('123'); // false
 */
const isFloat = (value) => {
  // Si es un número, verificar que tenga decimales
  if (typeof value === 'number') {
    return Number.isFinite(value) && value % 1 !== 0;
  }
  
  // Si es string, verificar formato decimal
  if (typeof value === 'string') {
    const trimmed = value.trim();
    if (trimmed === '') return false;
    
    // Regex para números decimales (debe tener punto decimal)
    const floatRegex = /^[+-]?\d+\.\d+$/;
    return floatRegex.test(trimmed) && Number.isFinite(parseFloat(trimmed));
  }
  
  return false;
};

/**
 * Verifica si un valor es numérico (número o string numérico válido)
 * @param {any} value - El valor a verificar
 * @returns {boolean} true si es numérico
 * 
 * @example
 * isNumeric(123); // true
 * isNumeric('123.45'); // true
 * isNumeric('1.23e-4'); // true
 * isNumeric('abc'); // false
 * isNumeric(''); // false
 */
const isNumeric = (value) => {
  // Verificar números directamente
  if (typeof value === 'number') {
    return Number.isFinite(value);
  }
  
  // Verificar strings
  if (typeof value === 'string') {
    const trimmed = value.trim();
    
    // String vacío no es numérico
    if (trimmed === '') {
      return false;
    }
    
    // Regex para números con notación científica
    const numericRegex = /^[+-]?\d+(\.\d+)?([eE][+-]?\d+)?$/;
    
    if (!numericRegex.test(trimmed)) {
      return false;
    }
    
    // Verificar que sea un número finito válido
    const parsed = parseFloat(trimmed);
    return Number.isFinite(parsed);
  }
  
  return false;
};

/**
 * Formatea un número como moneda en formato español (Costa Rica)
 * @param {number|string} amount - El monto a formatear
 * @returns {string} El monto formateado como moneda
 * 
 * @example
 * formatCurrency(1234.56); // "1,234.56"
 * formatCurrency('1234.56'); // "1,234.56"
 * formatCurrency('abc'); // "0.00"
 * formatCurrency(null); // "0.00"
 */
const formatCurrency = (amount) => {
  let numericAmount = amount;
  
  // Convertir string numérico a número
  if (isNumberString(amount)) {
    numericAmount = parseFloat(amount);
  }
  
  // Si no es un número válido, usar 0
  if (!Number.isFinite(numericAmount)) {
    numericAmount = 0;
  }
  
  // Formatear usando Intl.NumberFormat para mejor rendimiento y precisión
  try {
    return new Intl.NumberFormat('es-CR', {
      minimumFractionDigits: 2,
      maximumFractionDigits: 2,
    }).format(numericAmount);
  } catch (error) {
    // Fallback al método original si Intl.NumberFormat falla
    return numericAmount.toLocaleString('es-ES', {
      minimumFractionDigits: 2,
      maximumFractionDigits: 2,
    }).replace(/\./g, 'x').replace(/,/g, '.').replace(/x/g, ',');
  }
};

// Registro global para compatibilidad (solo si no existen)
if (typeof window !== 'undefined') {
  if (!function_exists('formatoMoneda')) {
    window.formatoMoneda = formatCurrency;
  }
  if (!function_exists('is_number_string')) {
    window.is_number_string = isNumberString;
  }
  if (!function_exists('is_float')) {
    window.is_float = isFloat;
  }
  if (!function_exists('is_numeric')) {
    window.is_numeric = isNumeric;
  }
} else if (typeof global !== 'undefined') {
  // Para Node.js
  if (!function_exists('formatoMoneda')) {
    global.formatoMoneda = formatCurrency;
  }
  if (!function_exists('is_number_string')) {
    global.is_number_string = isNumberString;
  }
  if (!function_exists('is_float')) {
    global.is_float = isFloat;
  }
  if (!function_exists('is_numeric')) {
    global.is_numeric = isNumeric;
  }
}