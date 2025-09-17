/**
 * CRUD6 Table JavaScript Library
 * 
 * Provides dynamic table functionality for CRUD6 sprinkle including:
 * - AJAX data loading
 * - Sorting and filtering
 * - Pagination
 * - Create, Edit, Delete operations
 */

class CRUD6Table {
    constructor(selector, options) {
        this.table = $(selector);
        this.options = {
            model: '',
            schema: {},
            apiUrl: '',
            createUrl: '',
            updateUrl: '',
            deleteUrl: '',
            pageSize: 25,
            currentPage: 1,
            sorts: {},
            filters: {},
            search: '',
            ...options
        };
        
        this.data = [];
        this.totalCount = 0;
        this.loading = false;
    }

    init() {
        this.setupEventHandlers();
        this.loadData();
    }

    setupEventHandlers() {
        const self = this;
        
        // Search input
        $('input[name="search"]').on('input', function() {
            self.options.search = $(this).val();
            self.options.currentPage = 1;
            self.debounceLoadData();
        });
        
        // Page size selector
        $('select[name="size"]').on('change', function() {
            self.options.pageSize = parseInt($(this).val());
            self.options.currentPage = 1;
            self.loadData();
        });
        
        // Column sorting
        this.table.find('th[data-sortable="true"]').on('click', function() {
            const field = $(this).data('field');
            const currentSort = self.options.sorts[field] || '';
            
            // Clear other sorts
            self.options.sorts = {};
            
            // Toggle sort direction
            if (currentSort === 'asc') {
                self.options.sorts[field] = 'desc';
            } else {
                self.options.sorts[field] = 'asc';
            }
            
            self.options.currentPage = 1;
            self.loadData();
        });
        
        // Row actions
        this.table.on('click', '.btn-edit', function() {
            const id = $(this).data('id');
            self.editRecord(id);
        });
        
        this.table.on('click', '.btn-delete', function() {
            const id = $(this).data('id');
            self.deleteRecord(id);
        });
    }

    debounceLoadData() {
        clearTimeout(this.searchTimeout);
        this.searchTimeout = setTimeout(() => {
            this.loadData();
        }, 300);
    }

    loadData() {
        if (this.loading) return;
        
        this.loading = true;
        this.showLoading();
        
        const params = {
            size: this.options.pageSize,
            page: this.options.currentPage,
            ...this.options.filters
        };
        
        // Add sorts
        Object.keys(this.options.sorts).forEach(field => {
            params[`sorts[${field}]`] = this.options.sorts[field];
        });
        
        // Add search
        if (this.options.search) {
            params.search = this.options.search;
        }
        
        $.ajax({
            url: this.options.apiUrl,
            method: 'GET',
            data: params,
            success: (response) => {
                this.data = response.rows || [];
                this.totalCount = response.count || 0;
                this.renderTable();
                this.renderPagination();
                this.renderInfo();
                this.loading = false;
            },
            error: (xhr) => {
                console.error('Failed to load data:', xhr);
                this.showError('Failed to load data. Please try again.');
                this.loading = false;
            }
        });
    }

    showLoading() {
        const tbody = this.table.find('tbody');
        const colspan = this.table.find('thead th').length;
        tbody.html(`
            <tr>
                <td colspan="${colspan}" class="text-center">
                    <div class="spinner-border spinner-border-sm" role="status">
                        <span class="sr-only">Loading...</span>
                    </div>
                    Loading...
                </td>
            </tr>
        `);
    }

    showError(message) {
        const tbody = this.table.find('tbody');
        const colspan = this.table.find('thead th').length;
        tbody.html(`
            <tr>
                <td colspan="${colspan}" class="text-center text-danger">
                    <i class="fas fa-exclamation-triangle"></i>
                    ${message}
                </td>
            </tr>
        `);
    }

    renderTable() {
        const tbody = this.table.find('tbody');
        
        if (this.data.length === 0) {
            const colspan = this.table.find('thead th').length;
            tbody.html(`
                <tr>
                    <td colspan="${colspan}" class="text-center text-muted">
                        No records found
                    </td>
                </tr>
            `);
            return;
        }
        
        const rows = this.data.map(row => this.renderRow(row)).join('');
        tbody.html(rows);
    }

    renderRow(row) {
        const cells = [];
        
        // Render data cells
        Object.keys(this.options.schema.fields).forEach(fieldName => {
            const field = this.options.schema.fields[fieldName];
            if (!field.hidden) {
                const value = row[fieldName];
                cells.push(`<td>${this.formatCellValue(value, field)}</td>`);
            }
        });
        
        // Render actions cell
        const primaryKey = this.options.schema.primary_key || 'id';
        const recordId = row[primaryKey];
        cells.push(`
            <td>
                <div class="btn-group btn-group-sm">
                    <button type="button" class="btn btn-outline-primary btn-edit" data-id="${recordId}">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button type="button" class="btn btn-outline-danger btn-delete" data-id="${recordId}">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </td>
        `);
        
        return `<tr>${cells.join('')}</tr>`;
    }

    formatCellValue(value, field) {
        if (value === null || value === undefined) {
            return '<span class="text-muted">—</span>';
        }
        
        const type = field.type || 'string';
        
        switch (type) {
            case 'boolean':
                return value ? 
                    '<span class="badge badge-success">Yes</span>' : 
                    '<span class="badge badge-secondary">No</span>';
                    
            case 'date':
            case 'datetime':
                return new Date(value).toLocaleDateString();
                
            case 'json':
                return typeof value === 'object' ? 
                    JSON.stringify(value) : 
                    value;
                    
            default:
                return String(value);
        }
    }

    renderPagination() {
        const pagination = $(`#crud6-pagination-${this.options.model}`);
        const totalPages = Math.ceil(this.totalCount / this.options.pageSize);
        
        if (totalPages <= 1) {
            pagination.empty();
            return;
        }
        
        const items = [];
        
        // Previous button
        const prevDisabled = this.options.currentPage === 1 ? 'disabled' : '';
        items.push(`
            <li class="page-item ${prevDisabled}">
                <a class="page-link" href="#" data-page="${this.options.currentPage - 1}">Previous</a>
            </li>
        `);
        
        // Page numbers
        const startPage = Math.max(1, this.options.currentPage - 2);
        const endPage = Math.min(totalPages, this.options.currentPage + 2);
        
        for (let i = startPage; i <= endPage; i++) {
            const active = i === this.options.currentPage ? 'active' : '';
            items.push(`
                <li class="page-item ${active}">
                    <a class="page-link" href="#" data-page="${i}">${i}</a>
                </li>
            `);
        }
        
        // Next button
        const nextDisabled = this.options.currentPage === totalPages ? 'disabled' : '';
        items.push(`
            <li class="page-item ${nextDisabled}">
                <a class="page-link" href="#" data-page="${this.options.currentPage + 1}">Next</a>
            </li>
        `);
        
        pagination.html(items.join(''));
        
        // Handle pagination clicks
        pagination.find('a.page-link').on('click', (e) => {
            e.preventDefault();
            const page = parseInt($(e.target).data('page'));
            if (page && page !== this.options.currentPage) {
                this.options.currentPage = page;
                this.loadData();
            }
        });
    }

    renderInfo() {
        const info = $(`#crud6-info-${this.options.model}`);
        const start = (this.options.currentPage - 1) * this.options.pageSize + 1;
        const end = Math.min(start + this.options.pageSize - 1, this.totalCount);
        
        info.text(`Showing ${start} to ${end} of ${this.totalCount} entries`);
    }

    editRecord(id) {
        // TODO: Open edit modal
        console.log('Edit record:', id);
    }

    deleteRecord(id) {
        if (confirm('Are you sure you want to delete this record?')) {
            const url = this.options.deleteUrl.replace('__ID__', id);
            
            $.ajax({
                url: url,
                method: 'DELETE',
                success: () => {
                    this.loadData(); // Reload table
                    // TODO: Show success message
                },
                error: (xhr) => {
                    console.error('Failed to delete record:', xhr);
                    // TODO: Show error message
                }
            });
        }
    }
}

// Make available globally
window.CRUD6Table = CRUD6Table;