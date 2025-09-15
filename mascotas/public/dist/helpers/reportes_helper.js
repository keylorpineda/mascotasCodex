if (!function_exists("print_to_pdf")) {
	/**
	 * Abre una URL en una nueva ventana para generar/mostrar PDF
	 * @param {string} route - URL del endpoint que genera el PDF
	 * @param {Object} params - Parámetros a enviar
	 * @param {string} method - Método HTTP ('GET' o 'POST')
	 * @param {Object} options - Opciones adicionales para la ventana
	 * 
	 * @example
	 * // Caso GET - Parámetros simples
	 * print_to_pdf('/reports/sales', { date: '2024-01-01' });
	 * 
	 * // Caso POST - Datos sensibles
	 * print_to_pdf('/reports/payroll', { 
	 *   employees: [1,2,3], 
	 *   token: 'abc123' 
	 * }, 'POST');
	 */
	const print_to_pdf = (route = null, params = {}, method = 'GET', options = {}) => {
	    // Validación de entrada
	    if (!route || typeof route !== 'string') {
	        throw new Error('La ruta debe ser una cadena válida.');
	    }
	    
	    // Validar método
	    method = method.toUpperCase();
	    if (!['GET', 'POST'].includes(method)) {
	        throw new Error('El método debe ser GET o POST.');
	    }
	    
	    // Validar parámetros
	    if (params && typeof params !== 'object') {
	        throw new Error('Los parámetros deben ser un objeto.');
	    }
	    
	    // Opciones por defecto para la ventana
	    const defaultOptions = {
	        height: 600,
	        width: 800,
	        scrollbars: 'yes',
	        resizable: 'yes',
	        toolbar: 'no',
	        menubar: 'no',
	        location: 'no'
	    };
	    
	    const windowOptions = { ...defaultOptions, ...options };
	    const windowFeatures = Object.entries(windowOptions)
	        .map(([key, value]) => `${key}=${value}`)
	        .join(',');
	    
	    try {
	        if (method === 'GET') {
	            // Procesar parámetros para GET
	            const processedParams = {};
	            for (const [key, value] of Object.entries(params)) {
	                processedParams[key] = typeof value === 'object' 
	                    ? JSON.stringify(value) 
	                    : value;
	            }
	            
	            const queryString = new URLSearchParams(processedParams).toString();
	            const finalUrl = route + (queryString ? '?' + queryString : '');
	            
	            const newWindow = window.open(finalUrl, '_blank', windowFeatures);
	            
	            if (!newWindow) {
	                console.warn('El popup fue bloqueado. Verifica la configuración del navegador.');
	                return false;
	            }
	            
	        } else if (method === 'POST') {
	            // Crear formulario dinámico para POST
	            const form = document.createElement('form');
	            form.method = 'POST';
	            form.action = route;
	            form.target = '_blank';
	            form.style.display = 'none';
	            
	            // Agregar parámetros como inputs ocultos
	            for (const [key, value] of Object.entries(params)) {
	                const input = document.createElement('input');
	                input.type = 'hidden';
	                input.name = key;
	                input.value = typeof value === 'object' 
	                    ? JSON.stringify(value) 
	                    : value;
	                form.appendChild(input);
	            }
	            
	            document.body.appendChild(form);
	            
	            // Configurar ventana antes de enviar
	            const submitHandler = () => {
	                const newWindow = window.open('', '_blank', windowFeatures);
	                newWindow.name = "win_" + Math.floor(Math.random() * 10000);
	                if (!newWindow) {
	                    console.warn('El popup fue bloqueado. Verifica la configuración del navegador.');
	                    document.body.removeChild(form);
	                    return false;
	                }
	                form.target = newWindow.name;
	                console.log(form, newWindow);
	                form.submit();
	                
	                // Limpiar después de un pequeño delay
	                setTimeout(() => {
	                    if (document.body.contains(form)) {
	                        document.body.removeChild(form);
	                    }
	                }, 100);
	            };
	            
	            submitHandler();
	        }
	        
	        return true;
	        
	    } catch (error) {
	        console.error('Error al generar PDF:', error);
	        throw error;
	    }
	}

	// Registro global para compatibilidad
	if (typeof window !== 'undefined') {
	  window.print_to_pdf = print_to_pdf;
	}
}
if (!function_exists("print_to_printer")) {
	/**
	 * Imprime contenido desde una URL usando un iframe
	 * @param {string} route - URL del recurso a imprimir
	 * @param {Object} [data={}] - Datos a enviar con la petición
	 * @param {string} [method='GET'] - Método HTTP (GET o POST)
	 * @param {Object} [options={}] - Opciones adicionales
	 * @param {string} [options.containerSelector='#reportes-container'] - Selector del contenedor
	 * @param {number} [options.timeout=30000] - Timeout en milisegundos
	 * @param {boolean} [options.isDownload=false] - Si es una descarga de archivo
	 * @param {Function} [options.onSuccess] - Callback cuando se carga exitosamente
	 * @param {Function} [options.onError] - Callback cuando hay error
	 * @param {Function} [options.onAfterPrint] - Callback después de imprimir
	 * @returns {Promise<HTMLIFrameElement>} Promise que resuelve con el iframe creado
	 * 
	 * @example
	 * // GET request para mostrar datos
	 * print_to_printer('/report', { id: 123, format: 'html' })
	 *   .then(iframe => console.log('Reporte cargado'))
	 *   .catch(error => console.error('Error:', error));
	 * 
	 * // POST request para descarga
	 * print_to_printer('/report', { data: complexData }, 'POST', { isDownload: true })
	 *   .then(iframe => console.log('Descarga iniciada'));
	 */
	const print_to_printer = async (route, data = {}, method = 'GET', options = {}) => {
	  // Configuración por defecto
	  const config = {
	    containerSelector: '#reportes',
	    timeout: 30000,
	    isDownload: false,
	    onSuccess: null,
	    onError: null,
	    onAfterPrint: null,
	    ...options
	  };

	  // Validaciones
	  if (!route || typeof route !== 'string') {
	    throw new Error('La ruta es requerida y debe ser una cadena válida');
	  }

	  if (!['GET', 'POST'].includes(method.toUpperCase())) {
	    throw new Error('El método debe ser GET o POST');
	  }

	  try {
	    const container = getContainer(config.containerSelector);
	    clearContainer(container);

	    const iframe = await createAndLoadIframe(route, data, method.toUpperCase(), container, config);
	    
	    setupPrintHandlers(iframe, container, config);
	    
	    if (config.onSuccess) {
	      config.onSuccess(iframe);
	    }

	    return iframe;

	  } catch (error) {
	    console.error('Error en print_to_printer:', error);
	    
	    if (config.onError) {
	      config.onError(error);
	    }
	    
	    throw error;
	  }
	},

	/**
	 * Obtiene el contenedor donde se insertará el iframe
	 * @param {string} containerSelector - Selector del contenedor
	 * @returns {HTMLElement} Elemento contenedor
	 */
	getContainer = (containerSelector) => {
	  const container = document.querySelector(containerSelector);
	  
	  if (!container) {
	    throw new Error(`Contenedor no encontrado: ${containerSelector}`);
	  }
	  
	  return container;
	},

	/**
	 * Limpia el contenido del contenedor
	 * @param {HTMLElement} container - Contenedor a limpiar
	 */
	clearContainer = (container) => {
	  // Limpiar listeners previos
	  cleanupPreviousListeners();
	  
	  // Limpiar contenido
	  container.innerHTML = '';
	},

	/**
	 * Crea y carga el iframe según el método HTTP
	 * @param {string} route - URL del recurso
	 * @param {Object} data - Datos a enviar
	 * @param {string} method - Método HTTP
	 * @param {HTMLElement} container - Contenedor
	 * @param {Object} config - Configuración
	 * @returns {Promise<HTMLIFrameElement>} Promise con el iframe
	 */
	createAndLoadIframe = (route, data, method, container, config) => {
	  return new Promise((resolve, reject) => {
	    const iframe = document.createElement('iframe');
	    iframe.style.width = '100%';
	    iframe.style.height = '600px';
	    iframe.style.border = 'none';
	    iframe.setAttribute('title', 'Documento para imprimir');
	    
	    let resolved = false;
	    
	    // Timeout para evitar esperas indefinidas
	    const timeoutId = setTimeout(() => {
	      if (!resolved) {
	        resolved = true;
	        reject(new Error('Timeout: El documento tardó demasiado en cargar'));
	      }
	    }, config.timeout);

	    const resolveIframe = () => {
	      if (!resolved) {
	        resolved = true;
	        clearTimeout(timeoutId);
	        resolve(iframe);
	      }
	    };

	    const rejectIframe = (error) => {
	      if (!resolved) {
	        resolved = true;
	        clearTimeout(timeoutId);
	        reject(error);
	      }
	    };

	    if (config.isDownload) {
	      // Para descargas, usar estrategia específica
	      handleDownloadIframe(iframe, route, data, method, container, resolveIframe, rejectIframe);
	    } else {
	      // Para visualización normal
	      handleDisplayIframe(iframe, route, data, method, container, resolveIframe, rejectIframe);
	    }
	  });
	},

	/**
	 * Maneja iframe para visualización de contenido
	 * @param {HTMLIFrameElement} iframe - Iframe a manejar
	 * @param {string} route - URL del recurso
	 * @param {Object} data - Datos a enviar
	 * @param {string} method - Método HTTP
	 * @param {HTMLElement} container - Contenedor
	 * @param {Function} resolveIframe - Función para resolver
	 * @param {Function} rejectIframe - Función para rechazar
	 */
	handleDisplayIframe = (iframe, route, data, method, container, resolveIframe, rejectIframe) => {
	  // iframe.onload = () => {
	  //   try {
	  //     // Verificar si el documento está completamente cargado
	  //     const iframeDocument = iframe.contentDocument || iframe.contentWindow.document;
	  //     if (iframeDocument.readyState === 'complete') {
	  //       resolveIframe();
	  //     } else {
	  //       // Esperar a que el documento esté completo
	  //       iframeDocument.addEventListener('readystatechange', () => {
	  //         if (iframeDocument.readyState === 'complete') {
	  //           resolveIframe();
	  //         }
	  //       });
	  //     }
	  //   } catch (error) {
	  //     // Problemas de CORS, resolver de todas formas
	  //     resolveIframe();
	  //   }
	  // };
		iframe.onload = () => {
		    try {
		      const iframeDocument = iframe.contentDocument || iframe.contentWindow.document;
		      
		      // Verificar errores antes de resolver
		      if (iframeDocument && iframeDocument.body) {
		        const bodyText = iframeDocument.body.textContent || iframeDocument.body.innerText;
		        try {
		            // Intentar parsear como JSON (tu backend retorna JSON)
		            const jsonData = JSON.parse(bodyText);
		            // Si es una respuesta de error estructurada
		            if (typeof jsonData === "object" && jsonData["MENSAJE"]) {
		              downloadDetected = true;
		              // const errorMessage = jsonData.message || jsonData.error || 'Error desconocido';
		              rejectIframe(jsonData);
		              return;
		            }
		            throw new Error(jsonData);
		        } catch (parseError) {
		            // Si no es JSON válido, verificar por códigos HTTP o texto
		            if (
		            	bodyText.includes('403') || 
		                bodyText.includes('Forbidden') || 
		                bodyText.includes('No tienes permisos') ||
		                bodyHTML.includes('error') || 
		                bodyHTML.includes('danger')
		            ) {
						downloadDetected = true;
						rejectIframe(bodyText.trim() || 'Error desconocido');
						return;
		            }
		        }
		      }
		      
		      // Si no hay errores, proceder normalmente
		      if (iframeDocument.readyState === 'complete') {
		        resolveIframe();
		      } else {
		        iframeDocument.addEventListener('readystatechange', () => {
		          if (iframeDocument.readyState === 'complete') {
		            resolveIframe();
		          }
		        });
		      }
		    } catch (error) {
		      // Problemas de CORS, resolver de todas formas
		      resolveIframe();
		    }
		  };
	  iframe.onerror = () => {
	    rejectIframe(new Error('Error al cargar el documento'));
	  };

	  if (method === 'GET') {
	    loadIframeWithGet(iframe, route, data, container);
	  } else {
	    loadIframeWithPost(iframe, route, data, container);
	  }
	},

	/**
	 * Maneja iframe para descargas
	 * @param {HTMLIFrameElement} iframe - Iframe a manejar
	 * @param {string} route - URL del recurso
	 * @param {Object} data - Datos a enviar
	 * @param {string} method - Método HTTP
	 * @param {HTMLElement} container - Contenedor
	 * @param {Function} resolveIframe - Función para resolver
	 * @param {Function} rejectIframe - Función para rechazar
	 */
	handleDownloadIframe = (iframe, route, data, method, container, resolveIframe, rejectIframe) => {
	  // Generar token único para esta descarga
	  const downloadToken = 'download_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
	  
	  // Añadir el token a los datos
	  let modifiedRoute = route;
	  let modifiedData = { ...data };
	  
	  if (method === 'GET') {
	    const separator = route.includes('?') ? '&' : '?';
	    modifiedRoute = `${route}${separator}downloadToken=${downloadToken}`;
	  } else {
	    modifiedData.downloadToken = downloadToken;
	  }

	  // Estrategia múltiple para detectar descarga
	  let downloadDetected = false;
	  let attempts = 0;
	  const maxAttempts = 100; // 10 segundos máximo (100 * 100ms)

	  const checkDownload = () => {
	    attempts++;
	    
	    // Método 1: Verificar cookie de descarga
	    const cookieExists = document.cookie
	      .split('; ')
	      .some(cookie => cookie.startsWith(`downloadComplete_${downloadToken}=`));
	    
	    if (cookieExists) {
	      // Limpiar la cookie
	      document.cookie = `downloadComplete_${downloadToken}=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;`;
	      downloadDetected = true;
	      resolveIframe();
	      return;
	    }

	    // Método 2: Verificar si el iframe está vacío (indicativo de descarga)
	    try {
	      const iframeDoc = iframe.contentDocument || iframe.contentWindow.document;
	      if (iframeDoc && iframeDoc.body && 
	          (iframeDoc.body.innerHTML.trim() === '' || iframeDoc.body.children.length === 0)) {
	        downloadDetected = true;
	        resolveIframe();
	        return;
	      }
	    } catch (e) {
	      // Error de CORS, probablemente descarga exitosa
	      downloadDetected = true;
	      resolveIframe();
	      return;
	    }

	    // Continuar verificando si no se ha detectado descarga
	    if (attempts < maxAttempts && !downloadDetected) {
	      setTimeout(checkDownload, 100);
	    }
	  };

	  // Configurar eventos del iframe
	  // iframe.onload = () => {
	  //   if (!downloadDetected) {
	  //     // Para descargas, resolver después de un breve delay
	  //     setTimeout(() => {
	  //       if (!downloadDetected) {
	  //         downloadDetected = true;
	  //         resolveIframe();
	  //       }
	  //     }, 500);
	  //   }
	  // };

	  iframe.onload = () => {
	    try {
	      const iframeDoc = iframe.contentDocument || iframe.contentWindow.document;
	      
	      if (iframeDoc && iframeDoc.body) {
	        // Verificar si hay contenido de error
	        const bodyText = iframeDoc.body.textContent || iframeDoc.body.innerText;
	        const bodyHTML = iframeDoc.body.innerHTML;
	        
	        // Si hay contenido visible (no descarga), verificar si es error
	        if (bodyText.trim() && bodyHTML.trim()) {
	          try {
	            // Intentar parsear como JSON (tu backend retorna JSON)
	            const jsonData = JSON.parse(bodyText);
	            // Si es una respuesta de error estructurada
	            if (typeof jsonData === "object" && jsonData["MENSAJE"]) {
	              downloadDetected = true;
	              // const errorMessage = jsonData.message || jsonData.error || 'Error desconocido';
	              rejectIframe(jsonData);
	              return;
	            }
	            throw new Error(jsonData);
	          } catch (parseError) {
	            // Si no es JSON válido, verificar por códigos HTTP o texto
	            if (
	            	bodyText.includes('403') || 
	                bodyText.includes('Forbidden') || 
	                bodyText.includes('No tienes permisos') ||
	                bodyHTML.includes('error') || 
	                bodyHTML.includes('danger')
	            ) {
					downloadDetected = true;
					rejectIframe(bodyText.trim() || 'Error desconocido');
					return;
	            }
	          }
	        }
	      }
	      
	      // Si llegamos aquí, asumir que es descarga exitosa
	      if (!downloadDetected) {
	        setTimeout(() => {
	          if (!downloadDetected) {
	            downloadDetected = true;
	            resolveIframe();
	          }
	        }, 500);
	      }
	      
	    } catch (error) {
	      // Error de CORS, probablemente descarga exitosa
	      if (!downloadDetected) {
	        downloadDetected = true;
	        resolveIframe();
	      }
	    }
	  };
	  iframe.onerror = () => {
	    if (!downloadDetected) {
	      rejectIframe(new Error('Error en la descarga'));
	    }
	  };

	  // Cargar el iframe
	  if (method === 'GET') {
	    loadIframeWithGet(iframe, modifiedRoute, modifiedData, container);
	  } else {
	    loadIframeWithPost(iframe, modifiedRoute, modifiedData, container);
	  }

	  // Iniciar verificación de descarga después de un pequeño delay
	  setTimeout(() => {
	    if (!downloadDetected) {
	      checkDownload();
	    }
	  }, 1000);
	},

	/**
	 * Carga iframe usando método GET
	 * @param {HTMLIFrameElement} iframe - Iframe a cargar
	 * @param {string} route - URL base
	 * @param {Object} data - Parámetros query
	 * @param {HTMLElement} container - Contenedor
	 */
	loadIframeWithGet = (iframe, route, data, container) => {
	  try {
	    const url = new URL(route, window.location.origin);
	    
	    // Agregar parámetros query
	    Object.entries(data).forEach(([key, value]) => {
	      if (value !== null && value !== undefined) {
	        url.searchParams.append(key, String(value));
	      }
	    });

	    iframe.src = url.toString();
	    container.appendChild(iframe);
	    
	  } catch (error) {
	    throw new Error(`URL inválida: ${route}`);
	  }
	},

	/**
	 * Carga iframe usando método POST
	 * @param {HTMLIFrameElement} iframe - Iframe a cargar
	 * @param {string} route - URL destino
	 * @param {Object} data - Datos del formulario
	 * @param {HTMLElement} container - Contenedor
	 */
	loadIframeWithPost = (iframe, route, data, container) => {
	  const iframeName = `print_iframe_${Date.now()}`;
	  iframe.name = iframeName;
	  container.appendChild(iframe);

	  const form = createPostForm(route, data, iframeName);
	  
	  // Agregar form temporalmente al DOM
	  document.body.appendChild(form);
	  
	  // Enviar formulario
	  form.submit();
	  
	  // Limpiar formulario después de un breve delay
	  setTimeout(() => {
	    if (form.parentNode) {
	      form.parentNode.removeChild(form);
	    }
	  }, 100);
	},

	/**
	 * Crea un formulario POST para enviar datos al iframe
	 * @param {string} action - URL destino
	 * @param {Object} data - Datos del formulario
	 * @param {string} target - Nombre del iframe destino
	 * @returns {HTMLFormElement} Formulario creado
	 */
	createPostForm = (action, data, target) => {
	  const form = document.createElement('form');
	  form.method = 'POST';
	  form.action = action;
	  form.target = target;
	  form.style.display = 'none';

	  // Agregar campos del formulario
	  Object.entries(data).forEach(([key, value]) => {
	    if (value !== null && value !== undefined) {
	      const input = document.createElement('input');
	      input.type = 'hidden';
	      input.name = key;
	      input.value = typeof value === 'object' ? JSON.stringify(value) : String(value);
	      form.appendChild(input);
	    }
	  });

	  return form;
	},

	/**
	 * Configura los manejadores de eventos de impresión
	 * @param {HTMLIFrameElement} iframe - Iframe a manejar
	 * @param {HTMLElement} container - Contenedor
	 * @param {Object} config - Configuración
	 */
	setupPrintHandlers = (iframe, container, config) => {
	  const printHandler = () => {
	    try {
	      // Limpiar iframe del contenedor
	      if (iframe.parentNode) {
	        iframe.parentNode.removeChild(iframe);
	      }
	      
	      // Ejecutar callback personalizado
	      if (config.onAfterPrint) {
	        config.onAfterPrint();
	      }
	      
	    } catch (error) {
	      console.error('Error al limpiar después de imprimir:', error);
	    }
	  };

	  // Guardar referencia para limpieza posterior
	  iframe._printHandler = printHandler;
	  
	  // Agregar listener
	  window.addEventListener('afterprint', printHandler);
	},

	/**
	 * Limpia listeners previos para evitar memory leaks
	 */
	cleanupPreviousListeners = () => {
	  // Remover listeners anteriores si existen
	  const existingIframes = document.querySelectorAll('iframe[name^="print_iframe"]');
	  existingIframes.forEach(iframe => {
	    if (iframe._printHandler) {
	      window.removeEventListener('afterprint', iframe._printHandler);
	    }
	  });
	};

	// Registro global para compatibilidad
	if (typeof window !== 'undefined') {
	  window.print_to_printer = print_to_printer;
	}
}