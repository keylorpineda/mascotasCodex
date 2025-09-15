window.EDITORES = window.EDITORES || {};
const init_easepicker = () => {
	document.querySelectorAll(`[data-app-easepick-range]`).forEach(el => {
	    let picker = new easepick.create({
	        element: el,
	        lang: 'es-ES',
	        css: [ base_url("public/dist/easepick/easepick.css"), ],
	        zIndex: 2020,
	        format: "DD/MM/YYYY",
	        grid: 2,
	        calendars: 2,
	        autoApply: true,
	        locale: {
	            cancel: "Cancelar",
	            apply: "Aplicar",
	        },
	        plugins: [ "RangePlugin", ],
            setup(picker) {
                picker.on('select', (event) => {
                    el.dispatchEvent(new Event("change"));
                });
            },
	    });

	    if (!el.hasAttribute(`data-app-easepick-no-clean`)) {
		    el.addEventListener('keydown', function(event) {
		        // Verifica si la tecla presionada es 'Backspace' o 'Delete'
		        if (event.key === 'Backspace' || event.key === 'Delete') {
		            // Borra el contenido del input y el rango de fechas seleccionado
		            this.value = '';
		            picker.clear();
		        }
		    });
	    }

	    el.readOnly = false;
	});

	document.querySelectorAll(`[data-app-easepick-single]`).forEach(el => {
		const picker = new easepick.create({
			element: el,
			lang: 'es-ES',
			css: [ base_url("public/dist/easepick/easepick.css"), ],
			zIndex: 2020,
			format: "DD/MM/YYYY",
			grid: 2,
			calendars: 1,
			autoApply: true,
		    locale: {
		        cancel: "Cancelar",
		        apply: "Aplicar"
		    },
            setup(picker) {
                picker.on('select', (event) => {
                    el.dispatchEvent(new Event("change"));
                });
            },
		});

	    // Agrega un evento de escucha al input
	    el.addEventListener('keydown', function(event) {
	        // Verifica si la tecla presionada es 'Backspace' o 'Delete'
	        if (event.key === 'Backspace' || event.key === 'Delete') {
	            // Borra el contenido del input y el rango de fechas seleccionado
	            this.value = '';
	            picker.clear();
	        }
	    });

	    el.readOnly = false;
	});
}, init_editor = () => {
	Object.entries(EDITORES).forEach(([k, v]) => {
		v.destroy();
		delete EDITORES[k];
	});
    const editorConfig = {
        toolbar: {
            items: [
                'heading',
                '|',
                'bold',
                'italic',
                'link',
                '|',
                'bulletedList',
                'numberedList',
                'outdent',
                'indent',
                '|',
                'blockQuote',
                'insertTable',
                '|',
                'undo',
                'redo',
                'selectAll'
            ]
        },
        removePlugins: [
            'ImageUpload',
            'ImageInsert',
            'MediaEmbed',
            'FileRepository',
            'CKFinderUploadAdapter',
            'CKFinder',
            'EasyImage',
            'CKBoxUploadAdapter',
            'CKBoxEditing',
            'CKBox',
        ]
    };
	document.querySelectorAll(`[data-app-editor-classic]`).forEach(
		(el, i) => ClassicEditor.create( el, editorConfig ).then( ckeditor => {
			EDITORES[el.getAttribute("id") || `editor${i}`] = ckeditor;
			editor = ckeditor;
			document.querySelector(`.ck-body-wrapper`).classList.add("d-none")
		}).catch( err => {
			console.error( err.stack );
		})
	);
}, init_dibujar_canvas = () => {
	const dbody = document.body;
	const scripts_pizarra_dibujo = document.querySelectorAll(`script[data-scr-pizarra-dibujo]`);
	if (!scripts_pizarra_dibujo.length) {
		scripts_pizarra_dibujo.forEach(scr => dbody.removeChild(src));
	}
	const scr = document.createElement("script");
	const attr_scr = {
		"data-scr-pizarra-dibujo": "",
		"src": base_url(`assets/dist/canvas/dibujar-canvas.js?v=${(Math.floor(Math.random()*60*60)*60)}`),
		"type": "text/javascript",
	};
	for (const key in attr_scr) {
		scr.setAttribute(key, attr_scr[key]);
	}
	dbody.appendChild(scr);
}