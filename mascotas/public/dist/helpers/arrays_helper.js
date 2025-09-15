
if (!function_exists("array_chunk")) {
	/**
	 * Divide un arreglo en lotes del tamaño indicado.
	 * @param {Array} items - El arreglo original que se va a dividir.
	 * @param {number} size - El tamaño de cada lote.
	 * @returns {Array<Array>} - Un arreglo de lotes.
	 */
	function array_chunk(items, size = 20) {
		if (!Array.isArray(items)) throw new TypeError("Se esperaba un arreglo.");
		if (typeof size !== "number" || size <= 0) throw new RangeError("El tamaño debe ser un número positivo.");

		return Array.from(
			{ length: Math.ceil(items.length / size) },
			(_, index) => items.slice(index * size, index * size + size)
		);
	}
}

if (!function_exists("is_array")) {
	function is_array(variable) {
		return Array.isArray(variable);
	}
}

if (!function_exists("array_sum")) {
	function array_sum(array) {
		if (!Array.isArray(array)) {
			throw new TypeError("El argumento debe ser un array.");
		}

		return array.reduce((accumulator, currentValue) => {
			if (typeof currentValue !== 'number') {
				throw new TypeError("El array debe contener solo números.");
			}
			return accumulator + parseFloat(currentValue);
		}, 0);
	}
}
