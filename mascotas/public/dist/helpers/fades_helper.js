if (!function_exists("fadeOut")) {
  // Método para ocultar un elemento con efecto suave
  function fadeOut(element) {
    return new Promise((resolve, reject) => {
      let op = 1;  // opacidad inicial
      let timer = requestAnimationFrame(function fade() {
        if (op <= 0.1){
          cancelAnimationFrame(timer);
          element.style.display = 'none';
          element.style.opacity = 1;
          resolve(); // Resuelve la promesa cuando la animación termina
        } else {
          op -= 0.1;
          element.style.opacity = op;
          requestAnimationFrame(fade);
        }
      });
    });
  }
}

if (!function_exists("fadeIn")) {
  // Método para mostrar un elemento con efecto suave
  function fadeIn(element, display) {
    return new Promise((resolve, reject) => {
      let op = 0.1;  // opacidad inicial
      element.style.opacity = op;
      element.style.display = display || 'block';
      let timer = requestAnimationFrame(function fade() {
        if (op >= 1){
          cancelAnimationFrame(timer);
          resolve(); // Resuelve la promesa cuando la animación termina
        } else {
          op += 0.1;
          element.style.opacity = op;
          requestAnimationFrame(fade);
        }
      });
    });
  }
}

if (!function_exists("toggleFade")) {
  // Método para alternar entre mostrar u ocultar un elemento
  function toggleFade(element) {
    // Obtener el estilo computado del elemento
    let style = window.getComputedStyle(element);
    // Verificar si el elemento está oculto. si está oculto, usar fadeIn, si está visible, usar fadeOut
    return (style.display === 'none' || style.opacity === '0') ? fadeIn(element) : fadeOut(element) ;
  }
}