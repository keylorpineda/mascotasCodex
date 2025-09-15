(() => {
	const FILTROS_CONTAINER = document.querySelector(`[data-app-filtros]`),
		MODAL_COMUNICADO    = document.querySelector(`#plantillaModal`),
        MODAL_EMERGENCIAS   = document.querySelector("#emergenciasModal"),
        MODAL_CORREOS       = document.querySelector("#correoSustitucionModal"),
        TABLA_COMUNICADOS   = document.querySelector(`[data-tabla-comunicados]`),
        TABLA_EMERGENCIAS   = document.querySelector(`[data-tabla-emergencias]`),
        OPCIONES_EMPLEADOS  = Array.from(document.querySelector(`#lista-empleados`).content.cloneNode(true).querySelectorAll(`option`));
    let DATOS_COMUNICADO_GUARDAR = {
    }, CONFIGURACION_CAMPOS_COMUNICADO_GUARDAR = {
        NOMBRE_EMPLEADO:      'value',
        TIPO_EMPLEADO:        'value',
        CEDULA_EMPLEADO:      'textContent',
        FECHA_RIGE:           'textContent',
        FECHA_VENCE:          'textContent',
        OFICIO:               'value',
        FECHA_ENVIO_REGISTRO: 'textContent',
        NOTAS_RH:             'value',
        CAJEROS:              'textContent',
        ALMUERZO:             'value',
        HORARIO_CUBRIR:       'value',
        SOLO_CLIENTE:         'value',
        RECONTRATABLE:        'value',
        ESTADO_EMERGENCIA:    'value',
    }, id_sustitucion, dataTableEmergencias;

    const validar_cantidad_comprobantes = (nuevos) => {
        const BOTON = TABLA_COMUNICADOS.querySelector(`button[data-id="${id_sustitucion}"]`);
        const ROW = dataTable.row(BOTON.closest("tr")).data();
        let maximos = 8;
        const adjuntos = ROW["ADJUNTOS"]
            .split("\\")
            .filter(
                (item) => item.trim().length
            )
            .join("");
        try {
            const adjuntos_length = JSON.parse(adjuntos).length;
            const total_adjuntos  = adjuntos_length + nuevos;
            if (total_adjuntos > maximos) {
                alerta.Warning(`El número de archivos ingresados excede el total permitido. El máximo de archivos permitido es: '${maximos}'`).show();
                return false;
            }
        } catch (error) {
            alerta.Warning(`Error al validar la cantidad de adjuntos en el comunicado. Error: ${error}`).show();
            return false;
        }
        return true;
    }

    const toggle_columnas = (TABLE, ESTADO) => {
        const ES_ANULADOS = ESTADO === 'ANU';
        TABLE.columns([14, 15]).visible(ES_ANULADOS);          // anulados
        TABLE.columns([10, 11, 12, 13]).visible(!ES_ANULADOS); // procesados
    }, anular_comunicado = () => {
    	Modal.Close('#plantillaModal').then(() => {
    	Swal.fire({
    	    "title": 'Anular Comunicado',
    	    "html": `
    	        <div class="form-group">
    	            <label class="w-100">
    	            	MOTIVO:
    	            	<textarea
    	            		id="motivo_anulacion"
    	            		class="form-control"
    	            		placeholder="Motivo de la anulación..."
    	            		rows='3'
    	            		minlength="25"
    	            		required
    	            	></textarea>
    	            </label>
    	        </div>
    	    `,
    	    "confirmButtonText": 'Enviar <i class="fa-solid fa-paper-plane"></i>',
    	    "focusConfirm": false,
    	    "showCancelButton": true,
    	    "cancelButtonText": 'Cancelar <i class="fa-solid fa-window-close"></i>',
    	    "showLoaderOnConfirm": true,
    	    "preConfirm": function() {
    	        const motivo_anulacion = $.trim($('#motivo_anulacion').val())
    	        if (motivo_anulacion.length < 1) return Swal.showValidationMessage(`¡Atención! El campo "Motivo" es requerido y no puede quedar en blanco`)
    	        if (motivo_anulacion.length < 10) return Swal.showValidationMessage(`¡Atención! La descripción ingresada en el campo "Motivo" es demasiado corta, por favor prolonga a un mínimo de 25 caracteres`)
    	        return {motivo_anulacion}
    	    }
    	}).then((e) => {
    	    if (e.isDismissed) {
    	        Modal.Open('#plantillaModal').then(
    	        	() => getSustitucionesPDF()
    	        );
    	        return true;
    	    }

    	    return Modal.Open("#modalcargando").then(() => {
	    	    return $.ajax({
	    	        "url": base_url("sustituciones/listado/rh/rechazar"),
	    	        "method": "DELETE",
	    	        "data": {
		    	        motivo_anulacion: e.value.motivo_anulacion,
		    	        id_sustitucion: id_sustitucion,
		    	    },
	    	        "dataType": "json",
	    	        "beforeSend" : function () {
	    	            alerta.Info("Por favor espere, se está procesando su petición.").show();
	    	        }
	    	    }).done(function( resp ) {
	    	        dataTable.ajax.reload(null, false);
	    	        return alerta[capitalize(resp["TIPO"])](resp["MENSAJE"]).show();
	    	    }).fail(function( err ) {
	    	        alerta('error', 'Se ha presentado un imprevisto al momento de procesar su solicitud :(', '')
	    	        console.error(err);
	            	() => Modal.Close(`#modalcargando`).then(
	            		() => confirmar.Danger(`No se han podido cargar los datos debido a un imprevisto. Error: ${ err["responseText"] }`, null, true)
	            	)
	    	    });
    	    });
    	})
    	});
    }, get_comunicado = async () => {
        const resp = $.ajax({
            "url": base_url("sustituciones/listado/rh/obtener"),
            "method": "GET",
            "data": {
                ID_SUSTITUCION: id_sustitucion,
                MODULO: "RRHH",
            },
            "dataType": "json",
        });

        const resp_await = await resp;
        if (resp_await["TIPO"]) return alerta[capitalize(resp_await["TIPO"])](resp_await["MENSAJE"]).show();

        Modal.Open("#modalcargando").then(
            () => {
                resp.done(function(sustituciones) {
                    MODAL_COMUNICADO.querySelector('.modal-body').innerHTML = document.querySelector("#comunicado").innerHTML;
                    return MODAL_COMUNICADO.querySelector('#empleadoslist').innerHTML = document.querySelector(`#lista-empleados`).innerHTML.toString();
                }).done(function (resp) {
                    // Mostrar/ocultar nombre fijo según tipo de sustitución
                    const isRLE = resp.tipoSustitucion === 'RLE';
                    const nombreFijo = MODAL_COMUNICADO.querySelector('#nombre_fijo');
                    const tNombreFijo = MODAL_COMUNICADO.querySelector('#t_nombre_fijo');
                    
                    nombreFijo.classList.toggle('d-none', isRLE);
                    nombreFijo.classList.toggle('d-block', !isRLE);
                    tNombreFijo.classList.toggle('d-none', isRLE);
                    tNombreFijo.classList.toggle('d-block', !isRLE);
                    
                    // Configurar campo recontratable según motivo
                    const requiereRecontratable = ['Renuncia', 'Despido'].includes(resp.motivo);
                    const recontratable = MODAL_COMUNICADO.querySelector('#recontratable');
                    
                    recontratable.required = requiereRecontratable;
                    recontratable.parentElement.classList.toggle('d-none', !requiereRecontratable);
                    
                    // Agregar notas adicionales si es necesario
                    const tieneNotas = resp.notas_adicionales?.trim().length > 0;
                    if (resp.registro_cambios === 1 || tieneNotas) {
                        MODAL_COMUNICADO.querySelector('#sendForm').disabled = true;
                        const notasHTML = `
                            <div class="col-md-12 form-group">
                                <label class="w-100">
                                    Nota adicional para planilla:
                                    <textarea id="notas_adicionales" class="form-control mb-1" placeholder="Nota adicional para planilla..." rows="3" minlength="25"></textarea>
                                    <button type="button" class="btn btn-success btn-icon w-100" data-toggle="tooltip" id="GuardarNotaAdicional" title="Guardar nota">
                                        <i class="fa-solid fa-floppy-disk"></i>
                                        Guardar nota para planilla
                                    </button>
                                </label>
                            </div>
                        `;
                        MODAL_COMUNICADO.querySelector('#observ_rrhh').parentElement.parentElement.insertAdjacentHTML('afterend', notasHTML);
                    }
                    
                    // Agregar botón de descarga si hay comprobantes
                    if (resp.comprobante) {
                        try {
                            const ADJUNTOS = resp.comprobante.split("\\").filter((item) => item.trim().length).join("");
                            const recursos = JSON.parse(ADJUNTOS);
                            if (recursos.length > 0) {
                                const downloadButton = `
                                    <button
                                        type="button"
                                        class="btn btn-primary btn-icon w-100 download-resources"
                                        data-toggle="tooltip"
                                        title="Descargar comprobantes"
                                        data-comunicado-idsustitucion="${id_sustitucion}"
                                    >
                                        <i class="fa-solid fa-file-download"></i>
                                        Descargar Comprobantes
                                    </button>
                                `;
                                
                                MODAL_COMUNICADO.querySelector('#btnDescargarRecursosPDF').innerHTML = downloadButton;
                                $(MODAL_COMUNICADO).off(
                                    'click', '.download-resources'
                                ).on(
                                    'click', '.download-resources', descargar_adjuntos_comunicado
                                );
                            }
                        } catch (e) {
                            console.warn('Error parsing comprobantes:', e);
                        }
                    }
                    
                    // Agregar campo de motivo de anulación si está anulado
                    if (resp.anulado === 1) {
                        const anulacionHTML = `
                            <div class="col-md-12 form-group">
                                <label class="w-100">
                                    Motivo de la anulación:
                                    <textarea
                                        id="motivo_anulacion"
                                        class="form-control"
                                        placeholder="Motivo de la anulación..."
                                        rows="3"
                                        minlength="25"
                                        disabled
                                    ></textarea>
                                </label>
                            </div>
                        `;
                        
                        MODAL_COMUNICADO.querySelector('#observ_rrhh').parentElement.insertAdjacentHTML('afterend', anulacionHTML);
                    }
                    
                    // Mostrar/ocultar nota de rechazo nómina
                    const notaRechazoNomina = MODAL_COMUNICADO.querySelector('#notarechazonomina');
                    const parentElement = notaRechazoNomina.parentElement.parentElement;
                    parentElement.classList.toggle('d-none', resp.estado !== 'RNM');

                    $(document).off("show.bs.collapse", "#collapseAusencias, #collapseSustituciones")
                    $(document).on("show.bs.collapse", "#collapseAusencias, #collapseSustituciones", function (e) {
                        const collapse = this
                        const data = {
                            codigo_sustituto: resp.codigo_empleado2,
                            cedula_sustituto: resp.cedula,
                            codigo_ausente  : resp.codigo_empleado,
                            cedula_ausente  : resp.cedula_fijo,
                            ACCION          : collapse.querySelector(`[name="ACCION"]`).value,
                            t               : "getAusenciasSustituciones",
                        }

                        $(collapse).find("#NOMBRE_AUSENTE").text(resp.nombre_fijo)
                        $(collapse).find("#NOMBRE_SUSTITUTO").text(resp.nombre_empleado)

                        const table = collapse.querySelector(".table tbody")
                        table.innerHTML = ''
                        $.ajax({
                            "url": base_url("Controllers/SustitucionesController"),
                            "method": "POST",
                            "data": data,
                            "dataType": "json",
                        })
                        .done(function (resp) {
                            $(collapse).find("#CANTIDAD_AUSENCIAS").text(resp.length)
                            $(collapse).find("#CANTIDAD_NOMBRAMIENTOS").text(resp.length)
                            if (!resp.length) {
                                return $(table).html(`
                                    <tr>
                                        <td colspan="100%" class="text-center">
                                            Ningún dato disponible en esta tabla...
                                        </td>
                                    </tr>
                                `)
                            }
                            resp.forEach((item) => {
                                $(table).append(`
                                <tr>
                                <td>${item.oficio_num}</td>
                                <td>${moment(item.fecha_inicial, "YYYY-MM-DD HH:mm:ss").format( "DD/MM/YYYY" )}</td>
                                <td>${moment(item.fecha_final, "YYYY-MM-DD HH:mm:ss").format( "DD/MM/YYYY" )}</td>
                                <td>${item.nombre_sustituto || item.nombre_fijo}</td>
                                </tr>
                                `)
                            })
                        })
                        .fail(function (err) {
                        })
                    })
                }).done(function (resp) {
                    // Deshabilitar botones si está anulado o tiene cambios registrados
                    if (resp.anulado === 1 || resp.registro_cambios === 1) {
                        MODAL_COMUNICADO.querySelector('#sendForm').disabled = true;
                        MODAL_COMUNICADO.querySelector('#anular-comunicado').disabled = true;
                    }
                    
                    // Configurar firma
                    const firma = MODAL_COMUNICADO.querySelector('#firma');
                    if (firma && resp.firma) {
                        firma.src = resp.firma;
                    }
                    
                    // Poblar todos los campos del formulario
                    Object.entries(resp).forEach(([key, value]) => {
                        if (!value) return;
                        const element = MODAL_COMUNICADO.querySelector(`#${key}`);
                        if (!element) return;
                        const val = value.toString().trim();
                        if (isValidHttpUrl(val)) {
                            element.src = val;
                        } else {
                            const isInput = ['INPUT', 'SELECT', 'TEXTAREA'].includes(element.tagName);
                            if (isInput) {
                                element.value = val;
                            } else {
                                element.textContent = val;
                            }
                        }
                    });

                    // Configurar select de tipo empleado
                    const tipoEmpleadoSelect = MODAL_COMUNICADO.querySelector('select[name="tipoempleado"]');
                    if (tipoEmpleadoSelect && resp.tipoempleado) {
                        tipoEmpleadoSelect.value = resp.tipoempleado;
                    }
                }).done((resp) => {
                    // Abrir modal
                    return Modal.Open('#plantillaModal').then(
                        () => {
                            const { sendForm, soloCliente, notaRH, nombreEmpleado, tipoempleado } = {
                                sendForm: MODAL_COMUNICADO.querySelector('#sendForm'),
                                soloCliente: MODAL_COMUNICADO.querySelector('[name="SOLO_CLIENTE"]'),
                                notaRH: MODAL_COMUNICADO.querySelector('[name="observ_rrhh"]'),
                                nombreEmpleado: MODAL_COMUNICADO.querySelector('[name="nombre_empleado"]'),
                                tipoempleado: MODAL_COMUNICADO.querySelector('[name="tipoempleado"]')
                            };
                            // tipoempleado.value = resp.tipoempleado;
                            
                            // Validar elementos críticos
                            if (!sendForm || !soloCliente || !notaRH) return;
                            
                            // Configurar según estado
                            const estado = resp.estado;
                            
                            if (estado !== 'PND') {
                                MODAL_COMUNICADO.querySelector(`#ESTADO_EMERGENCIA`).disabled = true;
                            }

                            if (estado === 'ANU') {
                                // Anulado: deshabilitar y ocultar
                                sendForm.disabled = true;
                                sendForm.classList.add('d-none');
                            } else if (estado === 'PRN') {
                                // Procesado-nómina: configurar solo cliente
                                soloCliente.value = 'S';
                                notaRH.disabled = true;
                                sendForm.disabled = false;
                                sendForm.classList.remove('d-none');
                            } else {
                                // Otros estados: habilitar formulario
                                sendForm.disabled = false;
                                sendForm.classList.remove('d-none');
                            }
                            
                            // Event listener para validación de nombre
                            if (nombreEmpleado) {
                                nombreEmpleado.removeEventListener('change', validar_nombre_sustituto);
                                nombreEmpleado.addEventListener('change', validar_nombre_sustituto);
                            }
                        }
                    );
                }).fail(function (err) {
                    alerta.Danger(`Se ha presentado un imprevisto al momento de obtener el registro :( \nError: ${err.responseText}`).show();
                    console.error(err)
                });
            }
        );

        return resp;
    }, validar_datos_comunicado = (ev) => {
        ev.preventDefault();
        
        // Validaciones de campos requeridos
        const validations = [
            {
                element: document.getElementById('hora_entrada_salida'),
                message: 'No se ha especificado el horario a cubrir'
            },
            {
                element: document.getElementById('tiempo_alimento'),
                message: 'No se ha especificado el tiempo de alimentación'
            },
            {
                element: document.getElementById('observ_rrhh'),
                message: 'Se debe indicar una observación con un mínimo de 10 caracteres',
                condition: (val) => val.length >= 10
            }
        ];
        
        // Ejecutar validaciones
        for (const validation of validations) {
            const value = validation.element.value.trim();
            const isValid = validation.condition ? validation.condition(value) : value.length > 0;
            
            if (!isValid) {
                validation.element.focus();
                return alerta.Warning(validation.message).show();
            }
        }
        
        DATOS_COMUNICADO_GUARDAR = agregar_valores_guardar_comunicado({
            NOMBRE_EMPLEADO:      [ MODAL_COMUNICADO.querySelector('#nombre_empleado'),                  '#nombre_empleado',                 ],
            TIPO_EMPLEADO:        [ MODAL_COMUNICADO.querySelector('select[name="tipoempleado"]'),       'select[name="tipoempleado"]',      ],
            CEDULA_EMPLEADO:      [ MODAL_COMUNICADO.querySelector('#cedula'),                           '#cedula',                          ],
            FECHA_RIGE:           [ MODAL_COMUNICADO.querySelector('#fecha_inicial'),                    '#fecha_inicial',                   ],
            FECHA_VENCE:          [ MODAL_COMUNICADO.querySelector('#fecha_final'),                      '#fecha_final',                     ],
            OFICIO:               [ MODAL_COMUNICADO.querySelector('#oficio_num'),                       '#oficio_num',                      ],
            FECHA_ENVIO_REGISTRO: [ MODAL_COMUNICADO.querySelector('#fecha_envio_registro'),             '#fecha_envio_registro',            ],
            NOTAS_RH:             [ MODAL_COMUNICADO.querySelector('#observ_rrhh'),                      '#observ_rrhh',                     ],
            CAJEROS:              [ MODAL_COMUNICADO.querySelector('#limpieza_cajero'),                  '#limpieza_cajero',                 ],
            ALMUERZO:             [ MODAL_COMUNICADO.querySelector('#tiempo_alimento'),                  '#tiempo_alimento',                 ],
            HORARIO_CUBRIR:       [ MODAL_COMUNICADO.querySelector('#hora_entrada_salida'),              '#hora_entrada_salida',             ],
            SOLO_CLIENTE:         [ MODAL_COMUNICADO.querySelector('input[name="SOLO_CLIENTE"]'),        'input[name="SOLO_CLIENTE"]',       ],
            RECONTRATABLE:        [ MODAL_COMUNICADO.querySelector('#recontratable'),                    '#recontratable',                   ],
            ESTADO_EMERGENCIA:    [ MODAL_COMUNICADO.querySelector('select[name="ESTADO_EMERGENCIA"]'),  'select[name="ESTADO_EMERGENCIA"]', ],
        });

        // Validar empleado seleccionado
        const OPCION_EMPLEADO  = MODAL_COMUNICADO.querySelector(
            `#empleadoslist option[value="${DATOS_COMUNICADO_GUARDAR.NOMBRE_EMPLEADO[2]}"]`
        );
        
        if (!OPCION_EMPLEADO) {
            return alerta.Warning('No se ha encontrado la información del colaborador que laborará en la lista de empleado, por favor verifica que el nombre está bien escrito o ponte en contacto con el administrador del sistema').show();
        }
        
        // Preparar datos del formulario
        const data = {
            nombre_sustituto:    DATOS_COMUNICADO_GUARDAR.NOMBRE_EMPLEADO[2],
            tipoempleado:        DATOS_COMUNICADO_GUARDAR.TIPO_EMPLEADO[2],
            cedula:              DATOS_COMUNICADO_GUARDAR.CEDULA_EMPLEADO[2],
            fecha_inicial:       moment(DATOS_COMUNICADO_GUARDAR.FECHA_RIGE[2], 'DD/MM/YYYY').format('YYYY-MM-DD'),
            fecha_final:         moment(DATOS_COMUNICADO_GUARDAR.FECHA_VENCE[2], 'DD/MM/YYYY').format('YYYY-MM-DD'),
            oficio_num:          DATOS_COMUNICADO_GUARDAR.OFICIO[2],
            fecha_envio:         DATOS_COMUNICADO_GUARDAR.FECHA_ENVIO_REGISTRO[2],
            observ_rrhh:         DATOS_COMUNICADO_GUARDAR.NOTAS_RH[2],
            limpieza_cajero:     DATOS_COMUNICADO_GUARDAR.CAJEROS[2],
            tiempo_alimento:     DATOS_COMUNICADO_GUARDAR.ALMUERZO[2],
            hora_entrada_salida: DATOS_COMUNICADO_GUARDAR.HORARIO_CUBRIR[2],
            solo_cliente:        DATOS_COMUNICADO_GUARDAR.SOLO_CLIENTE[2],
            ESTADO_EMERGENCIA:   DATOS_COMUNICADO_GUARDAR.ESTADO_EMERGENCIA[2],
            codigo_empleado2:    OPCION_EMPLEADO.getAttribute("data-empleado-codigo"),
            id_sustitucion
        };

        // Validar campo recontratable si está visible
        const recontratable = DATOS_COMUNICADO_GUARDAR.NOMBRE_EMPLEADO[0];
        const isRecontratableVisible = !recontratable.parentElement.classList.contains('d-none');
        
        if (isRecontratableVisible) {
            if (!DATOS_COMUNICADO_GUARDAR.NOMBRE_EMPLEADO[2]) {
                recontratable.focus();
                return alerta.Warning('Es requerido indicar si el colaborador puede o no ser recontratado').show();
            }
            data.recontratable = DATOS_COMUNICADO_GUARDAR.NOMBRE_EMPLEADO[2];
        }
        
        // Preparar FormData con archivos si existen
        const formData = new FormData();
        const files = MODAL_COMUNICADO.querySelector('input[name="comprobantes"]').files;

        if (files.length > 0) {
            if (!validar_cantidad_comprobantes(files.length)) { return; }            
            // Agregar archivos al FormData
            Array.from(files).forEach(file => formData.append('comprobantes[]', file));
        }
        
        // Agregar todos los datos al FormData
        Object.entries(data).forEach(([key, value]) => formData.append(key, value));
        
        const CORREOS_CLIENTES = MODAL_COMUNICADO.querySelector('#correos')?.textContent.trim();
        // Cerrar modal y enviar
        return Modal.Close(`#${MODAL_COMUNICADO.id}`).then(
            () => {
                const BOTON_SOLO_GUARDAR = MODAL_CORREOS.querySelector(`[data-correo-accion="SOG"]`).classList;
                (DATOS_COMUNICADO_GUARDAR.SOLO_CLIENTE[2] === 'S')
                    ? BOTON_SOLO_GUARDAR.add("d-none")
                    : BOTON_SOLO_GUARDAR.remove("d-none");
                return Modal.Open(`#${MODAL_CORREOS.id}`).then(
                    () => preparar_envio_comunicado(formData, CORREOS_CLIENTES)
                )
            }
        );
    }, validar_nombre_sustituto = (ev) => {
        const INPUT = ev.target;
        const NOMBRE = INPUT?.value.trim();

        MATCH = autocomplete.findMatch(
            NOMBRE, "EMPLEADOS"
        )?.match;

        if (false !== autocomplete.hasValidWordMatch(NOMBRE, MATCH)) {
            INPUT.value = MATCH;
            MODAL_COMUNICADO.querySelector(`#cedula`).textContent = MODAL_COMUNICADO.querySelector(
                `#empleadoslist option[value="${MATCH}"]`
            )?.getAttribute(`data-empleado-cedula`);
            return true;
        }
        return false;
    }, agregar_valores_guardar_comunicado = (data) => {
        Object.keys(data).forEach(key => {
            const elemento = data[key][0];
            const propiedad = CONFIGURACION_CAMPOS_COMUNICADO_GUARDAR[key];
            
            if (elemento && propiedad) {
                const valor = elemento[propiedad]?.trim() || '';
                data[key].push(valor);
            }
        });
        return data;
    }, preparar_envio_comunicado = (formData, CORREOS_CLIENTES) => {
        MODAL_CORREOS.querySelector(`#correos_clientes`).value = CORREOS_CLIENTES;
        $(MODAL_CORREOS.querySelectorAll(`[data-correo-accion]`)).off("click").on("click", (ev) => {
            const BOTON = ev.target.closest("button");
            if (BOTON.getAttribute("data-correo-accion") === "NAD") {
                return Modal.Close(`#${MODAL_CORREOS.id}`).then(
                    () => get_comunicado().finally(
                        () => Object.entries(DATOS_COMUNICADO_GUARDAR).forEach(
                            ([key, el]) => {
                                if (el[0]) return MODAL_COMUNICADO.querySelector(el[1])[CONFIGURACION_CAMPOS_COMUNICADO_GUARDAR[key]] = el[2];
                            }
                        )
                    )
                );
            }

            if (BOTON.getAttribute("data-correo-accion") !== "SOG") {
                const CORREO_UTILIZAR  = MODAL_CORREOS.querySelector("#correo_utilizar");
                const ASUNTO           = MODAL_CORREOS.querySelector('#asunto');
                const MENSAJE          = editor.getData();

                if (ASUNTO?.value.trim().length < 1) {
                    ASUNTO.focus();
                    return alerta.Warning(`¡Atención! El campo "ASUNTO" es requerido y no puede quedar en blanco`).show();
                }
                if (MENSAJE.length < 10) {
                    editor.editing.view.focus();
                    return alerta.Warning(`¡Atención! El mensaje es demasiado corto, por favor prolonga a un mínimo de 25 caracteres`).show();
                }
                if (MENSAJE.length < 1) {
                    MENSAJE.focus();
                    return alerta.Warning(`¡Atención! El campo "MENSAJE" es requerido y no puede quedar en blanco`).show();
                }
                if (CORREOS_CLIENTES.trim().length < 1) {
                    MODAL_CORREOS.querySelector('#correos_clientes').focus();
                    return alerta.Warning(`¡Atención! Es requerido indicar para quien va dirigido el mensaje`).show();
                }
            }

            formData.append(
                'enviar_correo',
                BOTON.getAttribute("data-correo-accion") === "SOG"
                    ? 'false'
                    : 'true'
            );
            return procesar_comunicado(formData);
        });
    }, procesar_comunicado = (formData) => {
        if (formData.get('enviar_correo') === 'true') {
            const ADJUNTOS = MODAL_CORREOS.querySelector(`[name="adjuntos[]"]`).files;
            for (let i = 0; i < ADJUNTOS.length; i++) {
                formData.append('adjuntos[]', ADJUNTOS[i])
            }
            formData.append(
                "correo_utilizar",
                MODAL_CORREOS.querySelector("#correo_utilizar")?.value.trim()
            );
            formData.append(
                "asunto",
                MODAL_CORREOS.querySelector('#asunto')?.value.trim()
            );
            formData.append(
                "mensaje",
                editor.getData().trim()
            );
            formData.append(
                "correos_clientes",
                MODAL_CORREOS.querySelector('#correos_clientes')?.value.trim()
            );
        }
        return Modal.Close(`#${MODAL_CORREOS.id}`).then(
            () => Modal.Open("#modalcargando").then(
                () => $.ajax({
                    "url": base_url("sustituciones/listado/rh/guardar"),
                    "method": "POST",
                    "data": formData,
                    "dataType": "json",
                    "contentType": false,
                    "processData": false,
                    "cache": false,
                    "beforeSend" : function () { ; }
                }).done(function( resp ) {
                    console.log(resp);
                    if (resp["TIPO"] === "SUCCESS") {
                        dataTable.ajax.reload(null, false);
                    }
                    alerta[capitalize(resp["TIPO"])](resp["MENSAJE"]).show();
                }).fail(function( err ) {
                    alerta.Danger('Se ha presentado un imprevisto al momento de procesar su solicitud :( Error: ' + err.responseText).show();
                    console.error(err);
                }).always((resp) => {
                    Modal.Close("#modalcargando").then(
                        () => {
                            if (resp["TIPO"] && resp["TIPO"] !== "SUCCESS") {
                                return get_comunicado().finally(
                                    () => Object.entries(DATOS_COMUNICADO_GUARDAR).forEach(
                                        ([key, el]) => {
                                            if (el[0]) return MODAL_COMUNICADO.querySelector(el[1])[CONFIGURACION_CAMPOS_COMUNICADO_GUARDAR[key]] = el[2];
                                        }
                                    )
                                );
                            }
                            DATOS_COMUNICADO_GUARDAR = {};
                        }
                    );
                })
            )
        );
    }, descargar_comunicado =  (ev) => {
        if (MODAL_COMUNICADO.querySelector('#hora_entrada_salida').value.trim().length == 0) {
            return alerta.Warning('No se ha especificado el horario a cubrir').show();
        }
        return print_to_pdf(
            base_url("sustituciones/listado/rh/descargar"),
            {
                ID_SUSTITUCION:      id_sustitucion,
                FECHA_COMUNICADO:    MODAL_COMUNICADO.querySelector(`#fecha_envio_registro`)?.textContent.trim(),
                NUMERO_OFICIO:       MODAL_COMUNICADO.querySelector(`#oficio_num`)?.value.trim(),
                HONORIFICO:          MODAL_COMUNICADO.querySelector(`#honorifico`)?.textContent.trim(),
                CONTACTO:            MODAL_COMUNICADO.querySelector(`#contacto`)?.textContent.trim(),
                PUESTO:              MODAL_COMUNICADO.querySelector(`#puesto`)?.textContent.trim(),
                CLIENTE:             MODAL_COMUNICADO.querySelector(`#cliente`)?.textContent.trim(),
                SALUDO:              MODAL_COMUNICADO.querySelector(`#saludo`)?.textContent.trim(),
                NOMBRE_SUSTITUTO:    MODAL_COMUNICADO.querySelector(`#nombre_empleado`)?.value.trim(),
                CEDULA_SUSTITUTO:    MODAL_COMUNICADO.querySelector(`#cedula`)?.textContent.trim(),
                NOMBRE_AUSENTE:      MODAL_COMUNICADO.querySelector(`#nombre_fijo`)?.textContent.trim(),
                UBICACION:           MODAL_COMUNICADO.querySelector(`#Lugar`)?.textContent.trim(),
                MOTIVO_AUSENCIA:     MODAL_COMUNICADO.querySelector(`#motivo`)?.textContent.trim(),
                FECHA_RIGE:          MODAL_COMUNICADO.querySelector(`#fecha_inicial`)?.textContent.trim(),
                FECHA_FINAL:         MODAL_COMUNICADO.querySelector(`#fecha_final`)?.textContent.trim(),
                HORARIO_REGULAR:     MODAL_COMUNICADO.querySelector(`#horario_regular`)?.textContent.trim(),
                HORARIO_CUBRIR:      MODAL_COMUNICADO.querySelector(`#hora_entrada_salida`)?.value.trim(),
                TIEMPO_ALIMENTACION: MODAL_COMUNICADO.querySelector(`#tiempo_alimento`)?.value.trim(),
                LIMPIEZA_CAJERO:     MODAL_COMUNICADO.querySelector(`#limpieza_cajero`)?.textContent.trim(),
                CORREOS:             MODAL_COMUNICADO.querySelector(`#correos`)?.textContent.trim(),
                FIRMA_USUARIO:       MODAL_COMUNICADO.querySelector(`#firma`).getAttribute('src'),
                NOMBRE_USUARIO:      MODAL_COMUNICADO.querySelector(`#rrhh_procesado_por`)?.textContent.trim(),
                CORREO_USUARIO:      MODAL_COMUNICADO.querySelector(`#correo-usuario`)?.textContent.trim(),
            },
            "POST"
        );
    }, descargar_adjuntos_comunicado = (ev) => {
        const BOTON = ev.target.closest("button");
        BOTON.disabled = true;

        const obtener_lista_adjuntos = () => $.ajax({
            "url": base_url("sustituciones/listado/rh/descargar_adjuntos"),
            "method": "GET",
            "data": { ID_SUSTITUCION: BOTON.getAttribute("data-comunicado-idsustitucion") },
            "dataType": "json",
            "beforeSend" : function () { ; }
        }).done(function( resp ) {
            console.log(resp);
            enviar_descarga_adjuntos(resp);
        }).fail(function( err ) {
            alerta.Danger('Se ha presentado un imprevisto al momento de procesar su solicitud :( Error: ' + err.responseText).show();
            console.error(err);
        }).always((resp) => {
            BOTON.disabled = false;
        });

        if (Modal.IsShow(`#${MODAL_COMUNICADO.id}`)) {
            DATOS_COMUNICADO_GUARDAR = agregar_valores_guardar_comunicado({
                NOMBRE_EMPLEADO:      [ MODAL_COMUNICADO.querySelector('#nombre_empleado'), '#nombre_empleado', ],
                TIPO_EMPLEADO:        [ MODAL_COMUNICADO.querySelector('select[name="tipoempleado"]'), 'select[name="tipoempleado"]', ],
                CEDULA_EMPLEADO:      [ MODAL_COMUNICADO.querySelector('#cedula'), '#cedula', ],
                FECHA_RIGE:           [ MODAL_COMUNICADO.querySelector('#fecha_inicial'), '#fecha_inicial', ],
                FECHA_VENCE:          [ MODAL_COMUNICADO.querySelector('#fecha_final'), '#fecha_final', ],
                OFICIO:               [ MODAL_COMUNICADO.querySelector('#oficio_num'), '#oficio_num', ],
                FECHA_ENVIO_REGISTRO: [ MODAL_COMUNICADO.querySelector('#fecha_envio_registro'), '#fecha_envio_registro', ],
                NOTAS_RH:             [ MODAL_COMUNICADO.querySelector('#observ_rrhh'), '#observ_rrhh', ],
                CAJEROS:              [ MODAL_COMUNICADO.querySelector('#limpieza_cajero'), '#limpieza_cajero', ],
                ALMUERZO:             [ MODAL_COMUNICADO.querySelector('#tiempo_alimento'), '#tiempo_alimento', ],
                HORARIO_CUBRIR:       [ MODAL_COMUNICADO.querySelector('#hora_entrada_salida'), '#hora_entrada_salida', ],
                SOLO_CLIENTE:         [ MODAL_COMUNICADO.querySelector('[name="SOLO_CLIENTE"]'), '[name="SOLO_CLIENTE"]', ],
                RECONTRATABLE:        [ MODAL_COMUNICADO.querySelector('#recontratable'), '#recontratable', ],
            });
            return Modal.Close(`#${MODAL_COMUNICADO.id}`).then(
                () => Modal.Open("#modalcargando").then(
                    () => obtener_lista_adjuntos().always(
                        () => Modal.Close("#modalcargando").then(
                            () => get_comunicado().finally(
                                () => Object.entries(DATOS_COMUNICADO_GUARDAR).forEach(
                                    ([key, el]) => {
                                        if (el[0]) return MODAL_COMUNICADO.querySelector(el[1])[CONFIGURACION_CAMPOS_COMUNICADO_GUARDAR[key]] = el[2];
                                    }
                                )
                            )
                        )
                    )
                )
            );
        }
        return Modal.Open("#modalcargando").then(
            () => obtener_lista_adjuntos().always(
                () => Modal.Close("#modalcargando")
            )
        );
    }, enviar_descarga_adjuntos = async (files, batchSize = 3) => {
        /**
         * Descarga múltiples archivos desde tu array { FILE, NAME }
         * Procesa archivos en lotes para evitar saturar el navegador
         * @param {Array} files - Array con objetos { FILE: 'base64', NAME: 'filename.ext' }
         */

        if (!validarArchivos(files)) { return alerta.Danger("Uno o más de los archivos que intentas descargar están corruptos").show(); }
        
        const cleanBase64 = (base64String) => {
            // Remover cualquier prefijo data URL si existe
            let cleaned = base64String;
            if (base64String.includes(',')) {
                cleaned = base64String.split(',')[1];
            }
            
            // Remover espacios en blanco, saltos de línea, etc.
            cleaned = cleaned.replace(/\s+/g, '');
            
            // Validar que solo contenga caracteres base64 válidos
            const base64Regex = /^[A-Za-z0-9+/]*={0,2}$/;
            if (!base64Regex.test(cleaned)) {
                throw new Error('Cadena base64 contiene caracteres inválidos');
            }
            
            return cleaned;
        };

        const downloadFile = (file) => {
            return new Promise((resolve, reject) => {
                try {
                    // Validar entrada
                    if (!file.FILE || !file.NAME) {
                        throw new Error('Archivo debe tener propiedades FILE y NAME');
                    }

                    const ext = file.NAME.split('.').pop().toLowerCase();
                    const mimeTypes = {
                        'pdf': 'application/pdf',
                        'png': 'image/png', 
                        'jpg': 'image/jpeg', 
                        'jpeg': 'image/jpeg', 
                        'gif': 'image/gif',
                        'webp': 'image/webp',
                        'svg': 'image/svg+xml',
                        'txt': 'text/plain', 
                        'json': 'application/json',
                        'doc': 'application/msword', 
                        'docx': 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                        'xls': 'application/vnd.ms-excel', 
                        'xlsx': 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                        'zip': 'application/zip',
                        'rar': 'application/x-rar-compressed'
                    };
                    const mimeType = mimeTypes[ext] || 'application/octet-stream';
                    
                    // Limpiar y validar base64
                    const cleanedBase64 = cleanBase64(file.FILE);
                    
                    // Decodificar base64
                    const byteCharacters = atob(cleanedBase64);
                    const bytes = new Uint8Array(byteCharacters.length);
                    
                    for (let i = 0; i < byteCharacters.length; i++) {
                        bytes[i] = byteCharacters.charCodeAt(i);
                    }
                    
                    const blob = new Blob([bytes], { type: mimeType });
                    
                    // Crear y ejecutar descarga
                    const url = URL.createObjectURL(blob);
                    const a = document.createElement('a');
                    a.href = url;
                    a.download = file.NAME;
                    
                    // Agregar al DOM temporalmente para algunos navegadores
                    document.body.appendChild(a);
                    a.click();
                    document.body.removeChild(a);
                    
                    // Liberar memoria
                    URL.revokeObjectURL(url);
                    
                    resolve(`✅ Descargado: ${file.NAME} (${(blob.size / 1024).toFixed(2)} KB)`);
                    
                } catch (error) {
                    console.error(`Error procesando ${file.NAME}:`, error);
                    reject(`❌ Error descargando ${file.NAME}: ${error.message}`);
                }
            });
        };

        console.log(`🚀 Iniciando descarga de ${files.length} archivos en lotes de ${batchSize}`);
        
        // Procesar archivos en lotes
        for (let i = 0; i < files.length; i += batchSize) {
            const batch = files.slice(i, i + batchSize);
            const batchNumber = Math.floor(i/batchSize) + 1;
            
            console.log(`📦 Procesando lote ${batchNumber} (${batch.length} archivos)...`);
            
            const batchPromises = batch.map(downloadFile);
            
            try {
                const results = await Promise.all(batchPromises);
                console.log(`✅ Lote ${batchNumber} completado:`);
                results.forEach(result => console.log(`  ${result}`));
                
                // Delay entre lotes para no saturar el navegador
                if (i + batchSize < files.length) {
                    console.log(`⏳ Esperando 500ms antes del siguiente lote...`);
                    await new Promise(resolve => setTimeout(resolve, 500));
                }
                
            } catch (error) {
                console.error(`❌ Error en lote ${batchNumber}:`, error);
                // Continuar con los siguientes lotes aunque uno falle
            }
        }
        
        console.log(`🎉 Proceso de descarga completado`);
    }, validarArchivos = (files) => {
        // Función auxiliar para validar archivos antes de descargar

        const errores = [];
        
        files.forEach((file, index) => {
            if (!file.FILE) {
                errores.push(`Archivo ${index + 1}: Falta propiedad FILE`);
            }
            if (!file.NAME) {
                errores.push(`Archivo ${index + 1}: Falta propiedad NAME`);
            }
            if (file.FILE && typeof file.FILE !== 'string') {
                errores.push(`Archivo ${index + 1}: FILE debe ser una cadena`);
            }
        });
        
        if (errores.length > 0) {
            console.error('❌ Errores de validación:');
            errores.forEach(error => console.error(`  ${error}`));
            return false;
        }
        
        return true;
    }, guardar_nota_adicional = (ev) => {
        const INPUT_NOTA = MODAL_COMUNICADO.querySelector(`#notas_adicionales`);
        const INPUT_ESTADO_EMERGENCIA = MODAL_COMUNICADO.querySelector(`#notas_adicionales`);
        const BOTON = ev.target.closest("button");
        if (INPUT_NOTA?.value.trim().length == 0) {
            INPUT_NOTA.focus();
            return alerta.Info('La nota debe contener un mínimo de 10 caracteres').show();
        }
        BOTON.disabled = true;

        let formData = new FormData();

        formData.append("id_sustitucion", id_sustitucion);
        formData.append("nota_adicional", INPUT_NOTA?.value.trim());
        formData.append("ESTADO_EMERGENCIA", INPUT_ESTADO_EMERGENCIA?.value.trim());

        const ADJUNTOS = MODAL_COMUNICADO.querySelector('input[name="comprobantes"]').files;
        if (ADJUNTOS.length > 0) {
            if (!validar_cantidad_comprobantes(ADJUNTOS.length)) { return; }            
            // Agregar archivos al FormData
            Array.from(ADJUNTOS).forEach(file => formData.append('comprobantes[]', file));
        }
        
        return Modal.Close(`#${MODAL_COMUNICADO.id}`).then(
            () => Modal.Open("#modalcargando").then(
                () => {
                    return $.ajax({
                        "url": base_url("sustituciones/listado/rh/guardar_nota_adicional"),
                        "method": "POST",
                        "data": formData,
                        "dataType": "json",
                        "contentType": false,
                        "processData": false,
                        "cache": false,
                        "beforeSend" : function () { ; }
                    }).done(function( resp ) {
                        alerta[capitalize(resp["TIPO"])](resp["MENSAJE"]).show();
                        return Modal.Close("#modalcargando");
                    }).fail(function( err ) {
                        return Modal.Close("#modalcargando").then(
                            () => get_comunicado().finally(
                                () => {
                                    MODAL_COMUNICADO.querySelector(`#notas_adicionales`).value = formData.get("nota_adicional");
                                }
                            )
                        );
                        alerta.Danger('Se ha presentado un imprevisto al momento de procesar su solicitud :( Error: ' + err.responseText).show();
                        console.error(err);
                    }).always((resp) => {
                        BOTON.disabled = true;
                    });
                }
            )
        );
    }, render_emergencias = () => {
        if (dataTableEmergencias) {
            return dataTableEmergencias.ajax.reload(null, false);
        }
        dataTableEmergencias = new DataTable(TABLA_EMERGENCIAS, {
            dom: `
                <'dt-layout-row'<'dt-layout-cell dt-layout-start' B><'dt-layout-cell dt-layout-end' f>>
                <'dt-layout-row dt-layout-table' <'dt-layout-cell  dt-layout-full' t>>
                <'dt-layout-row'<'dt-layout-cell dt-layout-start' i><'dt-layout-cell dt-layout-end' p>>
            `,
            bLengthChange: true,
            pageLength: 25,
            buttons: [
                {
                    'extend': 'excelHtml5',
                    'title': 'Listado de Sustituciones',
                    'exportOptions': {
                        'columns': [1,2,3,4,5,6,7,8,9,16,17,18,19],
                        'format': {
                            'body': (data, row, column, node) => {
                                if (!node) { return ""; }
                                return (
                                    node.innerText || node.textContent
                                ).trim();
                            },
                        },
                    },
                    'titleAttr': 'Excel',
                    'text': '<i class="far fa-2x fa-file-excel"></i>',
                },
                {
                    'extend': 'copyHtml5',
                    'title': 'Listado de Sustituciones',
                    'exportOptions': {
                        'columns': [1,2,3,4,5,6,7,8,9,16,17,18,19],
                        'format': {
                            'body': (data, row, column, node) => {
                                if (!node) { return ""; }
                                return (
                                    node.innerText || node.textContent
                                ).trim();
                            },
                        },
                    },
                    'titleAttr': 'Copiar',
                    'text': '<i class="far fa-2x fa-copy"></i>',
                },
            ],
            ajax: {
                url: base_url("sustituciones/listado/rh/obtener_emergencias"),
                method: "GET",
                responseType: "json",
                data: function (p) {
                    p.FECHA_SOLICITUD = MODAL_EMERGENCIAS.querySelector(`[data-filtro-emergencias-fechasolicitud]`)?.value.trim();
                    p.ESTADO          = MODAL_EMERGENCIAS.querySelector(`[data-filtro-emergencias-estado]`)?.value.trim();
                },
                beforeSend: function() {
                    dataTableEmergencias.clear().draw();
                },
                error: function (resp) {
                    const MENSAJE = resp["responseJSON"] && resp["responseJSON"]["MENSAJE"];
                    console.log(resp);
                    setTimeout(
                        () => Modal.Close(`#${MODAL_EMERGENCIAS.id}`).then(
                            () => confirmar.Danger(`No se han podido cargar los datos debido a un imprevisto. Error: ${ MENSAJE || resp["responseText"] }`, null, true)
                        ),
                        1000
                    );
                    dataTable.processing(false);
                    dataTable.clear().draw();
                },
            },
            scrollX: true,
            bAutoWidth: false,
            processing: true,
            // order: [[0, "desc"], [2, "asc"]],
            columns: [
                {
                    "targets": 0,
                    "class": "fs-5 p-1",
                    "orderable": false,
                    "data": "FECHA_SOLICITUD",
                    'render': function(data, type, row, meta) {
                        if (data === null || data.includes('0000-00-00')) { return `---`; }
                        return `
                            <span class="d-none">${moment(data).format("YYYYMMDDHHmmss")}</span>
                            ${moment(data).format("DD/MM/YYYY HH:mm:ss")}
                        `;
                    }
                },
                {
                    "targets": 1,
                    "class": "fs-5 p-1",
                    "orderable": false,
                    "data": "USUARIO_GESTOR",
                    'render': function(data, type, row, meta) {
                        return data;
                    }
                },
                {
                    "targets": 2,
                    "class": "fs-5 p-1",
                    "orderable": false,
                    "data": "OFICIO",
                    'render': function(data, type, row, meta) {
                        return data;
                    }
                },
                {
                    "targets": 3,
                    "class": "fs-5 p-1",
                    "orderable": false,
                    "data": "UBICACION",
                    'render': function(data, type, row, meta) {
                        return data;
                    }
                },
                {
                    "targets": 4,
                    "class": "fs-5 p-1",
                    "orderable": false,
                    "data": "AUSENTE",
                    'render': function(data, type, row, meta) {
                        return data;
                    }
                },
                {
                    "targets": 5,
                    "class": "fs-5 p-1",
                    "orderable": false,
                    "data": "SUSTITUTO",
                    'render': function(data, type, row, meta) {
                        return data;
                    }
                },
                {
                    "targets": 6,
                    "class": "fs-5 p-1",
                    "orderable": false,
                    "data": "MOTIVO",
                    'render': function(data, type, row, meta) {
                        return data;
                    }
                },
                {
                    "targets": 7,
                    "class": "fs-5 p-1",
                    "orderable": false,
                    "data": "HORARIO",
                    'render': function(data, type, row, meta) {
                        return data;
                    }
                },
                {
                    "targets": 8,
                    "class": "fs-5 p-1",
                    "orderable": false,
                    "data": "NOTAS",
                    'render': function(data, type, row, meta) {
                        return data;
                    }
                },
                {
                    "targets": 9,
                    "class": "fs-5 p-1 fw-bold",
                    "orderable": false,
                    "data": "STATE",
                    'render': function(data, type, row, meta) {
                        return data;
                    }
                },
            ],
            language: {
                url: base_url(`public/dist/datatables/language_esp.json`),
            },
            "createdRow": function (row, data, dataIndex) {
                switch (data["ESTADO"]) {
                    case "RT":
                        row.classList.add("table-warning");
                    break;
                    case "SC":
                        row.classList.add("table-danger");
                    break;
                    case "RS":
                        row.classList.add("table-success");
                    break;
                }
            },
            initComplete: function() {
                function handleFilterEvent(event) {
                    const target = event.target;
                    if (!target.matches("[data-col-dt]")) { return }
                    const columnIndex = target.getAttribute("data-col-dt");
                    const value = target.value.trim();
                    dataTableEmergencias.column(columnIndex).search(value).draw();
                }
                MODAL_EMERGENCIAS.querySelector(`[data-app-filtros-emergencias]`).addEventListener("input", handleFilterEvent);
                MODAL_EMERGENCIAS.querySelector(`[data-app-filtros-emergencias]`).addEventListener("change", handleFilterEvent);

                MODAL_EMERGENCIAS.querySelector(`[data-filtro-emergencias-buscar]`).addEventListener(
                    "click", () => dataTableEmergencias.ajax.reload(null, false)
                )
            },
            drawCallback: function () {
            },
        });
    };

    const dataTable = new DataTable(TABLA_COMUNICADOS, {
        dom: `
            <'dt-layout-row'<'dt-layout-cell dt-layout-start' B><'dt-layout-cell dt-layout-end' f>>
            <'dt-layout-row dt-layout-table' <'dt-layout-cell  dt-layout-full' t>>
            <'dt-layout-row'<'dt-layout-cell dt-layout-start' i><'dt-layout-cell dt-layout-end' p>>
        `,
        bLengthChange: true,
        pageLength: 25,
        buttons: [
            {
                'extend': 'excelHtml5',
                'title': 'Listado de Sustituciones',
                'exportOptions': {
                    'columns': [1,2,3,4,5,6,7,8,9,16,17,18,19],
                    'format': {
                        'body': (data, row, column, node) => {
                            if (!node) { return ""; }
                            return (
                                node.innerText || node.textContent
                            ).trim();
                        },
                    },
                },
                'titleAttr': 'Excel',
                'text': '<i class="far fa-2x fa-file-excel"></i>',
            },
            {
                'extend': 'copyHtml5',
                'title': 'Listado de Sustituciones',
                'exportOptions': {
                    'columns': [1,2,3,4,5,6,7,8,9,16,17,18,19],
                    'format': {
                        'body': (data, row, column, node) => {
                            if (!node) { return ""; }
                            return (
                                node.innerText || node.textContent
                            ).trim();
                        },
                    },
                },
                'titleAttr': 'Copiar',
                'text': '<i class="far fa-2x fa-copy"></i>',
            },
        ],
        ajax: {
            url: base_url("sustituciones/listado/rh/obtener"),
            method: "GET",
            responseType: "json",
            data: function (p) {
                p.ESTADO = 	   FILTROS_CONTAINER.querySelector(`[data-filtro-estado]`)?.value.trim();
                p.MOTIVO = 	   FILTROS_CONTAINER.querySelector(`[data-filtro-motivo]`)?.value.trim();
                p.AUSENTE =    FILTROS_CONTAINER.querySelector(`[data-filtro-ausente]`)?.value.trim();
                p.SUSTITUTO =  FILTROS_CONTAINER.querySelector(`[data-filtro-sustituto]`)?.value.trim();
                p.FECHA_RIGE = FILTROS_CONTAINER.querySelector(`[data-filtro-fecharige]`)?.value.trim();
            },
            beforeSend: function() {
                Modal.Open("#modalcargando");
            },
            error: function (resp) {
	            const MENSAJE = resp["responseJSON"] && resp["responseJSON"]["MENSAJE"];
	            console.log(resp);
	            setTimeout(
	            	() => Modal.Close(`#modalcargando`).then(
	            		() => confirmar.Danger(`No se han podido cargar los datos debido a un imprevisto. Error: ${ MENSAJE || resp["responseText"] }`, null, true)
	            	),
	            	1000
	            );
            	dataTable.processing(false);
            	dataTable.clear().draw();
            },
        },
        scrollX: true,
        bAutoWidth: false,
        processing: true,
        order: [[0, "desc"], [2, "asc"]],
        columns: [
            {
                "targets": 0,
                "class": "fs-5 p-1",
                "orderable": false,
                "data": "ID_SUSTITUCION",
                'render': function(data, type, row, meta) {
                    let adjuntos = "";
                    let acciones = `
                        <button
                        	type="button"
                        	class="btn btn-sm btn-${row["ESTADO_RH"] == 2 ? "warning" : "info"} m-1 ver-comunicado"
                        	data-id="${data}"
                        >
                            <i
                            	class="fa-solid fa-eye"
                            	data-toggle="tooltip"
                            	title="Ver sustitución"
                            ></i>
                        </button>
                    `;
                    if (row["ADJUNTOS"]) {
                        ADJUNTOS = row["ADJUNTOS"].split("\\").filter((item) => item.trim().length).join("");
                        if (JSON.parse(ADJUNTOS).length) {
                            acciones += `
                                <button
                                	type="button"
                                	class="btn btn-sm btn-primary m-1 download-resources"
                                	data-comunicado-idsustitucion="${data}"
                                >
                                    <i
                                    	class="fa-solid fa-file-download"
                                    	data-toggle="tooltip"
                                    	title="Descargar recursos"
                                    ></i>
                                </button>
                            `;
                        }
                    }
                    return `
                        <div class="d-flex">
                        	${acciones}
                        </div>
                        <span class="d-none">
                    		${row["ESTADO_RH"]}
                        	${adjuntos}
                        </span>
                    `;
                }
            },
            {
                "targets": 1,
                "class": "fs-5 p-1",
                "data": "FECHA_SOLICITUD",
                'render': function(data, type, row, meta) {
                    if (data === null || data.includes('0000-00-00')) { return `---`; }
                    return `
                        <span class="d-none">${moment(data).format("YYYYMMDDHHmmss")}</span>
                        ${moment(data).format("DD/MM/YYYY HH:mm:ss")}
                    `;
                }
            },
            {
                "targets": 2,
                "class": "fs-5 p-1",
                "orderable": false,
                "data": "USUARIO_SOLICITUD",
                'render': function(data, type, row, meta) {
                    return data;
                }
            },
            {
                "targets": 3,
                "class": "fs-5 p-1",
                "data": "OFICIO",
                'render': function(data, type, row, meta) {
                    return data.length !== 0
                    	? data
                    	: "Sin Definir";
                }
            },
            {
                "targets": 4,
                "class": "fs-5 p-1",
                "orderable": true,
                "data": "AUSENTE",
                'render': function(data, type, row, meta) {
                    return data;
                }
            },
            {
                "targets": 5,
                "class": "fs-5 p-1",
                "orderable": true,
                "data": "UBICACION",
                'render': function(data, type, row, meta) {
                    return data;
                }
            },
            {
                "targets": 6,
                "class": "fs-5 p-1",
                "orderable": true,
                "data": "SUSTITUTO",
                'render': function(data, type, row, meta) {
                    return data;
                }
            },
            {
                "targets": 7,
                "class": "fs-5 p-1",
                "data": "FECHA_RIGE",
                'render': function(data, type, row, meta) {
                    if (data === null || data.includes('0000-00-00')) { return `---`; }
                    return `
                        <span class="d-none">${moment(data).format("YYYYMMDD")}</span>
                        ${moment(data).format("DD/MM/YYYY")}
                    `;
                }
            },
            {
                "targets": 8,
                "class": "fs-5 p-1",
                "data": "FECHA_VENCE",
                'render': function(data, type, row, meta) {
                    if (data === null || data === "Indefinido") { return `Indefinido`; }
                    return `
                        <span class="d-none">${moment(data).format("YYYYMMDD")}</span>
                        ${moment(data).format("DD/MM/YYYY")}
                    `;
                }
            },
            {
                "targets": 9,
                "class": "fs-5 p-1",
                "data": "MOTIVO",
                'render': function(data, type, row, meta) {
                    return data;
                }
            },
            {
                "targets": 10,
                "class": "fs-5 p-1",
                "orderable": false,
                "data": "FECHA_GESTION_RH",
                'render': function(data, type, row, meta) {
                    return data === null || data.includes('0000-00-00')
                    	? "---"
                    	: moment(data).format("DD/MM/YYYY HH:mm:ss");
                }
            },
            {
                "targets": 11,
                "class": "fs-5 p-1",
                "orderable": false,
                "data": "USUARIO_GESTION_RH",
                'render': function(data, type, row, meta) {
                    return data == null
                    	? 'Gestor sin definir'
                    	: data;
                }
            },
            {
                "targets": 12,
                "class": "fs-5 p-1",
                "orderable": false,
                "data": "FECHA_GESTION_NOMINA",
                'render': function(data, type, row, meta) {
                    return data === null || data.includes('0000-00-00')
                    	? "---"
                    	: moment(data).format("DD/MM/YYYY HH:mm:ss");
                }
            },
            {
                "targets": 13,
                "class": "fs-5 p-1",
                "orderable": false,
                "data": "USUARIO_GESTION_NOMINA",
                'render': function(data, type, row, meta) {
                    return data == null
                    	? '---'
                    	: data;
                }
            },
            {
                "targets": 14,
                "class": "fs-5 p-1",
                "orderable": false,
                "data": "USUARIO_ANULADO",
                "visible": false,
                'render': function(data, type, row, meta) {
                    return data == null
                    	? '---'
                    	: data;
                }
            },
            {
                "targets": 15,
                "class": "fs-5 p-1",
                "orderable": false,
                "data": "FECHA_ANULADO",
                "visible": false,
                'render': function(data, type, row, meta) {
                    return data === null || data.includes('0000-00-00')
                    	? "---"
                    	: moment(data).format("DD/MM/YYYY HH:mm:ss");
                }
            },
            {
                "visible": false,
                "targets": 16,
                "class": "fs-5 p-1",
                "data": "ADJUNTOS",
                "render": function (data, type, row, meta) {
                    return data != null
                    	? 'Si posee'
                    	: 'No posee';
                },
            },
            {
                "visible": false,
                "targets": 17,
                "class": "fs-5 p-1",
                "data": "NOTAS_RH",
                "render": function (data, type, row, meta) {
                    return data;
                },
            },
            {
                "visible": false,
                "targets": 18,
                "class": "fs-5 p-1",
                "data": "NOTAS_SOLICITANTE",
                "render": function (data, type, row, meta) {
                    return data;
                },
            },
            {
                "visible": false,
                "targets": 19,
                "class": "fs-5 p-1",
                "data": "HORARIO_CUBRIR",
                "render": function (data, type, row, meta) {
                    return data;
                },
            },
        ],
        language: {
            url: base_url(`public/dist/datatables/language_esp.json`),
        },
        initComplete: function() {
            function handleFilterEvent(event) {
                const target = event.target;
                if (!target.matches("[data-col-dt]")) { return }
                const columnIndex = target.getAttribute("data-col-dt");
                const value = target.value.trim();
                dataTable.column(columnIndex).search(value).draw();
            }
            FILTROS_CONTAINER.addEventListener("input", handleFilterEvent);
            FILTROS_CONTAINER.addEventListener("change", handleFilterEvent);
        },
        drawCallback: function () {
            setTimeout(() => Modal.Close(`#modalcargando`), 1000);
            toggle_columnas(
            	this.api(),
            	FILTROS_CONTAINER.querySelector('[data-filtro-estado]')?.value
            );

            $(TABLA_COMUNICADOS).off(
                'click', '.ver-comunicado'
            ).on(
                'click', '.ver-comunicado', function (e) {
                    id_sustitucion = $(this).data('id')
                    get_comunicado();
                }
            ).off(
                'click', '.download-resources'
            ).on(
                'click', '.download-resources', descargar_adjuntos_comunicado
            );
        },
    });
    
    const autocomplete = new SmartAutocomplete({ "debug": true, "minScore": 25, });
    autocomplete
        .registerDataSource(
            'EMPLEADOS',
            OPCIONES_EMPLEADOS.map((o) => o.value.trim())
        );

    window.addEventListener("load", () => {
        init_easepicker();
        init_editor();

        FILTROS_CONTAINER.querySelector(`[data-filtro-buscar]`).addEventListener(
            "click", () => dataTable.ajax.reload(null, false)
        );

        MODAL_COMUNICADO.querySelector(`[data-comunicado-anular]`).addEventListener(
        	"click", anular_comunicado
        );

        MODAL_COMUNICADO.querySelector(`#sendForm`).addEventListener(
            "click", validar_datos_comunicado
        );

        MODAL_COMUNICADO.querySelector(`#descargaComunicado`).addEventListener(
            "click", descargar_comunicado
        );

        return $(document).on(
            'show.bs.modal', `#${MODAL_EMERGENCIAS.id}`, render_emergencias
        ).on(
            'show.bs.modal', `#${MODAL_COMUNICADO.id}`, function (e) {
                MODAL_COMUNICADO.querySelector('#sendForm').disabled =  false;
                MODAL_COMUNICADO.querySelector('#anular-comunicado').disabled = false;
            }
        ).on(
            'hidden.bs.modal', `#${MODAL_COMUNICADO.id}`, function (e) {
                MODAL_COMUNICADO.querySelector('.modal-body').innerHTML = "";
            }
        ).on(
            'click', '#GuardarNotaAdicional', guardar_nota_adicional
        ).on(
            'click', '#anular-comunicado', function (e) {
                const NOTA = prompt("¿Por qué deseas anular este registro?");
                if (NOTA.trim().length === 0) { return alerta.Warning("Es requerido indicar el motivo para poder anular los comunicados").show(); }
                return Modal.Close(`#${MODAL_COMUNICADO.id}`).then(
                    () => $.ajax({
                        "url": base_url("sustituciones/listado/rh/anular"),
                        "method": "DELETE",
                        "data": {
                            NOTA: NOTA.trim(),
                            ID_SUSTITUCION: id_sustitucion,
                        },
                        "dataType": "json",
                        "beforeSend" : function () {
                        }
                    }).done(function( res ) {
                        $('#sendForm').attr('disabled', false)
                        dataTable.ajax.reload(null, false);
                        return alerta(res.type, res.message, res.title)
                    }).fail(function( err ) {
                        alerta('error', 'Se ha presentado un imprevisto al momento de procesar su solicitud :(', '')
                        console.error(err);
                    })
                );
            }
        );
    });
})();