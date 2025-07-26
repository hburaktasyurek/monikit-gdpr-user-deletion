<?php
/**
 * Deletion Form Template
 * 
 * This template can be embedded anywhere using the shortcode [monigpdr_deletion_form]
 */

// Ensure this file is being included within WordPress
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Get shortcode attributes
$title = isset( $atts['title'] ) ? $atts['title'] : __( 'Delete Account', 'monikit-app-gdpr-user-data-deletion' );
$subtitle = isset( $atts['subtitle'] ) ? $atts['subtitle'] : __( 'Request deletion of your account. You will receive a confirmation email.', 'monikit-app-gdpr-user-data-deletion' );
$show_title = isset( $atts['show_title'] ) ? $atts['show_title'] === 'true' : true;
$show_subtitle = isset( $atts['show_subtitle'] ) ? $atts['show_subtitle'] === 'true' : true;
$form_class = isset( $atts['class'] ) ? $atts['class'] : 'monigpdr-deletion-form-embedded';
$style = isset( $atts['style'] ) ? $atts['style'] : 'default';

// Generate unique ID for this form instance
$form_id = 'monigpdr-deletion-form-' . uniqid();
?>

<div class="monigpdr-deletion-form-wrapper <?php echo esc_attr( $form_class ); ?> monigpdr-style-<?php echo esc_attr( $style ); ?>">
	
	<?php if ( $show_title || $show_subtitle ) : ?>
		<div class="monigpdr-form-header">
			<?php if ( $show_title ) : ?>
				<h2 class="monigpdr-form-title"><?php echo esc_html( $title ); ?></h2>
			<?php endif; ?>
			
			<?php if ( $show_subtitle ) : ?>
				<p class="monigpdr-form-subtitle"><?php echo esc_html( $subtitle ); ?></p>
			<?php endif; ?>
		</div>
	<?php endif; ?>

	<div class="monigpdr-deletion-form-container">
		<form id="<?php echo esc_attr( $form_id ); ?>" class="monigpdr-deletion-form">
			
			<div class="monigpdr-form-step" id="<?php echo esc_attr( $form_id ); ?>-step-1">
				<div class="monigpdr-form-group">
					<label for="<?php echo esc_attr( $form_id ); ?>-email">
						<?php echo esc_html( $current_lang === 'de' ? 'E-Mail-Adresse' : 'Email Address' ); ?> *
					</label>
					<input 
						type="email" 
						id="<?php echo esc_attr( $form_id ); ?>-email" 
						name="email" 
						required 
						placeholder="<?php echo esc_attr( $current_lang === 'de' ? 'ihre@email.de' : 'your@email.com' ); ?>"
						class="monigpdr-input"
					>
				</div>
				
				<div class="monigpdr-form-actions">
					<button type="submit" class="monigpdr-btn monigpdr-btn-primary">
						<?php echo esc_html( $current_lang === 'de' ? 'Löschung anfordern' : 'Request Deletion' ); ?>
					</button>
				</div>
			</div>

			<div class="monigpdr-form-step" id="<?php echo esc_attr( $form_id ); ?>-step-2" style="display: none;">
				<div class="monigpdr-form-group">
					<label for="<?php echo esc_attr( $form_id ); ?>-confirmation-code">
						<?php echo esc_html( $current_lang === 'de' ? 'Bestätigungscode' : 'Confirmation Code' ); ?> *
					</label>
					<input 
						type="text" 
						id="<?php echo esc_attr( $form_id ); ?>-confirmation-code" 
						name="confirmation_code" 
						placeholder="<?php echo esc_attr( $current_lang === 'de' ? '123456' : '123456' ); ?>"
						class="monigpdr-input monigpdr-code-input"
						maxlength="6"
						pattern="[0-9]{6}"
					>
					<small class="monigpdr-help-text">
						<?php echo esc_html( $current_lang === 'de' 
							? 'Geben Sie den 6-stelligen Code ein, der an Ihre E-Mail-Adresse gesendet wurde.' 
							: 'Enter the 6-digit code sent to your email address.' 
						); ?>
					</small>
				</div>
				
				<div class="monigpdr-form-actions">
					<button type="button" class="monigpdr-btn monigpdr-btn-secondary" data-back-btn="<?php echo esc_attr( $form_id ); ?>">
						<?php echo esc_html( $current_lang === 'de' ? 'Zurück' : 'Back' ); ?>
					</button>
					<button type="submit" class="monigpdr-btn monigpdr-btn-primary">
						<?php echo esc_html( $current_lang === 'de' ? 'Bestätigen' : 'Confirm' ); ?>
					</button>
				</div>
			</div>

			<div class="monigpdr-form-step" id="<?php echo esc_attr( $form_id ); ?>-step-3" style="display: none;">
				<div class="monigpdr-final-confirmation">
					<div class="monigpdr-warning-icon">
						⚠️
					</div>
					<h3><?php echo esc_html( $current_lang === 'de' ? 'Letzte Bestätigung' : 'Final Confirmation' ); ?></h3>
					<p>
						<?php echo esc_html( $current_lang === 'de' 
							? 'Sind Sie sicher, dass Sie Ihr Konto unwiderruflich löschen möchten? Diese Aktion kann nicht rückgängig gemacht werden.' 
							: 'Are you sure you want to permanently delete your account? This action cannot be undone.' 
						); ?>
					</p>
					
					<div class="monigpdr-checkbox-group">
						<label class="monigpdr-checkbox-label">
							<input type="checkbox" id="<?php echo esc_attr( $form_id ); ?>-confirm-deletion" name="confirm_deletion">
							<span class="monigpdr-checkbox-custom"></span>
							<?php echo esc_html( $current_lang === 'de' 
								? 'Ich verstehe, dass diese Aktion unwiderruflich ist und mein Konto permanent gelöscht wird.' 
								: 'I understand that this action is irreversible and my account will be permanently deleted.' 
							); ?>
						</label>
					</div>
				</div>
				
				<div class="monigpdr-form-actions">
					<button type="button" class="monigpdr-btn monigpdr-btn-secondary" data-cancel-btn="<?php echo esc_attr( $form_id ); ?>">
						<?php echo esc_html( $current_lang === 'de' ? 'Abbrechen' : 'Cancel' ); ?>
					</button>
					<button type="submit" class="monigpdr-btn monigpdr-btn-danger" data-delete-btn="<?php echo esc_attr( $form_id ); ?>">
						<?php echo esc_html( $current_lang === 'de' ? 'Konto löschen' : 'Delete My Account' ); ?>
					</button>
				</div>
			</div>
		</form>
	</div>

	<div class="monigpdr-messages" id="<?php echo esc_attr( $form_id ); ?>-messages"></div>
</div> 