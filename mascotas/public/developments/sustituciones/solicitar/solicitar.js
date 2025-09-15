(function () {
	const SUTITUCIONES_MODAL = document.querySelector(`#SustitucionesModal`);
	let infoUbicaciones = {},
	    COLABORADORES = {},
	    infoEmpleado = {},
	    dataForm = '';

	const guardar_solicitud_sustitucion = () => {
		const boton = SUTITUCIONES_MODAL.querySelector(`#guardar`);
		boton.disabled = true;
		const formData = set_data();
        return $.ajax({
            "url": base_url('sustituciones/solicitar/guardar'),
            "method": "POST",
            "data": formData,
            "dataType": "json",
            "contentType": false,
            "processData": false,
            "cache": false,
            "beforeSend" : function () {
                alerta.Info("Por favor espere, se está procesando su petición.");
            }
        }).done(function (resp) {
            if (resp["TIPO"] == "SUCCESS") {
            	return confirmar[capitalize(resp["TIPO"])](resp["MENSAJE"], null, true).then((resp) => {
            		if (false !== resp) {
            			window.open(RUTA_CONSULTA, '_blank', 'height=600,width=800');
            		}
            	});
                return window.location.reload();
            }
        	console.log(resp);
            if (resp["RUTA_CONSULTA"]) {
            	const RUTA_CONSULTA = resp["RUTA_CONSULTA"];
            	return confirmar[capitalize(resp["TIPO"])](resp["MENSAJE"]).then((resp) => {
            		if (false !== resp) {
            			window.open(RUTA_CONSULTA, '_blank', 'height=600,width=800');
            		}
            	});
            }
            alerta[capitalize(resp["TIPO"])](resp["MENSAJE"]).show();
            return console.log(resp)
        }).fail(function(err) {
            alerta.Danger(`Se presentó un imprevisto al momento de procesar la solicitud. Error: ${err.responseText}`);
            console.error(err);
        }).always(() => {
            boton.disabled = false;
        });
	}, set_data = () => {
		const formData = new FormData(SUTITUCIONES_MODAL.querySelector(`#formSustitucion`));
		for (const key in infoEmpleado) {
			formData.append(key, infoEmpleado[key]);
		}
		const INPUT_COMPROBANTES = document.querySelector(`input[name="comprobantes"]`).files;
		COMPROBANTES_LENGHT = INPUT_COMPROBANTES.length;
	    for (let i = 0; i < COMPROBANTES_LENGHT; i++) {
	        formData.append('comprobantes[]', INPUT_COMPROBANTES[i]);
	    }
		formData.append("t", "postSustitucion");
		formData.append("tipoSustitucion", dataForm);
		return formData;
	}, render_input_colaborador = (t) => {
	    let input = ''
	    switch (t) {
	        case "aus":
	            input = `
	            	<label class="w-100">
		                NOMBRE DEL COLABORADOR QUE AUSENTE:
		                <input list="nombre-ausente-list" class="form-control lista-nombres" name="nombre-ausente" placeholder="Nombre del colaborador ausente" autocomplete="off" required />
		                <datalist id="nombre-ausente-list"></datalist>
	            	</label>
	            `;
	        break
	        case "sus":
	            input = `
	            	<label class="w-100">
		                NOMBRE DEL COLABORADOR QUE LABORARÁ:
		                <input list="nombre-sustituto-list" class="form-control lista-nombres" name="nombre-sustituto" placeholder="Nombre del colaborador que laborará" autocomplete="off" required />
		                <datalist id="nombre-sustituto-list"></datalist>
	            	</label>
	            `;
	        break
	    }
	    return input
	}, get_time_am_pm = (time) => {
	    if (typeof time !== 'string') return ''
	    let [ hours, minutes ] = time.split(':'), meridian;
	    if (hours > 12) {
	        meridian = 'PM';
	        hours -= 12;
	    } else if (hours < 12) {
	        meridian = 'AM';
	        if (hours == 0) hours = 12;
	    } else {
	        meridian = 'PM';
	    }
	    return (`${hours}:${minutes} ${meridian}`)
	}
	// ========================================
	// FUNCIONES UTILITARIAS
	// ========================================

	/**
	 * Normaliza texto removiendo acentos y convirtiéndolo a minúsculas
	 * @param {string} text - Texto a normalizar
	 * @returns {string} Texto normalizado
	 */
	function normalizeText(text) {
	    if (!text || typeof text !== 'string') return '';

	    return text
	        .normalize("NFD") // Normalización Unicode
	        .replace(/[\u0300-\u036f]/g, "") // Remover acentos
	        .toLowerCase() // Convertir a minúsculas
	        .trim(); // Remover espacios al inicio y final
	}

	/**
	 * Verifica si todas las palabras de la búsqueda están presentes en el nombre
	 * @param {string} name - Nombre donde buscar
	 * @param {string} query - Consulta de búsqueda
	 * @returns {boolean} True si todas las palabras están presentes
	 */
	function matchesQuery(name, query) {
	    if (!name || !query) return false;

	    const queryWords = query.split(" ").filter(word => word.trim().length > 0);
	    return queryWords.every(word => name.includes(word));
	}

	/**
	 * Busca coincidencias exactas en una lista de nombres
	 * @param {string} searchText - Texto a buscar
	 * @param {Array<string>} namesList - Lista de nombres donde buscar
	 * @returns {string} Nombre encontrado o texto original si no hay coincidencia única
	 */
	function findExactMatch(searchText, namesList) {
	    const normalizedQuery = normalizeText(searchText);

	    if (!normalizedQuery || !Array.isArray(namesList)) {
	        return searchText;
	    }

	    const filteredNames = namesList.filter(name => 
	        matchesQuery(normalizeText(name), normalizedQuery)
	    );

	    // Solo retorna el resultado si hay exactamente una coincidencia
	    return filteredNames.length === 1 ? filteredNames[0] : searchText;
	}

	// ========================================
	// FUNCIONES PRINCIPALES
	// ========================================

	/**
	 * Maneja la selección de empleados y actualiza los campos correspondientes
	 * @param {Event} event - Evento del input
	 */
	function selectEmployee(event) {
	    const input = event.target.closest("input");
	    if (!input) {
	        return console.warn('No se encontró el input asociado al evento');
	    }

	    const value = input.value?.trim().toUpperCase();
	    const listId = input.getAttribute("list");

	    if (!value || !listId) {
	        return console.warn('Valor o lista no válidos:', { value, listId });
	    }

	    // Obtener elementos del DOM
	    const elements = getRequiredElements(listId);
	    if (!elements.isValid) {
	        return console.error('No se pudieron obtener todos los elementos necesarios');
	    }

	    // Buscar el colaborador
	    const employee = findEmployeeByValue(elements.employeesList, value);
	    if (!employee) {
	        return console.warn('Empleado no encontrado:', value);
	    }

	    // Procesar según el tipo de formulario
	    processEmployeeSelection(employee, listId, elements);
	}

	/**
	 * Obtiene los elementos necesarios del DOM
	 * @param {string} listId - ID de la lista de empleados
	 * @returns {Object} Objeto con los elementos y estado de validez
	 */
	function getRequiredElements(listId) {
	    const employeesList = SUTITUCIONES_MODAL?.querySelector(`#${listId}`);
	    const locationsList = SUTITUCIONES_MODAL?.querySelector('#lugares_datalist');
	    const locationInput = SUTITUCIONES_MODAL?.querySelector('#Lugar');
	    const scheduleInput = SUTITUCIONES_MODAL?.querySelector('#horario-regular');

	    const isValid = !!(employeesList && locationsList && locationInput && scheduleInput);

	    return {
	        employeesList,
	        locationsList,
	        locationInput,
	        scheduleInput,
	        locations: isValid ? createLocationsMap(locationsList) : {},
	        isValid
	    };
	}

	/**
	 * Crea un mapa de ubicaciones código -> nombre
	 * @param {Element} locationsList - Lista de ubicaciones
	 * @returns {Object} Mapa de ubicaciones
	 */
	function createLocationsMap(locationsList) {
	    const options = locationsList.querySelectorAll('option');
	    return Array.from(options).reduce((map, option) => {
	        const code = option.getAttribute('data-ubicacion-codigo');
	        if (code) {
	            map[code] = option.value;
	        }
	        return map;
	    }, {});
	}

	/**
	 * Busca un empleado por su valor en la lista
	 * @param {Element} employeesList - Lista de empleados
	 * @param {string} value - Valor a buscar
	 * @returns {Element|null} Elemento encontrado o null
	 */
	function findEmployeeByValue(employeesList, value) {
	    const options = employeesList.querySelectorAll('option');
	    return Array.from(options).find(option => option.value === value) || null;
	}

	/**
	 * Procesa la selección del empleado según el tipo de formulario
	 * @param {Element} employee - Elemento del empleado seleccionado
	 * @param {string} listId - ID de la lista
	 * @param {Object} elements - Elementos del DOM
	 */
	function processEmployeeSelection(employee, listId, elements) {
	    const employeeData 		   = extractEmployeeData(employee);
	    const isAbsentEmployee 	   = listId === 'nombre-ausente-list';
	    const isSubstituteEmployee = listId === 'nombre-sustituto-list';

	    // Configuraciones por tipo de formulario
	    const formConfigs = {
	        'SF': {
	            showLocationForAbsent: true,
	            showScheduleForAbsent: true
	        },
	        'FS': {
	            showLocationForAbsent: false,
	            showScheduleForAbsent: false
	        },
	        'FF': {
	            showLocationForAbsent: true,
	            showScheduleForAbsent: true
	        },
	        'SS': {
	            showLocationForAbsent: false,
	            showScheduleForAbsent: false
	        },
	        'RLE': {
	            showLocationForAbsent: false,
	            showScheduleForAbsent: false,
	            isSpecialCase: true
	        }
	    };

	    const config = formConfigs[dataForm] || formConfigs['SF'];

	    if (config.isSpecialCase && dataForm === 'RLE') {
	        handleRLECase(employeeData);
	    } else if (isAbsentEmployee) {
	        handleAbsentEmployee(employeeData, elements, config);
	    } else if (isSubstituteEmployee) {
	        handleSubstituteEmployee(employeeData);
	    }
	}

	/**
	 * Extrae los datos del empleado desde el elemento DOM
	 * @param {Element} employee - Elemento del empleado
	 * @returns {Object} Datos del empleado
	 */
	function extractEmployeeData(employee) {
	    return {
	        cedula: employee.getAttribute('data-empleado-cedula') || null,
	        codigo: employee.getAttribute('data-empleado-codigo') || null,
	        ubicacion: employee.getAttribute('data-empleado-ubicacion') || null,
	        horario: employee.getAttribute('data-empleado-horario') || null
	    };
	}

	/**
	 * Maneja el caso especial RLE
	 * @param {Object} employeeData - Datos del empleado
	 */
	function handleRLECase(employeeData) {
	    if (!infoEmpleado) return;

	    infoEmpleado.cod_lugar = employeeData.ubicacion;
	    infoEmpleado.cedula_ausente = null;
	    infoEmpleado.codigo_ausente = null;
	    infoEmpleado.cedula_sustituto = employeeData.cedula;
	    infoEmpleado.codigo_sustituto = employeeData.codigo;
	}

	/**
	 * Maneja la selección de empleado ausente
	 * @param {Object} employeeData - Datos del empleado
	 * @param {Object} elements - Elementos del DOM
	 * @param {Object} config - Configuración del formulario
	 */
	function handleAbsentEmployee(employeeData, elements, config) {
	    if (!infoEmpleado) return;

	    // Actualizar campos de ubicación y horario si es necesario
	    if (config.showLocationForAbsent && employeeData.ubicacion) {
	        const locationName = elements.locations[employeeData.ubicacion];
	        if (locationName && elements.locationInput) {
	            elements.locationInput.value = locationName;
	        }
	        infoEmpleado.cod_lugar = employeeData.ubicacion;
	    }

	    if (config.showScheduleForAbsent && elements.scheduleInput) {
	        elements.scheduleInput.value = employeeData.horario || "No Posee...";
	    }

	    // Actualizar información del empleado ausente
	    infoEmpleado.cedula_ausente = employeeData.cedula;
	    infoEmpleado.codigo_ausente = employeeData.codigo;
	}

	/**
	 * Maneja la selección de empleado sustituto
	 * @param {Object} employeeData - Datos del empleado
	 */
	function handleSubstituteEmployee(employeeData) {
	    if (!infoEmpleado) return;

	    infoEmpleado.cedula_sustituto = employeeData.cedula;
	    infoEmpleado.codigo_sustituto = employeeData.codigo;
	}

	/**
	 * Busca empleados existentes y actualiza el input con coincidencias
	 * @param {Event} event - Evento del input
	 */
	function searchExistingEmployees(event) {
	    const input = event.target;
	    if (!input) return;

	    const value = input.value;
	    const listId = input.getAttribute("list");

	    if (!value || !listId) return;

	    // Obtener lista de nombres desde el datalist
	    const datalist = document.querySelector(`#${listId}`);
	    if (!datalist) {
	        return console.warn('No se encontró la lista de datos:', listId);
	    }

	    const options = datalist.querySelectorAll('option');
	    const names = Array.from(options).map(option => option.value.trim());

	    // Buscar coincidencia exacta
	    const matchingName = findExactMatch(value, names);

	    // Actualizar el valor si es diferente
	    if (matchingName !== value) {
	        input.value = matchingName;
	        // Disparar evento para notificar el cambio
	        input.dispatchEvent(new Event("input", { bubbles: true }));
	    }
	}

	// ========================================
	// ALIASES PARA COMPATIBILIDAD
	// ========================================

	// Mantener nombres originales para compatibilidad con código existente
	const nombre_existe = findExactMatch;
	const select_empleados = selectEmployee;
	const search_empleados_existen = searchExistingEmployees;
	const pnd_option = (
		`
			<option
				value="PENDIENTE"
				data-empleado-codigo=""
				data-empleado-cedula=""
				data-empleado-horario=""
				data-empleado-ubicacion=""
			></option>`
	);

	const tipos_sustituciones = {
		"RLE": () => {
		    SUTITUCIONES_MODAL.querySelector('#fijo').innerHTML = render_input_colaborador('sus');
		    SUTITUCIONES_MODAL.querySelector('#sustituto').remove();

		    SUTITUCIONES_MODAL.querySelector('#Lugar').readOnly = false;
		    SUTITUCIONES_MODAL.querySelector("#horario").readOnly = false;
		    SUTITUCIONES_MODAL.querySelector('#lugares_datalist').innerHTML = document.querySelector(`#lista-ubicaciones`).innerHTML;

		    SUTITUCIONES_MODAL.querySelector(`#nombre-sustituto-list`).innerHTML = [
		    	pnd_option,
		    	document.querySelector(`#lista-empleados-fijos`).innerHTML,
		    	document.querySelector(`#lista-empleados-sustitutos`).innerHTML,
		    ].join("");
		},
		"SF": () => {
		    SUTITUCIONES_MODAL.querySelector('#fijo').innerHTML 	 = render_input_colaborador('aus');
		    SUTITUCIONES_MODAL.querySelector('#sustituto').innerHTML = render_input_colaborador('sus');

		    SUTITUCIONES_MODAL.querySelector('#Lugar').readOnly = false;
		    SUTITUCIONES_MODAL.querySelector("#horario").readOnly = false;
		    SUTITUCIONES_MODAL.querySelector('#lugares_datalist').innerHTML = document.querySelector(`#lista-ubicaciones`).innerHTML;

		    SUTITUCIONES_MODAL.querySelector(`#nombre-sustituto-list`).innerHTML = [
		    	pnd_option,
		    	document.querySelector(`#lista-empleados-sustitutos`).innerHTML,
		    ];
		    SUTITUCIONES_MODAL.querySelector(`#nombre-ausente-list`).innerHTML 	 = document.querySelector(`#lista-empleados-fijos`).innerHTML;
		},
		"FF": () => {
		    SUTITUCIONES_MODAL.querySelector('#fijo').innerHTML 	 = render_input_colaborador('aus');
		    SUTITUCIONES_MODAL.querySelector('#sustituto').innerHTML = render_input_colaborador('sus');

		    SUTITUCIONES_MODAL.querySelector('#Lugar').readOnly = false;
		    SUTITUCIONES_MODAL.querySelector("#horario").readOnly = false;
		    SUTITUCIONES_MODAL.querySelector('#lugares_datalist').innerHTML = document.querySelector(`#lista-ubicaciones`).innerHTML;

		    SUTITUCIONES_MODAL.querySelector(`#nombre-sustituto-list`).innerHTML = [
		    	pnd_option,
		    	document.querySelector(`#lista-empleados-fijos`).innerHTML,
		    ];
		    SUTITUCIONES_MODAL.querySelector(`#nombre-ausente-list`).innerHTML 	 = document.querySelector(`#lista-empleados-fijos`).innerHTML;
		},
		"SS": () => {
		    SUTITUCIONES_MODAL.querySelector('#fijo').innerHTML 	 = render_input_colaborador('aus');
		    SUTITUCIONES_MODAL.querySelector('#sustituto').innerHTML = render_input_colaborador('sus');

		    SUTITUCIONES_MODAL.querySelector('#Lugar').readOnly = false;
		    SUTITUCIONES_MODAL.querySelector("#horario").readOnly = false;
		    SUTITUCIONES_MODAL.querySelector('#lugares_datalist').innerHTML = document.querySelector(`#lista-ubicaciones`).innerHTML;

		    SUTITUCIONES_MODAL.querySelector(`#nombre-sustituto-list`).innerHTML = [
		    	pnd_option,
		    	document.querySelector(`#lista-empleados-sustitutos`).innerHTML,
		    ];
		    SUTITUCIONES_MODAL.querySelector(`#nombre-ausente-list`).innerHTML	 = document.querySelector(`#lista-empleados-sustitutos`).innerHTML;
		},
		"FS": () => {
		    SUTITUCIONES_MODAL.querySelector('#fijo').innerHTML 	 = render_input_colaborador('aus');
		    SUTITUCIONES_MODAL.querySelector('#sustituto').innerHTML = render_input_colaborador('sus');

		    SUTITUCIONES_MODAL.querySelector('#Lugar').readOnly = false;
		    SUTITUCIONES_MODAL.querySelector("#horario").readOnly = false;
		    SUTITUCIONES_MODAL.querySelector('#lugares_datalist').innerHTML = document.querySelector(`#lista-ubicaciones`).innerHTML;

		    SUTITUCIONES_MODAL.querySelector(`#nombre-sustituto-list`).innerHTML = [
		    	pnd_option,
		    	document.querySelector(`#lista-empleados-fijos`).innerHTML
		    ];
		    SUTITUCIONES_MODAL.querySelector(`#nombre-ausente-list`).innerHTML 	 = document.querySelector(`#lista-empleados-sustitutos`).innerHTML;
		}
	};

	$(($) => {
	    $(SUTITUCIONES_MODAL).on('show.bs.modal', function (e) {
	    	init_custom_files();
	        let time = setInterval(() => {
	            if (document.querySelector(`#nombre-sustituto-list`)) {
	                clearInterval(time);
	                let timeout = null;
	                $(".lista-nombres").off("input").on("input", function (ev) {
	                	if (timeout !== null) { clearTimeout(timeout); }
	                	timeout = setTimeout(() => {
	                		clearTimeout(timeout);
	                		select_empleados(ev);
	                	}, 1000);
	                }).off("change").on("change", search_empleados_existen);
	            }
	        }, 500);
	    }).on("submit", "#formSustitucion", function (e) {
	        e.preventDefault();

	        const FECHA_INICIO = document.querySelector(`[name="fecha_inicial"]`)?.value.trim();
	        const FECHA_FIN    = document.querySelector(`[name="fecha_final"]`)?.value.trim();

	        if (FECHA_FIN.length === 0 && !confirm("No se ha especificado la fecha de salida, ¿está seguro de procesar esta solicitud de todas formas?")) { return; }

	        const FECHA_RIGE = new Date(FECHA_INICIO);
	        const FECHA_VENCE = new Date(FECHA_FIN);

		    // Ignorar la hora estableciendo ambas fechas a medianoche
		    FECHA_RIGE.setHours(0, 0, 0, 0);
		    FECHA_VENCE.setHours(0, 0, 0, 0);

	        if (FECHA_VENCE < FECHA_RIGE) return alerta.Info('Atención!, La fecha de salida debe ser igual o mayor que la fecha de entrada').show();

	        const UBICACIONES 		   = Array.from(SUTITUCIONES_MODAL?.querySelector('#lugares_datalist').querySelectorAll(`option`));
	        const AUSENTES_OPTIONS 	   = SUTITUCIONES_MODAL.querySelector(`#fijo`)
	        	?  (
	        		dataForm === "RLE"
	        			? Array.from(getRequiredElements(`nombre-sustituto-list`).employeesList?.querySelectorAll('option'))
	        			: Array.from(getRequiredElements(`nombre-ausente-list`).employeesList?.querySelectorAll('option'))
	        	)
	        	: [];
	        const SUTITUCIONES_OPTIONS = SUTITUCIONES_MODAL.querySelector(`#sustituto`)
	        	? Array.from(getRequiredElements(`nombre-sustituto-list`).employeesList?.querySelectorAll('option'))
	        	: [];

	        const LISTA_COMPLETA_EMPLEADOS = {
		        ...SUTITUCIONES_OPTIONS.reduce((carry, empleado) => {
		            carry[empleado?.getAttribute("data-empleado-codigo")] = empleado?.value;
		            return carry;
		        }, {}),
	        	...AUSENTES_OPTIONS.reduce((carry, empleado) => {
		            carry[empleado?.getAttribute("data-empleado-codigo")] = empleado?.value;
		            return carry;
		        }, {}),
	        };

	        infoEmpleado.cod_lugar = UBICACIONES.filter(
        		(o) => o.value === SUTITUCIONES_MODAL?.querySelector(`[name="Lugar"]`)?.value
        	)[0].getAttribute(`data-ubicacion-codigo`);

	        if (infoEmpleado["nombre_sustituto"] && infoEmpleado["nombre_sustituto"].trim() !== "PENDIENTE" && !LISTA_COMPLETA_EMPLEADOS[infoEmpleado["codigo_sustituto"]]) {
	            return alerta.Warning("El dato ingresado en el campo del colaborador que laborará no es valido, por favor elige una de las opciones dadas").show();
	        }

	        if (dataForm !== 'RLE' && !LISTA_COMPLETA_EMPLEADOS[infoEmpleado["codigo_ausente"]]) {
	            return alerta.Warning("El dato ingresado en el campo del colaborador ausente no es valido, por favor elige una de las opciones dadas").show();
	        }

			const INPUT_COMPROBANTES = document.querySelector(`input[name="comprobantes"]`).files;
	        if (INPUT_COMPROBANTES.length > 4) {
	            return alerta.Info("El número de comprobantes adjuntos supera la cantidad permitida, por favor ingresa un máximo de 4 archivos con extención: .jpg, .jpeg, .png o .pdf.").show();
	        }

	        return guardar_solicitud_sustitucion();
	    }).on("change", "input[type='datetime-local']", function (e) {
	        const hora_entrada = $('#fecha_inicial').val().split('T')[1] || null;
	        const hora_salida  = $('#fecha_final').val().split('T')[1]   || null;
	        $('#horario').val(`De ${get_time_am_pm(hora_entrada)} A ${get_time_am_pm(hora_salida)}`);
	    })

	    $(`[data-sustituciones-mostrar-formulario]`).on("click", (ev) => {
	        infoEmpleado = {};
	        dataForm = ev.target.closest(`[data-form]`).getAttribute("data-form");
	        SUTITUCIONES_MODAL.querySelector(".modal-body").innerHTML = document.querySelector(`#formulario-solicitud`).innerHTML;
	        call_func_name(dataForm, tipos_sustituciones);
	        $(SUTITUCIONES_MODAL).modal("show");
	    })
	});
})();