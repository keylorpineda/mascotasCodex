// Traemos el canvas y otros elementos interactivos mediante el data attribute del elemento HTML
let canvas = document.querySelector(`[data-canvas-pizarra-dibujo]`);
let punta = document.querySelector(`[data-canvas-pizarra-puntero]`);
let tint = document.querySelector(`[data-canvas-pizarra-color]`);
let btn_reset = document.querySelector(`[data-canvas-pizarra-limpiar]`);

(function() {
    // Obtenemos un intervalo regular (Tiempo) en la pantalla
    window.requestAnimFrame = (function(callback) {
        return window.requestAnimationFrame ||
        window.webkitRequestAnimationFrame ||
        window.mozRequestAnimationFrame ||
        window.oRequestAnimationFrame ||
        window.msRequestAnimationFrame ||
        function(callback) {
            window.setTimeout(callback, 1000/60); // Retrasa la ejecución de la función para mejorar la experiencia
        };
    })();

    let ctx = canvas.getContext("2d");

    // Variables para el estado del dibujo
    let drawing = false;
    let mousePos = { x: 0, y: 0 };
    let lastPos = mousePos;

    // Eventos del mouse
    canvas.addEventListener("mousedown", function(e) {
        drawing = true;
        lastPos = getMousePos(canvas, e);
    }, false);

    canvas.addEventListener("mouseup", function(e) {
        drawing = false;
    }, false);

    canvas.addEventListener("mousemove", function(e) {
        mousePos = getMousePos(canvas, e);
    }, false);

    // Eventos del touch
    canvas.addEventListener("touchstart", function(e) {
        mousePos = getTouchPos(canvas, e);
        e.preventDefault(); // Evita el desplazamiento al tocar el canvas
        let touch = e.touches[0];
        let mouseEvent = new MouseEvent("mousedown", {
            clientX: touch.clientX,
            clientY: touch.clientY
        });
        canvas.dispatchEvent(mouseEvent);
    }, false);

    canvas.addEventListener("touchend", function(e) {
        e.preventDefault(); // Evita el desplazamiento al tocar el canvas
        let mouseEvent = new MouseEvent("mouseup", {});
        canvas.dispatchEvent(mouseEvent);
    }, false);

    canvas.addEventListener("touchleave", function(e) {
        // Realiza el mismo proceso que touchend en caso de que el dedo se deslice fuera del canvas
        e.preventDefault(); // Evita el desplazamiento al tocar el canvas
        let mouseEvent = new MouseEvent("mouseup", {});
        canvas.dispatchEvent(mouseEvent);
    }, false);

    canvas.addEventListener("touchmove", function(e) {
        e.preventDefault(); // Evita el desplazamiento al tocar el canvas
        let touch = e.touches[0];
        let mouseEvent = new MouseEvent("mousemove", {
            clientX: touch.clientX,
            clientY: touch.clientY
        });
        canvas.dispatchEvent(mouseEvent);
    }, false);

    // Obtener la escala del canvas
    function getScale() {
        return {
            x: canvas.width / canvas.offsetWidth,
            y: canvas.height / canvas.offsetHeight
        };
    }

    // Obtener la posición del mouse relativa al canvas
    function getMousePos(canvasDom, mouseEvent) {
        let rect = canvasDom.getBoundingClientRect();
        let scale = getScale();
        return {
            x: (mouseEvent.clientX - rect.left) * scale.x,
            y: (mouseEvent.clientY - rect.top) * scale.y
        };
    }

    // Obtener la posición del toque relativa al canvas
    function getTouchPos(canvasDom, touchEvent) {
        let rect = canvasDom.getBoundingClientRect();
        let scale = getScale();
        return {
            x: (touchEvent.touches[0].clientX - rect.left) * scale.x,
            y: (touchEvent.touches[0].clientY - rect.top) * scale.y
        };
    }

    // Dibujar en el canvas
    function renderCanvas() {
        if (drawing) {
            ctx.strokeStyle = tint.value;
            ctx.beginPath();
            ctx.moveTo(lastPos.x, lastPos.y);
            ctx.lineTo(mousePos.x, mousePos.y);
            ctx.lineWidth = punta.value;
            ctx.stroke();
            ctx.closePath();
            lastPos = mousePos;
        }
    }

    // Limpiar el canvas
    function clearCanvas() {
        ctx.clearRect(0, 0, canvas.width, canvas.height);
    }

    // Permitir la animación
    (function drawLoop() {
        requestAnimFrame(drawLoop);
        renderCanvas();
    })();

    // Event listener para el botón de limpiar
    if (btn_reset) {
        btn_reset.addEventListener("click", function(e) {
            clearCanvas();
        }, false);
    }
})();