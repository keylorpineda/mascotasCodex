const MODAL_COMUNICADO = select("#plantillaModal"),
  MODAL_CORREO_COMUNICADO = select("#correoSustitucionModal"),
  GUARDAR_COMUNICADO = select("#GUARDAR-COMUNICADO");
const COLABORADORES = {
    "F": [],
    "S": [],
}
let dataTable, editor, sustitucion;

const get_sustitucion = () => {
    return $.ajax({
        "url": base_url(`sustituciones/obtener_detalle`), // "http://localhost/Go/Controllers/SustitucionesController",
        "method": "GET",
        "data": {
            id_sustitucion: select("[data-app-control-sustitucion-abierta]")?.value,
        },
        "dataType": "json",
    }).done(function(sustituciones) {
        MODAL_COMUNICADO.querySelector(".modal-body").innerHTML = select("#comunicado").innerHTML;
        const OPCIONES = COLABORADORES[(sustituciones.tipoempleado || "F")].map(empleado => `<option value="${empleado.NOMBRE}" data-id="${empleado.EMPLEADO}" data-cedula="${empleado.IDENTIFICACION}">${empleado.NOMBRE}</option>`)
        select(`#plantillaModal [name="tipoempleado"]`).value = sustituciones.tipoempleado;
        OPCIONES.unshift(
            `<option value="" data-id="" data-cedula="---">Seleccione...</option>`,
            `<option value="PENDIENTE" data-id="PENDIENTE" data-cedula="PENDIENTE">PENDIENTE</option>`,
            `<option value="NO APLICA" data-id="NO APLICA" data-cedula="NO APLICA">NO APLICA</option>`,
        );
        return select(`#nombre_sustituto`).innerHTML = OPCIONES.join("");
    }).done(function (resp) {
        const nombre_ausente = select('#nombre_ausente').classList;
        const t_nombre_ausente = select('#t_nombre_ausente').classList;
        if (resp.tipoSustitucion === 'RLE') {
            nombre_ausente.remove("d-block");
            t_nombre_ausente.remove("d-block");
            nombre_ausente.add("d-none");
            t_nombre_ausente.add("d-none");
        } else {
            nombre_ausente.add("d-block");
            t_nombre_ausente.add("d-block");
            nombre_ausente.remove("d-none");
            t_nombre_ausente.remove("d-none");
        }
        const recontratable = select("#recontratable");
        if (["Renuncia", "Despido"].includes(resp.motivo)) {
            recontratable.required = true;
            recontratable.parentNode.classList.remove("d-none");
        } else {
            recontratable.required = false;
            recontratable.parentNode.classList.add("d-none");
        }
        if ([ "PRN", "RNM" ].includes(resp.estado) || resp.nota_adicional != null) {
            let div = document.createElement('div');
            div.classList.add("col-md-12");
            div.innerHTML = `
                <label class="w-100">
                    Nota adicional para planilla
                    <textarea id="nota_adicional" class="form-control mb-1" placeholder="Nota adicional para planilla..." rows="3" minlength="25"></textarea>
                </label>
                <button type="button" class="btn btn-success w-100" data-toggle="tooltip" id="GuardarNotaAdicional" title="Guardar nota">
                    <i class="fas fa-floppy-disk"></i>
                    Guardar nota para planilla
                </button>
            `;
            document.getElementById('nota_rrhh').closest(".col-md-12").insertAdjacentElement('afterend', div);
        }
        if (resp.estado === "ANU") {
            let div = document.createElement('div');
            div.classList.add("col-md-12");
            div.innerHTML = `
                <label class="w-100">
                    Motivo de la anulación
                    <textarea id="nota_anulacion" class="form-control" placeholder="Motivo de la anulación..." rows='3' minlength="25" disabled></textarea>
                </label>
            `;
            document.getElementById('nota_rrhh').closest(".col-md-12").insertAdjacentElement('afterend', div);
        }
        if (resp.comprobante) {
            const recursos = resp.comprobante.replace(/\\+/g, '')
            const cleanRecursos = JSON.parse(recursos)
            if (cleanRecursos.length) {
                select('#btnDescargarRecursosPDF').innerHTML = `
                    <button
                        type="button"
                        class="btn btn-primary w-100 btn-sm download-resources"
                        data-toggle="tooltip"
                        title="Descargar comprobantes"
                        data-id="${select("[data-app-control-sustitucion-abierta]")?.value}"
                    >
                        <i class="fas fa-file-download fa-lg"></i>
                        Descargar Comprobantes
                    </button>
                `;
            }
        }
        $("#collapseAusencias, #collapseSustituciones").on("show.bs.collapse", function (e) {
            const collapse = this;
            const data = {
                codigo_sustituto: resp.codigo_empleado2,
                cedula_sustituto: resp.cedula,
                codigo_ausente  : resp.codigo_empleado,
                cedula_ausente  : resp.cedula_fijo,
                ACCION          : collapse.querySelector(`[name="ACCION"]`).value,
                t               : "getAusenciasSustituciones",
            };
            $(collapse).find("#NOMBRE_AUSENTE").text(resp.nombre_ausente);
            $(collapse).find("#NOMBRE_SUSTITUTO").text(resp.nombre_empleado);
            mostrar_datos_empleados_collapse(data, collapse);
        });
    }).done(function (resp) {
        console.log(resp);
        const display = ([ "ANU", "PRN", "RNM" ].includes(resp.estado)) ? "none" : "";
        GUARDAR_COMUNICADO.style.display = display;
        select('#anular-comunicado').style.display = display;
        select('#nota_rrhh').disabled = ([ "ANU", "PRN", "RNM" ].includes(resp.estado));
        $('#plantillaModal select[name="tipoempleado"]').val(resp.tipoempleado).change();
        Object.entries(resp).forEach(([key, value]) => {
            const elem = select(`#plantillaModal #${key}`, false);
            if (elem && value) {
                let val = value.toString().trim();
                if (is_url(val)) { return elem.setAttribute("src", val); }
                const nodename = elem.nodeName;
                if (nodename && ["input", "select"].includes(nodename.toLowerCase())) { return elem.value = val; } 
                return elem.innerText = val;
            }
        })
        Modal.Open('#plantillaModal');
        return init_custom_files();
    }).fail(function (err){
        alerta.Danger('Se ha presentado un imprevisto al momento de obtener el registro :( Un motivo probable es que el cliente/lugar no se encuentre registrado.').show();
        console.error(err.responseText)
        VerificarEstadoComunicado("C")
    });
}, getEmpleados = () => {
    $.ajax({
      "url": "http://201.200.254.64/MASIZA/Controllers/EmpleadosController",
      "method": "POST",
      "data": { t: "getEmpleados" },
      "dataType": "json",
    }).done(function (resp) {
        COLABORADORES["F"] = [...resp["F"]]
        COLABORADORES["S"] = [...resp["S"]]
        // .reduce((carry, item) => {
        //     carry[item["EMPLEADO"]] = item;
        //     return carry;
        // }, {});
    }).fail(function(err) {
        alerta.Danger("Se presentó un imprevisto al momento de procesarla solicitud").show();
        console.error(err);
    });
}, guardar_anulacion_sustitucion = (ev) => {
    const boton = ev.target.closest("button");
    boton.disabled = true;
    return $.ajax({
        "url": base_url("Controllers/SustitucionesController"),
        "method": "POST",
        "data": {
            t: 'anularSustituciones',
            motivo_anulacion: e.value.motivo_anulacion,
            id_sustitucion: select("[data-app-control-sustitucion-abierta]")?.value,
        },
        "dataType": "json",
        "beforeSend" : function () {
            alerta("info", "Por favor espere, se está procesando su petición.", "")
        }
    }).done(function( resp ) {
        if (resp.type === "success") {
            $('#anularModal').off("hidden.bs.modal");
            Modal.Close('#anularModal');
        }
        alerta[strToCapitalize(resp.type)](resp.message, resp.title).show();
    }).fail(function( err ) {
        alerta('error', 'Se ha presentado un imprevisto al momento de procesar su solicitud :(', '')
        console.error(err);
    }).always((_) => {
        boton.disabled = false;
    });
}, renderAcciones = (idsustitucion, comprobantes) => {
    let acciones = `
        <button
            type="button"
            class="btn btn-info btn-sm ver-comunicado"
            data-id="${idsustitucion}"
            title="Ver comunicado"
            style="margin: 2px;"
        >
            <i class="fas fa-eye fa-1x"></i>
        </button>
    `
    if (comprobantes != null) {
        const recursos = comprobantes.replace(/\\+/g, '')
        const cleanRecursos = JSON.parse(recursos)
        if (cleanRecursos.length) {
            acciones += `
                <button
                    type="button"
                    class="btn btn-primary btn-sm download-resources"
                    data-id="${idsustitucion}"
                    style="margin: 2px;"
                >
                    <i class="fas fa-file-download" data-toggle="tooltip" title="Descargar recursos"></i>
                </button>
            `
        }
    }
    return acciones
}, downloadRecursos = (recurso) => {
    let a = document.createElement('a');
    a.href = "../Recursos/Comprobantes/"+recurso;
    let archivo = recurso
    a.download = archivo;
    a.click();
    window.URL.revokeObjectURL(recurso);
}, validarCantidadComprobantes = (nuevos) => {
    let maximos = 8
    let comprobante = document.querySelector(`.ver-comunicado[data-id="${select("[data-app-control-sustitucion-abierta]")?.value}"]`).closest('td').querySelector("span").innerHTML

    const recursos = ((comprobante == '' || comprobante == null)?'{}':comprobante.replace(/\\+/g, ''))
    const comprobanteLength = (typeof JSON.parse(recursos).length  == 'undefined')?0:JSON.parse(recursos).length

    if ((maximos - (comprobanteLength + nuevos)) >= 0 && comprobanteLength <= 8)
        return true
    else {
        alerta("info", `El número de archivos ingresados excede el total permitido. Total de archivos hasta exceder el máximo permitido: '${(maximos - comprobanteLength)}'`, "")
        return false
    }
}, sendComunicado = (formData) => {
    return $.ajax({
        "url": base_url("Controllers/SustitucionesController"),
        "method": "POST",
        "data": formData,
        "dataType": "json",
        "contentType": false,
        "processData": false,
        "cache": false,
        "beforeSend" : function () {
            alerta("info", "Por favor espere, se está procesando su petición.", "")
            Modal.Open("#modalcargando")
            $("#guardar").attr("disabled", true);
        }
    }).done(function( resp ) {
        alerta(resp.type, resp.message, resp.title)
        if (resp.isOk) {
            return get_sustitucion()
        }
        dataTable.ajax.reload(null, false);
    }).fail(function( err ) {
        alerta('error', 'Se ha presentado un imprevisto al momento de procesar su solicitud :(', '')
        console.error(err);
    }).always((resp) => {
        GUARDAR_COMUNICADO.disabled = false;
        let time = setTimeout(() => {
            Modal.Close("#modalcargando")
            clearTimeout(time)
        }, 1000)
    })
}, sendCorreo = (formData) => {
    Swal.fire({
        "title": 'Enviar Correo',
        "html": `
            <div class="form-group">
                <label></label>
                <select class="form-control" id="correo_utilizar">
                    <!-- <option value="GOSEVEN">goseven@grupomasiza.com</option> -->
                    <option value="GMAILINFO">info.masiza@gmail.com</option>
                    <option selected value="GMAILINGRESO">ingreso.masiza@gmail.com</option>
                </select>
            </div>
            <div class="form-group">
                <label>Correos</label>
                <input class="form-control" value="${$('#correos').val()}" placeholder="Correos..." id="correos_clientes" />
            </div>
            <div class="form-group">
                <label>Asunto</label>
                <input id="asunto" class="form-control" placeholder="Asunto del mensaje..." required/>
            </div>
            <div class="form-group">
                <label>Mensaje</label>
                <textarea id="mensaje" class="form-control" placeholder="Cuerpo del mensaje..." rows='2' required></textarea>
            </div>
            <div class="form-group">
                <label style="width: 100%;">
                  Adjuntos adicionales:
                  <input type="file" name="adjuntos" accept="image/jpg,image/jpeg,image/png,image/gif" style="display: none;" onchange="document.querySelector('[data-app-input-file]').value = this.files[0].name" />
                  <div style="display: flex;">
                    <input type="text" class="form-control" data-app-input-file placeholder="Click para buscar..." onclick="document.querySelector('[name=adjuntos]').click()" readonly style="border-bottom-right-radius: 0px; border-top-right-radius: 0px; width: 75%;" />
                    <button type="button" class="btn btn-warning" style="border-bottom-left-radius: 0px; border-top-left-radius: 0px; width: 25%;" onclick="document.querySelector('[name=adjuntos]').click()">Buscar</button>
                  </div>
                </label>
            </div>
        `,
        "customClass": {
            "confirmButton": 'btn btn-success m-1',
            "cancelButton": 'btn btn-secondary m-1',
            "denyButton": 'btn btn-warning m-1',
        },
        "buttonsStyling": false,
        "confirmButtonText": 'Enviar <i class="far fa-paper-plane"></i>',
        "focusConfirm": false,
        "cancelButtonText": 'Cancelar <i class="far fa-window-close"></i>',
        "showCancelButton": true,
        "denyButtonText": 'Solo Registrar <i data-feather="save"></i>',
        "showDenyButton": true,
        "preConfirm": function() {
            const asunto = $('#asunto').val()
            const mensaje = $('#mensaje').val()
            const correos_clientes = $('#correos_clientes').val()

            if (asunto.length < 1) return Swal.showValidationMessage(`¡Atención! El campo "Asunto" es requerido y no puede quedar en blanco`)
            if (asunto.length < 1) return Swal.showValidationMessage(`¡Atención! El campo "Mensaje" es requerido y no puede quedar en blanco`)
            if (correos_clientes.length < 1) return Swal.showValidationMessage(`¡Atención! El campo "Correos" es requerido y no puede quedar en blanco`)

            if (asunto.length < 1) return Swal.showValidationMessage(`¡Atención! El campo "Asunto" es requerido y no puede quedar en blanco`)
            if (mensaje.length < 10) return Swal.showValidationMessage(`¡Atención! La descripción ingresada en el campo "Mensaje" es demasiado corta, por favor prolonga a un mínimo de 25 caracteres`)

            return {
                asunto: asunto,
                mensaje: mensaje,
                adjuntos: $('input[name="adjuntos"]')[0].files,
                correo_utilizar: $("#correo_utilizar").val(),
                correos_clientes: correos_clientes,
            }
        }
    }).then((e) => {
        if (e.isDismissed) {
            get_sustitucion()
            return true
        }
        if (e.value) {
            for (let i = 0; i < e.value.adjuntos.length; i++) {
                formData.append('adjuntos[]', e.value.adjuntos[i])
            }
            formData.append("correo_utilizar", e.value.correo_utilizar)
            formData.append("asunto", e.value.asunto)
            formData.append("mensaje", e.value.mensaje)
            formData.append("correos_clientes", e.value.correos_clientes)
        }
        if (e.isDenied)
            formData.append('enviar_correo', 'false')
        return sendComunicado(formData)
    }).then(() => {
        feather.replace()
        bsCustomFileInput.init()
    })
}, VerificarEstadoComunicado = (estado) => {
    // estado = { a: abrir, c: cerrar }
    return $.ajax({
        "url": base_url(`sustituciones/validar_estado`), // "http://201.200.254.64/MASIZA/Controllers/SustitucionesController",
        "method": "GET",
        "data": { estado, modulo: "RRHH", id_sustitucion: select("[data-app-control-sustitucion-abierta]")?.value },
        "dataType": "json",
    }).done(function( resp ) {
        if (resp.TIPO) {
            return alerta[capitalize(resp.TIPO)](resp.MENSAJE, resp.TITULO).show();
        }
    }).fail(function( err ) {
        alerta.Danger(`Se ha presentado un imprevisto al momento de validar el estado del comunicado :( Error: ${err.responseText}`).show();
        console.error(err);
    });
}, postToNewWindow = (url, data) => {
    // Crear formulario
    let form = document.createElement('form');
    form.action = url;
    form.method = 'POST';
    form.target = '_blank';

    // Agregar campos ocultos con los datos a enviar
    for (let name in data) {
        let input = document.createElement('input');
        input.type = 'hidden';
        input.name = name;
        input.value = data[name];
        form.appendChild(input);
    }

    // Agregar el formulario al documento y enviarlo
    document.body.appendChild(form);
    form.submit();

    // Eliminar el formulario del documento
    document.body.removeChild(form);
}, mostrar_datos_empleados_collapse = (data, collapse) => {
    const table = collapse.querySelector(".table tbody")
    table.innerHTML = ''
    return $.ajax({
        "url": base_url("Controllers/SustitucionesController"),
        "method": "POST",
        "data": data,
        "dataType": "json",
    }).done(function (resp) {
        collapse.querySelector("#CANTIDAD_AUSENCIAS").innerText = resp.length;
        collapse.querySelector("#CANTIDAD_NOMBRAMIENTOS").innerText = resp.length;
        if (!resp.length) {
            return table.innerHTML = `
                <tr>
                    <td colspan="100%" class="text-center">
                        Ningún dato disponible en esta tabla...
                    </td>
                </tr>
            `;
        }
        const filas = resp.map((item) => `
            <tr>
                <td>${item.oficio_num}</td>
                <td>${moment(item.fecha_inicial, "YYYY-MM-DD HH:mm:ss").format( "DD/MM/YYYY" )}</td>
                <td>${moment(item.fecha_final, "YYYY-MM-DD HH:mm:ss").format( "DD/MM/YYYY" )}</td>
                <td>${item.nombre_sustituto || item.nombre_fijo}</td>
            </tr>
        `);
        table.innerHTML = filas.join("");
    }).fail(function (err) {
        alerta.Danger(err.responseText).show();
        console.error(err);
    })
}, mostrar_modal_correo = () => {
    $(MODAL_COMUNICADO).on("hidden.bs.modal", () => {
        $(MODAL_COMUNICADO).off("hidden.bs.modal");
        $(MODAL_CORREO_COMUNICADO).modal("show");
    }).modal("hide");
    $(MODAL_CORREO_COMUNICADO).on("hidden.bs.modal", () => {
        const keys = {
            "cedula": "cedula_sustituto",
            "fecha_inicial": "fecha_rige",
            "fecha_final": "fecha_vence",
            "oficio_num": "oficio",
            "fecha_envio": "fecha_comunicado",
            "observ_rrhh": "nota_rrhh",
            "limpieza_cajero": "limpieza_cajero",
            "tiempo_alimento": "tiempo_alimento",
            "nombre_sustituto": "nombre_sustituto",
            "hora_entrada_salida": "hora_cubrir",
            "recontratable": "recontratable",
            "correos_clientes": "correos",
        }
        get_sustitucion().done(() => {
            Object.entries(sustitucion).forEach(([key, value]) => {
                const el = select(`#${keys[key]}`);
                if (!el) { return; }
                console.log(el);
            });
            $(MODAL_CORREO_COMUNICADO).off("hidden.bs.modal");
            $(MODAL_COMUNICADO).modal("show");
        });
    });
}

const procesar_comunicado = () => {
    const TIEMPO_ALIMENTACION = select(`#tiempo_alimento`),
      HORARIO_CUBRIR = select(`#hora_cubrir`),
      NOTA_RH = select(`#nota_rrhh`),
      EMPLEADO = select(`#nombre_sustituto`),
      RECONTRATABLE = select(`#recontratable`);

    if (HORARIO_CUBRIR?.value.trim().length == 0) {
        HORARIO_CUBRIR.focus();
        return alerta.Info('No se ha especificado el horario a cubrir').show();
    }

    if (TIEMPO_ALIMENTACION?.value.trim().length == 0) {
        TIEMPO_ALIMENTACION.focus();
        return alerta.Info('No se ha especificado el tiempo de alimentación').show();
    }

    if (NOTA_RH?.value.trim().length < 10) {
        NOTA_RH.focus();
        return alerta.Info('Se debe indicar una observación con un mínimo de 10 caracteres').show();
    }

    if (EMPLEADO?.value.trim().length === 0) {
        EMPLEADO.focus();
        return alerta.Warning('Se debe indicar el empleado que laborará para procesar el comunicado').show();
    }

    if (!RECONTRATABLE.parentNode.classList.contains("d-none") && RECONTRATABLE?.value.trim().length === 0) {
        recontratable[0].focus()
        return alerta.Info('Es requerido indicar si el colaborador puede o no ser recontratado').show();
    }

    const CEDULA_SUSTITUTO = select(`#cedula_sustituto`),
      FECHA_RIGE = select(`#fecha_rige`),
      FECHA_VENCE = select(`#fecha_vence`),
      OFICIO = select(`#oficio`),
      FECHA_COMUNICADO = select(`#fecha_comunicado`),
      LIMPIEZA_CAJERO = select(`#limpieza_cajero`),
      ID_SUSTITUCION = select(`[data-app-control-sustitucion-abierta]`),
      CORREO_SALIDA = select(`#correos`);

    sustitucion = {
        t: 'putSustitucion',
        cedula: CEDULA_SUSTITUTO.textContent.trim(),
        fecha_inicial: moment(FECHA_RIGE.textContent.trim(),  "DD/MM/YYYY").format("YYYY-MM-DD"),
        fecha_final:   moment(FECHA_VENCE.textContent.trim(), "DD/MM/YYYY").format("YYYY-MM-DD"),
        oficio_num:  OFICIO?.value.trim(),
        fecha_envio: FECHA_COMUNICADO.textContent.trim(),
        observ_rrhh: NOTA_RH?.value.trim(),
        limpieza_cajero: LIMPIEZA_CAJERO.textContent.trim(),
        tiempo_alimento: TIEMPO_ALIMENTACION?.value.trim(),
        nombre_sustituto: EMPLEADO?.value.trim(),
        codigo_empleado2: EMPLEADO.querySelector(`option:checked`).getAttribute(`data-id`).trim(),
        hora_entrada_salida: HORARIO_CUBRIR?.value.trim(),
        recontratable: RECONTRATABLE?.value.trim(),
        id_sustitucion: ID_SUSTITUCION?.value.trim(),
        correos_clientes: CORREO_SALIDA.textContent.trim(),
    }

    return mostrar_modal_correo();
}

window.addEventListener("beforeunload", function (e) {
    VerificarEstadoComunicado("C")
});

window.addEventListener("load", () => {
    const table = select("#tSustituciones");
    dataTable = new DataTable(table, {
        dom: 'Birtp',
        pageLength: 25,
        buttons: [
            {
                'extend': 'excelHtml5',
                'title': 'Listado de Sustituciones',
                'exportOptions': {
                    'columns': [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, -3, -2, -1],
                },
                'titleAttr': 'Excel',
                'text': '<i class="far fa-2x fa-file-excel"></i>',
            },
            {
                'extend': 'pdfHtml5',
                'title': 'Listado de Sustituciones',
                'exportOptions': {
                    'columns': [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, -3, -2, -1],
                },
                'titleAttr': 'PDF',
                'text': '<i class="far fa-2x fa-file-pdf"></i>',
            },
        ],
        ajax: {
            url: base_url(`sustituciones/obtener`), // "http://201.200.254.64/MASIZA/Controllers/SustitucionesController",
            method: "GET",
            responseType: "json",
            data: function (d) {
                const [ RIGE, VENCE ] = select("[data-filtro-fecha]")?.value.split(" - ");
                const ESTADO = select(`[data-filtro-estado]`)?.value;
                d.estado     = (ESTADO === "A" ? 0 : ESTADO);
                d.anulado    = (ESTADO === "A" ? 1 : 0);
                d.fechaDesde = RIGE  ? moment(RIGE, "DD/MM/YYYY").format("YYYY-MM-DD")  : "";
                d.fechaHasta = VENCE ? moment(VENCE, "DD/MM/YYYY").format("YYYY-MM-DD") : "";
                d.t = "getSustituciones";
            },
            // success: function(response) {
            //     console.log(response)
            //     return response; // No es necesario realizar ninguna transformación si el API ya devuelve un JSON válido
            // },
            dataSrc: function(response) {
                return response; // No es necesario realizar ninguna transformación si el API ya devuelve un JSON válido
            },
            error: function (response) {
                console.log(response)
                return response; // No es necesario realizar ninguna transformación si el API ya devuelve un JSON válido
            },
        },
        bAutoWidth: false,
        processing: true,
        order: [[1, "ASC"]],
        columns: [
            {
                "targets": 0,
                "orderable": false,
                "data": "id_sustitucion",
                'render': function(data, type, row, meta) {
                    return `
                        <div class="d-flex">
                            ${renderAcciones(data, row.comprobante)}
                        </div>
                        <span class="d-none">${row.comprobante||''}</span>
                    `
                }
            },
            {
                "targets": 1,
                "data": "Fecha_Hora",
                'render': function(data, type, row, meta) {
                    return moment(data).format("DD/MM/YYYY HH:mm:ss")
                }
            },
            {
                "targets": 2,
                "orderable": false,
                "data": "Supervisor",
                'render': function(data, type, row, meta) {
                    return data
                }
            },
            {
                "targets": 3,
                "data": "oficio_num",
                'render': function(data, type, row, meta) {
                    return (data.length !== 0)?data:"Sin Definir"
                }
            },
            {
                "targets": 4,
                "orderable": true,
                "data": "nombre_fijo",
                'render': function(data, type, row, meta) {
                    return data
                }
            },
            {
                "targets": 5,
                "orderable": true,
                "data": "Lugar",
                'render': function(data, type, row, meta) {
                    return data
                }
            },
            {
                "targets": 6,
                "orderable": true,
                "data": "nombre_sustituto",
                'render': function(data, type, row, meta) {
                    return data
                }
            },
            {
                "targets": 7,
                "data": "fecha_inicial",
                'render': function(data, type, row, meta) {
                    return moment(data).format("DD/MM/YYYY")
                }
            },
            {
                "targets": 8,
                "data": "fecha_final",
                'render': function(data, type, row, meta) {
                    return moment(data).format("DD/MM/YYYY")
                }
            },
            {
                "targets": 9,
                "data": "motivo",
                'render': function(data, type, row, meta) {
                    return data
                }
            },
            {
                "targets": 10,
                "orderable": false,
                "data": "fecha_envio_comunicado_rrhh",
                'render': function(data, type, row, meta) {
                    if (data === null || data.includes('0000-00-00')) {
                        return `--`
                    }
                    return moment(data).format("DD/MM/YYYY HH:mm:ss")
                }
            },
            {
                "targets": 11,
                "orderable": false,
                "data": "rrhh_procesado_por",
                'render': function(data, type, row, meta) {
                    return (data == null)?'Gestor sin definir':data
                }
            },
            {
                "targets": 12,
                "orderable": false,
                "data": "fecha_proceso_nomina",
                'render': function(data, type, row, meta) {
                    if (data === null || data.includes('0000-00-00')) {
                        return `--`
                    }
                    return moment(data).format("DD/MM/YYYY HH:mm:ss")
                }
            },
            {
                "targets": 13,
                "orderable": false,
                "data": "nomina_procesado_por",
                'render': function(data, type, row, meta) {
                    return (data == null)?'--':data
                }
            },
            {
                "targets": 14,
                "orderable": false,
                "data": "anulado_por",
                "visible": false,
                'render': function(data, type, row, meta) {
                    return (data == null)?'--':data
                }
            },
            {
                "targets": 15,
                "orderable": false,
                "data": "fecha_anulacion",
                "visible": false,
                'render': function(data, type, row, meta) {
                    if (data === null || data.includes('0000-00-00')) {
                        return `--`
                    }
                    return moment(data).format("DD/MM/YYYY HH:mm:ss")
                }
            },
            {
                "visible": false,
                "targets": -1,
                "data": "comprobante",
                "render": function (data, type, row, meta) {
                    return `${row.comprobante != null?'Si posee':'No posee'}`
                },
            },
            {
                "visible": false,
                "targets": -2,
                "data": "observ_rrhh",
                "render": function (data, type, row, meta) {
                    return data
                },
            },
            {
                "visible": false,
                "targets": -3,
                "data": "Observaciones",
                "render": function (data, type, row, meta) {
                    return data
                },
            },
        ],
        language: { url: base_url("public/dist/datatables/language_esp.json"), },
        initComplete: function () {
            const api = this.api();
            const filterContainer = select("[data-app-filtros]");
            function handleFilterEvent(event) {
                const target = event.target;
                if (!target.matches("[data-col-dt]")) { return; }
                const columnIndex = target.getAttribute("data-col-dt");
                const value = target.value.trim();
                dataTable.column(columnIndex).search(value).draw();
            }
            filterContainer.addEventListener("change", handleFilterEvent);
            getEmpleados();
        },
        drawCallback: function () {
            $('[data-toggle="tooltip"]').tooltip()
            let table = this.api()

            let colanulados = table.columns([14, 15,])
            let colprocesados = table.columns([10,11,12,13])
            colanulados.visible(false)
            colprocesados.visible(true)
            if (select(`[data-filtro-estado]`)?.value === 'A') {
                colanulados.visible(true)
                colprocesados.visible(false)
            }
        }
    });

    $(document).on('change', '#plantillaModal select[name="tipoempleado"]', (e) => {
        const OPCIONES = COLABORADORES[e.target.value].map(empleado => `<option value="${empleado.NOMBRE}" data-id="${empleado.EMPLEADO}" data-cedula="${empleado.IDENTIFICACION}">${empleado.NOMBRE}</option>`);
        OPCIONES.unshift(
            `<option value="" data-id="" data-cedula="---">Seleccione...</option>`,
            `<option value="PENDIENTE" data-id="PENDIENTE" data-cedula="PENDIENTE">PENDIENTE</option>`,
            `<option value="NO APLICA" data-id="NO APLICA" data-cedula="NO APLICA">NO APLICA</option>`,
        );
        return select(`#nombre_sustituto`).innerHTML = OPCIONES.join("");
    }).on('change', '#nombre_sustituto', function (e) {
        const EMPLEADO = e.target;
        const cedula = EMPLEADO.querySelector("option:checked").getAttribute('data-cedula');
        return select('#cedula_sustituto').innerText = cedula;
    }).on('click', '#buscar', function(e) {
        const ESTADO = select(`[data-filtro-estado]`)?.value.trim();
        const FECHA  = select("[data-filtro-fecha]");
        if (ESTADO !== '0'  && FECHA?.value.trim().length === 0) {
            alerta.Warning("Por favor ingresa un rango de fechas").show();
            return FECHA.focus();
        }
        dataTable.ajax.reload(null, false);
    }).on('show.bs.modal', '#plantillaModal', function (e) {
        GUARDAR_COMUNICADO.disabled = false;
        select('#anular-comunicado').disabled = false;
    }).on('click', '.ver-comunicado', function (e) {
        select("[data-app-control-sustitucion-abierta]").value = this.getAttribute("data-id");
        VerificarEstadoComunicado('A').done((resp) => {
            if (resp == 1) { get_sustitucion(); }
        });
    }).on('click', '#GuardarNotaAdicional', function (e) {
        this.disabled = true;
        const NOTA = select(`#notas_adicionales`);
        if (NOTA?.value.trim().length == 0) {
            return alerta.Info('La nota debe contener un mínimo de 10 caracteres').show();
        }

        let formData = new FormData();
        const COMPROBANTES = select('input[name="comprobantes"]');
        const newComprobantes = COMPROBANTES.files.length;
        if (newComprobantes !== 0) {
            if (validarCantidadComprobantes(newComprobantes)) {
                for (let i = 0; i < COMPROBANTES.files.length; i++) {
                    formData.append('comprobantes[]', COMPROBANTES.files[i]);
                }
            } else return;
        }

        const idsustitucion = select("[data-app-control-sustitucion-abierta]")?.value;
        const data = {
            t: 'postNotaAdicional',
            id_sustitucion: idsustitucion,
            nota_adicional: NOTA?.value.trim(),
            comprobante: document.querySelector(`.ver-comunicado[data-id="${idsustitucion}"]`).closest('td').querySelector("span").innerHTML,
        };

        for (const key in data) {
            formData.append(key, data[key]);
        }

        $.ajax({
            "url": base_url("Controllers/SustitucionesController"),
            "method": "POST",
            "data": formData,
            "dataType": "json",
            "contentType": false,
            "processData": false,
            "cache": false,
            "beforeSend" : function () {
                alerta.Info("Por favor espere, se está procesando su petición.").show();
            }
        }).done(function( resp ) {
            Modal.Close("#plantillaModal")
            dataTable.ajax.reload(null, false);
            alerta[strToCapitalize(resp.type)](resp.message, resp.title);
            return console.log(resp)
        }).fail(function( err ) {
            alerta.Danger('Se ha presentado un imprevisto al momento de procesar su solicitud :(').show();
            console.error(err);
        }).always(function (resp) {
            this.disabled = false;
        });
    }).off('submit', '#formulario').on('submit', '#formulario', function (e) {
        e.preventDefault();

        let formData = new FormData()
        const newComprobantes = $('input[name="comprobantes"]')[0].files.length
        if (newComprobantes !== 0) {
            if (validarCantidadComprobantes(newComprobantes)) {
                for (let i = 0; i < $('input[name="comprobantes"]')[0].files.length; i++) {
                    formData.append('comprobantes[]', $('input[name="comprobantes"]')[0].files[i])
                }
            }else return
        }

        for (const key in data) {
            formData.append(key, data[key])
        }

        Modal.Close('#plantillaModal')
        return sendCorreo(formData)
    }).on('hidden.bs.modal', '#plantillaModal', function (e) {
        VerificarEstadoComunicado("C");
        MODAL_COMUNICADO.querySelector(".modal-body").innerHTML = "";
    }).on('click', "#GUARDAR-COMUNICADO", procesar_comunicado)
      .on('click', '.download-resources', function (e) {
        let comprobante = document.querySelector(`.ver-comunicado[data-id="${$(this).data("id")}"]`).closest('td').querySelector("span").innerHTML
        const recursos = comprobante.replace(/\\+/g, '')
        const cleanRecursos = JSON.parse(recursos)
        if (typeof cleanRecursos === 'object' || Array.isArray(cleanRecursos)) {
            for (let i = 0; i < cleanRecursos.length; i++) {
                downloadRecursos(cleanRecursos[i])
            }
        }
    }).on('click', '#descargaComunicado', function (e) {
        if ($('#hora_entrada_salida').val().trim().length == 0) {
            return alerta('warning', 'No se ha especificado el horario a cubrir', '')
        }
        let data = {
            t: 'reporteComunicado',
            FECHA_COMUNICADO    : $(`#fecha_envio_registro`).text(),
            NUMERO_OFICIO       : $(`#oficio_num`).val(),
            HONORIFICO          : $(`#honorifico`).text(),
            CONTACTO            : $(`#contacto`).text(),
            PUESTO              : $(`#puesto`).text(),
            CLIENTE             : $(`#cliente`).text(),
            SALUDO              : $(`#saludo`).text(),
            NOMBRE_SUSTITUTO    : $(`#nombre_sustituto`).val(),
            CEDULA_SUSTITUTO    : $(`#cedula`).text(),
            NOMBRE_AUSENTE      : $(`#nombre_fijo`).text(),
            UBICACION           : $(`#Lugar`).text(),
            MOTIVO_AUSENCIA     : $(`#motivo`).text(),
            FECHA_RIGE          : $(`#fecha_inicial`).text(),
            FECHA_FINAL         : $(`#fecha_final`).text(),
            HORARIO_REGULAR     : $(`#horario_regular`).text(),
            HORARIO_CUBRIR      : $(`#hora_entrada_salida`).val(),
            TIEMPO_ALIMENTACION : $(`#tiempo_alimento`).val(),
            LIMPIEZA_CAJERO     : $(`#limpieza_cajero`).text(),
            CORREOS             : $(`#correos`).text(),
            FIRMA_USUARIO       : $(`#firma`).attr('src'),
            NOMBRE_USUARIO      : $(`#rrhh_procesado_por`).text(),
            CORREO_USUARIO      : $(`#correo-usuario`).text(),
        }
        return postToNewWindow(base_url("Controllers/SustitucionesController"), data);
    }).on('click', '#anular-comunicado', function (e) {
        Modal.Close('#plantillaModal');
        $("#GUARDAR-ANULACION-SUSTITUCION").off("click").on("click", guardar_anulacion_sustitucion);
        $('#anularModal').on("hidden.bs.modal", (e) => get_sustitucion());
        Modal.Open('#anularModal');
    });

    init_easepicker();
    init_editor();
});