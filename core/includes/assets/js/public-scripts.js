/**
 * Monikit Public Deletion Page Scripts
 */

(function($) {
    'use strict';

    // Global function to initialize forms
    window.initMonigpdrForm = function(formId) {
        new MonigpdrForm(formId);
    };

    // Form class to handle multiple instances
    function MonigpdrForm(formId) {
        this.formId = formId;
        this.currentStep = 1;
        this.userEmail = '';
        this.isProcessing = false;
        
        // DOM elements
        this.$form = $('#' + formId);
        this.$messages = $('#' + formId + '-messages');
        this.$step1 = $('#' + formId + '-step-1');
        this.$step2 = $('#' + formId + '-step-2');
        this.$step3 = $('#' + formId + '-step-3');
        this.$emailInput = $('#' + formId + '-email');
        this.$codeInput = $('#' + formId + '-confirmation-code');
        this.$confirmCheckbox = $('#' + formId + '-confirm-deletion');
        
        this.init();
    }

    // MonigpdrForm prototype methods
    MonigpdrForm.prototype.init = function() {
        this.bindEvents();
        this.setupCodeInput();
    };

    MonigpdrForm.prototype.bindEvents = function() {
        var self = this;
        this.$form.on('submit', function(e) { self.handleFormSubmit(e); });
        $('[data-back-btn="' + this.formId + '"]').on('click', function() { self.goToStep1(); });
        $('[data-cancel-btn="' + this.formId + '"]').on('click', function() { self.goToStep1(); });
        this.$emailInput.on('input', function() { self.clearMessages(); });
        this.$codeInput.on('input', function() { self.clearMessages(); });
        this.$confirmCheckbox.on('change', function() { self.clearMessages(); });
    };

    MonigpdrForm.prototype.setupCodeInput = function() {
        this.$codeInput.on('input', function() {
            let value = $(this).val().replace(/\D/g, '');
            if (value.length > 6) {
                value = value.substring(0, 6);
            }
            $(this).val(value);
        });

        this.$codeInput.on('keypress', function(e) {
            if (e.which < 48 || e.which > 57) {
                e.preventDefault();
            }
        });
    };

    MonigpdrForm.prototype.handleFormSubmit = function(e) {
        e.preventDefault();

        if (this.isProcessing) {
            return;
        }

        this.clearMessages();

        switch (this.currentStep) {
            case 1:
                this.handleStep1Submit();
                break;
            case 2:
                this.handleStep2Submit();
                break;
            case 3:
                this.handleStep3Submit();
                break;
        }
    };

    MonigpdrForm.prototype.handleStep1Submit = function() {
        const email = this.$emailInput.val().trim();

        if (!email) {
            this.showMessage('error', monigpdr_ajax.strings.email_required);
            this.$emailInput.focus();
            return;
        }

        if (!this.isValidEmail(email)) {
            this.showMessage('error', monigpdr_ajax.strings.email_required);
            this.$emailInput.focus();
            return;
        }

        this.userEmail = email;
        this.setLoading(true);

        $.ajax({
            url: monigpdr_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'monigpdr_request_deletion',
                email: email,
                nonce: monigpdr_ajax.nonce
            },
            success: (response) => {
                this.setLoading(false);
                
                if (response.success) {
                    this.showMessage('success', response.data.message);
                    this.goToStep2();
                } else {
                    this.showMessage('error', response.data.message);
                }
            },
            error: () => {
                this.setLoading(false);
                this.showMessage('error', monigpdr_ajax.strings.error_occurred);
            }
        });
    };

    MonigpdrForm.prototype.handleStep2Submit = function() {
        const code = this.$codeInput.val().trim();

        if (!code) {
            this.showMessage('error', monigpdr_ajax.strings.code_required);
            this.$codeInput.focus();
            return;
        }

        if (code.length !== 6) {
            this.showMessage('error', monigpdr_ajax.strings.invalid_code);
            this.$codeInput.focus();
            return;
        }

        this.setLoading(true);

        $.ajax({
            url: monigpdr_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'monigpdr_confirm_deletion',
                email: this.userEmail,
                code: code,
                nonce: monigpdr_ajax.nonce
            },
            success: (response) => {
                this.setLoading(false);
                
                if (response.success) {
                    this.showMessage('success', response.data.message);
                    this.goToStep3();
                } else {
                    this.showMessage('error', response.data.message);
                }
            },
            error: () => {
                this.setLoading(false);
                this.showMessage('error', monigpdr_ajax.strings.error_occurred);
            }
        });
    };

    MonigpdrForm.prototype.handleStep3Submit = function() {
        if (!this.$confirmCheckbox.is(':checked')) {
            this.showMessage('error', 'Please confirm that you understand this action is irreversible.');
            this.$confirmCheckbox.focus();
            return;
        }

        this.setLoading(true);

        // Simulate final deletion process
        setTimeout(() => {
            this.setLoading(false);
            this.showMessage('success', monigpdr_ajax.strings.account_deleted);
            
            // Disable form after successful deletion
            this.$form.find('input, button').prop('disabled', true);
            
            // Show completion message
            setTimeout(() => {
                this.showCompletionMessage();
            }, 2000);
        }, 1500);
    };

    MonigpdrForm.prototype.goToStep2 = function() {
        this.$step1.hide();
        this.$step2.show();
        this.$step3.hide();
        this.currentStep = 2;
        this.$codeInput.focus();
    };

    MonigpdrForm.prototype.goToStep3 = function() {
        this.$step1.hide();
        this.$step2.hide();
        this.$step3.show();
        this.currentStep = 3;
        this.$confirmCheckbox.focus();
    };

    MonigpdrForm.prototype.goToStep1 = function() {
        this.$step1.show();
        this.$step2.hide();
        this.$step3.hide();
        this.currentStep = 1;
        this.$emailInput.focus();
        
        // Reset form
        this.$form[0].reset();
        this.userEmail = '';
        this.clearMessages();
    };

    MonigpdrForm.prototype.showMessage = function(type, message) {
        const messageClass = `monigpdr-message ${type}`;
        const $message = $(`<div class="${messageClass}">${message}</div>`);
        
        this.$messages.append($message);
        
        // Auto-remove after 5 seconds
        setTimeout(() => {
            $message.fadeOut(() => {
                $message.remove();
            });
        }, 5000);
        
        // Scroll to message
        $('html, body').animate({
            scrollTop: $message.offset().top - 100
        }, 300);
    };

    MonigpdrForm.prototype.showCompletionMessage = function() {
        const completionHtml = `
            <div class="monigpdr-completion">
                <div class="monigpdr-completion-icon">✅</div>
                <h2>Account Deletion Complete</h2>
                <p>Your account has been successfully deleted. Thank you for using our service.</p>
                <p><small>You can now close this page.</small></p>
            </div>
        `;
        
        this.$form.closest('.monigpdr-deletion-form-container').html(completionHtml);
    };

    MonigpdrForm.prototype.clearMessages = function() {
        this.$messages.empty();
    };

    MonigpdrForm.prototype.setLoading = function(loading) {
        this.isProcessing = loading;
        const $submitBtn = this.$form.find('button[type="submit"]');
        
        if (loading) {
            $submitBtn.addClass('monigpdr-loading').prop('disabled', true);
        } else {
            $submitBtn.removeClass('monigpdr-loading').prop('disabled', false);
        }
    };

    MonigpdrForm.prototype.isValidEmail = function(email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    };

    // Initialize when DOM is ready
    jQuery(document).ready(function($) {
        // Initialize all deletion forms on the page
        $('.monigpdr-deletion-form').each(function() {
            var formId = $(this).attr('id');
            if (formId) {
                new MonigpdrForm(formId);
            }
        });
        
        // Add CSS for completion state
        const styles = `
            <style>
                .monigpdr-completion {
                    text-align: center;
                    padding: 40px 20px;
                }
                .monigpdr-completion-icon {
                    font-size: 4rem;
                    margin-bottom: 20px;
                }
                .monigpdr-completion h2 {
                    color: #22543d;
                    margin-bottom: 16px;
                }
                .monigpdr-completion p {
                    color: #718096;
                    margin-bottom: 12px;
                }
                .monigpdr-completion small {
                    color: #a0aec0;
                }
            </style>
        `;
        $('head').append(styles);
    });

})(jQuery); 