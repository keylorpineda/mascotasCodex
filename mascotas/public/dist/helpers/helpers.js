function function_exists(functionName) {
	return typeof window[functionName] === 'function' && window.hasOwnProperty(functionName);
}

if (!function_exists("select")) {
	function select(selector, getAll = true) {
		if (typeof selector !== 'string' || selector.trim() === '') {
	    	throw new Error('El selector proporcionado no es válido.');
		}
		try {
		    const elements = document.querySelectorAll(selector);
		    if (elements.length === 0) {
		    	throw new Error(`No se encontraron elementos con el selector: ${selector}`);
		    }
		    return elements.length === 1 || !getAll ? elements[0] : Array.from(elements);
		} catch (error) {
		    // console.error(error);
		    return null;
		}
	}
}

if (!function_exists("empty")) {
	function empty(value) {
		return (typeof value === 'undefined' || value === null || value === '' || (Array.isArray(value) && value.length === 0) || (typeof value === 'object' && Object.keys(value).length === 0)) || (value instanceof HTMLElement && value.nodeType === 1);
	}
}

if (!function_exists("isset")) {
	function isset(variable) {
		return typeof variable !== 'undefined' && variable !== null && variable !== '';
	}
}

if (!function_exists("serialize")) {
	function serialize (formName) {
		const form = select(formName);
		if (!form) {
			throw new Error(`No se encontró el formulario con el nombre: ${formName}`);
		}
		const formData = new FormData(form);
		const serializedData = {};
		for (const [key, value] of formData.entries()) {
		    if (serializedData.hasOwnProperty(key)) {
		    	if (Array.isArray(serializedData[key])) {
		        	serializedData[key].push(value);
		    	} else {
		        	serializedData[key] = [serializedData[key], value];
		    	}
		    } else {
		    	serializedData[key] = value;
		    }
		}
		return serializedData;
	}
}

if (!function_exists("randomString")) {
	/**
	 * Pseudo-random string generator
	 * http://stackoverflow.com/a/27872144/383904
	 * Default: return a random alpha-numeric string
	 * 
	 * @param {Integer} len Desired length
	 * @param {String} an Optional (alphanumeric), "a" (alpha), "n" (numeric)
	 * @return {String}
	 */
	function randomString(len, an) {
		an = an && an.toLowerCase();
		let str = "",
		i = 0,
		min = an == "a" ? 10 : 0,
		max = an == "n" ? 10 : 62;
		for (; i++ < len;) {
			let r = Math.random() * (max - min) + min << 0;
			str += String.fromCharCode(r += r > 9 ? r < 36 ? 55 : 61 : 48);
		}
		return str;
	}
}

if (!function_exists("sanitizedValue")) {
	function sanitizeValue(value) {
		// Escapar caracteres especiales que pueden ser utilizados para inyección de código
		return value.replace(/[<>"'`]/g, "");
	}
}

if (!function_exists("$_GET")) {
	var $_GET = (function() {
		const urlParams = new URLSearchParams(window.location.search);
		const variablesGet = {};
		for (let [key, value] of urlParams) {
			// Validación básica: escapar caracteres especiales
			const sanitizedValue = sanitizeValue(value);
			variablesGet[key] = sanitizedValue;
		}
		return variablesGet;
	})()
}

if (!function_exists("capitalize")) {
	function capitalize(s) {
	    return s.split(' ').map((palabra) => {
	        return palabra.charAt(0).toUpperCase() + palabra.slice(1).toLowerCase();
	    }).join(' ');
	}
}

if (!function_exists("string_to_html")) {
	function string_to_html(str) {
		// Crear una instancia de DOMParser
		const parser = new DOMParser();
		// Analizar la cadena de texto como HTML
		const doc = parser.parseFromString(str, 'text/html');
		// Devolver el primer elemento hijo del cuerpo del documento
		return doc.body.firstChild;
	}
}

if (!function_exists("isValidHttpUrl")) {
	function isValidHttpUrl(string) {
		let url;
		try {
			url = new URL(string);
		} catch (_) {
			return false;
		}
		return url.protocol === "http:" || url.protocol === "https:";
	}
}

if (!function_exists("navigate_with_press_enter_key")) {
	function navigate_with_press_enter_key(event) {
	    if (event.key === "Enter") {
	        const formulario = event.target.closest("form");
	        let index = Array.prototype.indexOf.call(formulario.elements, event.target);
	        while (index < formulario.elements.length - 1) {
	            index++;
	            const elemento = formulario.elements[index];
	            const tagName = elemento.tagName.toUpperCase();
	            const tipoInput = elemento.type.toLowerCase();
	            let tiposPermitidos = ["text", "password", "number", "email", "tel", "date", "time", "datetime-local", "submit", "button"];
	            if (tiposPermitidos.includes(tipoInput) || tagName === "TEXTAREA" || tagName === "SELECT" || (tagName === "BUTTON" && tipoInput === "submit")) {
	            	elemento.focus();
	            	if (tagName === "INPUT" || tagName === "TEXTAREA") { elemento.select(); }
	                event.preventDefault();
	                return true;
	            }
	        }
	        return false;
	    }
	    return true;
	}
}