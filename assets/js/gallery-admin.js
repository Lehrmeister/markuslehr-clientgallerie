/**
 * Gallery Admin JavaScript
 * 
 * Handles admin interface interactions for gallery management
 * 
 * @package MarkusLehr ClientGallerie
 * @author Markus Lehr
 * @since 1.0.0
 */

(function($) {
    'use strict';

    const GalleryAdmin = {
        
        /**
         * Initialize the admin interface
         */
        init: function() {
            this.bindEvents();
            this.setupAutoSlugGeneration();
        },

        /**
         * Bind event handlers
         */
        bindEvents: function() {
            // Create gallery form
            $('#mlcg-create-gallery-form').on('submit', this.handleCreateGallery.bind(this));
            
            // Edit gallery form
            $('#mlcg-edit-gallery-form').on('submit', this.handleEditGallery.bind(this));
            
            // Gallery actions
            $(document).on('click', '.edit-gallery', this.handleEditClick.bind(this));
            $(document).on('click', '.delete-gallery', this.handleDeleteClick.bind(this));
            $(document).on('click', '.publish-gallery, .unpublish-gallery', this.handlePublishClick.bind(this));
            
            // Modal controls
            $('#cancel-edit').on('click', this.hideEditModal.bind(this));
            
            // Close modal on outside click
            $(document).on('click', '#mlcg-edit-gallery-modal', function(e) {
                if (e.target === this) {
                    GalleryAdmin.hideEditModal();
                }
            });
        },

        /**
         * Setup automatic slug generation from name
         */
        setupAutoSlugGeneration: function() {
            $('#gallery-name').on('input', function() {
                const name = $(this).val();
                const slug = name
                    .toLowerCase()
                    .replace(/[^a-z0-9\s-]/g, '')
                    .replace(/\s+/g, '-')
                    .replace(/-+/g, '-')
                    .replace(/^-|-$/g, '');
                
                $('#gallery-slug').val(slug);
            });
        },

        /**
         * Handle create gallery form submission
         */
        handleCreateGallery: function(e) {
            e.preventDefault();
            
            const $form = $(e.target);
            const $spinner = $form.find('.spinner');
            const $submit = $form.find('input[type="submit"]');
            
            // Show loading state
            $spinner.addClass('is-active');
            $submit.prop('disabled', true);
            
            // Prepare form data
            const formData = {
                action: 'mlcg_create_gallery',
                nonce: mlcgGallery.nonce,
                name: $form.find('[name="name"]').val(),
                slug: $form.find('[name="slug"]').val(),
                client_id: $form.find('[name="client_id"]').val(),
                description: $form.find('[name="description"]').val()
            };

            // Send AJAX request
            $.post(mlcgGallery.ajaxUrl, formData)
                .done(function(response) {
                    if (response.success) {
                        GalleryAdmin.showNotice(response.data.message, 'success');
                        $form[0].reset();
                        // Reload page to show new gallery
                        setTimeout(function() {
                            location.reload();
                        }, 1000);
                    } else {
                        GalleryAdmin.showNotice(response.data.message, 'error');
                    }
                })
                .fail(function() {
                    GalleryAdmin.showNotice('Network error occurred', 'error');
                })
                .always(function() {
                    $spinner.removeClass('is-active');
                    $submit.prop('disabled', false);
                });
        },

        /**
         * Handle edit button click
         */
        handleEditClick: function(e) {
            const galleryId = $(e.target).data('gallery-id');
            const $row = $(`tr[data-gallery-id="${galleryId}"]`);
            
            // Populate edit form with current data
            const galleryName = $row.find('td:first strong').text();
            const gallerySlug = $row.find('td:eq(1)').text();
            const galleryDescription = $row.find('td:first small').text() || '';
            
            $('#edit-gallery-id').val(galleryId);
            $('#edit-gallery-name').val(galleryName);
            $('#edit-gallery-slug').val(gallerySlug);
            $('#edit-gallery-description').val(galleryDescription);
            
            this.showEditModal();
        },

        /**
         * Handle edit gallery form submission
         */
        handleEditGallery: function(e) {
            e.preventDefault();
            
            const $form = $(e.target);
            const $spinner = $form.find('.spinner');
            const $submit = $form.find('input[type="submit"]');
            
            // Show loading state
            $spinner.addClass('is-active');
            $submit.prop('disabled', true);
            
            // Prepare form data
            const formData = {
                action: 'mlcg_update_gallery',
                nonce: mlcgGallery.nonce,
                id: $form.find('[name="id"]').val(),
                name: $form.find('[name="name"]').val(),
                slug: $form.find('[name="slug"]').val(),
                description: $form.find('[name="description"]').val()
            };

            // Send AJAX request
            $.post(mlcgGallery.ajaxUrl, formData)
                .done(function(response) {
                    if (response.success) {
                        GalleryAdmin.showNotice(response.data.message, 'success');
                        GalleryAdmin.hideEditModal();
                        // Reload page to show changes
                        setTimeout(function() {
                            location.reload();
                        }, 1000);
                    } else {
                        GalleryAdmin.showNotice(response.data.message, 'error');
                    }
                })
                .fail(function() {
                    GalleryAdmin.showNotice('Network error occurred', 'error');
                })
                .always(function() {
                    $spinner.removeClass('is-active');
                    $submit.prop('disabled', false);
                });
        },

        /**
         * Handle delete button click
         */
        handleDeleteClick: function(e) {
            const galleryId = $(e.target).data('gallery-id');
            const $row = $(`tr[data-gallery-id="${galleryId}"]`);
            const galleryName = $row.find('td:first strong').text();
            
            if (!confirm(`${mlcgGallery.strings.confirmDelete}\n\nGallery: ${galleryName}`)) {
                return;
            }
            
            // Prepare form data
            const formData = {
                action: 'mlcg_delete_gallery',
                nonce: mlcgGallery.nonce,
                id: galleryId
            };

            // Send AJAX request
            $.post(mlcgGallery.ajaxUrl, formData)
                .done(function(response) {
                    if (response.success) {
                        GalleryAdmin.showNotice(response.data.message, 'success');
                        $row.fadeOut(function() {
                            $(this).remove();
                        });
                    } else {
                        GalleryAdmin.showNotice(response.data.message, 'error');
                    }
                })
                .fail(function() {
                    GalleryAdmin.showNotice('Network error occurred', 'error');
                });
        },

        /**
         * Handle publish/unpublish button click
         */
        handlePublishClick: function(e) {
            const $button = $(e.target);
            const galleryId = $button.data('gallery-id');
            const action = $button.data('action');
            
            // Prepare form data
            const formData = {
                action: 'mlcg_publish_gallery',
                nonce: mlcgGallery.nonce,
                id: galleryId,
                status: action
            };

            // Send AJAX request
            $.post(mlcgGallery.ajaxUrl, formData)
                .done(function(response) {
                    if (response.success) {
                        GalleryAdmin.showNotice(response.data.message, 'success');
                        // Reload page to show status change
                        setTimeout(function() {
                            location.reload();
                        }, 1000);
                    } else {
                        GalleryAdmin.showNotice(response.data.message, 'error');
                    }
                })
                .fail(function() {
                    GalleryAdmin.showNotice('Network error occurred', 'error');
                });
        },

        /**
         * Show edit modal
         */
        showEditModal: function() {
            $('#mlcg-edit-gallery-modal').show();
            $('body').addClass('modal-open');
        },

        /**
         * Hide edit modal
         */
        hideEditModal: function() {
            $('#mlcg-edit-gallery-modal').hide();
            $('body').removeClass('modal-open');
        },

        /**
         * Show admin notice
         */
        showNotice: function(message, type = 'info') {
            const $notice = $(`
                <div class="notice notice-${type} is-dismissible">
                    <p>${message}</p>
                    <button type="button" class="notice-dismiss">
                        <span class="screen-reader-text">Dismiss this notice.</span>
                    </button>
                </div>
            `);
            
            $('.wrap h1').after($notice);
            
            // Auto-dismiss success notices
            if (type === 'success') {
                setTimeout(function() {
                    $notice.fadeOut();
                }, 3000);
            }
            
            // Bind dismiss button
            $notice.find('.notice-dismiss').on('click', function() {
                $notice.fadeOut();
            });
        }
    };

    // Initialize when document is ready
    $(document).ready(function() {
        GalleryAdmin.init();
    });

})(jQuery);
