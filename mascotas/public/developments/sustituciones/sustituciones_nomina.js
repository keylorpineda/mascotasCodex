const render_calendario = (__CALENDAR_CONTAINER) => {
    return new Promise((resolve, reject) => {
        try {
            __CALENDAR_CONTAINER.innerHTML = select("#CALENDARIO").innerHTML
            const script = document.createElement("script")
            script.src   = base_url("public/js/calendar.js")
            __CALENDAR_CONTAINER.appendChild(script)
            resolve(script)
        } catch (error) {
            reject(error)
        }
    })
}, marcar_fechas_calendario = (DIAS_CALENDARIO) => {
    const FECHA_RIGE  = moment(select("#plantillaModal #FECHA_RIGE").innerText , "DD/MM/YYYY").format("YYYY-MM-DD")
    const FECHA_VENCE = moment(select("#plantillaModal #FECHA_VENCE").innerText, "DD/MM/YYYY").format("YYYY-MM-DD")
    const ANNIO_MES = select("#plantillaModal .calendar_header").querySelector("h2").innerText
    DIAS_CALENDARIO.forEach((DIA) => {
        const FECHA = moment(`${DIA.innerText} ${ANNIO_MES}`, 'D MMMM YYYY')
        if (isSameOrBetween(FECHA, FECHA_RIGE, FECHA_VENCE)) {
            DIA.classList.add("bg-warning")
        }
    })
}, isSameOrBetween = (fecha, inicio, fin) => {
    return fecha.isSame(inicio) || fecha.isSame(fin) || fecha.isBetween(inicio, fin)
}, agregar_fechas_rebajar = (e) => {
    if (e.target && e.target.id === 'AGREGAR_REGISTROS_REBAJAR') {
        const FECHAS_MARCADAS = select("#plantillaModal .calendar_content").querySelectorAll(`.bg-warning`);
        const ACC_PER = select(`#plantillaModal #ACC_PER tbody`);
        ACC_PER.innerHTML = '';

        if (FECHAS_MARCADAS.length !== 0) {
            const ANNIO_MES = select("#plantillaModal .calendar_header").querySelector("h2").innerText
            const HTML_ACC_PER = []
            let QUI = 1, FECHA_RIGE = '', FECHA_VENCE = '', i = 0;

            FECHAS_MARCADAS.forEach((elemento, indice) => {
                let isFirstQUI  = false || (parseInt(elemento.textContent) >= 1 && parseInt(elemento.textContent) <= 15);
                let isSecondQUI = false || (parseInt(elemento.textContent) >= 16 && parseInt(elemento.textContent) <= 31);

                if (isFirstQUI) {
                    if (i === 0) { FECHA_RIGE = elemento.textContent; }
                    i++;
                    FECHA_VENCE = elemento.textContent;

                    if ((indice === FECHAS_MARCADAS.length - 1) || (typeof FECHAS_MARCADAS[indice + 1] !== 'undefined' && parseInt(FECHAS_MARCADAS[indice + 1].textContent) >= 16)) {
                        const date_rige  = moment(`${FECHA_RIGE} ${ANNIO_MES}`, 'D MMMM YYYY');
                        const date_vence = moment(`${FECHA_VENCE} ${ANNIO_MES}`, 'D MMMM YYYY');

                        HTML_ACC_PER.push(`<tr>
                            <td>${i}</td>
                            <td>${date_rige.format("DD/MM/YYYY")}</td>
                            <td>${date_vence.format("DD/MM/YYYY")}</td>
                        </tr>`);

                        isSecondQUI = true;
                        isFirstQUI = false;
                        i = 0;
                        return true;
                    }
                }

                if (isSecondQUI) {
                    if (i === 0) { FECHA_RIGE = elemento.textContent; }
                    i++;
                    FECHA_VENCE = elemento.textContent;

                    if (indice === FECHAS_MARCADAS.length - 1) {
                        const date_rige  = moment(`${FECHA_RIGE} ${ANNIO_MES}`, 'D MMMM YYYY');
                        const date_vence = moment(`${FECHA_VENCE} ${ANNIO_MES}`, 'D MMMM YYYY');

                        HTML_ACC_PER.push(`<tr>
                            <td>${i}</td>
                            <td>${date_rige.format("DD/MM/YYYY")}</td>
                            <td>${date_vence.format("DD/MM/YYYY")}</td>
                        </tr>`);
                    }
                }
            });
            ACC_PER.innerHTML = HTML_ACC_PER.join("")
        }
    }
}, evento_change_horas_pagar = (input) => {
    input.addEventListener('change', function (e) {
        const HORA_DIURNA   = 8,
              HORA_MIXTA    = 7,
              HORA_NOCTURNA = 6

        let HORA_ENTRADA = select("#plantillaModal #HORA_ENTRADA").value,
            HORA_SALIDA  = select("#plantillaModal #HORA_SALIDA").value

        if (HORA_ENTRADA.trim().length !== 0 && HORA_SALIDA.trim().length !== 0) {
            let TIEMPO_ENTRADA = moment(HORA_ENTRADA, 'HH:mm'),
                TIEMPO_SALIDA  = moment(HORA_SALIDA, 'HH:mm')

            let TIEMPO_ENTRADA_MS = TIEMPO_ENTRADA.valueOf(),
                TIEMPO_SALIDA_MS  = TIEMPO_SALIDA.valueOf()

            const TIPO_JORNADA = select('#plantillaModal #TIPO_JORNADA')
            // MIXTO = 1658449822023 = 07:30pm
            // NOCTURNO = 1658464201926 = 10:30pm
            let time = (((TIEMPO_SALIDA_MS - TIEMPO_ENTRADA_MS)/1000)/60)/60

            if (HORA_SALIDA > '19:00' && HORA_SALIDA < '22:30') {
                time = ((time.toFixed(2)/HORA_MIXTA)*HORA_DIURNA)
                TIPO_JORNADA.textContent = 'Mixto'
            } else if (HORA_SALIDA >= '22:30') {
                time = ((time.toFixed(2)/HORA_NOCTURNA)*HORA_DIURNA)
                TIPO_JORNADA.textContent = 'Nocturna'
            } else {
                TIPO_JORNADA.textContent = 'Diurno'
            }

            if (time >= 0) {
                return select("#plantillaModal #HORA_TOTAL").value = time.toFixed(2)
            }
        }
        return select("#plantillaModal #HORA_TOTAL").value = '0'
    })
}, agregar_horas_pagar = () => {
    const FECHAS_MARCADAS = select("#plantillaModal .calendar_content").querySelectorAll(`.bg-warning`)
    if (!empty(FECHAS_MARCADAS)) {
        const HORAS = select(`#plantillaModal #HORAS tbody`)
        let ANNIO_MES = select("#plantillaModal .calendar_header").querySelector("h2").innerText
        const DIAS = []
        FECHAS_MARCADAS.forEach((DIA) => {
            const FECHA = moment(`${DIA.innerText} ${ANNIO_MES}`, 'D MMMM YYYY')
            DIAS.push(`
                <tr>
                    <td>${capitalize(FECHA.format("dddd"))}</td>
                    <td>${FECHA.format("DD/MM/YYYY")}</td>
                    <td>
                        <input type="number" name="HORA_LABORADA" class="form-control" value="${($("#HORA_TOTAL").val().trim().length !== 0)?$("#HORA_TOTAL").val():0.0}" maxlength="3" />
                    </td>
                </tr>
            `)
        })
        return HORAS.innerHTML = DIAS.join("")
    }
    alerta.Warning("No se han especificado días para guardar").show()
}, cambiar_tipo_accion = (ev) => {
    const PADRE  = ev.target.parentNode;
    const OPCION = ev.target.querySelector("option:checked");

    let HORAS_REBAJAR    = PADRE.querySelector("#HORAS_REBAJAR");
    let PERMISO_AUSENCIA = PADRE.querySelector("#PERMISO_AUSENCIA");
    if (HORAS_REBAJAR)    HORAS_REBAJAR.parentNode.remove();
    if (PERMISO_AUSENCIA) PERMISO_AUSENCIA.parentNode.remove();

    select("#AGREGAR_REGISTROS_REBAJAR").disabled = false;
    if (OPCION.getAttribute("data-tipo-accion") === "RH") {
        PADRE.appendChild(string_to_html(`
            <div class="w-100 mt-2">
                <label class="w-100" for="HORAS_REBAJAR">Horas a rebajar</label>
                <input type="number" class="form-control form-control-sm" id="HORAS_REBAJAR" placeholder="Horas por rebajar" />
            </div>
        `));
        select("#AGREGAR_REGISTROS_REBAJAR").disabled = true;
        select("#ACC_PER tbody").innerHTML = "";
    } else if (ev.target.value === "005") {
        PADRE.appendChild(string_to_html(`
            <div class="w-100 mt-2">
                <label style="width: 100%;" for="PERMISO_AUSENCIA">Tipo de ausencia</label>
                <select class="form-control form-control-sm" id="PERMISO_AUSENCIA" name="PERMISO_AUSENCIA" placeholder="Tipo de ausencia..." required>
                    <option selected disabled value=''>Elige una de las opciones</option>
                    <option value='JUS'>JUSTIFICADA</option>
                    <option value='INJ'>INJUSTIFICADA</option>
                    <option value='AMO'>AMONESTADA</option>
                </select>
            </div>
        `));
    }
}, fechas_pendientes = () => {
    const MES_CALENDARIO  = moment(__MODAL.querySelector(".calendar_header h2").innerText, "MMMM YYYY")

    const MES_FECHA_VENCE = moment(select("#plantillaModal #FECHA_VENCE").innerText, "DD/MM/YYYY")
    const SWITCH_DERECHO  = __MODAL.querySelector(".switch-right")
    SWITCH_DERECHO.classList.remove("text-danger")
    if (MES_FECHA_VENCE.isAfter(MES_CALENDARIO)) {
        SWITCH_DERECHO.classList.add("text-danger")
    }

    const MES_FECHA_RIGE = moment(select("#plantillaModal #FECHA_RIGE").innerText, "DD/MM/YYYY")
    const SWITCH_IZQUIERDO = __MODAL.querySelector(".switch-left")
    SWITCH_IZQUIERDO.classList.remove("text-danger")

    if (MES_FECHA_RIGE.isBefore(MES_CALENDARIO)) {
        SWITCH_IZQUIERDO.classList.add("text-danger")
    }
}, guardar_horas = (datos) => {
    $.ajax({
        "url": base_url("horas/guardar"),
        "method": "POST",
        "data": datos,
        "dataType": "json",
        "beforeSend": function () {
        },
    })
    .done(function( resp ) {
        return console.log(resp)
        alerta[capitalize(resp.TIPO)](resp.MENSAJE).show()
    })
    .fail(function(err){
        alerta["Danger"]('Se ha presentado un imprevisto al momento de procesar su solicitud :(' + err.responseText).show()
        console.error(err);
    })
}, render_inputs_planilla = (PLANILLA) => {
    const inputs_planillas = {
        Recargos: [`
            <label style="width: 100%;">
                Monto Neto:
                <input type="number" name="MONTO_NETO" class="form-control form-control-sm" step="0.01" readonly required placeholder="99999..." />
            </label>
            <label style="width: 100%;">
                Rebajo CCSS:
                <input type="number" name="REBAJO_CAJA" class="form-control form-control-sm" step="0.01" placeholder="Rebajo para Seguro..." />
            </label>
            <label style="width: 100%;">
                Monto Bruto:
                <input type="number" name="MONTO_BRUTO" class="form-control form-control-sm" step="0.01" placeholder="99999..." />
            </label>
        `],
        Adicionales: [`
            <label style="width: 100%;">
                Rebajo CCSS:
                <input type="number" name="REBAJO_CAJA" class="form-control form-control-sm" step="0.01" placeholder="Rebajo para Seguro..." autocomplete="nope" />
            </label>
            <label style="width: 100%;">
                Motivo:
                <input type="text" name="MOTIVO" class="form-control form-control-sm" placeholder="Motivo de pago..." autocomplete="nope" />
            </label>
        `],
        Correcciones: [`
            <label style="width: 100%;">
                Salario por Hora:
                <input type="number" step="0.01" name="SALARIO_POR_HORA" class="form-control form-control-sm" placeholder="999..." />
            </label>
        `],
        Vacaciones: [`
            <label style="width: 100%;">
                Monto por Día:
                <input type="number" step="0.01" name="MONTO_POR_DIA" class="form-control form-control-sm" placeholder="9999..." />
            </label>
        `],
        Liquidaciones: [`
            <label style="width: 100%;">
                Liquidaciones no se cancelan por comunicado!
            </label>
        `],
    }
    return inputs_planillas[PLANILLA] || ""
}, guardar_acciones = (datos) => {
    return console.log(datos)
    $.ajax({
        "url": base_url("acciones/guardar"),
        "method": "POST",
        "data": datos,
        "dataType": "json",
        "beforeSend": function () {
        },
    })
    .done(function( resp ) {
        return console.log(resp)
        alerta[capitalize(resp.TIPO)](resp.MENSAJE).show()
    })
    .fail(function(err){
        alerta["Danger"]('Se ha presentado un imprevisto al momento de procesar su solicitud :(').show()
        console.error(err);
    })
}, get_horas_laboradas = () => {
    const HORAS = []
    let LISTA_HORAS = select(`#HORAS tbody tr`)
    if (!is_array(LISTA_HORAS)) { LISTA_HORAS = [ LISTA_HORAS ] }
    LISTA_HORAS.forEach((tr) => {
        const td = tr.querySelectorAll("td")
        HORAS.push({
            CANTIDAD_HORAS: td[2].querySelector(`input[name="HORA_LABORADA"]`).value,
            FECHA_LABORADA: td[1].textContent,
        })
    })
    return HORAS
}

const __MODAL = select("#plantillaModal")
$(() => {
    Modal.Open(`#modalcargando`)
    moment.locale('es')
	const table = select("#tsustituciones");
    const dataTable = new DataTable(table, {
        ajax: {
            url: base_url('sustituciones/obtenernomina'),
            method: "GET",
            responseType: "json",
            data: function (d) {
                d.MOTIVO_SUSTITUCION = select('[data-app-filtro-motivo]')?.value;
                d.FECHA_RIGE      	 = select('[data-app-filtro-fecha-rige]')?.value
                d.ESTADO 			 = select('[data-app-filtro-estado]')?.value;
            },
            // success: function(response) {
            //     console.log(response)
            //     return response; // No es necesario realizar ninguna transformación si el API ya devuelve un JSON válido
            // },
            error: function(response) {
                console.log(response)
                return response; // No es necesario realizar ninguna transformación si el API ya devuelve un JSON válido
            },
        },
        bAutoWidth: false,
        processing: true,
        columns: [
            {
                "targets": 1,
                "orderable": false,
                "class": "fs-5 p-1",
                "data": "ID_SUSTITUCION",
                'render': function(data, type, row, meta) {
                    return ``
                }
            },
            {
                "targets": 2,
                "orderable": false,
                "class": "fs-5 p-1",
                "data": "ID_SUSTITUCION",
                'render': function(data, type, row, meta) {
                    return `
                    	<button type="button" class="btn btn-primary btn-sm" data-app-idsustitucion="${row.ID_SUSTITUCION}" data-target="#plantillaModal" data-toggle="modal">
                    		<i class="fa-regular fa-eye"></i>
                    	</button>
                    `
                }
            },
            {
                "targets": 3,
                "orderable": false,
                "class": "fs-5 p-1",
                "data": "OFICIO",
                'render': function(data, type, row, meta) {
                    return data
                }
            },
            {
                "targets": 4,
                "orderable": false,
                "class": "fs-5 p-1",
                "data": "FECHA_REGISTRO",
                'render': function(data, type, row, meta) {
                    return data
                }
            },
            {
                "targets": 5,
                "orderable": false,
                "class": "fs-5 p-1 text-right",
                "data": "USUARIO_REGISTRO",
                'render': function(data, type, row, meta) {
                    return data
                }
            },
            {
                "targets": 6,
                "orderable": false,
                "class": "fs-5 p-1",
                "data": "AUSENTE",
                'render': function(data, type, row, meta) {
                    return data
                }
            },
            {
                "targets": 7,
                "orderable": false,
                "class": "fs-5 p-1",
                "data": "LUGAR",
                'render': function(data, type, row, meta) {
                    return data
                }
            },
            {
                "targets": 8,
                "orderable": false,
                "class": "fs-5 p-1",
                "data": "SUSTITUTO",
                'render': function(data, type, row, meta) {
                    return data
                }
            },
            {
                "targets": 9,
                "orderable": false,
                "class": "fs-5 p-1",
                "data": "FECHA_RIGE",
                'render': function(data, type, row, meta) {
                    return data
                }
            },
            {
                "targets": 10,
                "orderable": false,
                "class": "fs-5 p-1",
                "data": "FECHA_VENCE",
                'render': function(data, type, row, meta) {
                    return data
                }
            },
            {
                "targets": 11,
                "orderable": false,
                "class": "fs-5 p-1",
                "data": "MOTIVO",
                'render': function(data, type, row, meta) {
                    return data
                }
            },
            {
                "targets": 12,
                "orderable": false,
                "class": "fs-5 p-1",
                "data": "FECHA_REGISTRO_RH",
                'render': function(data, type, row, meta) {
                    return data
                }
            },
            {
                "targets": 13,
                "orderable": false,
                "class": "fs-5 p-1",
                "data": "USUARIO_REGISTRO_RH",
                'render': function(data, type, row, meta) {
                    return data
                }
            },
            {
                "targets": 14,
                "orderable": false,
                "class": "fs-5 p-1",
                "data": "FECHA_REGISTRO_PLANILLA",
                'render': function(data, type, row, meta) {
                    return data
                }
            },
            {
                "targets": 15,
                "orderable": false,
                "class": "fs-5 p-1",
                "data": "USUARIO_REGISTRO_PLANILLA",
                'render': function(data, type, row, meta) {
                    return data
                }
            },
            {
                "targets": 16,
                "orderable": false,
                "class": "fs-5 p-1",
                "data": "FECHA_ANULACION",
                'render': function(data, type, row, meta) {
                    return data
                }
            },
            {
                "targets": 17,
                "orderable": false,
                "class": "fs-5 p-1",
                "data": "USUARIO_ANULACION",
                'render': function(data, type, row, meta) {
                    return data
                }
            },
        ],
        language: {
            url: "//cdn.datatables.net/plug-ins/1.11.3/i18n/es_es.json"
        },
        initComplete: function() {
            const api = this.api()
            function handleFilterEvent(event) {
                const target = event.target;
                if (!target.matches("[data-col-dt]")) { return }
                const columnIndex = target.getAttribute("data-col-dt");
                const value = target.value.trim();
                api.column(columnIndex).search(value).draw();
            }
        },
        drawCallback: function () {
            setTimeout(() => Modal.Close(`#modalcargando`), 500)
        }
    });
    select(`#ACTUALIZAR`).onclick = (ev) => dataTable.ajax.reload()
    document.addEventListener('click', agregar_fechas_rebajar)
	$(__MODAL).on("show.bs.modal", function(ev) {
		const __BODY_MODAL = __MODAL.querySelector(`.modal-body`)
        __BODY_MODAL.innerHTML = select("#COMUNICADO-SUSTITUCION").innerHTML
        const ID_SUSTITUCION = ev.relatedTarget.getAttribute("data-app-idsustitucion")
        $.ajax({
            "url": base_url("sustituciones/obtenercomunicado"),
            "method": "GET",
            "data": { idregistro: ID_SUSTITUCION, },
            "dataType": "json",
            "beforeSend": function () {
            },
        }).done(( DATA ) => {
            if (isset(DATA.COMUNICADO.TIEMPO_CAJEROS)) {
                __BODY_MODAL.querySelector(`#TITULO_CAJEROS`).classList.remove("d-none")
            }
            if (isset(DATA.COMUNICADO.NOMBRE_AUSENTE)) {
                __BODY_MODAL.querySelector(`#TITULO_AUSENTE`).classList.remove("d-none")
            }
            Object.entries(DATA.COMUNICADO).forEach(([key, value]) => {
                if (is_date(value)) { value = moment(value).format("DD/MM/YYYY") }
                __BODY_MODAL.querySelector(`#${key}`).innerText = value
            })
            Object.entries(DATA.AUSENTE).forEach(([key, value]) => {
                __BODY_MODAL.querySelector(`#accordionEmpleados #${key}`).innerText = value
            })
            Object.entries(DATA.SUSTITUTO).forEach(([key, value]) => {
                __BODY_MODAL.querySelector(`#accordionEmpleados #${key}`).innerText = value
            })
        }).done(( DATA ) => {
            select(`input[name='HORAS_PAGAR']`).forEach(evento_change_horas_pagar)
            select(`#AGREGAR-HORAS-PAGAR`).onclick = agregar_horas_pagar
            select(`#TIPO_ACCION`).onchange = cambiar_tipo_accion
        }).fail(function( err ){
            alerta.Danger("Se ha presentado un imprevisto al momento de intentar tramitar la planilla :(").show()
            console.error(err);
        }).always((DATA) => {
            $(__MODAL).off('show.bs.collapse', `.collapse`).on('show.bs.collapse', `.collapse`, function () {
                if (this.matches("#collapseFour") || this.matches("#collapseFive")) { return }
                render_calendario(this.querySelector(`[data-app-identificador="CALENDARIO-CONTAINER"]`)).then((resp) => {
                    resp.addEventListener('load', () => {
                        fechas_pendientes()
                        $(this).off("click", `.switch-right, .switch-left`).on("click", `.switch-right, .switch-left`, fechas_pendientes)
                        marcar_fechas_calendario(this.querySelectorAll(".calendar_content div:not(:empty)"))
                    })
                }).then(() => {
                    $(__MODAL).off("click", ".switch-month").on("click", ".switch-month", function() { return marcar_fechas_calendario(select("#plantillaModal .calendar_content div:not(:empty)")) })
                })
                let FECHAS_CONTAINER = this.querySelector(`[data-app-identificador="HORAS-CONTAINER"]`)
                if (isset(FECHAS_CONTAINER)) {
                    FECHAS_CONTAINER.innerHTML = select("#TABLA_HORAS").innerHTML
                } else {
                    FECHAS_CONTAINER = this.querySelector(`[data-app-identificador="ACC_PER-CONTAINER"]`)
                    FECHAS_CONTAINER.innerHTML = select("#TABLA_ACC_PER").innerHTML
                }
                this.querySelector(".calendar_content").onclick = (ev) => {
                    const item = ev.target
                    if (item.matches(".font-weight-bold")) {
                        const clases = item.classList
                        if (clases.contains("bg-warning")) {
                            clases.remove("bg-warning")
                        }else {
                            clases.add("bg-warning")
                        }
                    }
                }
            }).off('hide.bs.collapse', `.collapse`).on('hide.bs.collapse', `.collapse`, function () {
                if (this.matches("#collapseFour") || this.matches("#collapseFive")) { return }
                this.querySelector(`[data-app-identificador="CALENDARIO-CONTAINER"]`).innerHTML = ""
            }).off('change', `[name="ID_PLANILLA"]`).on('change', `[name="ID_PLANILLA"]`, function () {
                const inputs = render_inputs_planilla(this.querySelector("option:checked").textContent.trim())
                const PLANILLAS_CONTAINER = select(`[data-app-planillas]`)
                PLANILLAS_CONTAINER.innerHTML = inputs
                $(PLANILLAS_CONTAINER).off("change", `[name="MONTO_BRUTO"], [name="REBAJO_CAJA"]`).on("change", `[name="MONTO_BRUTO"], [name="REBAJO_CAJA"]`, function (ev) {
                    const MONTO_BRUTO = PLANILLAS_CONTAINER.querySelector(`[name="MONTO_BRUTO"]`)
                    const REBAJO_CAJA = PLANILLAS_CONTAINER.querySelector(`[name="REBAJO_CAJA"]`)
                    const MONTO_NETO  = PLANILLAS_CONTAINER.querySelector(`[name="MONTO_NETO"]`)

                    MONTO_NETO.value = parseFloat(MONTO_BRUTO?.value) - parseFloat(MONTO_BRUTO?.value) * (parseFloat(REBAJO_CAJA?.value) / 100)
                    console.log(MONTO_NETO.value)
                })
            }).off('click', `#GUARDAR-HORAS`).on('click', `#GUARDAR-HORAS`, function () {
                // VALORES
                    const REBAJO_CAJA = select(`#collapseTwo [name="REBAJO_CAJA"]`)?.value.trim()
                    const CODIGO_PAGO = select("#CODIGO_PAGO")?.value.trim()
                    const EMPLEADO    = select("#CODIGO_SUSTITUO").textContent.trim()
                    const AUSENTE     = select(":not(.collapse) > #NOMBRE_AUSENTE", false).textContent.trim()
                    const CEDULA      = select("#IDENTIFICACION_SUSTITUO").textContent.trim()
                    const LUGAR       = select("#LUGAR").textContent.trim()
                    const NOMINA      = select("#NOMINA_SUSTITUO").textContent.trim()
                    const NOTA        = select("#NOTAS_PAGO")?.value.trim()
                    const ID_PLANILLA = select(`[name="ID_PLANILLA"]`)?.value
                    const SALARIO_POR_HORA = select(`[name="SALARIO_POR_HORA"]`)?.value
                    const MONTO_POR_DIA    = select(`[name="MONTO_POR_DIA"]`)?.value
                    const MOTIVO           = select(`[name="MOTIVO"]`)?.value
                    const TIPO_JORNADA =  select("#TIPO_JORNADA").textContent.trim()
                    const MONTO_BRUTO  = select(`[name="MONTO_BRUTO"]`)?.value
                    const MONTO_NETO   = select(`[name="MONTO_NETO"]`)?.value
                if (!empty(ID_PLANILLA) && empty(select(`[data-app-planillas]`).querySelectorAll(`input`))) {
                    return alerta["Info"]('No es posible guardar registro de liquidaciones desde el módulo actual').show()
                }
                if (CODIGO_PAGO == 'N') { return select("#CODIGO_PAGO").focus() }
                if (NOTA.length == 0) { return select("#NOTAS_PAGO").focus() }
                const HORAS = get_horas_laboradas()
                return guardar_horas({
                    CEDULA,
                    NOTA,
                    HORAS,
                    ID_PLANILLA,
                    ID_SUSTITUCION,
                    CODIGO_PAGO,
                    EMPLEADO,
                    NOMINA,
                    AUSENTE,
                    LUGAR,
                    REBAJO_CAJA,
                    SALARIO_POR_HORA,
                    MONTO_POR_DIA,
                    MOTIVO,
                    TIPO_JORNADA,
                    MONTO_BRUTO,
                    MONTO_NETO,
                })
            }).off('click', `#GUARDAR-ACCIONES`).on('click', `#GUARDAR-ACCIONES`, function () {
                const TIPO_ACCION = select("#TIPO_ACCION")
                const NOTA        = select("#NOTAS_REBAJO").value
                const CODIGO_PAGO = TIPO_ACCION.value
                const EMPLEADO = select("#CODIGO_AUSENTE").textContent
                const NOMINA = select("#NOMINA_AUSENTE").textContent

                const DIAS_REBAJAR = []
                let HORAS_REBAJAR  = 0

                if (TIPO_ACCION.querySelector("option:checked").getAttribute("data-tipo-accion") !== "RH") {
                    const FILAS = select("#ACC_PER tbody tr")
                    if (empty(FILAS)) { return alerta.Warning("No se han especificado fechas para rebajar").show() }
                    FILAS.forEach((tr) => {
                        const CELLS = tr.querySelectorAll("td")
                        DIAS_REBAJAR.push({
                            "DIAS_ACCION": CELLS[0].textContent,
                            "FECHA_RIGE":  moment(CELLS[1].textContent, "DD/MM/YYYY").format("YYYY-MM-DD"),
                            "FECHA_VENCE": moment(CELLS[2].textContent, "DD/MM/YYYY").format("YYYY-MM-DD"),
                        })
                    })
                } else {
                    HORAS_REBAJAR = select("#HORAS_REBAJAR").value
                    if (empty(HORAS_REBAJAR) || HORAS_REBAJAR == 0) { return alerta.Warning("No se han especificado horas para rebajar").show() }
                }
                return guardar_acciones({
                    NOTA,
                    DIAS_REBAJAR,
                    HORAS_REBAJAR,
                    ID_SUSTITUCION,
                    TIPO_ACCION: TIPO_ACCION.value,
                    EMPLEADO,
                    NOMINA,
                    PERMISO_AUSENCIA: select(`#PERMISO_AUSENCIA`)?.value,
                })
            })
        })
	})

    $('[data-app-filtro-fecha-rige]').daterangepicker({
        opens: 'left',
        locale: {
            format: 'DD/MM/YYYY',
            separator: ' - ',
            applyLabel: 'Aplicar',
            cancelLabel: 'Cancelar',
            fromLabel: 'Desde',
            toLabel: 'Hasta',
            customRangeLabel: 'Personalizado',
            daysOfWeek: ['Dom', 'Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb'],
            monthNames: ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'],
            firstDay: 1
        }
    }).val("");
})