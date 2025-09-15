/**
 * SmartAutocomplete - Sistema de autocompletado inteligente reutilizable
 * Autor: Javier Fallas
 * Versión: 1.0
 */
class SmartAutocomplete {
    constructor(options = {}) {
        // Configuración por defecto
        this.config = {
            minScore: 30,           // Puntuación mínima para considerar una coincidencia
            minSimilarity: 0.35,    // Similitud mínima para palabras muy largas
            caseSensitive: false,   // Sensible a mayúsculas/minúsculas
            removeAccents: true,    // Quitar tildes y acentos
            debug: false,           // Mostrar información de debug
            onMatch: null,          // Callback cuando encuentra coincidencia
            onNoMatch: null,        // Callback cuando no encuentra coincidencia
            ...options
        };
        
        // Almacenar las listas de datos
        this.dataSources = new Map();
    }
    
    /**
     * Registra una nueva lista de datos
     * @param {string} name - Nombre de la lista
     * @param {Array} data - Array de strings con los datos
     */
    registerDataSource(name, data) {
        if (!Array.isArray(data)) {
            throw new Error('Los datos deben ser un array');
        }
        this.dataSources.set(name, data);
        return this;
    }
    
    /**
     * Conecta el autocompletado a un input específico
     * @param {string|HTMLElement} inputElement - Selector CSS o elemento DOM del input
     * @param {string} dataSourceName - Nombre de la lista de datos a usar
     * @param {Object} options - Opciones específicas para este input
     */
    attachToInput(inputElement, dataSourceName, options = {}) {
        // Obtener el elemento DOM
        const input = typeof inputElement === 'string' 
            ? document.querySelector(inputElement) 
            : inputElement;
            
        if (!input) {
            throw new Error('Elemento input no encontrado');
        }
        
        if (!this.dataSources.has(dataSourceName)) {
            throw new Error(`Lista de datos '${dataSourceName}' no está registrada`);
        }
        
        const config = { ...this.config, ...options };
        const dataSource = this.dataSources.get(dataSourceName);
        
        // Event listeners
        input.addEventListener('blur', (e) => {
            this.handleBlur(e.target, dataSource, config);
        });
        
        input.addEventListener('focus', (e) => {
            this.handleFocus(e.target, config);
        });
        
        return this;
    }
    
    /**
     * Busca la mejor coincidencia manualmente
     * @param {string} input - Texto de entrada
     * @param {string} dataSourceName - Nombre de la lista de datos
     * @param {Object} options - Opciones específicas
     * @returns {Object|null} Resultado con la coincidencia y detalles
     */
    findMatch(input, dataSourceName, options = {}) {
        if (!this.dataSources.has(dataSourceName)) {
            throw new Error(`Lista de datos '${dataSourceName}' no está registrada`);
        }
        
        const config = { ...this.config, ...options };
        const dataSource = this.dataSources.get(dataSourceName);
        
        const result = this.findBestMatch(input, dataSource, config);
        
        return result ? {
            match: result.match,
            score: result.score,
            wasChanged: this.normalizeText(input, config) !== this.normalizeText(result.match, config),
            debug: result.debug
        } : null;
    }

    /**
     * Búsqueda con validación estricta de coincidencias
     * @param {string} input - Texto de entrada
     * @param {string} dataSourceName - Nombre de la fuente de datos
     * @param {Object} options - Opciones de configuración
     * @returns {Object|null} Resultado validado o null si no hay coincidencia real
     */
    findStrictMatch(input, dataSourceName, options = {}) {
        const result = this.findMatch(input, dataSourceName, options);
        
        if (!result || !this.hasValidWordMatch(input, result.match, options)) {
            return null;
        }
        
        return result;
    }

    /**
     * Valida que la coincidencia tenga palabras reales en común con el input (ESTRICTO)
     * @param {string} input - Texto original buscado
     * @param {string} match - Coincidencia encontrada
     * @param {Object} config - Configuración (opcional)
     * @returns {boolean} true si tiene coincidencias válidas, false si no
     */
    hasValidWordMatch(input, match, config = this.config) {
        if (!input || !match) return false;
        
        const normalizedInput = this.normalizeText(input, config);
        const normalizedMatch = this.normalizeText(match, config);
        const inputWords = normalizedInput.split(' ').filter(w => w.length >= 3); // Mínimo 3 caracteres
        const matchWords = normalizedMatch.split(' ').filter(w => w.length >= 2);
        
        if (inputWords.length === 0) return false;
        
        let validMatches = 0;
        
        // Cada palabra del input debe tener una coincidencia clara
        for (const inputWord of inputWords) {
            let hasMatch = false;
            
            for (const matchWord of matchWords) {
                // Solo coincidencias muy claras:
                // 1. Palabra exacta
                if (inputWord === matchWord) {
                    hasMatch = true;
                    break;
                }
                // 2. Palabra del match contiene completamente la del input (mín 3 chars)
                else if (inputWord.length >= 3 && matchWord.includes(inputWord)) {
                    hasMatch = true;
                    break;
                }
                // 3. Solo para palabras largas (6+ chars): similitud muy alta
                else if (inputWord.length >= 6 && matchWord.length >= 6) {
                    const similarity = this.calculateSimilarity(inputWord, matchWord);
                    if (similarity >= 0.85) { // Muy estricto: 85%
                        hasMatch = true;
                        break;
                    }
                }
            }
            
            if (hasMatch) {
                validMatches++;
            }
        }
        
        // Requiere que al menos 70% de las palabras del input tengan coincidencia clara
        const requiredMatches = Math.ceil(inputWords.length * 0.7);
        return validMatches >= requiredMatches;
    }
    /**
     * Normaliza el texto según la configuración
     */
    normalizeText(text, config = this.config) {
        let normalized = text;
        
        if (!config.caseSensitive) {
            normalized = normalized.toLowerCase();
        }
        
        if (config.removeAccents) {
            normalized = normalized
                .normalize('NFD')
                .replace(/[\u0300-\u036f]/g, '');
        }
        
        return normalized
            .replace(/[^\w\s]/g, ' ')
            .replace(/\s+/g, ' ')
            .trim();
    }
    
    /**
     * Calcula la distancia de Levenshtein entre dos strings
     */
    levenshteinDistance(str1, str2) {
        const len1 = str1.length;
        const len2 = str2.length;
        const matrix = Array(len2 + 1).fill(null).map(() => Array(len1 + 1).fill(null));
        
        for (let i = 0; i <= len1; i++) matrix[0][i] = i;
        for (let j = 0; j <= len2; j++) matrix[j][0] = j;
        
        for (let j = 1; j <= len2; j++) {
            for (let i = 1; i <= len1; i++) {
                const cost = str1[i - 1] === str2[j - 1] ? 0 : 1;
                matrix[j][i] = Math.min(
                    matrix[j - 1][i] + 1,
                    matrix[j][i - 1] + 1,
                    matrix[j - 1][i - 1] + cost
                );
            }
        }
        
        return matrix[len2][len1];
    }
    
    /**
     * Calcula la similitud entre dos strings (0-1)
     */
    calculateSimilarity(str1, str2) {
        if (str1 === str2) return 1;
        
        const maxLen = Math.max(str1.length, str2.length);
        if (maxLen === 0) return 1;
        
        const distance = this.levenshteinDistance(str1, str2);
        return Math.max(0, 1 - (distance / maxLen));
    }
    
    /**
     * Encuentra la mejor coincidencia en una lista
     */
    findBestMatch(input, options, config) {
        if (!input || input.trim() === '') return null;
        
        const normalizedInput = this.normalizeText(input, config);
        const inputWords = normalizedInput.split(' ').filter(word => word.length > 0);
        
        let bestMatch = null;
        let bestScore = 0;
        let debugInfo = [];
        
        for (const option of options) {
            const normalizedOption = this.normalizeText(option, config);
            const optionWords = normalizedOption.split(' ').filter(word => word.length > 0);
            
            let totalScore = 0;
            let wordScores = [];
            
            // Coincidencia exacta completa
            if (normalizedInput === normalizedOption) {
                totalScore = 1000;
                wordScores.push('Coincidencia exacta completa');
            }
            // Coincidencia parcial
            else if (normalizedOption.includes(normalizedInput)) {
                totalScore = 800 + (normalizedInput.length / normalizedOption.length) * 100;
                wordScores.push('Coincidencia parcial');
            }
            // Análisis por palabras
            else {
                for (const inputWord of inputWords) {
                    if (inputWord.length < 2) continue;
                    
                    let bestWordScore = 0;
                    let bestWordMatch = '';
                    
                    for (const optionWord of optionWords) {
                        let wordScore = 0;
                        
                        if (inputWord === optionWord) {
                            wordScore = 100;
                            bestWordMatch = `${inputWord} (exacta)`;
                        }
                        else if (optionWord.includes(inputWord)) {
                            wordScore = 80 + (inputWord.length / optionWord.length) * 15;
                            bestWordMatch = `${inputWord} en ${optionWord}`;
                        }
                        else if (inputWord.includes(optionWord)) {
                            wordScore = 70 + (optionWord.length / inputWord.length) * 10;
                            bestWordMatch = `${optionWord} en ${inputWord}`;
                        }
                        else {
                            const similarity = this.calculateSimilarity(inputWord, optionWord);
                            let threshold = config.minSimilarity;
                            
                            // Ajustar umbral según longitud
                            if (Math.max(inputWord.length, optionWord.length) >= 6) {
                                threshold = Math.max(0.4, threshold);
                            }
                            if (Math.max(inputWord.length, optionWord.length) >= 8) {
                                threshold = Math.max(0.35, threshold);
                            }
                            
                            if (similarity >= threshold) {
                                wordScore = similarity * 70;
                                bestWordMatch = `${inputWord}~${optionWord} (${(similarity*100).toFixed(0)}%)`;
                            }
                        }
                        
                        if (wordScore > bestWordScore) {
                            bestWordScore = wordScore;
                        }
                    }
                    
                    if (bestWordScore > 0) {
                        totalScore += bestWordScore;
                        wordScores.push(bestWordMatch);
                    }
                }
                
                // Bonus por cobertura
                const wordsMatched = wordScores.length;
                const totalWords = Math.max(inputWords.length, optionWords.length);
                const coverage = wordsMatched / totalWords;
                totalScore += coverage * 50;
            }
            
            debugInfo.push({
                option: option,
                score: totalScore,
                details: wordScores
            });
            
            if (totalScore > bestScore && totalScore > config.minScore) {
                bestScore = totalScore;
                bestMatch = option;
            }
        }
        
        return bestMatch ? {
            match: bestMatch,
            score: bestScore,
            debug: debugInfo.sort((a, b) => b.score - a.score)
        } : null;
    }
    
    /**
     * Maneja el evento blur del input
     */
    handleBlur(inputElement, dataSource, config) {
        const userInput = inputElement.value.trim();
        
        if (userInput === '') {
            this.clearStyles(inputElement);
            return;
        }
        
        const result = this.findBestMatch(userInput, dataSource, config);
        
        if (result) {
            const wasChanged = this.normalizeText(userInput, config) !== this.normalizeText(result.match, config);
            
            inputElement.value = result.match;
            this.applySuccessStyles(inputElement);
            
            if (config.onMatch) {
                config.onMatch({
                    original: userInput,
                    match: result.match,
                    wasChanged: wasChanged,
                    score: result.score,
                    debug: config.debug ? result.debug : null
                });
            }
        } else {
            this.applyErrorStyles(inputElement);
            
            if (config.onNoMatch) {
                config.onNoMatch({
                    input: userInput
                });
            }
        }
    }
    
    /**
     * Maneja el evento focus del input
     */
    handleFocus(inputElement, config) {
        this.clearStyles(inputElement);
    }
    
    /**
     * Aplica estilos de éxito
     */
    applySuccessStyles(element) {
        element.style.borderColor = '#2196F3';
        element.style.backgroundColor = '#E3F2FD';
    }
    
    /**
     * Aplica estilos de error
     */
    applyErrorStyles(element) {
        element.style.borderColor = '#FF9800';
        element.style.backgroundColor = '#FFF3E0';
    }
    
    /**
     * Limpia los estilos
     */
    clearStyles(element) {
        element.style.borderColor = '';
        element.style.backgroundColor = '';
    }
}