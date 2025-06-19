/**
 * Gallery Admin JavaScript - Advanced
 * 
 * Handles all admin interface interactions for gallery management
 * Modern, responsive interface with AJAX operations
 * 
 * @package MarkusLehr\ClientGallerie
 * @author Markus Lehr
 * @since 1.0.0
 */

(function($) {
    'use strict';

    // Plugin namespace
    const MLCG = {
        config: {
            ajaxUrl: mlcgGallery?.ajaxUrl || ajaxurl,
            nonce: mlcgGallery?.nonce || '',
            strings: mlcgGallery?.strings || {}
        },
        
        // Initialize plugin
        init: function() {
            this.bindEvents();
            this.initModals();
            this.initFormValidation();
            this.initAutoSave();
            console.log('MLCG Gallery Admin initialized');
        },

        // Bind all event handlers
        bindEvents: function() {
            // Gallery creation form
            $(document).on('submit', '#mlcg-create-gallery-form', this.handleCreateGallery.bind(this));
            
            // Gallery editing
            $(document).on('click', '.edit-gallery', this.handleEditGallery.bind(this));
            $(document).on('submit', '#mlcg-edit-gallery-form', this.handleUpdateGallery.bind(this));
            
            // Status changes
            $(document).on('click', '.publish-gallery, .unpublish-gallery', this.handleStatusChange.bind(this));
            
            // Gallery deletion
            $(document).on('click', '.delete-gallery', this.handleDeleteGallery.bind(this));
            
            // Modal controls
            $(document).on('click', '#cancel-edit, .mlcg-modal-close', this.closeModal.bind(this));
            $(document).on('click', '.mlcg-modal-overlay', this.closeModal.bind(this));
            
            // Auto-slug generation
            $(document).on('input', '#gallery-name', this.generateSlug.bind(this));
            
            // Keyboard shortcuts
            $(document).on('keydown', this.handleKeyboardShortcuts.bind(this));
        },

        // Initialize modals
        initModals: function() {
            // Create modal container if not exists
            if (!$('#mlcg-modal-container').length) {
                $('body').append('<div id="mlcg-modal-container"></div>');
            }
        },

        // Initialize form validation
        initFormValidation: function() {
            // Real-time validation
            $(document).on('blur', 'input[required], select[required]', function() {
                MLCG.validateField($(this));
            });
            
            // Slug validation
            $(document).on('blur', '#gallery-slug, #edit-gallery-slug', function() {
                MLCG.validateSlug($(this));
            });
        },

        // Initialize auto-save functionality
        initAutoSave: function() {
            // Restore draft on page load
            this.restoreDraft();
            
            // Auto-save drafts every 30 seconds
            setInterval(() => {
                this.autoSaveDrafts();
            }, 30000);
        },

        // Handle gallery creation
        handleCreateGallery: function(e) {
            e.preventDefault();
            
            const $form = $(e.target);
            const $submitBtn = $form.find('input[type="submit"]');
            const $spinner = $form.find('.spinner');
            
            // Validate form
            if (!this.validateForm($form)) {
                return false;
            }
            
            // Show loading state
            $submitBtn.prop('disabled', true);
            $spinner.addClass('is-active');
            
            // Prepare form data
            const formData = this.getFormData($form);
            formData.action = 'mlcg_create_gallery';
            formData.nonce = this.config.nonce;
            
            // AJAX request
            $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: formData,
                success: (response) => {
                    if (response.success) {
                        this.showNotice(this.config.strings.createSuccess || 'Gallery created successfully', 'success');
                        $form[0].reset();
                        this.clearDraft();
                        this.refreshGalleryList();
                    } else {
                        this.showNotice(response.data?.message || 'An error occurred', 'error');
                    }
                },
                error: () => {
                    this.showNotice('Network error occurred', 'error');
                },
                complete: () => {
                    $submitBtn.prop('disabled', false);
                    $spinner.removeClass('is-active');
                }
            });
        },

        // Handle gallery editing
        handleEditGallery: function(e) {
            e.preventDefault();
            
            const galleryId = $(e.target).data('gallery-id');
            this.openEditModal(galleryId);
        },

        // Open edit modal and load gallery data
        openEditModal: function(galleryId) {
            const $modal = this.createEditModal();
            
            // Load gallery data
            $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'mlcg_get_gallery',
                    gallery_id: galleryId,
                    nonce: this.config.nonce
                },
                success: (response) => {
                    if (response.success) {
                        this.populateEditForm(response.data.gallery);
                        this.showModal($modal);
                    } else {
                        this.showNotice(response.data?.message || 'Failed to load gallery', 'error');
                    }
                },
                error: () => {
                    this.showNotice('Failed to load gallery data', 'error');
                }
            });
        },

        // Create edit modal HTML
        createEditModal: function() {
            const modalHtml = `
                <div class="mlcg-modal-overlay">
                    <div class="mlcg-modal">
                        <div class="mlcg-modal-header">
                            <h2>Edit Gallery</h2>
                            <button class="mlcg-modal-close" type="button">&times;</button>
                        </div>
                        <div class="mlcg-modal-body">
                            <form id="mlcg-edit-gallery-form">
                                <input type="hidden" id="edit-gallery-id" name="id">
                                
                                <div class="form-row">
                                    <label for="edit-gallery-name">Gallery Name *</label>
                                    <input type="text" id="edit-gallery-name" name="name" class="regular-text" required>
                                </div>
                                
                                <div class="form-row">
                                    <label for="edit-gallery-slug">Slug</label>
                                    <input type="text" id="edit-gallery-slug" name="slug" class="regular-text">
                                    <span class="description">URL-friendly version</span>
                                </div>
                                
                                <div class="form-row">
                                    <label for="edit-gallery-description">Description</label>
                                    <textarea id="edit-gallery-description" name="description" rows="3" class="large-text"></textarea>
                                </div>
                                
                                <div class="form-row">
                                    <label for="edit-gallery-status">Status</label>
                                    <select id="edit-gallery-status" name="status">
                                        <option value="draft">Draft</option>
                                        <option value="published">Published</option>
                                    </select>
                                </div>
                            </form>
                        </div>
                        <div class="mlcg-modal-footer">
                            <button type="button" class="button button-primary" id="save-gallery-changes">Update Gallery</button>
                            <button type="button" class="button" id="cancel-edit">Cancel</button>
                            <span class="spinner"></span>
                        </div>
                    </div>
                </div>
            `;
            
            $('#mlcg-modal-container').html(modalHtml);
            return $('#mlcg-modal-container .mlcg-modal-overlay');
        },

        // Populate edit form with gallery data
        populateEditForm: function(gallery) {
            $('#edit-gallery-id').val(gallery.id);
            $('#edit-gallery-name').val(gallery.name);
            $('#edit-gallery-slug').val(gallery.slug);
            $('#edit-gallery-description').val(gallery.description || '');
            $('#edit-gallery-status').val(gallery.status);
        },

        // Handle gallery update
        handleUpdateGallery: function(e) {
            e.preventDefault();
            
            const $form = $(e.target);
            const $modal = $form.closest('.mlcg-modal-overlay');
            const $saveBtn = $('#save-gallery-changes');
            const $spinner = $modal.find('.spinner');
            
            // Validate form
            if (!this.validateForm($form)) {
                return false;
            }
            
            // Show loading state
            $saveBtn.prop('disabled', true);
            $spinner.addClass('is-active');
            
            // Prepare form data
            const formData = this.getFormData($form);
            formData.action = 'mlcg_update_gallery';
            formData.nonce = this.config.nonce;
            
            // AJAX request
            $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: formData,
                success: (response) => {
                    if (response.success) {
                        this.showNotice(this.config.strings.updateSuccess || 'Gallery updated successfully', 'success');
                        this.closeModal();
                        this.refreshGalleryList();
                    } else {
                        this.showNotice(response.data?.message || 'Update failed', 'error');
                    }
                },
                error: () => {
                    this.showNotice('Network error occurred', 'error');
                },
                complete: () => {
                    $saveBtn.prop('disabled', false);
                    $spinner.removeClass('is-active');
                }
            });
        },

        // Handle status changes (publish/unpublish)
        handleStatusChange: function(e) {
            e.preventDefault();
            
            const $button = $(e.target);
            const galleryId = $button.data('gallery-id');
            const action = $button.data('action');
            
            if (!confirm(`Are you sure you want to ${action} this gallery?`)) {
                return;
            }
            
            $button.prop('disabled', true);
            
            $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'mlcg_change_gallery_status',
                    gallery_id: galleryId,
                    status: action,
                    nonce: this.config.nonce
                },
                success: (response) => {
                    if (response.success) {
                        this.showNotice(`Gallery ${action}ed successfully`, 'success');
                        this.refreshGalleryList();
                    } else {
                        this.showNotice(response.data?.message || 'Status change failed', 'error');
                    }
                },
                error: () => {
                    this.showNotice('Network error occurred', 'error');
                },
                complete: () => {
                    $button.prop('disabled', false);
                }
            });
        },

        // Handle gallery deletion
        handleDeleteGallery: function(e) {
            e.preventDefault();
            
            const $button = $(e.target);
            const galleryId = $button.data('gallery-id');
            
            if (!confirm(this.config.strings.confirmDelete || 'Are you sure you want to delete this gallery?')) {
                return;
            }
            
            $button.prop('disabled', true);
            
            $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'mlcg_delete_gallery',
                    gallery_id: galleryId,
                    nonce: this.config.nonce
                },
                success: (response) => {
                    if (response.success) {
                        this.showNotice(this.config.strings.deleteSuccess || 'Gallery deleted successfully', 'success');
                        $(`tr[data-gallery-id="${galleryId}"]`).fadeOut(300, function() {
                            $(this).remove();
                            MLCG.updateTableCounts();
                        });
                    } else {
                        this.showNotice(response.data?.message || 'Delete failed', 'error');
                    }
                },
                error: () => {
                    this.showNotice('Network error occurred', 'error');
                },
                complete: () => {
                    $button.prop('disabled', false);
                }
            });
        },

        // Utility functions
        showModal: function($modal) {
            $modal.fadeIn(300);
            $('body').addClass('mlcg-modal-open');
        },

        closeModal: function() {
            $('.mlcg-modal-overlay').fadeOut(300);
            $('body').removeClass('mlcg-modal-open');
        },

        showNotice: function(message, type = 'info') {
            // Remove existing notices
            $('.notice').remove();
            
            const noticeHtml = `
                <div class="notice notice-${type} is-dismissible">
                    <p>${message}</p>
                    <button type="button" class="notice-dismiss">
                        <span class="screen-reader-text">Dismiss this notice.</span>
                    </button>
                </div>
            `;
            
            $('.wrap h1').after(noticeHtml);
            
            // Auto-dismiss after 5 seconds
            setTimeout(() => {
                $('.notice').fadeOut();
            }, 5000);
        },

        validateForm: function($form) {
            let isValid = true;
            
            $form.find('[required]').each(function() {
                if (!this.value.trim()) {
                    $(this).addClass('error');
                    isValid = false;
                } else {
                    $(this).removeClass('error');
                }
            });
            
            return isValid;
        },

        validateField: function($field) {
            if ($field.prop('required') && !$field.val().trim()) {
                $field.addClass('error');
                return false;
            } else {
                $field.removeClass('error');
                return true;
            }
        },

        validateSlug: function($field) {
            const slug = $field.val();
            if (slug && !/^[a-z0-9-]+$/.test(slug)) {
                $field.addClass('error');
                this.showNotice('Slug can only contain lowercase letters, numbers, and hyphens', 'error');
                return false;
            } else {
                $field.removeClass('error');
                return true;
            }
        },

        getFormData: function($form) {
            const data = {};
            $form.serializeArray().forEach(function(field) {
                data[field.name] = field.value;
            });
            return data;
        },

        generateSlug: function(e) {
            const $nameField = $(e.target);
            const $slugField = $('#gallery-slug');
            
            if (!$slugField.val()) {
                const slug = $nameField.val()
                    .toLowerCase()
                    .replace(/[^a-z0-9\s-]/g, '')
                    .replace(/\s+/g, '-')
                    .replace(/-+/g, '-')
                    .replace(/^-|-$/g, '');
                
                $slugField.val(slug);
            }
        },

        refreshGalleryList: function() {
            // Reload the page for now - could be improved with partial refresh
            window.location.reload();
        },

        updateTableCounts: function() {
            const totalRows = $('.wp-list-table tbody tr').length;
            $('.displaying-num').text(`${totalRows} items`);
        },

        // Auto-save functionality
        autoSaveDrafts: function() {
            const $form = $('#mlcg-create-gallery-form');
            if ($form.length && $form.find('input[name="name"]').val()) {
                const formData = this.getFormData($form);
                localStorage.setItem('mlcg_draft_gallery', JSON.stringify(formData));
            }
        },

        restoreDraft: function() {
            const draft = localStorage.getItem('mlcg_draft_gallery');
            if (draft) {
                try {
                    const data = JSON.parse(draft);
                    Object.keys(data).forEach(key => {
                        $(`#gallery-${key}`).val(data[key]);
                    });
                } catch (e) {
                    console.log('Failed to restore draft:', e);
                }
            }
        },

        clearDraft: function() {
            localStorage.removeItem('mlcg_draft_gallery');
        },

        // Keyboard shortcuts
        handleKeyboardShortcuts: function(e) {
            // Ctrl+S or Cmd+S to save
            if ((e.ctrlKey || e.metaKey) && e.keyCode === 83) {
                e.preventDefault();
                const $form = $('.mlcg-modal:visible form, #mlcg-create-gallery-form');
                if ($form.length) {
                    $form.submit();
                }
            }
            
            // Escape to close modal
            if (e.keyCode === 27) {
                this.closeModal();
            }
        }
    };

    // Initialize when document is ready
    $(document).ready(function() {
        MLCG.init();
    });

    // Handle save button click for edit modal
    $(document).on('click', '#save-gallery-changes', function() {
        $('#mlcg-edit-gallery-form').submit();
    });

    // Global access
    window.MLCG = MLCG;

})(jQuery);
