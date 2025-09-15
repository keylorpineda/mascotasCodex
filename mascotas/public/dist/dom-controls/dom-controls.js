  /**
   * Inicializa un grupo de checkboxes con funcionalidad "seleccionar todos"
   * @param {string} masterSelector - Selector del checkbox master
   * @param {string} itemSelector - Selector de los checkboxes hijos
   */
  function initCheckboxGroup(masterSelector, itemSelector) {
    // 1. Selección de elementos con validación
    const masterChk = document.querySelector(masterSelector);
    const itemChks = document.querySelectorAll(itemSelector);
    
    // Validación de elementos
    if (!masterChk) {
      console.error('Master checkbox no encontrado con selector:', masterSelector);
      return;
    }
    
    if (itemChks.length === 0) {
      console.warn('No se encontraron checkboxes hijos con selector:', itemSelector);
      return;
    }
    
    /**
     * Actualiza el estado del master checkbox basado en los hijos
     */
    function updateMasterState() {
      const checkedCount = Array.from(itemChks).filter(c => c.checked).length;
      const totalCount = itemChks.length;
      
      if (checkedCount === 0) {
        masterChk.checked = false;
        masterChk.indeterminate = false;
      } else if (checkedCount === totalCount) {
        masterChk.checked = true;
        masterChk.indeterminate = false;
      } else {
        masterChk.checked = false;
        masterChk.indeterminate = true;
      }
    }
    
    // 2. Al cambiar el master, togglear todos los hijos
    masterChk.addEventListener('change', () => {
      const estado = masterChk.checked;
      itemChks.forEach(chk => chk.checked = estado);
      
      // Limpiar estado indeterminado después de la acción manual
      masterChk.indeterminate = false;
    });
    
    // 3. Cada vez que un hijo cambie, actualizamos el master
    itemChks.forEach(chk => {
      chk.addEventListener('change', updateMasterState);
    });
    
    // 4. Establecer estado inicial
    updateMasterState();
    
    // Retornar objeto con métodos útiles (opcional)
    return {
      master: masterChk,
      items: itemChks,
      selectAll: () => {
        itemChks.forEach(chk => chk.checked = true);
        updateMasterState();
      },
      deselectAll: () => {
        itemChks.forEach(chk => chk.checked = false);
        updateMasterState();
      },
      getSelectedCount: () => Array.from(itemChks).filter(c => c.checked).length,
      getTotalCount: () => itemChks.length
    };
  }
