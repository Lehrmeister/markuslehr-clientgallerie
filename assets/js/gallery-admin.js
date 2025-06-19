/**
 * Gallery Admin JavaScript
 * 
 * Handles admin interface interactions for gallery management
 * 
 * @package MarkusLehr ClientGallerie
 * @author Markus Lehr
 * @since 1.0.0
 */

class MLCGAdmin {
    constructor() {
        this.init();
    }

    init() {
        // Bind events
        this.bindEvents();
        
        console.log('MLCG Admin initialized');
    }

    bindEvents() {
        // Edit gallery
        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('mlcg-edit')) {
                e.preventDefault();
                this.editGallery(e.target.dataset.id);
            }
        });

        // Delete gallery
        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('mlcg-delete')) {
                e.preventDefault();
                this.deleteGallery(e.target.dataset.id);
            }
        });

        // Publish/Unpublish gallery
        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('mlcg-publish')) {
                e.preventDefault();
                this.publishGallery(e.target.dataset.id);
            }
            if (e.target.classList.contains('mlcg-unpublish')) {
                e.preventDefault();
                this.unpublishGallery(e.target.dataset.id);
            }
        });

        // New gallery form submission
        const newGalleryForm = document.getElementById('mlcg-new-gallery-form');
        if (newGalleryForm) {
            newGalleryForm.addEventListener('submit', (e) => {
                e.preventDefault();
                this.createGallery();
            });
        }
    }

    async editGallery(galleryId) {
        try {
            console.log('Edit gallery:', galleryId);
            alert('Edit functionality will be implemented next');
        } catch (error) {
            console.error('Error editing gallery:', error);
            this.showNotice('Error editing gallery', 'error');
        }
    }

    async deleteGallery(galleryId) {
        if (!confirm(mlcgGallery.strings.confirmDelete)) {
            return;
        }

        try {
            const response = await this.ajaxRequest('delete_gallery', {
                gallery_id: galleryId
            });

            if (response.success) {
                const row = document.querySelector(`tr[data-gallery-id="${galleryId}"]`);
                if (row) {
                    row.remove();
                }
                this.showNotice(mlcgGallery.strings.deleteSuccess, 'success');
            } else {
                throw new Error(response.data);
            }
        } catch (error) {
            console.error('Error deleting gallery:', error);
            this.showNotice('Error deleting gallery: ' + error.message, 'error');
        }
    }

    async publishGallery(galleryId) {
        try {
            const response = await this.ajaxRequest('publish_gallery', {
                gallery_id: galleryId
            });

            if (response.success) {
                this.updateGalleryStatus(galleryId, 'published');
                this.showNotice('Gallery published successfully', 'success');
            } else {
                throw new Error(response.data);
            }
        } catch (error) {
            console.error('Error publishing gallery:', error);
            this.showNotice('Error publishing gallery: ' + error.message, 'error');
        }
    }

    async unpublishGallery(galleryId) {
        try {
            const response = await this.ajaxRequest('unpublish_gallery', {
                gallery_id: galleryId
            });

            if (response.success) {
                this.updateGalleryStatus(galleryId, 'draft');
                this.showNotice('Gallery unpublished successfully', 'success');
            } else {
                throw new Error(response.data);
            }
        } catch (error) {
            console.error('Error unpublishing gallery:', error);
            this.showNotice('Error unpublishing gallery: ' + error.message, 'error');
        }
    }

    async createGallery() {
        const form = document.getElementById('mlcg-new-gallery-form');
        const formData = new FormData(form);

        try {
            const response = await this.ajaxRequest('create_gallery', {
                name: formData.get('name'),
                description: formData.get('description'),
                client_id: formData.get('client_id') || 1
            });

            if (response.success) {
                this.showNotice(mlcgGallery.strings.createSuccess, 'success');
                window.location.href = 'admin.php?page=markuslehr-clientgallery';
            } else {
                throw new Error(response.data);
            }
        } catch (error) {
            console.error('Error creating gallery:', error);
            this.showNotice('Error creating gallery: ' + error.message, 'error');
        }
    }

    updateGalleryStatus(galleryId, newStatus) {
        const row = document.querySelector(`tr[data-gallery-id="${galleryId}"]`);
        if (row) {
            const statusCell = row.querySelector('.mlcg-status');
            const actionsCell = row.querySelector('.mlcg-actions');
            
            if (statusCell) {
                statusCell.textContent = newStatus.charAt(0).toUpperCase() + newStatus.slice(1);
                statusCell.className = `mlcg-status status-${newStatus}`;
            }

            if (actionsCell) {
                if (newStatus === 'published') {
                    const publishBtn = actionsCell.querySelector('.mlcg-publish');
                    if (publishBtn) {
                        publishBtn.textContent = 'Unpublish';
                        publishBtn.className = 'mlcg-unpublish';
                    }
                } else {
                    const unpublishBtn = actionsCell.querySelector('.mlcg-unpublish');
                    if (unpublishBtn) {
                        unpublishBtn.textContent = 'Publish';
                        unpublishBtn.className = 'mlcg-publish';
                    }
                }
            }
        }
    }

    async ajaxRequest(action, data) {
        const formData = new FormData();
        formData.append('action', 'mlcg_' + action);
        formData.append('nonce', mlcgGallery.nonce);
        
        for (const [key, value] of Object.entries(data)) {
            formData.append(key, value);
        }

        const response = await fetch(mlcgGallery.ajaxUrl, {
            method: 'POST',
            body: formData
        });

        return await response.json();
    }

    showNotice(message, type = 'info') {
        const notice = document.createElement('div');
        notice.className = `notice notice-${type} is-dismissible`;
        notice.innerHTML = `
            <p>${message}</p>
            <button type="button" class="notice-dismiss">
                <span class="screen-reader-text">Dismiss this notice.</span>
            </button>
        `;

        const h1 = document.querySelector('.wrap h1');
        if (h1) {
            h1.parentNode.insertBefore(notice, h1.nextSibling);
        }

        if (type === 'success') {
            setTimeout(() => {
                notice.remove();
            }, 3000);
        }

        notice.querySelector('.notice-dismiss').addEventListener('click', () => {
            notice.remove();
        });
    }
}

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    new MLCGAdmin();
});

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
