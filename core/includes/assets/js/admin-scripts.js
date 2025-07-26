/**
 * Monikit Admin Scripts
 * 
 * @package MONIGPDR
 * @since 1.0.0
 */

(function($) {
    'use strict';

    // Document ready
    $(document).ready(function() {
        
        // Initialize admin functionality
        MonikitAdmin.init();
        
        // Initialize button event handlers
        MonikitAdmin.initButtonHandlers();
        
    });

    // Monikit Admin object
    var MonikitAdmin = {
        
        /**
         * Initialize admin functionality
         */
        init: function() {
            this.initPasswordToggle();
            this.initPlaceholderInfo();
            this.initFormValidation();
            this.initWysiwygEnhancements();
        },

        /**
         * Initialize button event handlers
         */
        initButtonHandlers: function() {
            // Test Keycloak connection button
            $('.test-keycloak-connection').on('click', function(e) {
                e.preventDefault();
                MonikitAdmin.testKeycloakConnection();
            });

            // Preview email buttons
            $('.preview-email-en').on('click', function(e) {
                e.preventDefault();
                MonikitAdmin.previewEmailTemplate('en');
            });

            $('.preview-email-de').on('click', function(e) {
                e.preventDefault();
                MonikitAdmin.previewEmailTemplate('de');
            });
        },

        /**
         * Initialize password field toggle functionality
         */
        initPasswordToggle: function() {
            $('.monikit-settings-wrap input[type="password"]').each(function() {
                var $input = $(this);
                var $toggle = $('<button type="button" class="button password-toggle" style="margin-left: 5px;">👁</button>');
                
                $input.after($toggle);
                
                $toggle.on('click', function(e) {
                    e.preventDefault();
                    
                    if ($input.attr('type') === 'password') {
                        $input.attr('type', 'text');
                        $toggle.text('🙈');
                    } else {
                        $input.attr('type', 'password');
                        $toggle.text('👁');
                    }
                });
            });
        },

        /**
         * Initialize placeholder information display
         */
        initPlaceholderInfo: function() {
            // Add placeholder info to email template fields
            $('.monikit-settings-wrap textarea[name*="email_html"]').each(function() {
                var $textarea = $(this);
                var $info = $('<div class="placeholder-info"><h4>Available Placeholders:</h4><p><code>{user_email}</code> - User email address<br><code>{confirmation_link}</code> - Clickable confirmation link<br><code>{confirmation_code}</code> - Verification code</p></div>');
                
                $textarea.after($info);
            });
        },

        /**
         * Initialize form validation
         */
        initFormValidation: function() {
            $('.monikit-settings-wrap form').on('submit', function(e) {
                var isValid = true;
                var $form = $(this);
                
                // Check required fields
                $form.find('input[required], select[required], textarea[required]').each(function() {
                    var $field = $(this);
                    var value = $field.val().trim();
                    
                    if (!value) {
                        $field.addClass('error');
                        isValid = false;
                        
                        // Show error message
                        if (!$field.next('.error-message').length) {
                            $field.after('<span class="error-message" style="color: #dc3232; font-size: 12px; display: block; margin-top: 5px;">This field is required.</span>');
                        }
                    } else {
                        $field.removeClass('error');
                        $field.next('.error-message').remove();
                    }
                });
                
                // Check specific required Keycloak fields
                var requiredKeycloakFields = [
                    'keycloak_base_url',
                    'keycloak_realm', 
                    'keycloak_client_id',
                    'keycloak_admin_username',
                    'keycloak_admin_password'
                ];
                
                requiredKeycloakFields.forEach(function(fieldName) {
                    var $field = $form.find('input[name="monikit_settings[' + fieldName + ']"]');
                    if ($field.length) {
                        var value = $field.val().trim();
                        if (!value) {
                            $field.addClass('error');
                            isValid = false;
                            
                            if (!$field.next('.error-message').length) {
                                $field.after('<span class="error-message" style="color: #dc3232; font-size: 12px; display: block; margin-top: 5px;">This field is required.</span>');
                            }
                        } else {
                            $field.removeClass('error');
                            $field.next('.error-message').remove();
                        }
                    }
                });
                
                // Validate URL fields
                $form.find('input[name*="keycloak_base_url"]').each(function() {
                    var $field = $(this);
                    var value = $field.val().trim();
                    
                    if (value && !MonikitAdmin.isValidUrl(value)) {
                        $field.addClass('error');
                        isValid = false;
                        
                        if (!$field.next('.error-message').length) {
                            $field.after('<span class="error-message" style="color: #dc3232; font-size: 12px; display: block; margin-top: 5px;">Please enter a valid URL.</span>');
                        }
                    }
                });
                
                // Validate email template fields
                $form.find('textarea[name*="email_html"]').each(function() {
                    var $field = $(this);
                    var value = $field.val().trim();
                    
                    if (value && !MonikitAdmin.hasRequiredPlaceholders(value)) {
                        $field.addClass('error');
                        isValid = false;
                        
                        if (!$field.next('.error-message').length) {
                            $field.after('<span class="error-message" style="color: #dc3232; font-size: 12px; display: block; margin-top: 5px;">Email template must contain {confirmation_link} and {confirmation_code} placeholders.</span>');
                        }
                    }
                });
                
                if (!isValid) {
                    e.preventDefault();
                    
                    // Scroll to first error
                    var $firstError = $form.find('.error').first();
                    if ($firstError.length) {
                        $('html, body').animate({
                            scrollTop: $firstError.offset().top - 100
                        }, 500);
                    }
                    
                    // Show error notification
                    MonikitAdmin.showNotification('Please fix the errors above.', 'error');
                }
            });
        },

        /**
         * Initialize WYSIWYG editor enhancements
         */
        initWysiwygEnhancements: function() {
            // Add placeholder button to WYSIWYG editors
            if (typeof tinymce !== 'undefined') {
                tinymce.on('AddEditor', function(e) {
                    var editor = e.editor;
                    
                    if (editor.id && editor.id.indexOf('email_html') !== -1) {
                        editor.addButton('placeholders', {
                            text: 'Placeholders',
                            icon: false,
                            onclick: function() {
                                editor.windowManager.open({
                                    title: 'Insert Placeholder',
                                    body: {
                                        type: 'listbox',
                                        name: 'placeholder',
                                        label: 'Select placeholder:',
                                        values: [
                                            {text: 'User Email', value: '{user_email}'},
                                            {text: 'Confirmation Link', value: '{confirmation_link}'},
                                            {text: 'Confirmation Code', value: '{confirmation_code}'}
                                        ]
                                    },
                                    onsubmit: function(e) {
                                        editor.insertContent(e.data.placeholder);
                                    }
                                });
                            }
                        });
                    }
                });
            }
        },

        /**
         * Check if URL is valid
         */
        isValidUrl: function(string) {
            try {
                new URL(string);
                return true;
            } catch (_) {
                return false;
            }
        },

        /**
         * Check if email template has required placeholders
         */
        hasRequiredPlaceholders: function(content) {
            return content.indexOf('{confirmation_link}') !== -1 && 
                   content.indexOf('{confirmation_code}') !== -1;
        },

        /**
         * Show notification
         */
        showNotification: function(message, type) {
            type = type || 'info';
            
            var $notification = $('<div class="notice notice-' + type + ' is-dismissible"><p>' + message + '</p></div>');
            
            $('.monikit-settings-wrap h1').after($notification);
            
            // Auto dismiss after 5 seconds
            setTimeout(function() {
                $notification.fadeOut(function() {
                    $(this).remove();
                });
            }, 5000);
        },

        /**
         * Test Keycloak connection
         */
        testKeycloakConnection: function() {
            var $button = $('.test-keycloak-connection');
            var originalText = $button.text();
            
            $button.prop('disabled', true).text('Testing...');
            
            // Get form data
            var formData = {};
            $('.monikit-settings-wrap form').serializeArray().forEach(function(item) {
                formData[item.name] = item.value;
            });
            
            // Send AJAX request
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'monikit_test_keycloak_connection',
                    nonce: monikit_ajax.nonce,
                    settings: formData
                },
                success: function(response) {
                    if (response.success) {
                        MonikitAdmin.showNotification('Keycloak connection successful!', 'success');
                    } else {
                        MonikitAdmin.showNotification('Keycloak connection failed: ' + response.data, 'error');
                    }
                },
                error: function() {
                    MonikitAdmin.showNotification('Connection test failed. Please try again.', 'error');
                },
                complete: function() {
                    $button.prop('disabled', false).text(originalText);
                }
            });
        },

        /**
         * Preview email template
         */
        previewEmailTemplate: function(language) {
            var $button = $('.preview-email-' + language);
            var originalText = $button.text();
            
            $button.prop('disabled', true).text('Generating preview...');
            
            // Get template content
            var subject = $('input[name="monikit_settings[email_subject_' + language + ']"]').val();
            var html = $('textarea[name="monikit_settings[email_html_' + language + ']"]').val();
            
            if (!subject || !html) {
                MonikitAdmin.showNotification('Please fill in both subject and body fields.', 'warning');
                $button.prop('disabled', false).text(originalText);
                return;
            }
            
            // Replace placeholders with sample data
            var previewHtml = html
                .replace(/{user_email}/g, 'user@example.com')
                .replace(/{confirmation_link}/g, '<a href="#">https://example.com/confirm/123456</a>')
                .replace(/{confirmation_code}/g, '123456');
            
            // Open preview in new window
            var previewWindow = window.open('', '_blank', 'width=800,height=600');
            previewWindow.document.write(`
                <!DOCTYPE html>
                <html>
                <head>
                    <title>Email Preview - ${subject}</title>
                    <style>
                        body { font-family: Arial, sans-serif; margin: 20px; }
                        .email-preview { max-width: 600px; margin: 0 auto; }
                        .subject { font-size: 18px; font-weight: bold; margin-bottom: 20px; }
                        .content { line-height: 1.6; }
                    </style>
                </head>
                <body>
                    <div class="email-preview">
                        <div class="subject">Subject: ${subject}</div>
                        <div class="content">${previewHtml}</div>
                    </div>
                </body>
                </html>
            `);
            previewWindow.document.close();
            
            $button.prop('disabled', false).text(originalText);
        }
    };

})(jQuery); 