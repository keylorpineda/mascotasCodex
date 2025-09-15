if (!function_exists("is_date")) {
	function is_date(fecha, formato_validar = null) {
		return ValidadorFechas.validar(fecha, { detalleError: false, });
	}
}

if (!function_exists("calcular_anios_entre_fechas")) {
	function calcular_anios_entre_fechas(date1, date2 = undefined) {
	    function parsearFecha(fecha = undefined) {
	        let FORMATO = null;
	        if (typeof fecha !== "undefined") {
	            FORMATO = fechas_validador.validar(fecha);
	            if (false === FORMATO) { return false; }
	        }
	        const fechaMoment = (typeof fecha === "undefined") ? moment() : moment(fecha, FORMATO, true);
	        return fechaMoment.isValid() ? fechaMoment : null;
	    }

	    const startDate = parsearFecha(date1);
	    const endDate = parsearFecha(date2);

	    if (!startDate || !endDate) {
	        throw new Error("Formato de fecha no válido");
	    }

	    // Calcular diferencia en años con decimales
	    let years = endDate.diff(startDate, 'years', true);
	    years = Math.floor(years); // Redondear hacia abajo

	    return years;
	}
}

if (!function_exists("calcular_dias_entre_fechas")) {
	function calcular_dias_entre_fechas(date1, date2 = undefined) {
	    function parsearFecha(fecha = undefined) {
	        let FORMATO = null;
	        if (typeof fecha !== "undefined") {
	            FORMATO = fechas_validador.validar(fecha);
	            // console.log(fecha, FORMATO);
	            if (false === FORMATO) { return false; }
	        }
	        const fechaMoment = (typeof fecha === "undefined") ? moment() : moment(fecha, FORMATO, true);
	        return fechaMoment.isValid() ? fechaMoment : null;
	    }

	    const startDate = parsearFecha(date1);
	    const endDate = parsearFecha(date2);

	    if (!startDate || !endDate) {
	        throw new Error("Formato de fecha no válido");
	    }

	    // Asegurar la misma zona horaria para ambas fechas
	    startDate.utc();
	    endDate.utc();

	    // Calcular diferencia en días y sumar 1 para incluir ambas fechas
	    let days = endDate.diff(startDate, 'days') + 1;

	    return days;
	}
}

/* ====== DATE VALIDATOR ====== */

	const fechas_validador = {
	    formatos: [
	        "YYYY-MM-DD",
	        "DD/MM/YYYY",
	        "YYYY-MM-DD HH:mm:ss",
	        "DD/MM/YYYY HH:mm:ss",
	        "YYYY/MM/DD",
	        "DD-MM-YYYY",
	        "YYYY/MM/DD HH:mm:ss",
	        "DD-MM-YYYY HH:mm:ss",
	        "YYYY MM DD",
	        "DD MM YYYY",
	        "YYYY MM DD HH:mm:ss",
	        "DD MM YYYY HH:mm:ss",
	    ],

	    validar: function(string, formato_validar = null) {
	        this.formatos = !formato_validar ? this.formatos : (!Array.isArray(formato_validar) ? [formato_validar] : formato_validar);
	        for (let i = 0; i < this.formatos.length; i++) {
	            const formato = this.formatos[i];
	            const regex = this.construirExpresionRegular(formato);
	            if (regex.test(string)) {
	                const [dia, mes, anio] = this.extraerComponentesFecha(string, formato);
	                const date = new Date(anio, mes - 1, dia);
	                if (!isNaN(date.getTime()) && date.getDate() === dia) {
	                    return formato; // El string es una fecha válida
	                }
	            }
	        }
	        return false; // El string no es una fecha válida en ninguno de los formatos
	    },

	    construirExpresionRegular: function(formato) {
	        const regexFormato = formato
	            .replace(/[.*+?^${}()|[\]\\]/g, "\\$&")
	            .replace("YYYY", "(\\d{4})")
	            .replace("MM", "(\\d{2})")
	            .replace("DD", "(\\d{2})")
	            .replace("HH", "(\\d{2})")
	            .replace("mm", "(\\d{2})")
	            .replace("ss", "(\\d{2})");
	        return new RegExp(`^${regexFormato}$`);
	    },

	    extraerComponentesFecha: function(string, formato) {
	        const regex = this.construirExpresionRegular(formato);
	        const match = regex.exec(string);
	        if (!match) {
	            return [null, null, null];
	        }
	        const orden = formato.match(/(YYYY|MM|DD)/g);
	        const componentes = {};
	        orden.forEach((parte, index) => {
	            componentes[parte] = parseInt(match[index + 1], 10);
	        });
	        return [componentes.DD, componentes.MM, componentes.YYYY];
	    }
	};

/* ====== .\DATE VALIDATOR ====== */
	const ValidadorFechas = {
	    // Formatos soportados (inmutable)
	    FORMATOS_DEFAULT: [
	        "YYYY-MM-DD",
	        "DD/MM/YYYY", 
	        "MM/DD/YYYY",  // Formato US
	        "YYYY-MM-DD HH:mm:ss",
	        "DD/MM/YYYY HH:mm:ss",
	        "MM/DD/YYYY HH:mm:ss",
	        "YYYY/MM/DD",
	        "DD-MM-YYYY",
	        "YYYY/MM/DD HH:mm:ss", 
	        "DD-MM-YYYY HH:mm:ss",
	        "YYYY MM DD",
	        "DD MM YYYY",
	        "YYYY MM DD HH:mm:ss",
	        "DD MM YYYY HH:mm:ss",
	        "YYYY-MM-DDTHH:mm:ss",     // ISO 8601 básico
	        "YYYY-MM-DDTHH:mm:ssZ",    // ISO 8601 con Z
	        "YYYY-MM-DDTHH:mm:ss.sssZ" // ISO 8601 con milisegundos
	    ],

	    /**
	     * Valida una fecha con múltiples opciones
	     * @param {string} fechaString - La fecha a validar
	     * @param {Object} opciones - Configuración de validación
	     * @returns {Object} Resultado de la validación
	     */
	    validar(fechaString, opciones = {}) {
	        const config = {
	            formatos: opciones.formatos || this.FORMATOS_DEFAULT,
	            formatoEspecifico: opciones.formatoEspecifico || null,
	            minDate: opciones.minDate || null,
	            maxDate: opciones.maxDate || null,
	            soloFuturas: opciones.soloFuturas || false,
	            soloPasadas: opciones.soloPasadas || false,
	            excluirFinesDeSemana: opciones.excluirFinesDeSemana || false,
	            excluirFeriados: opciones.excluirFeriados || [],
	            detalleError: opciones.detalleError !== false, // true por defecto
	            ...opciones
	        };

	        // Validación inicial
	        if (!fechaString || typeof fechaString !== 'string') {
	            return this._crearRespuesta(false, 'Entrada inválida', null, 'El valor debe ser una cadena no vacía');
	        }

	        const formatosAUsar = config.formatoEspecifico
	        	? [config.formatoEspecifico]
	        	: (
	        		Array.isArray(config.formatos)
	        			? config.formatos
	        			: this.FORMATOS_DEFAULT
	        	);

	        // Intentar cada formato
	        for (const formato of formatosAUsar) {
	            const resultado = this._validarConFormato(fechaString, formato, config);
	            if (resultado.valido) {
	                return resultado;
	            }
	        }

	        return this._crearRespuesta(false, 'Formato no válido', null, 
	            `No coincide con ningún formato esperado: ${formatosAUsar.join(', ')}`);
	    },

	    /**
	     * Valida una fecha contra un formato específico
	     * @private
	     */
	    _validarConFormato(fechaString, formato, config) {
	        try {
	            const regex = this._construirRegex(formato);
	            const match = regex.exec(fechaString);
	            
	            if (!match) {
	                return this._crearRespuesta(false, 'No coincide con el formato', formato);
	            }

	            const componentes = this._extraerComponentes(match, formato);
	            
	            // Validar rangos de componentes
	            const validacionComponentes = this._validarComponentes(componentes);
	            if (!validacionComponentes.valido) {
	                return this._crearRespuesta(false, validacionComponentes.error, formato, validacionComponentes.detalle);
	            }

	            // Crear objeto Date y validar existencia de la fecha
	            const fecha = this._crearFecha(componentes);
	            if (!fecha || isNaN(fecha.getTime())) {
	                return this._crearRespuesta(false, 'Fecha inexistente', formato, 'La fecha no existe en el calendario');
	            }

	            // Validar que la fecha creada coincida con los componentes (evita fechas como 31 de febrero)
	            if (fecha.getDate() !== componentes.dia || 
	                fecha.getMonth() !== (componentes.mes - 1) || 
	                fecha.getFullYear() !== componentes.año) {
	                return this._crearRespuesta(false, 'Fecha inválida', formato, 'La fecha no existe (ej: 31 de febrero)');
	            }

	            // Validaciones adicionales
	            const validacionAdicional = this._validarRestricciones(fecha, config);
	            if (!validacionAdicional.valido) {
	                return this._crearRespuesta(false, validacionAdicional.error, formato, validacionAdicional.detalle);
	            }

	            return this._crearRespuesta(true, 'Fecha válida', formato, null, fecha, componentes);

	        } catch (error) {
	            return this._crearRespuesta(false, 'Error interno', formato, error.message);
	        }
	    },

	    /**
	     * Construye expresión regular para un formato
	     * @private
	     */
	    _construirRegex(formato) {
	        const regexFormato = formato
	            .replace(/[.*+?^${}()|[\]\\]/g, "\\$&")
	            .replace("YYYY", "(\\d{4})")
	            .replace("MM", "(\\d{2})")
	            .replace("DD", "(\\d{2})")
	            .replace("HH", "(\\d{2})")
	            .replace("mm", "(\\d{2})")
	            .replace("ss", "(\\d{2})")
	            .replace("sss", "(\\d{3})")  // milisegundos
	            .replace("T", "T")           // literal T
	            .replace("Z", "Z");          // literal Z

	        return new RegExp(`^${regexFormato}$`);
	    },

	    /**
	     * Extrae componentes de fecha del match de regex
	     * @private
	     */
	    _extraerComponentes(match, formato) {
	        const partes = formato.match(/(YYYY|MM|DD|HH|mm|ss|sss)/g) || [];
	        const componentes = {
	            año: null,
	            mes: null, 
	            dia: null,
	            hora: 0,
	            minuto: 0,
	            segundo: 0,
	            milisegundo: 0
	        };

	        partes.forEach((parte, index) => {
	            const valor = parseInt(match[index + 1], 10);
	            switch(parte) {
	                case 'YYYY': componentes.año = valor; break;
	                case 'MM': componentes.mes = valor; break;
	                case 'DD': componentes.dia = valor; break;
	                case 'HH': componentes.hora = valor; break;
	                case 'mm': componentes.minuto = valor; break;
	                case 'ss': componentes.segundo = valor; break;
	                case 'sss': componentes.milisegundo = valor; break;
	            }
	        });

	        return componentes;
	    },

	    /**
	     * Valida rangos de componentes individuales
	     * @private
	     */
	    _validarComponentes(comp) {
	        if (!comp.año || !comp.mes || !comp.dia) {
	            return { valido: false, error: 'Componentes faltantes', detalle: 'Año, mes y día son obligatorios' };
	        }

	        if (comp.año < 1000 || comp.año > 9999) {
	            return { valido: false, error: 'Año inválido', detalle: 'El año debe estar entre 1000 y 9999' };
	        }

	        if (comp.mes < 1 || comp.mes > 12) {
	            return { valido: false, error: 'Mes inválido', detalle: 'El mes debe estar entre 1 y 12' };
	        }

	        if (comp.dia < 1 || comp.dia > 31) {
	            return { valido: false, error: 'Día inválido', detalle: 'El día debe estar entre 1 y 31' };
	        }

	        if (comp.hora < 0 || comp.hora > 23) {
	            return { valido: false, error: 'Hora inválida', detalle: 'La hora debe estar entre 0 y 23' };
	        }

	        if (comp.minuto < 0 || comp.minuto > 59) {
	            return { valido: false, error: 'Minuto inválido', detalle: 'Los minutos deben estar entre 0 y 59' };
	        }

	        if (comp.segundo < 0 || comp.segundo > 59) {
	            return { valido: false, error: 'Segundo inválido', detalle: 'Los segundos deben estar entre 0 y 59' };
	        }

	        if (comp.milisegundo < 0 || comp.milisegundo > 999) {
	            return { valido: false, error: 'Milisegundo inválido', detalle: 'Los milisegundos deben estar entre 0 y 999' };
	        }

	        return { valido: true };
	    },

	    /**
	     * Crea objeto Date desde componentes
	     * @private
	     */
	    _crearFecha(comp) {
	        return new Date(
	            comp.año, 
	            comp.mes - 1, // JavaScript usa meses 0-11
	            comp.dia,
	            comp.hora || 0,
	            comp.minuto || 0, 
	            comp.segundo || 0,
	            comp.milisegundo || 0
	        );
	    },

	    /**
	     * Valida restricciones adicionales
	     * @private
	     */
	    _validarRestricciones(fecha, config) {
	        const ahora = new Date();

	        // Validar rango mínimo
	        if (config.minDate) {
	            const minDate = config.minDate instanceof Date ? config.minDate : new Date(config.minDate);
	            if (fecha < minDate) {
	                return { valido: false, error: 'Fecha muy antigua', detalle: `La fecha debe ser posterior a ${minDate.toLocaleDateString()}` };
	            }
	        }

	        // Validar rango máximo
	        if (config.maxDate) {
	            const maxDate = config.maxDate instanceof Date ? config.maxDate : new Date(config.maxDate);
	            if (fecha > maxDate) {
	                return { valido: false, error: 'Fecha muy reciente', detalle: `La fecha debe ser anterior a ${maxDate.toLocaleDateString()}` };
	            }
	        }

	        // Solo futuras
	        if (config.soloFuturas && fecha <= ahora) {
	            return { valido: false, error: 'Fecha no futura', detalle: 'Solo se permiten fechas futuras' };
	        }

	        // Solo pasadas
	        if (config.soloPasadas && fecha >= ahora) {
	            return { valido: false, error: 'Fecha no pasada', detalle: 'Solo se permiten fechas pasadas' };
	        }

	        // Excluir fines de semana (0=domingo, 6=sábado)
	        if (config.excluirFinesDeSemana) {
	            const diaSemana = fecha.getDay();
	            if (diaSemana === 0 || diaSemana === 6) {
	                return { valido: false, error: 'Fin de semana', detalle: 'No se permiten fechas en fines de semana' };
	            }
	        }

	        // Excluir feriados
	        if (config.excluirFeriados && config.excluirFeriados.length > 0) {
	            const fechaStr = fecha.toISOString().split('T')[0]; // YYYY-MM-DD
	            if (config.excluirFeriados.includes(fechaStr)) {
	                return { valido: false, error: 'Fecha feriado', detalle: 'La fecha coincide con un feriado excluido' };
	            }
	        }

	        return { valido: true };
	    },

	    /**
	     * Crea respuesta estandarizada
	     * @private
	     */
	    _crearRespuesta(valido, mensaje, formato, detalle = null, fecha = null, componentes = null) {
	        const respuesta = {
	            valido,
	            mensaje,
	            formato: formato || null
	        };

	        if (detalle) {
	            respuesta.detalle = detalle;
	        }

	        if (valido && fecha) {
	            respuesta.fecha = fecha;
	            respuesta.componentes = componentes;
	        }

	        return respuesta;
	    },

	    /**
	     * Método de conveniencia para validación simple
	     */
	    esValida(fechaString, formato = null) {
	        const resultado = this.validar(fechaString, { 
	            formatoEspecifico: formato,
	            detalleError: false 
	        });
	        return resultado.valido;
	    },

	    /**
	     * Obtiene todos los formatos soportados
	     */
	    obtenerFormatos() {
	        return [...this.FORMATOS_DEFAULT];
	    }
	};