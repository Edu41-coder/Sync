/**
 * DataTableWithPagination - Extension de DataTable avec recherche, filtres et pagination
 * Classe réutilisable pour tous les tableaux avec fonctionnalités complètes
 */

class DataTableWithPagination extends DataTable {
    constructor(tableId, options = {}) {
        super(tableId, options);
        
        // Options de pagination
        this.pagination = {
            currentPage: 1,
            rowsPerPage: options.rowsPerPage || 10,
            paginationId: options.paginationId || 'pagination',
            infoId: options.infoId || 'tableInfo'
        };
        
        // Options de recherche et filtres
        this.search = {
            inputId: options.searchInputId || 'searchInput',
            filters: options.filters || [] // [{id: 'filterType', column: 1}, ...]
        };
        
        // Stocker toutes les lignes originales
        this.allRows = Array.from(this.tbody.querySelectorAll('tr'));
        this.filteredRows = [...this.allRows];
        
        this.initPagination();
        this.initSearch();
        this.initFilters();
        this.renderTable();
    }
    
    /**
     * Initialiser la recherche
     */
    initSearch() {
        const searchInput = document.getElementById(this.search.inputId);
        if (!searchInput) return;
        
        searchInput.addEventListener('input', () => {
            this.applyFilters();
        });
    }
    
    /**
     * Initialiser les filtres
     */
    initFilters() {
        this.search.filters.forEach(filter => {
            const filterElement = document.getElementById(filter.id);
            if (!filterElement) return;
            
            filterElement.addEventListener('change', () => {
                this.applyFilters();
            });
        });
        
        // Bouton reset
        const resetBtn = document.getElementById('btnReset');
        if (resetBtn) {
            resetBtn.addEventListener('click', () => {
                this.resetFilters();
            });
        }
    }
    
    /**
     * Appliquer les filtres
     */
    applyFilters() {
        const searchInput = document.getElementById(this.search.inputId);
        const searchTerm = searchInput ? searchInput.value.toLowerCase() : '';
        
        this.filteredRows = this.allRows.filter(row => {
            // Recherche globale
            const text = row.textContent.toLowerCase();
            const matchSearch = !searchTerm || text.includes(searchTerm);
            
            if (!matchSearch) return false;
            
            // Filtres spécifiques
            let matchFilters = true;
            this.search.filters.forEach(filter => {
                const filterElement = document.getElementById(filter.id);
                if (!filterElement) return;
                
                const filterValue = filterElement.value.toLowerCase();
                if (!filterValue) return;
                
                const cells = row.querySelectorAll('td');
                const cellText = cells[filter.column] ? cells[filter.column].textContent.toLowerCase() : '';
                
                if (!cellText.includes(filterValue)) {
                    matchFilters = false;
                }
            });
            
            return matchFilters;
        });
        
        this.pagination.currentPage = 1;
        this.renderTable();
    }
    
    /**
     * Réinitialiser tous les filtres
     */
    resetFilters() {
        // Reset recherche
        const searchInput = document.getElementById(this.search.inputId);
        if (searchInput) searchInput.value = '';
        
        // Reset filtres
        this.search.filters.forEach(filter => {
            const filterElement = document.getElementById(filter.id);
            if (filterElement) filterElement.value = '';
        });
        
        // Reset tri
        this.options.currentSort = null;
        this.options.currentOrder = 'asc';
        
        // Reset icônes de tri
        const headers = this.thead.querySelectorAll('th i');
        headers.forEach(icon => {
            if (icon.classList.contains('fa-sort-up') || icon.classList.contains('fa-sort-down')) {
                icon.className = 'fas fa-sort';
            }
        });
        
        this.filteredRows = [...this.allRows];
        this.pagination.currentPage = 1;
        this.renderTable();
    }
    
    /**
     * Initialiser la pagination
     */
    initPagination() {
        // La pagination est gérée dans renderTable() et renderPagination()
    }
    
    /**
     * Override pour indiquer qu'on a une pagination
     */
    hasPagination() {
        return true;
    }
    
    /**
     * Override de la méthode sortTable pour gérer la pagination
     */
    sortTable(columnIndex, header) {
        // Appeler la méthode parent pour trier
        super.sortTable(columnIndex, header);
        
        // Mettre à jour filteredRows avec les lignes triées
        this.filteredRows = [...this.rows];
        
        // Réappliquer la pagination avec les lignes triées
        this.renderTable();
    }
    
    /**
     * Rendre le tableau avec pagination
     */
    renderTable() {
        // Vider le tbody
        this.tbody.innerHTML = '';
        
        // Calculer les indices
        const start = (this.pagination.currentPage - 1) * this.pagination.rowsPerPage;
        const end = start + this.pagination.rowsPerPage;
        const visibleRows = this.filteredRows.slice(start, end);
        
        // Afficher les lignes visibles
        visibleRows.forEach(row => this.tbody.appendChild(row));
        
        // Mettre à jour les infos de pagination
        this.updatePaginationInfo();
        
        // Rendre la pagination
        this.renderPagination();
    }
    
    /**
     * Mettre à jour les informations de pagination
     */
    updatePaginationInfo() {
        const infoElement = document.getElementById(this.pagination.infoId);
        if (!infoElement) return;
        
        const start = this.filteredRows.length > 0 ? 
            (this.pagination.currentPage - 1) * this.pagination.rowsPerPage + 1 : 0;
        const end = Math.min(
            this.pagination.currentPage * this.pagination.rowsPerPage,
            this.filteredRows.length
        );
        const total = this.filteredRows.length;
        
        const startSpan = infoElement.querySelector('#startEntry');
        const endSpan = infoElement.querySelector('#endEntry');
        const totalSpan = infoElement.querySelector('#totalEntries');
        
        if (startSpan) startSpan.textContent = start;
        if (endSpan) endSpan.textContent = end;
        if (totalSpan) totalSpan.textContent = total;
    }
    
    /**
     * Rendre la pagination
     */
    renderPagination() {
        const paginationElement = document.getElementById(this.pagination.paginationId);
        if (!paginationElement) return;
        
        paginationElement.innerHTML = '';
        
        const totalPages = Math.ceil(this.filteredRows.length / this.pagination.rowsPerPage);
        
        if (totalPages <= 1) return;
        
        // Bouton Précédent
        this.addPaginationButton(paginationElement, '«', this.pagination.currentPage - 1, 
            this.pagination.currentPage === 1);
        
        // Numéros de pages
        let startPage = Math.max(1, this.pagination.currentPage - 2);
        let endPage = Math.min(totalPages, this.pagination.currentPage + 2);
        
        // Première page
        if (startPage > 1) {
            this.addPaginationButton(paginationElement, '1', 1, false);
            if (startPage > 2) {
                this.addEllipsis(paginationElement);
            }
        }
        
        // Pages intermédiaires
        for (let i = startPage; i <= endPage; i++) {
            this.addPaginationButton(paginationElement, i.toString(), i, 
                false, i === this.pagination.currentPage);
        }
        
        // Dernière page
        if (endPage < totalPages) {
            if (endPage < totalPages - 1) {
                this.addEllipsis(paginationElement);
            }
            this.addPaginationButton(paginationElement, totalPages.toString(), totalPages, false);
        }
        
        // Bouton Suivant
        this.addPaginationButton(paginationElement, '»', this.pagination.currentPage + 1, 
            this.pagination.currentPage === totalPages);
    }
    
    /**
     * Ajouter un bouton de pagination
     */
    addPaginationButton(container, text, page, disabled, active = false) {
        const li = document.createElement('li');
        li.className = `page-item ${disabled ? 'disabled' : ''} ${active ? 'active' : ''}`;
        
        const a = document.createElement('a');
        a.className = 'page-link';
        a.href = '#';
        a.textContent = text;
        
        if (!disabled) {
            a.addEventListener('click', (e) => {
                e.preventDefault();
                this.goToPage(page);
            });
        }
        
        li.appendChild(a);
        container.appendChild(li);
    }
    
    /**
     * Ajouter des ellipsis
     */
    addEllipsis(container) {
        const li = document.createElement('li');
        li.className = 'page-item disabled';
        li.innerHTML = '<span class="page-link">...</span>';
        container.appendChild(li);
    }
    
    /**
     * Aller à une page spécifique
     */
    goToPage(page) {
        const totalPages = Math.ceil(this.filteredRows.length / this.pagination.rowsPerPage);
        
        if (page < 1 || page > totalPages) return;
        
        this.pagination.currentPage = page;
        this.renderTable();
    }
}
