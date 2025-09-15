class DOMQueryBuilder {
  constructor(selectors = []) {
    this.selectors = Array.isArray(selectors) ? selectors : [selectors];
    this.conditions = [];
    this.orConditions = [];
    this.notConditions = [];
    this.sortConfigs = [];
    this.limitCount = null;
    this.offsetCount = 0;
    this.groupByAttribute = null;
    this.havingConditions = [];
    this.distinctAttribute = null;
    this.joinConfigs = [];
    this.fromContainer = null;
    this.unionQueries = [];
    this.subqueries = new Map();
  }

  // Método estático para iniciar la consulta
  static select(...selectors) {
    return new DOMQueryBuilder(selectors);
  }

  // FROM - especificar contenedor base
  from(containerSelector) {
    this.fromContainer = containerSelector;
    return this;
  }

  // WHERE - condiciones AND
  where(attribute, operator = '=', value = null) {
    if (arguments.length === 1 && typeof attribute === 'object') {
      Object.entries(attribute).forEach(([attr, val]) => {
        this.conditions.push({ attribute: attr, operator: '=', value: val });
      });
    } else if (arguments.length === 2) {
      // where("name", "value") - operador implícito =
      this.conditions.push({ attribute, operator: '=', value: operator });
    } else if (arguments.length === 1) {
      // where("required") - solo verificar existencia
      this.conditions.push({ attribute, operator: 'exists', value: null });
    } else {
      this.conditions.push({ attribute, operator, value });
    }
    return this;
  }

  // OR WHERE
  orWhere(attribute, operator = '=', value = null) {
    if (arguments.length === 1 && typeof attribute === 'object') {
      Object.entries(attribute).forEach(([attr, val]) => {
        this.orConditions.push({ attribute: attr, operator: '=', value: val });
      });
    } else if (arguments.length === 2) {
      this.orConditions.push({ attribute, operator: '=', value: operator });
    } else if (arguments.length === 1) {
      this.orConditions.push({ attribute, operator: 'exists', value: null });
    } else {
      this.orConditions.push({ attribute, operator, value });
    }
    return this;
  }

  // WHERE NOT
  whereNot(attribute, operator = '=', value = null) {
    if (arguments.length === 2) {
      this.notConditions.push({ attribute, operator: '=', value: operator, negate: true });
    } else if (arguments.length === 1) {
      this.notConditions.push({ attribute, operator: 'exists', value: null, negate: true });
    } else {
      this.notConditions.push({ attribute, operator, value, negate: true });
    }
    return this;
  }

  // WHERE IN
  whereIn(attribute, values) {
    this.conditions.push({ attribute, operator: 'in', value: values });
    return this;
  }

  // WHERE NOT IN
  whereNotIn(attribute, values) {
    this.conditions.push({ attribute, operator: 'not_in', value: values });
    return this;
  }

  // WHERE BETWEEN
  whereBetween(attribute, min, max) {
    this.conditions.push({ attribute, operator: 'between', value: [min, max] });
    return this;
  }

  // WHERE LIKE (contiene)
  whereLike(attribute, pattern) {
    this.conditions.push({ attribute, operator: 'like', value: pattern });
    return this;
  }

  // WHERE NULL
  whereNull(attribute) {
    this.conditions.push({ attribute, operator: 'null', value: null });
    return this;
  }

  // WHERE NOT NULL
  whereNotNull(attribute) {
    this.conditions.push({ attribute, operator: 'not_null', value: null });
    return this;
  }

  // ORDER BY - soporta múltiples columnas
  orderBy(attribute, direction = 'ASC') {
    this.sortConfigs.push({ attribute, direction: direction.toUpperCase() });
    return this;
  }

  // ORDER BY múltiples columnas de una vez
  orderByMultiple(configs) {
    configs.forEach(config => {
      if (typeof config === 'string') {
        this.sortConfigs.push({ attribute: config, direction: 'ASC' });
      } else {
        this.sortConfigs.push({ attribute: config.attribute, direction: (config.direction || 'ASC').toUpperCase() });
      }
    });
    return this;
  }

  // GROUP BY
  groupBy(attribute) {
    this.groupByAttribute = attribute;
    return this;
  }

  // HAVING (para usar después de GROUP BY)
  having(attribute, operator, value) {
    this.havingConditions.push({ attribute, operator, value });
    return this;
  }

  // DISTINCT
  distinct(attribute = null) {
    this.distinctAttribute = attribute;
    return this;
  }

  // LIMIT
  limit(count) {
    this.limitCount = count;
    return this;
  }

  // OFFSET
  offSet(count) {
    this.offsetCount = count;
    return this;
  }

  // INNER JOIN - buscar elementos relacionados
  innerJoin(targetSelector, localAttribute, targetAttribute) {
    this.joinConfigs.push({
      type: 'inner',
      targetSelector,
      localAttribute,
      targetAttribute
    });
    return this;
  }

  // LEFT JOIN
  leftJoin(targetSelector, localAttribute, targetAttribute) {
    this.joinConfigs.push({
      type: 'left',
      targetSelector,
      localAttribute,
      targetAttribute
    });
    return this;
  }

  // RIGHT JOIN
  rightJoin(targetSelector, localAttribute, targetAttribute) {
    this.joinConfigs.push({
      type: 'right',
      targetSelector,
      localAttribute,
      targetAttribute
    });
    return this;
  }

  // UNION - combinar con otra query
  union(otherQuery) {
    this.unionQueries.push({ type: 'union', query: otherQuery });
    return this;
  }

  // UNION ALL
  unionAll(otherQuery) {
    this.unionQueries.push({ type: 'union_all', query: otherQuery });
    return this;
  }

  // INTERSECT - elementos que están en ambas queries
  intersect(otherQuery) {
    this.unionQueries.push({ type: 'intersect', query: otherQuery });
    return this;
  }

  // EXCEPT - elementos que están en esta query pero no en la otra
  except(otherQuery) {
    this.unionQueries.push({ type: 'except', query: otherQuery });
    return this;
  }

  // Subquery - para consultas anidadas
  whereExists(subqueryCallback) {
    const subquery = new DOMQueryBuilder();
    subqueryCallback(subquery);
    this.conditions.push({ type: 'subquery', operator: 'exists', subquery });
    return this;
  }

  // WHERE IN subquery
  whereInSubquery(attribute, subqueryCallback) {
    const subquery = new DOMQueryBuilder();
    subqueryCallback(subquery);
    this.conditions.push({ attribute, type: 'subquery', operator: 'in', subquery });
    return this;
  }

  // Verificar condiciones
  _checkCondition(element, condition) {
    if (condition.type === 'subquery') {
      const subResults = condition.subquery.getAll();
      if (condition.operator === 'exists') {
        return subResults.length > 0;
      } else if (condition.operator === 'in') {
        const elementValue = element.getAttribute(condition.attribute);
        return subResults.some(subEl => subEl.getAttribute(condition.attribute) === elementValue);
      }
    }

    const { attribute, operator, value, negate } = condition;
    let result = false;

    switch (operator) {
      case '=':
      case 'eq':
        result = element.getAttribute(attribute) === value;
        break;
      case '!=':
      case 'ne':
        result = element.getAttribute(attribute) !== value;
        break;
      case '>':
      case 'gt':
        result = parseFloat(element.getAttribute(attribute)) > parseFloat(value);
        break;
      case '>=':
      case 'gte':
        result = parseFloat(element.getAttribute(attribute)) >= parseFloat(value);
        break;
      case '<':
      case 'lt':
        result = parseFloat(element.getAttribute(attribute)) < parseFloat(value);
        break;
      case '<=':
      case 'lte':
        result = parseFloat(element.getAttribute(attribute)) <= parseFloat(value);
        break;
      case 'like':
        const attrValue = element.getAttribute(attribute) || '';
        const pattern = value.replace(/%/g, '.*').replace(/_/g, '.');
        result = new RegExp(pattern, 'i').test(attrValue);
        break;
      case 'in':
        result = value.includes(element.getAttribute(attribute));
        break;
      case 'not_in':
        result = !value.includes(element.getAttribute(attribute));
        break;
      case 'between':
        const elemValue = parseFloat(element.getAttribute(attribute));
        result = elemValue >= value[0] && elemValue <= value[1];
        break;
      case 'exists':
        result = element.hasAttribute(attribute);
        break;
      case 'null':
        result = !element.hasAttribute(attribute) || element.getAttribute(attribute) === null;
        break;
      case 'not_null':
        result = element.hasAttribute(attribute) && element.getAttribute(attribute) !== null;
        break;
      default:
        result = element.getAttribute(attribute) === value;
    }

    return negate ? !result : result;
  }

  // Verificar si elemento cumple condiciones
  _matchesConditions(element) {
    const andMatch = this.conditions.length === 0 || this.conditions.every(condition => {
      return this._checkCondition(element, condition);
    });

    const orMatch = this.orConditions.length === 0 || this.orConditions.some(condition => {
      return this._checkCondition(element, condition);
    });

    const notMatch = this.notConditions.length === 0 || this.notConditions.every(condition => {
      return this._checkCondition(element, condition);
    });

    return andMatch && orMatch && notMatch;
  }

  // Aplicar JOINs
  _applyJoins(elements) {
    if (this.joinConfigs.length === 0) return elements;

    let result = elements;

    this.joinConfigs.forEach(joinConfig => {
      const { type, targetSelector, localAttribute, targetAttribute } = joinConfig;
      const targetElements = Array.from(document.querySelectorAll(targetSelector));

      if (type === 'inner') {
        result = result.filter(element => {
          const localValue = element.getAttribute(localAttribute);
          return targetElements.some(target => target.getAttribute(targetAttribute) === localValue);
        });
      } else if (type === 'left') {
        // En LEFT JOIN mantenemos todos los elementos locales
        result = result.map(element => {
          const localValue = element.getAttribute(localAttribute);
          const matchedTarget = targetElements.find(target => target.getAttribute(targetAttribute) === localValue);
          // Podrías agregar datos del target al elemento si fuera necesario
          return element;
        });
      }
      // RIGHT JOIN sería más complejo de implementar en DOM
    });

    return result;
  }

  // Aplicar agrupación
  _applyGroupBy(elements) {
    if (!this.groupByAttribute) return { ungrouped: elements };

    const groups = {};
    elements.forEach(element => {
      const groupValue = element.getAttribute(this.groupByAttribute) || 'null';
      if (!groups[groupValue]) groups[groupValue] = [];
      groups[groupValue].push(element);
    });

    // Aplicar HAVING si existe
    if (this.havingConditions.length > 0) {
      Object.keys(groups).forEach(groupKey => {
        const groupElements = groups[groupKey];
        const groupPasses = this.havingConditions.every(condition => {
          // Ejemplo: having('count', '>', 5) - contar elementos en el grupo
          if (condition.attribute === 'count') {
            return this._checkNumericCondition(groupElements.length, condition.operator, condition.value);
          }
          // Más condiciones HAVING se pueden agregar aquí
          return true;
        });
        
        if (!groupPasses) {
          delete groups[groupKey];
        }
      });
    }

    return groups;
  }

  // Verificar condición numérica para HAVING
  _checkNumericCondition(actualValue, operator, expectedValue) {
    switch (operator) {
      case '>': return actualValue > expectedValue;
      case '>=': return actualValue >= expectedValue;
      case '<': return actualValue < expectedValue;
      case '<=': return actualValue <= expectedValue;
      case '=': return actualValue === expectedValue;
      case '!=': return actualValue !== expectedValue;
      default: return false;
    }
  }

  // Aplicar DISTINCT
  _applyDistinct(elements) {
    if (!this.distinctAttribute) return elements;

    const seen = new Set();
    return elements.filter(element => {
      const value = element.getAttribute(this.distinctAttribute);
      if (seen.has(value)) return false;
      seen.add(value);
      return true;
    });
  }

  // Aplicar ordenamiento múltiple
  _applySorting(elements) {
    if (this.sortConfigs.length === 0) return elements;

    return elements.sort((a, b) => {
      for (const { attribute, direction } of this.sortConfigs) {
        const valueA = this._getSortValue(a, attribute);
        const valueB = this._getSortValue(b, attribute);
        
        let comparison = 0;
        if (valueA < valueB) comparison = -1;
        else if (valueA > valueB) comparison = 1;
        
        if (comparison !== 0) {
          return direction === 'DESC' ? -comparison : comparison;
        }
      }
      return 0;
    });
  }

  // Aplicar UNION operations
  _applyUnions(elements) {
    if (this.unionQueries.length === 0) return elements;

    let result = [...elements];

    this.unionQueries.forEach(({ type, query }) => {
      const otherElements = query.getAll();
      
      switch (type) {
        case 'union':
          // UNION - combinar sin duplicados
          otherElements.forEach(element => {
            if (!result.includes(element)) {
              result.push(element);
            }
          });
          break;
        case 'union_all':
          // UNION ALL - combinar con duplicados
          result = result.concat(otherElements);
          break;
        case 'intersect':
          // INTERSECT - solo elementos que están en ambos
          result = result.filter(element => otherElements.includes(element));
          break;
        case 'except':
          // EXCEPT - elementos que están en result pero no en other
          result = result.filter(element => !otherElements.includes(element));
          break;
      }
    });

    return result;
  }

  // Obtener valor para ordenamiento
  _getSortValue(element, attribute) {
    const value = element.getAttribute(attribute);
    const numValue = parseFloat(value);
    return !isNaN(numValue) ? numValue : value || '';
  }

  // Ejecutar consulta completa
  getAll() {
    // Determinar contenedor base
    const container = this.fromContainer ? document.querySelector(this.fromContainer) : document;
    if (!container) return [];

    // Construir selector CSS
    const cssSelector = this.selectors.join(', ');
    
    // Obtener elementos base
    let elements = Array.from(container.querySelectorAll(cssSelector));
    
    // Aplicar filtros WHERE
    if (this.conditions.length > 0 || this.orConditions.length > 0 || this.notConditions.length > 0) {
      elements = elements.filter(element => this._matchesConditions(element));
    }
    
    // Aplicar JOINs
    elements = this._applyJoins(elements);
    
    // Aplicar DISTINCT
    elements = this._applyDistinct(elements);
    
    // Aplicar ordenamiento
    elements = this._applySorting(elements);
    
    // Aplicar GROUP BY
    if (this.groupByAttribute) {
      const groups = this._applyGroupBy(elements);
      // Para getAll(), aplanar los grupos
      elements = Object.values(groups).flat();
    }
    
    // Aplicar UNION operations
    elements = this._applyUnions(elements);
    
    // Aplicar OFFSET y LIMIT
    const start = this.offsetCount;
    const end = this.limitCount ? start + this.limitCount : elements.length;
    
    return elements.slice(start, end);
  }

  // Métodos de ejecución adicionales
  first() {
    const results = this.getAll();
    return results.length > 0 ? results[0] : null;
  }

  last() {
    const results = this.getAll();
    return results.length > 0 ? results[results.length - 1] : null;
  }

  count() {
    return this.getAll().length;
  }

  exists() {
    return this.count() > 0;
  }

  // Obtener resultados agrupados
  grouped() {
    if (!this.groupByAttribute) {
      throw new Error('grouped() requires groupBy() to be called first');
    }

    let elements = Array.from(document.querySelectorAll(this.selectors.join(', ')));
    
    if (this.conditions.length > 0 || this.orConditions.length > 0 || this.notConditions.length > 0) {
      elements = elements.filter(element => this._matchesConditions(element));
    }
    
    return this._applyGroupBy(elements);
  }

  // Obtener solo valores de un atributo específico
  pluck(attribute) {
    return this.getAll().map(element => element.getAttribute(attribute));
  }

  // Obtener elementos paginados
  paginate(page, perPage) {
    return this.offSet((page - 1) * perPage).limit(perPage).getAll();
  }

  // Funciones de agregación
  sum(attribute) {
    return this.getAll().reduce((sum, element) => {
      const value = parseFloat(element.getAttribute(attribute)) || 0;
      return sum + value;
    }, 0);
  }

  avg(attribute) {
    const elements = this.getAll();
    if (elements.length === 0) return 0;
    return this.sum(attribute) / elements.length;
  }

  min(attribute) {
    const values = this.pluck(attribute).map(v => parseFloat(v)).filter(v => !isNaN(v));
    return values.length > 0 ? Math.min(...values) : null;
  }

  max(attribute) {
    const values = this.pluck(attribute).map(v => parseFloat(v)).filter(v => !isNaN(v));
    return values.length > 0 ? Math.max(...values) : null;
  }

  // Debug mejorado
  debug() {
    console.group('DOM Query Debug');
    console.log('Selectors:', this.selectors);
    console.log('From container:', this.fromContainer);
    console.log('WHERE conditions:', this.conditions);
    console.log('OR conditions:', this.orConditions);
    console.log('NOT conditions:', this.notConditions);
    console.log('JOIN configs:', this.joinConfigs);
    console.log('ORDER BY:', this.sortConfigs);
    console.log('GROUP BY:', this.groupByAttribute);
    console.log('HAVING:', this.havingConditions);
    console.log('DISTINCT:', this.distinctAttribute);
    console.log('LIMIT:', this.limitCount);
    console.log('OFFSET:', this.offsetCount);
    console.log('UNION queries:', this.unionQueries.length);
    console.groupEnd();
    return this;
  }

  // Método para clonar la query (útil para reutilización)
  clone() {
    const cloned = new DOMQueryBuilder(this.selectors);
    cloned.conditions = [...this.conditions];
    cloned.orConditions = [...this.orConditions];
    cloned.notConditions = [...this.notConditions];
    cloned.sortConfigs = [...this.sortConfigs];
    cloned.limitCount = this.limitCount;
    cloned.offsetCount = this.offsetCount;
    cloned.groupByAttribute = this.groupByAttribute;
    cloned.havingConditions = [...this.havingConditions];
    cloned.distinctAttribute = this.distinctAttribute;
    cloned.joinConfigs = [...this.joinConfigs];
    cloned.fromContainer = this.fromContainer;
    cloned.unionQueries = [...this.unionQueries];
    return cloned;
  }
}
// Función global
function select(...selectors) {
  return DOMQueryBuilder.select(...selectors);
}

/*
  // ========== EJEMPLOS DE USO COMPLETOS ==========

  console.log('=== DOM Query Builder - Ejemplos de uso ===');

  // 1. Consulta básica con FROM
  const ejemploBasico = select("input", "textarea")
    .from("#main-container")
    .where("type", "text")
    .orderBy("name")
    .limit(5)
    .getAll();

  // 2. Consulta con múltiples operadores
  const consultaAvanzada = select("div")
    .where("data-price", ">", "100")
    .whereIn("data-category", ["electronics", "books"])
    .whereLike("data-title", "%laptop%")
    .orderBy("data-price", "DESC")
    .getAll();

  // 3. Consulta con JOINs
  const conJoins = select("article")
    .innerJoin("img", "data-id", "data-article-id")
    .where("data-status", "published")
    .orderBy("data-date", "DESC")
    .getAll();

  // 4. Consulta con GROUP BY y HAVING
  const agrupada = select("product")
    .where("data-available", "true")
    .groupBy("data-category")
    .having("count", ">", "3")
    .grouped();

  // 5. Consulta con UNION
  const otraQuery = select("span").where("class", "highlight");
  const conUnion = select("div")
    .where("class", "important")
    .union(otraQuery)
    .getAll();

  // 6. Subconsulta
  const conSubquery = select("article")
    .whereExists(subquery => 
      subquery.select("comment")
        .where("data-article-id", "=", "data-id")
        .where("data-approved", "true")
    )
    .getAll();

  // 7. Funciones de agregación
  const estadisticas = {
    total: select("product").count(),
    precioPromedio: select("product").avg("data-price"),
    precioMaximo: select("product").max("data-price"),
    precioMinimo: select("product").min("data-price")
  };

  // 8. Paginación
  const pagina2 = select("item")
    .where("data-active", "true")
    .orderBy("data-created", "DESC")
    .paginate(2, 10); // página 2, 10 elementos por página

  // 9. Consulta compleja combinando múltiples features
  const consultaCompleja = select("article", "section")
    .from("#content-area")
    .where("data-status", "published")
    .whereNot("data-hidden", "true")
    .whereBetween("data-rating", 3, 5)
    .orWhere("data-featured", "true")
    .innerJoin("img", "data-id", "data-content-id")
    .orderByMultiple([
      { attribute: "data-priority", direction: "DESC" },
      { attribute: "data-date", direction: "DESC" },
      "data-title"
    ])
    .distinct("data-author")
    .limit(20)
    .offSet(10)
    .debug()
    .getAll();

  console.log('Ejemplos ejecutados - revisa la consola para ver los resultados!');
*/