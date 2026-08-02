<?php
/**
 * Contact form for Lanton Church of Christ.
 *
 * A self-contained, plugin-free contact form. Exposes a
 * `[lanton_contact_form]` shortcode (used on the Contact page and at the
 * bottom of the home page), validates the submission, checks Google
 * reCAPTCHA v2, and emails the message to the church via wp_mail().
 *
 * ---------------------------------------------------------------------------
 * WHERE THE EMAIL GOES:
 *   Submissions are sent to LANTON_CONTACT_TO (just below). Change that one
 *   line to route the form somewhere else.
 *
 * ONE-TIME SETUP — Google reCAPTCHA v2 keys (free, ~3 minutes):
 *   1. Go to https://www.google.com/recaptcha/admin/create
 *   2. Label it (e.g. "Lanton Church"), choose **reCAPTCHA v2 →
 *      "I'm not a robot" Checkbox**.
 *   3. Add your domain(s) under "Domains" (add `localhost` too for local
 *      testing).
 *   4. You'll get a **Site key** and a **Secret key**. Add them EITHER:
 *        a) In wp-config.php:
 *             define( 'LANTON_RECAPTCHA_SITE_KEY',   'your-site-key' );
 *             define( 'LANTON_RECAPTCHA_SECRET_KEY', 'your-secret-key' );
 *        b) Or paste them into the *_FALLBACK constants just below.
 *
 *   Until both keys are present, the form still works (protected by a hidden
 *   honeypot + a WordPress nonce) and a setup reminder shows to logged-in
 *   admins only. Once the keys are added, the "I'm not a robot" checkbox
 *   appears and is enforced automatically.
 * ---------------------------------------------------------------------------
 *
 * @package lanton-church
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Where contact-form submissions are emailed.
 * TESTING: set to a temporary inbox for now. Production address is
 * carterstephens6151@gmail.com — switch it back before launch.
 */
const LANTON_CONTACT_TO = 'dtkcenterprise@gmail.com';

/** Paste keys here if you'd rather not edit wp-config.php (option (b) above). */
const LANTON_RECAPTCHA_SITE_KEY_FALLBACK   = '';
const LANTON_RECAPTCHA_SECRET_KEY_FALLBACK = '';

/**
 * Resolve the reCAPTCHA site key (public — printed in the page).
 *
 * @return string The key, or '' if none configured.
 */
function lanton_recaptcha_site_key() {
	if ( defined( 'LANTON_RECAPTCHA_SITE_KEY' ) && LANTON_RECAPTCHA_SITE_KEY ) {
		return (string) LANTON_RECAPTCHA_SITE_KEY;
	}
	return LANTON_RECAPTCHA_SITE_KEY_FALLBACK ? (string) LANTON_RECAPTCHA_SITE_KEY_FALLBACK : '';
}

/**
 * Resolve the reCAPTCHA secret key (server-side only — never printed).
 *
 * @return string The key, or '' if none configured.
 */
function lanton_recaptcha_secret_key() {
	if ( defined( 'LANTON_RECAPTCHA_SECRET_KEY' ) && LANTON_RECAPTCHA_SECRET_KEY ) {
		return (string) LANTON_RECAPTCHA_SECRET_KEY;
	}
	return LANTON_RECAPTCHA_SECRET_KEY_FALLBACK ? (string) LANTON_RECAPTCHA_SECRET_KEY_FALLBACK : '';
}

/** reCAPTCHA is active only when BOTH keys are present. */
function lanton_recaptcha_enabled() {
	return '' !== lanton_recaptcha_site_key() && '' !== lanton_recaptcha_secret_key();
}

/**
 * Register the reCAPTCHA script; the shortcode enqueues it only on pages
 * that actually render the form.
 */
function lanton_contact_register_assets() {
	wp_register_script(
		'lanton-recaptcha',
		'https://www.google.com/recaptcha/api.js',
		array(),
		null,
		array( 'strategy' => 'defer', 'in_footer' => true )
	);
}
add_action( 'wp_enqueue_scripts', 'lanton_contact_register_assets' );

/**
 * `[lanton_contact_form]` — the contact form.
 *
 * @return string HTML.
 */
function lanton_contact_form_shortcode() {
	// Where to come back to after submitting (to show the success/error notice).
	$redirect = home_url( add_query_arg( null, null ) );

	// Status banner after a redirect back from the handler.
	$status = isset( $_GET['lanton_contact'] ) ? sanitize_key( wp_unslash( $_GET['lanton_contact'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

	if ( lanton_recaptcha_enabled() ) {
		wp_enqueue_script( 'lanton-recaptcha' );
	}

	ob_start();
	?>
	<div class="lanton-contact-form-wrap">
		<?php if ( 'sent' === $status ) : ?>
			<div class="lanton-form-status is-success" role="status">
				<strong>Thank you!</strong> Your message has been sent — we'll be in touch soon.
			</div>
		<?php elseif ( 'captcha' === $status ) : ?>
			<div class="lanton-form-status is-error" role="alert">
				Please confirm the &ldquo;I'm not a robot&rdquo; check and try again.
			</div>
		<?php elseif ( 'missing' === $status ) : ?>
			<div class="lanton-form-status is-error" role="alert">
				Please fill in your name and a valid email, then try again.
			</div>
		<?php elseif ( 'error' === $status ) : ?>
			<div class="lanton-form-status is-error" role="alert">
				Sorry — something went wrong sending your message. Please try again, or email us directly.
			</div>
		<?php endif; ?>

		<?php if ( is_user_logged_in() && current_user_can( 'manage_options' ) && ! lanton_recaptcha_enabled() ) : ?>
			<div class="lanton-note" style="margin-bottom:1.25rem">
				<strong>Admin note:</strong> the form is live and emailing
				<?php echo esc_html( LANTON_CONTACT_TO ); ?>, protected by a honeypot.
				To enable the &ldquo;I'm not a robot&rdquo; reCAPTCHA, add your free keys
				(setup steps are in <code>inc/contact-form.php</code>). Only you can see this note.
			</div>
		<?php endif; ?>

		<form class="lanton-contact-form" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post">
			<?php // Hidden fields wrapped in a block-level div so wpautop can't wrap them in a <p> (which added empty space at the top). ?>
			<div class="lanton-form-hidden" style="display:none">
				<input type="hidden" name="action" value="lanton_contact">
				<input type="hidden" name="redirect_to" value="<?php echo esc_url( $redirect ); ?>">
				<?php wp_nonce_field( 'lanton_contact', 'lanton_contact_nonce' ); ?>
			</div>

			<?php // Honeypot: real people leave it blank; many bots fill every field. ?>
			<div class="lanton-hp" aria-hidden="true">
				<label>Leave this field empty <input type="text" name="lanton_hp" tabindex="-1" autocomplete="off"></label>
			</div>

			<div class="lanton-form-grid">
				<p class="lanton-field">
					<label for="lc-name">Name <span class="lanton-req">*</span></label>
					<input id="lc-name" type="text" name="name" autocomplete="name" required>
				</p>
				<p class="lanton-field">
					<label for="lc-email">Email <span class="lanton-req">*</span></label>
					<input id="lc-email" type="email" name="email" autocomplete="email" required>
				</p>
				<p class="lanton-field">
					<label for="lc-phone">Phone</label>
					<input id="lc-phone" type="tel" name="phone" autocomplete="tel">
				</p>
				<p class="lanton-field">
					<label for="lc-subject">Subject</label>
					<input id="lc-subject" type="text" name="subject">
				</p>
			</div>

			<p class="lanton-field">
				<label for="lc-message">Message</label>
				<textarea id="lc-message" name="message" rows="6"></textarea>
			</p>

			<?php if ( lanton_recaptcha_enabled() ) : ?>
				<div class="g-recaptcha" data-sitekey="<?php echo esc_attr( lanton_recaptcha_site_key() ); ?>"></div>
			<?php endif; ?>

			<button type="submit">Send Message</button>
		</form>
	</div>
	<?php
	// Collapse whitespace *between* tags so wpautop can't turn the newlines
	// separating the leading hidden inputs into stray <br>/<p> tags — those
	// were adding a large empty gap at the top of the form. Only pure
	// tag-to-tag gaps are removed; text content and its spacing are untouched.
	return preg_replace( '/>\s+</', '><', ob_get_clean() );
}
add_shortcode( 'lanton_contact_form', 'lanton_contact_form_shortcode' );

/**
 * Verify a reCAPTCHA v2 response token with Google.
 *
 * @param string $token The g-recaptcha-response value from the form.
 * @return bool True if the token is valid (or reCAPTCHA isn't configured).
 */
function lanton_recaptcha_verify( $token ) {
	if ( ! lanton_recaptcha_enabled() ) {
		return true; // No keys yet — fall back to nonce + honeypot only.
	}
	if ( '' === $token ) {
		return false;
	}

	$response = wp_remote_post(
		'https://www.google.com/recaptcha/api/siteverify',
		array(
			'timeout' => 15,
			'body'    => array(
				'secret'   => lanton_recaptcha_secret_key(),
				'response' => $token,
				'remoteip' => isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '',
			),
		)
	);

	if ( is_wp_error( $response ) ) {
		return false;
	}

	$body = json_decode( wp_remote_retrieve_body( $response ), true );
	return ! empty( $body['success'] );
}

/**
 * Redirect back to the form with a status code in the query string.
 *
 * @param string $status  One of: sent, missing, captcha, error.
 * @param string $fallback Raw redirect target submitted with the form.
 */
function lanton_contact_redirect( $status, $fallback ) {
	$target = wp_validate_redirect( $fallback, home_url( '/contact/' ) );
	$target = remove_query_arg( 'lanton_contact', $target );
	$target = add_query_arg( 'lanton_contact', $status, $target );
	// Land back on the form itself.
	wp_safe_redirect( $target . '#contact-form' );
	exit;
}

/**
 * Handle a contact-form submission: validate, check spam, email the church.
 */
function lanton_contact_handle() {
	$fallback = isset( $_POST['redirect_to'] ) ? esc_url_raw( wp_unslash( $_POST['redirect_to'] ) ) : home_url( '/contact/' );

	// CSRF: valid nonce required.
	if ( ! isset( $_POST['lanton_contact_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['lanton_contact_nonce'] ) ), 'lanton_contact' ) ) {
		lanton_contact_redirect( 'error', $fallback );
	}

	// Honeypot: if filled, silently pretend success (don't tip off the bot).
	if ( ! empty( $_POST['lanton_hp'] ) ) {
		lanton_contact_redirect( 'sent', $fallback );
	}

	// reCAPTCHA.
	$token = isset( $_POST['g-recaptcha-response'] ) ? sanitize_text_field( wp_unslash( $_POST['g-recaptcha-response'] ) ) : '';
	if ( ! lanton_recaptcha_verify( $token ) ) {
		lanton_contact_redirect( 'captcha', $fallback );
	}

	// Collect + sanitize fields.
	$name    = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '';
	$email   = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
	$phone   = isset( $_POST['phone'] ) ? sanitize_text_field( wp_unslash( $_POST['phone'] ) ) : '';
	$subject = isset( $_POST['subject'] ) ? sanitize_text_field( wp_unslash( $_POST['subject'] ) ) : '';
	$message = isset( $_POST['message'] ) ? sanitize_textarea_field( wp_unslash( $_POST['message'] ) ) : '';

	// Required fields (message is optional).
	if ( '' === $name || ! is_email( $email ) ) {
		lanton_contact_redirect( 'missing', $fallback );
	}

	// Build the email.
	$site    = wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES );
	$subj    = $subject ? $subject : 'Website contact form';
	$mail_to = LANTON_CONTACT_TO;

	$lines = array(
		'You have a new message from the ' . $site . ' website contact form.',
		'',
		'Name:    ' . $name,
		'Email:   ' . $email,
		'Phone:   ' . ( $phone ? $phone : '(not provided)' ),
		'Subject: ' . $subj,
		'',
		'Message:',
		$message,
		'',
		'---',
		'Sent from ' . home_url( '/' ),
	);
	$body = implode( "\r\n", $lines );

	// From = a site address (good deliverability); Reply-To = the visitor.
	$domain    = wp_parse_url( home_url(), PHP_URL_HOST );
	$domain    = $domain ? preg_replace( '/^www\./', '', $domain ) : 'localhost';
	$from_email = 'no-reply@' . $domain;
	$headers   = array(
		'From: ' . $site . ' <' . $from_email . '>',
		'Reply-To: ' . $name . ' <' . $email . '>',
		'Content-Type: text/plain; charset=UTF-8',
	);

	$sent = wp_mail( $mail_to, '[' . $site . '] ' . $subj, $body, $headers );

	lanton_contact_redirect( $sent ? 'sent' : 'error', $fallback );
}
add_action( 'admin_post_nopriv_lanton_contact', 'lanton_contact_handle' );
add_action( 'admin_post_lanton_contact', 'lanton_contact_handle' );
