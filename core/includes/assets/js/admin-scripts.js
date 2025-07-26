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
            this.initButtonHandlers();
            this.checkAndLoadDefaults();
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
            
            // Load default templates button
            $('.load-default-templates').on('click', function(e) {
                e.preventDefault();
                MonikitAdmin.loadDefaultTemplates();
            });
            
            // Save translations button
            $('#save-translations').on('click', function(e) {
                e.preventDefault();
                MonikitAdmin.saveTranslations();
            });
            
            // Prevent form submission
            $('#monikit-translations-form').on('submit', function(e) {
                e.preventDefault();
                MonikitAdmin.saveTranslations();
            });
            
            			// Logs page functionality
			if ($('#export-logs').length) {
				$('#export-logs').on('click', function(e) {
					e.preventDefault();
					MonikitAdmin.exportLogs();
				});
			}
			
			if ($('#cleanup-logs').length) {
				$('#cleanup-logs').on('click', function(e) {
					e.preventDefault();
					MonikitAdmin.cleanupLogs();
				});
			}
			
			// Bulk actions functionality
			if ($('#select-all-checkbox').length) {
				MonikitAdmin.initBulkActions();
			}
            
            // Log details modal
            $('.view-log-details').on('click', function(e) {
                e.preventDefault();
                var logId = $(this).data('log-id');
                MonikitAdmin.viewLogDetails(logId);
            });
            
            // Close modal
            $('.monikit-modal-close').on('click', function() {
                $('.monikit-modal').hide();
            });
            
            $(window).on('click', function(e) {
                if ($(e.target).hasClass('monikit-modal')) {
                    $('.monikit-modal').hide();
                }
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
            var $form = $('.monikit-settings-wrap form');
            
            // Real-time URL validation and formatting hint
            var $keycloakUrl = $form.find('input[name="monikit_settings[keycloak_base_url]"]');
            if ($keycloakUrl.length) {
                $keycloakUrl.on('blur', function() {
                    var url = $(this).val().trim();
                    if (url !== '' && !MonikitAdmin.isValidUrl(url)) {
                        MonikitAdmin.showNotification('Please enter a valid URL for Keycloak Base URL', 'warning');
                    }
                });
                
                // Show URL format hint if not already present
                if (!$keycloakUrl.next('.url-format-hint').length) {
                    $keycloakUrl.after('<p class="description url-format-hint">Format: https://your-keycloak-server.com/ (trailing slash will be added automatically)</p>');
                }
            }
            
            $form.on('submit', function(e) {
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
         * Show notification in Keycloak test section
         */
        showKeycloakTestNotification: function(message, type) {
            var $resultDiv = $('.keycloak-test-result');
            
            // Create notification element
            var $notification = $('<div class="keycloak-test-notification notice notice-' + type + ' is-dismissible" style="margin: 10px 0; padding: 10px;"><p>' + message + '</p></div>');
            
            // Clear previous result and show new one
            $resultDiv.html($notification).show();
            
            // Auto-dismiss after 8 seconds
            setTimeout(function() {
                $resultDiv.fadeOut();
            }, 8000);
        },

        /**
         * Show notification in email preview section
         */
        showEmailPreviewNotification: function(message, type) {
            var $resultDiv = $('.email-preview-result');
            
            // Create notification element
            var $notification = $('<div class="email-preview-notification notice notice-' + type + ' is-dismissible" style="margin: 10px 0; padding: 10px;"><p>' + message + '</p></div>');
            
            // Clear previous result and show new one
            $resultDiv.html($notification).show();
            
            // Auto-dismiss after 8 seconds
            setTimeout(function() {
                $resultDiv.fadeOut();
            }, 8000);
        },

        /**
         * Test Keycloak connection
         */
        testKeycloakConnection: function() {
            var $button = $('.test-keycloak-connection');
            var originalText = $button.text();
            
            $button.prop('disabled', true).text('🔐 Testing Token...');
            
            // Get form data - ensure we get all form fields
            var formData = {};
            $('.monikit-settings-wrap form').find('input, select, textarea').each(function() {
                var $field = $(this);
                var name = $field.attr('name');
                var value = $field.val();
                
                if (name && name.indexOf('monikit_settings') !== -1) {
                    formData[name] = value;
                }
            });
            
            // Debug: Log the form data to console
            console.log('Form data being sent:', formData);
            
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
                    console.log('Response:', response);
                    if (response.success) {
                        MonikitAdmin.showKeycloakTestNotification(response.data, 'success');
                    } else {
                        // Check if it's a warning (token valid but realm access failed)
                        if (response.data.indexOf('⚠️') !== -1) {
                            MonikitAdmin.showKeycloakTestNotification(response.data, 'warning');
                        } else {
                            MonikitAdmin.showKeycloakTestNotification(response.data, 'error');
                        }
                    }
                },
                error: function(xhr, status, error) {
                    console.log('AJAX Error:', xhr, status, error);
                    MonikitAdmin.showKeycloakTestNotification('Connection test failed. Please check your settings and try again.', 'error');
                },
                complete: function() {
                    $button.prop('disabled', false).text(originalText);
                }
            });
        },

        /**
         * Check if email fields are empty and load defaults if needed
         */
        checkAndLoadDefaults: function() {
            // Only run this on the main settings page, not on translations page
            if ($('#monikit-translations-form').length > 0) {
                return; // We're on translations page, skip this function
            }
            
            // Check if email fields exist before trying to access them
            var $emailSubjectEn = $('input[name="monikit_settings[email_subject_en]"]');
            var $emailSubjectDe = $('input[name="monikit_settings[email_subject_de]"]');
            
            if (!$emailSubjectEn.length || !$emailSubjectDe.length) {
                return; // Email fields don't exist on this page
            }
            
            // Check if email fields are empty
            var emailSubjectEn = $emailSubjectEn.val() ? $emailSubjectEn.val().trim() : '';
            var emailSubjectDe = $emailSubjectDe.val() ? $emailSubjectDe.val().trim() : '';
            
            // Check WYSIWYG content
            var emailHtmlEn = '';
            var emailHtmlDe = '';
            
            if (typeof tinymce !== 'undefined') {
                if (tinymce.get('email_html_en')) {
                    emailHtmlEn = tinymce.get('email_html_en').getContent() ? tinymce.get('email_html_en').getContent().trim() : '';
                }
                if (tinymce.get('email_html_de')) {
                    emailHtmlDe = tinymce.get('email_html_de').getContent() ? tinymce.get('email_html_de').getContent().trim() : '';
                }
            } else {
                var $emailHtmlEn = $('textarea[name="monikit_settings[email_html_en]"]');
                var $emailHtmlDe = $('textarea[name="monikit_settings[email_html_de]"]');
                
                if ($emailHtmlEn.length) {
                    emailHtmlEn = $emailHtmlEn.val() ? $emailHtmlEn.val().trim() : '';
                }
                if ($emailHtmlDe.length) {
                    emailHtmlDe = $emailHtmlDe.val() ? $emailHtmlDe.val().trim() : '';
                }
            }
            
            // If all email fields are empty, automatically load defaults
            if (!emailSubjectEn && !emailSubjectDe && !emailHtmlEn && !emailHtmlDe) {
                console.log('Email fields are empty, loading default templates...');
                this.loadDefaultTemplates();
            }
        },

        /**
         * Load default email templates
         */
        loadDefaultTemplates: function() {
            var $button = $('.load-default-templates');
            var originalText = $button.text();
            
            $button.prop('disabled', true).text('Loading templates...');
            
            // Send AJAX request to get default templates
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'monikit_load_default_templates',
                    nonce: monikit_ajax.nonce
                },
                success: function(response) {
                    console.log('Default templates response:', response);
                    if (response.success) {
                        // Fill in the email fields with default templates
                        $('input[name="monikit_settings[email_subject_en]"]').val(response.data.email_subject_en);
                        $('input[name="monikit_settings[email_subject_de]"]').val(response.data.email_subject_de);
                        
                        // For WYSIWYG editors, we need to set the content differently
                        if (typeof tinymce !== 'undefined') {
                            // Set content for English WYSIWYG
                            if (tinymce.get('email_html_en')) {
                                tinymce.get('email_html_en').setContent(response.data.email_html_en);
                            }
                            
                            // Set content for German WYSIWYG
                            if (tinymce.get('email_html_de')) {
                                tinymce.get('email_html_de').setContent(response.data.email_html_de);
                            }
                        } else {
                            // Fallback for textarea if WYSIWYG is not available
                            $('textarea[name="monikit_settings[email_html_en]"]').val(response.data.email_html_en);
                            $('textarea[name="monikit_settings[email_html_de]"]').val(response.data.email_html_de);
                        }
                        
                        MonikitAdmin.showEmailPreviewNotification('Default templates loaded successfully!', 'success');
                    } else {
                        MonikitAdmin.showEmailPreviewNotification('Failed to load default templates.', 'error');
                    }
                },
                error: function(xhr, status, error) {
                    console.log('AJAX Error:', xhr, status, error);
                    MonikitAdmin.showEmailPreviewNotification('Failed to load default templates. Please try again.', 'error');
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
            
            try {
                $button.prop('disabled', true).text('Generating preview...');
                
                // Add a timeout to prevent button from getting stuck
                var timeoutId = setTimeout(function() {
                    $button.prop('disabled', false).text(originalText);
                    MonikitAdmin.showEmailPreviewNotification('Preview generation timed out. Please try again.', 'warning');
                }, 10000); // 10 second timeout
                
                // Get template content
                var subject = $('input[name="monikit_settings[email_subject_' + language + ']"]').val();
                var html = '';
                
                // Get content from WYSIWYG editor or textarea
                if (typeof tinymce !== 'undefined' && tinymce.get('email_html_' + language)) {
                    html = tinymce.get('email_html_' + language).getContent();
                } else {
                    html = $('textarea[name="monikit_settings[email_html_' + language + ']"]').val();
                }
                
                if (!subject || !html) {
                    MonikitAdmin.showEmailPreviewNotification('Please fill in both subject and body fields.', 'warning');
                    $button.prop('disabled', false).text(originalText);
                    return;
                }
                
                // Replace placeholders with sample data
                var previewHtml = html
                    .replace(/{user_email}/g, 'user@example.com')
                    .replace(/{confirmation_link}/g, 'https://example.com/confirm/123456')
                    .replace(/{confirmation_code}/g, '123456');
                
                // Open preview in new window
                var previewWindow = window.open('', '_blank', 'width=800,height=600');
                if (previewWindow) {
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
                } else {
                    MonikitAdmin.showEmailPreviewNotification('Popup blocked. Please allow popups for this site.', 'warning');
                }
                
                // Clear the timeout since preview was successful
                clearTimeout(timeoutId);
                
            } catch (error) {
                console.error('Preview error:', error);
                MonikitAdmin.showEmailPreviewNotification('Error generating preview: ' + error.message, 'error');
                clearTimeout(timeoutId);
            } finally {
                $button.prop('disabled', false).text(originalText);
            }
        },

        /**
         * Save translations
         */
        saveTranslations: function() {
            var $button = $('#save-translations');
            var $form = $('#monikit-translations-form');
            
            if (!$button.length) {
                console.error('Save button not found');
                return;
            }
            
            if (!$form.length) {
                console.error('Translation form not found');
                return;
            }
            
            var $spinner = $button.siblings('.spinner');
            var originalText = $button.text();
            
            // Show spinner and disable button
            $spinner.addClass('is-active');
            $button.prop('disabled', true).text('Saving...');
            
            // Collect form data
            var formData = new FormData();
            formData.append('action', 'monikit_save_translations');
            formData.append('nonce', $('#monikit_translations_nonce').val());
            
            // Collect translations
            var translations = {
                en: {},
                de: {}
            };
            
            $('#monikit-translations-form input[name^="translations[en]"]').each(function() {
                var key = $(this).attr('name').match(/\[([^\]]+)\]$/)[1];
                translations.en[key] = $(this).val();
            });
            
            $('#monikit-translations-form input[name^="translations[de]"]').each(function() {
                var key = $(this).attr('name').match(/\[([^\]]+)\]$/)[1];
                translations.de[key] = $(this).val();
            });
            
            formData.append('translations', JSON.stringify(translations));
            
            // Send AJAX request
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.success) {
                        MonikitAdmin.showNotification(response.data.message, 'success');
                    } else {
                        MonikitAdmin.showNotification(response.data.message, 'error');
                    }
                },
                error: function(xhr, status, error) {
                    console.log('AJAX Error:', xhr, status, error);
                    MonikitAdmin.showNotification('Failed to save translations. Please try again.', 'error');
                },
                complete: function() {
                    $spinner.removeClass('is-active');
                    $button.prop('disabled', false).text(originalText);
                }
            });
        },

        /**
         * Show notification
         */
        showNotification: function(message, type) {
            var noticeClass = type === 'success' ? 'notice-success' : 'notice-error';
            var $notice = $('<div class="notice ' + noticeClass + ' is-dismissible monikit-sticky-notice"><p>' + message + '</p></div>');
            
            // Position the notification right before the form for better visibility
            var $form = $('#monikit-translations-form');
            if ($form.length) {
                $form.before($notice);
            } else {
                // Fallback to top of page
                $('.wrap h1').after($notice);
            }
            
            // Scroll to notification if it's not visible
            $('html, body').animate({
                scrollTop: $notice.offset().top - 50
            }, 500);
            
            // Add a subtle highlight effect to the notification
            $notice.hide().fadeIn(300).addClass('monikit-notice-highlight');
            
            // Auto-dismiss after 8 seconds for success, 10 seconds for error
            var dismissTime = type === 'success' ? 8000 : 10000;
            setTimeout(function() {
                $notice.fadeOut(500, function() {
                    $(this).remove();
                });
            }, dismissTime);
            
            // Add visual feedback to the form
            if (type === 'success') {
                $('#monikit-translations-form').addClass('saved-successfully');
                setTimeout(function() {
                    $('#monikit-translations-form').removeClass('saved-successfully');
                }, 2000);
            }
        },

        /**
         * Export logs to CSV
         */
        exportLogs: function() {
            var $button = $('#export-logs');
            var originalText = $button.text();
            
            $button.prop('disabled', true).text('Exporting...');
            
            // Get current filters
            var filters = {
                email: $('#email').val() || '',
                action: $('#action').val() || '',
                status: $('#status').val() || '',
                date_from: $('#date_from').val() || '',
                date_to: $('#date_to').val() || ''
            };
            
            $.post(ajaxurl, {
                action: 'monikit_export_logs',
                nonce: $('#monikit_logs_nonce').val(),
                filters: filters
            }, function(response) {
                if (response.success) {
                    // Create download link
                    var blob = new Blob([response.data.csv], {type: 'text/csv'});
                    var url = window.URL.createObjectURL(blob);
                    var a = document.createElement('a');
                    a.href = url;
                    a.download = 'monikit-deletion-logs-' + new Date().toISOString().split('T')[0] + '.csv';
                    document.body.appendChild(a);
                    a.click();
                    document.body.removeChild(a);
                    window.URL.revokeObjectURL(url);
                    
                    MonikitAdmin.showNotification('Logs exported successfully.', 'success');
                } else {
                    MonikitAdmin.showNotification('Export failed: ' + response.data.message, 'error');
                }
            }).fail(function() {
                MonikitAdmin.showNotification('Export failed. Please try again.', 'error');
            }).always(function() {
                $button.prop('disabled', false).text(originalText);
            });
        },

        		/**
		 * Cleanup old logs
		 */
		cleanupLogs: function() {
			var retentionDays = $('#retention-period').val();
			var retentionText = $('#retention-period option:selected').text();
			
			var confirmMessage = 'This will delete logs older than ' + retentionText + '. This action cannot be undone. Continue?';
			
			if (!confirm(confirmMessage)) {
				return;
			}
			
			var $button = $('#cleanup-logs');
			var originalText = $button.text();
			
			$button.prop('disabled', true).text('Cleaning up...');
			
			$.post(ajaxurl, {
				action: 'monikit_cleanup_logs',
				nonce: $('#monikit_logs_nonce').val(),
				retention_days: retentionDays
			}, function(response) {
				if (response.success) {
					MonikitAdmin.showNotification(response.data.message, 'success');
					setTimeout(function() {
						location.reload();
					}, 1500);
				} else {
					MonikitAdmin.showNotification('Cleanup failed: ' + response.data.message, 'error');
				}
			}).fail(function() {
				MonikitAdmin.showNotification('Cleanup failed. Please try again.', 'error');
			}).always(function() {
				$button.prop('disabled', false).text(originalText);
			});
		},

        		/**
		 * View log details
		 */
		viewLogDetails: function(logId) {
			$('#log-details-content').html('<p>Loading...</p>');
			$('#log-details-modal').show();
			
			$.post(ajaxurl, {
				action: 'monikit_get_log_details',
				nonce: $('#monikit_logs_nonce').val(),
				log_id: logId
			}, function(response) {
				if (response.success) {
					$('#log-details-content').html(response.data.html);
				} else {
					$('#log-details-content').html('<p>Failed to load log details.</p>');
				}
			}).fail(function() {
				$('#log-details-content').html('<p>Failed to load log details.</p>');
			});
		},

		/**
		 * Initialize bulk actions
		 */
		initBulkActions: function() {
			// Select all checkbox
			$('#select-all-checkbox').on('change', function() {
				var isChecked = $(this).is(':checked');
				$('.log-checkbox').prop('checked', isChecked);
				MonikitAdmin.updateSelectedCount();
				MonikitAdmin.updateDeleteButton();
			});

			// Individual checkboxes
			$('.log-checkbox').on('change', function() {
				MonikitAdmin.updateSelectedCount();
				MonikitAdmin.updateDeleteButton();
				MonikitAdmin.updateSelectAllCheckbox();
			});

			// Select all button
			$('#select-all-logs').on('click', function() {
				$('.log-checkbox').prop('checked', true);
				$('#select-all-checkbox').prop('checked', true);
				MonikitAdmin.updateSelectedCount();
				MonikitAdmin.updateDeleteButton();
			});

			// Deselect all button
			$('#deselect-all-logs').on('click', function() {
				$('.log-checkbox').prop('checked', false);
				$('#select-all-checkbox').prop('checked', false);
				MonikitAdmin.updateSelectedCount();
				MonikitAdmin.updateDeleteButton();
			});

			// Delete selected button
			$('#delete-selected-logs').on('click', function() {
				MonikitAdmin.deleteSelectedLogs();
			});

			// Delete single log buttons
			$('.delete-single-log').on('click', function() {
				var logId = $(this).data('log-id');
				MonikitAdmin.deleteSingleLog(logId);
			});
		},

		/**
		 * Update selected count
		 */
		updateSelectedCount: function() {
			var count = $('.log-checkbox:checked').length;
			$('.selected-count').text(count + ' selected');
		},

		/**
		 * Update delete button state
		 */
		updateDeleteButton: function() {
			var count = $('.log-checkbox:checked').length;
			$('#delete-selected-logs').prop('disabled', count === 0);
		},

		/**
		 * Update select all checkbox state
		 */
		updateSelectAllCheckbox: function() {
			var totalCheckboxes = $('.log-checkbox').length;
			var checkedCheckboxes = $('.log-checkbox:checked').length;
			
			if (checkedCheckboxes === 0) {
				$('#select-all-checkbox').prop('indeterminate', false).prop('checked', false);
			} else if (checkedCheckboxes === totalCheckboxes) {
				$('#select-all-checkbox').prop('indeterminate', false).prop('checked', true);
			} else {
				$('#select-all-checkbox').prop('indeterminate', true);
			}
		},

		/**
		 * Delete selected logs
		 */
		deleteSelectedLogs: function() {
			var selectedIds = [];
			$('.log-checkbox:checked').each(function() {
				selectedIds.push($(this).val());
			});

			if (selectedIds.length === 0) {
				MonikitAdmin.showNotification('No logs selected for deletion.', 'error');
				return;
			}

			var confirmMessage = 'Are you sure you want to delete ' + selectedIds.length + ' selected log entries? This action cannot be undone.';
			
			if (!confirm(confirmMessage)) {
				return;
			}

			var $button = $('#delete-selected-logs');
			var originalText = $button.text();
			
			$button.prop('disabled', true).text('Deleting...');
			
			$.post(ajaxurl, {
				action: 'monikit_delete_selected_logs',
				nonce: $('#monikit_logs_nonce').val(),
				log_ids: selectedIds
			}, function(response) {
				if (response.success) {
					MonikitAdmin.showNotification(response.data.message, 'success');
					setTimeout(function() {
						location.reload();
					}, 1500);
				} else {
					MonikitAdmin.showNotification('Delete failed: ' + response.data.message, 'error');
				}
			}).fail(function() {
				MonikitAdmin.showNotification('Delete failed. Please try again.', 'error');
			}).always(function() {
				$button.prop('disabled', false).text(originalText);
			});
		},

		/**
		 * Delete single log
		 */
		deleteSingleLog: function(logId) {
			var confirmMessage = 'Are you sure you want to delete this log entry? This action cannot be undone.';
			
			if (!confirm(confirmMessage)) {
				return;
			}

			var $button = $('.delete-single-log[data-log-id="' + logId + '"]');
			var originalText = $button.text();
			
			$button.prop('disabled', true).text('Deleting...');
			
			$.post(ajaxurl, {
				action: 'monikit_delete_single_log',
				nonce: $('#monikit_logs_nonce').val(),
				log_id: logId
			}, function(response) {
				if (response.success) {
					MonikitAdmin.showNotification(response.data.message, 'success');
					$button.closest('tr').fadeOut(500, function() {
						$(this).remove();
						MonikitAdmin.updateSelectedCount();
						MonikitAdmin.updateDeleteButton();
						MonikitAdmin.updateSelectAllCheckbox();
					});
				} else {
					MonikitAdmin.showNotification('Delete failed: ' + response.data.message, 'error');
				}
			}).fail(function() {
				MonikitAdmin.showNotification('Delete failed. Please try again.', 'error');
			}).always(function() {
				$button.prop('disabled', false).text(originalText);
			});
		}
    };

})(jQuery); 