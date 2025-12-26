/**
 * DataTable - Tri et filtrage côté client
 * Système de tri réutilisable pour tous les tableaux
 */

class DataTable {
    constructor(tableId, options = {}) {
        this.table = document.getElementById(tableId);
        if (!this.table) {
            console.error(`Table avec l'ID "${tableId}" non trouvée`);
            return;
        }
        
        this.tbody = this.table.querySelector('tbody');
        this.thead = this.table.querySelector('thead');
        this.rows = Array.from(this.tbody.querySelectorAll('tr'));
        
        // Options
        this.options = {
            sortable: options.sortable !== false, // true par défaut
            currentSort: options.currentSort || null,
            currentOrder: options.currentOrder || 'asc',
            excludeColumns: options.excludeColumns || [], // Colonnes à exclure du tri
            ...options
        };
        
        this.init();
    }
    
    init() {
        if (this.options.sortable) {
            this.makeSortable();
        }
    }
    
    /**
     * Rendre les colonnes triables
     */
    makeSortable() {
        const headers = this.thead.querySelectorAll('th');
        
        headers.forEach((header, index) => {
            // Vérifier si la colonne doit être triable
            if (this.options.excludeColumns.includes(index)) {
                return;
            }
            
            // Ajouter le curseur pointer
            header.style.cursor = 'pointer';
            header.style.userSelect = 'none';
            
            // Ajouter l'icône de tri
            if (!header.querySelector('.sort-icon')) {
                const icon = document.createElement('i');
                icon.className = 'fas fa-sort sort-icon ms-1 text-muted';
                icon.style.fontSize = '0.875rem';
                header.appendChild(icon);
            }
            
            // Event listener pour le tri
            header.addEventListener('click', () => {
                this.sortTable(index, header);
            });
        });
    }
    
    /**
     * Trier le tableau
     */
    sortTable(columnIndex, header) {
        // Déterminer l'ordre de tri
        const currentIcon = header.querySelector('.sort-icon');
        let order = 'asc';
        
        if (currentIcon.classList.contains('fa-sort-up')) {
            order = 'desc';
        }
        
        // Réinitialiser toutes les icônes
        this.thead.querySelectorAll('.sort-icon').forEach(icon => {
            icon.className = 'fas fa-sort sort-icon ms-1 text-muted';
        });
        
        // Mettre à jour l'icône de la colonne triée
        if (order === 'asc') {
            currentIcon.className = 'fas fa-sort-up sort-icon ms-1 text-primary';
        } else {
            currentIcon.className = 'fas fa-sort-down sort-icon ms-1 text-primary';
        }
        
        // Trier les lignes
        const sortedRows = this.rows.sort((rowA, rowB) => {
            const cellA = rowA.cells[columnIndex];
            const cellB = rowB.cells[columnIndex];
            
            // Récupérer le contenu (texte ou data-sort)
            let valueA = cellA.dataset.sort || cellA.textContent.trim();
            let valueB = cellB.dataset.sort || cellB.textContent.trim();
            
            // Détection automatique du type
            const isNumber = !isNaN(parseFloat(valueA.replace(/[^\d.-]/g, ''))) && 
                           !isNaN(parseFloat(valueB.replace(/[^\d.-]/g, '')));
            
            if (isNumber) {
                valueA = parseFloat(valueA.replace(/[^\d.-]/g, ''));
                valueB = parseFloat(valueB.replace(/[^\d.-]/g, ''));
            } else {
                valueA = valueA.toLowerCase();
                valueB = valueB.toLowerCase();
            }
            
            // Comparaison
            if (valueA < valueB) return order === 'asc' ? -1 : 1;
            if (valueA > valueB) return order === 'asc' ? 1 : -1;
            return 0;
        });
        
        // NE PAS réorganiser le DOM ici si on a une pagination
        // Laisser la classe enfant gérer l'affichage
        if (!this.hasPagination()) {
            // Réorganiser le DOM uniquement pour DataTable basique
            this.tbody.innerHTML = '';
            sortedRows.forEach(row => this.tbody.appendChild(row));
        }
        
        // Mettre à jour les lignes
        this.rows = sortedRows;
        
        // Callback optionnel
        if (this.options.onSort) {
            this.options.onSort(columnIndex, order);
        }
    }
    
    /**
     * Vérifier si la table a une pagination (override dans la classe enfant)
     */
    hasPagination() {
        return false;
    }
    
    /**
     * Filtrer le tableau
     */
    filter(searchTerm, columns = null) {
        searchTerm = searchTerm.toLowerCase();
        
        this.rows.forEach(row => {
            let found = false;
            const cells = Array.from(row.cells);
            
            cells.forEach((cell, index) => {
                // Si des colonnes spécifiques sont définies, filtrer uniquement sur celles-ci
                if (columns && !columns.includes(index)) {
                    return;
                }
                
                const text = cell.textContent.toLowerCase();
                if (text.includes(searchTerm)) {
                    found = true;
                }
            });
            
            row.style.display = found ? '' : 'none';
        });
        
        // Mettre à jour le compteur si présent
        this.updateCounter();
    }
    
    /**
     * Mettre à jour le compteur de résultats
     */
    updateCounter() {
        const visibleRows = this.rows.filter(row => row.style.display !== 'none');
        const counter = document.querySelector('[data-table-counter]');
        
        if (counter) {
            counter.textContent = `${visibleRows.length} résultat(s) affiché(s)`;
        }
    }
    
    /**
     * Réinitialiser les filtres
     */
    reset() {
        this.rows.forEach(row => row.style.display = '');
        this.updateCounter();
    }
}

// Export pour utilisation globale
window.DataTable = DataTable;
