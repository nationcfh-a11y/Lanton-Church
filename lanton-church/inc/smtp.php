<?php
/**
 * Reliable email sending (SMTP) for Lanton Church of Christ.
 *
 * By default WordPress hands outgoing mail to the server's local PHP mailer,
 * which Gmail/Outlook often drop into spam — or which doesn't exist at all on
 * local previews (wp-now/Playground). This routes wp_mail() through a real
 * SMTP mailbox instead, so the contact-form emails actually arrive. It does the
 * same job as the "WP Mail SMTP" plugin, but ships with the theme (no plugin to
 * install, deploys with a simple theme copy). If you DO install WP Mail SMTP
 * later, this steps aside and lets the plugin take over.
 *
 * ---------------------------------------------------------------------------
 * QUICK START — send through a Gmail account (great for testing):
 *   1. The sending Gmail account needs 2-Step Verification ON:
 *        https://myaccount.google.com/security
 *   2. Create an "App Password" (a 16-char code Google gives you):
 *        https://myaccount.google.com/apppasswords
 *      Pick app "Mail", name it "Lanton Website" — copy the 16 characters.
 *   3. Add these to wp-config.php (above the "That's all" line), using that
 *      Gmail address and the App Password (NOT your normal password):
 *        define( 'LANTON_SMTP_HOST', 'smtp.gmail.com' );
 *        define( 'LANTON_SMTP_PORT', 587 );
 *        define( 'LANTON_SMTP_SECURE', 'tls' );          // 'tls' (587) or 'ssl' (465)
 *        define( 'LANTON_SMTP_USER', 'youraddress@gmail.com' );
 *        define( 'LANTON_SMTP_PASS', 'your16charapppass' );
 *        define( 'LANTON_SMTP_FROM', 'youraddress@gmail.com' );      // optional
 *        define( 'LANTON_SMTP_FROM_NAME', 'Lanton Church of Christ' );// optional
 *   4. Go to wp-admin → Tools → "Email (SMTP) Test" and send yourself a test.
 *
 *   (Prefer not to touch wp-config.php? Paste the same values into the
 *    *_FALLBACK constants just below — but wp-config.php is the safer home for
 *    a password, since it's outside the theme.)
 *
 * FOR A LIVE CHURCH SITE: the same steps work with the church's own mailbox, or
 * with a free transactional sender (Brevo, Mailgun, SendGrid, etc.) — just use
 * that provider's SMTP host / port / username / password.
 * ---------------------------------------------------------------------------
 *
 * @package lanton-church
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Non-secret defaults only. NEVER put the account username/password here —
 * this file is committed to a public repo. Set credentials in wp-config.php
 * on the live host (LANTON_SMTP_USER / LANTON_SMTP_PASS — see the Quick Start
 * comment above).
 */
const LANTON_SMTP_HOST_FALLBACK      = 'smtp.gmail.com';
const LANTON_SMTP_PORT_FALLBACK      = 587;
const LANTON_SMTP_SECURE_FALLBACK    = 'tls'; // 'tls', 'ssl', or '' for none.
const LANTON_SMTP_USER_FALLBACK      = '';
const LANTON_SMTP_PASS_FALLBACK      = '';
const LANTON_SMTP_FROM_FALLBACK      = '';
const LANTON_SMTP_FROM_NAME_FALLBACK = 'Lanton Church of Christ';

/**
 * Resolve one SMTP setting from a wp-config constant, else a file fallback.
 *
 * @param string $const   Constant name (e.g. 'LANTON_SMTP_HOST').
 * @param mixed  $default  Fallback value when the constant is undefined/empty.
 * @return mixed
 */
function lanton_smtp_get( $const, $default ) {
	if ( defined( $const ) && '' !== constant( $const ) && null !== constant( $const ) ) {
		return constant( $const );
	}
	return $default;
}

/**
 * The full SMTP config, or null if it isn't set up yet.
 *
 * Considered "configured" once host + username + password are all present.
 *
 * @return array|null
 */
function lanton_smtp_config() {
	$host = (string) lanton_smtp_get( 'LANTON_SMTP_HOST', LANTON_SMTP_HOST_FALLBACK );
	$user = (string) lanton_smtp_get( 'LANTON_SMTP_USER', LANTON_SMTP_USER_FALLBACK );
	$pass = (string) lanton_smtp_get( 'LANTON_SMTP_PASS', LANTON_SMTP_PASS_FALLBACK );

	if ( '' === $host || '' === $user || '' === $pass ) {
		return null;
	}

	$from = (string) lanton_smtp_get( 'LANTON_SMTP_FROM', LANTON_SMTP_FROM_FALLBACK );
	$name = (string) lanton_smtp_get( 'LANTON_SMTP_FROM_NAME', LANTON_SMTP_FROM_NAME_FALLBACK );

	return array(
		'host'      => $host,
		'port'      => (int) lanton_smtp_get( 'LANTON_SMTP_PORT', LANTON_SMTP_PORT_FALLBACK ),
		'secure'    => (string) lanton_smtp_get( 'LANTON_SMTP_SECURE', LANTON_SMTP_SECURE_FALLBACK ),
		'user'      => $user,
		'pass'      => $pass,
		'from'      => '' !== $from ? $from : $user,
		'from_name' => '' !== $name ? $name : wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES ),
	);
}

/** True once SMTP is ready to send. */
function lanton_smtp_enabled() {
	return null !== lanton_smtp_config();
}

/** True if the WP Mail SMTP plugin is active (then we defer to it). */
function lanton_smtp_plugin_active() {
	return defined( 'WPMS_PLUGIN_VER' ) || class_exists( 'WPMailSMTP\\Core' );
}

/**
 * Point PHPMailer at our SMTP mailbox for every wp_mail() call.
 *
 * @param PHPMailer\PHPMailer\PHPMailer $phpmailer The mailer (passed by reference).
 */
function lanton_smtp_phpmailer_init( $phpmailer ) {
	if ( lanton_smtp_plugin_active() ) {
		return; // Let WP Mail SMTP own the configuration.
	}

	$c = lanton_smtp_config();
	if ( ! $c ) {
		return; // Not set up yet — leave WordPress's default mailer alone.
	}

	$phpmailer->isSMTP();
	$phpmailer->Host        = $c['host'];
	$phpmailer->Port        = $c['port'];
	$phpmailer->SMTPAuth    = true;
	$phpmailer->Username    = $c['user'];
	$phpmailer->Password    = $c['pass'];
	$phpmailer->SMTPSecure  = $c['secure']; // '' disables encryption.
	$phpmailer->SMTPAutoTLS = '' !== $c['secure'];
	$phpmailer->setFrom( $c['from'], $c['from_name'], false );
}
add_action( 'phpmailer_init', 'lanton_smtp_phpmailer_init' );

/**
 * Keep the visible "From" address/name consistent with the SMTP mailbox, so
 * the message isn't rejected for a mismatched sender.
 */
function lanton_smtp_from_email( $email ) {
	$c = lanton_smtp_config();
	return ( $c && ! lanton_smtp_plugin_active() ) ? $c['from'] : $email;
}
add_filter( 'wp_mail_from', 'lanton_smtp_from_email' );

function lanton_smtp_from_name( $name ) {
	$c = lanton_smtp_config();
	return ( $c && ! lanton_smtp_plugin_active() ) ? $c['from_name'] : $name;
}
add_filter( 'wp_mail_from_name', 'lanton_smtp_from_name' );

/* -------------------------------------------------------------------------
 * Admin: Tools → "Email (SMTP) Test" — send a test message and see the result.
 * ---------------------------------------------------------------------------*/

/**
 * Register the Tools submenu page.
 */
function lanton_smtp_admin_menu() {
	add_management_page(
		__( 'Email (SMTP) Test', 'lanton-church' ),
		__( 'Email (SMTP) Test', 'lanton-church' ),
		'manage_options',
		'lanton-smtp-test',
		'lanton_smtp_test_page'
	);
}
add_action( 'admin_menu', 'lanton_smtp_admin_menu' );

/**
 * Render + handle the test-email page.
 */
function lanton_smtp_test_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$sent_ok  = null;   // null = no attempt yet, true/false after sending.
	$error    = '';
	$to       = defined( 'LANTON_CONTACT_TO' ) ? LANTON_CONTACT_TO : get_option( 'admin_email' );

	if ( isset( $_POST['lanton_smtp_test_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['lanton_smtp_test_nonce'] ) ), 'lanton_smtp_test' ) ) {
		$to = isset( $_POST['lanton_test_to'] ) ? sanitize_email( wp_unslash( $_POST['lanton_test_to'] ) ) : $to;

		// Capture any failure reason PHPMailer reports.
		$capture = static function ( $wp_error ) use ( &$error ) {
			$error = $wp_error->get_error_message();
		};
		add_action( 'wp_mail_failed', $capture );

		$sent_ok = wp_mail(
			$to,
			'[' . wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES ) . '] SMTP test email',
			"This is a test email from your church website.\r\n\r\nIf you received this, outgoing email is working correctly.",
			array( 'Content-Type: text/plain; charset=UTF-8' )
		);

		remove_action( 'wp_mail_failed', $capture );
	}

	$config     = lanton_smtp_config();
	$via_plugin = lanton_smtp_plugin_active();
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Email (SMTP) Test', 'lanton-church' ); ?></h1>

		<?php if ( $via_plugin ) : ?>
			<div class="notice notice-info"><p>
				<?php esc_html_e( 'The WP Mail SMTP plugin is active, so it is handling outgoing email. This test still works and uses that plugin\'s settings.', 'lanton-church' ); ?>
			</p></div>
		<?php elseif ( $config ) : ?>
			<div class="notice notice-success"><p>
				<?php
				printf(
					/* translators: %s: SMTP host */
					esc_html__( 'SMTP is configured (host: %s). Send a test below to confirm it works.', 'lanton-church' ),
					'<code>' . esc_html( $config['host'] ) . '</code>'
				);
				?>
			</p></div>
		<?php else : ?>
			<div class="notice notice-warning"><p>
				<?php esc_html_e( 'SMTP is not configured yet, so emails use the server default (often undelivered). Add the SMTP settings shown in inc/smtp.php, then reload this page.', 'lanton-church' ); ?>
			</p></div>
		<?php endif; ?>

		<?php if ( true === $sent_ok ) : ?>
			<div class="notice notice-success"><p>
				<?php
				printf(
					/* translators: %s: recipient address */
					esc_html__( 'Test email sent to %s. Check that inbox (and its spam folder).', 'lanton-church' ),
					'<strong>' . esc_html( $to ) . '</strong>'
				);
				?>
			</p></div>
		<?php elseif ( false === $sent_ok ) : ?>
			<div class="notice notice-error">
				<p><?php esc_html_e( 'The test email could not be sent.', 'lanton-church' ); ?></p>
				<?php if ( $error ) : ?>
					<p><strong><?php esc_html_e( 'Reason:', 'lanton-church' ); ?></strong> <code><?php echo esc_html( $error ); ?></code></p>
				<?php endif; ?>
			</div>
		<?php endif; ?>

		<form method="post">
			<?php wp_nonce_field( 'lanton_smtp_test', 'lanton_smtp_test_nonce' ); ?>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><label for="lanton_test_to"><?php esc_html_e( 'Send test to', 'lanton-church' ); ?></label></th>
					<td><input name="lanton_test_to" id="lanton_test_to" type="email" class="regular-text" value="<?php echo esc_attr( $to ); ?>" required></td>
				</tr>
			</table>
			<?php submit_button( __( 'Send Test Email', 'lanton-church' ) ); ?>
		</form>
	</div>
	<?php
}

/**
 * Nudge admins on the dashboard if the contact form is live but SMTP isn't set
 * up (so form emails probably aren't being delivered).
 */
function lanton_smtp_admin_notice() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$screen = get_current_screen();
	if ( ! $screen || 'dashboard' !== $screen->id ) {
		return;
	}
	if ( lanton_smtp_enabled() || lanton_smtp_plugin_active() ) {
		return;
	}
	?>
	<div class="notice notice-warning is-dismissible">
		<p>
			<strong><?php esc_html_e( 'Lanton Church:', 'lanton-church' ); ?></strong>
			<?php esc_html_e( 'Outgoing email (contact form) is not set up yet, so messages may not be delivered.', 'lanton-church' ); ?>
			<a href="<?php echo esc_url( admin_url( 'tools.php?page=lanton-smtp-test' ) ); ?>"><?php esc_html_e( 'Set up & test email', 'lanton-church' ); ?></a>.
		</p>
	</div>
	<?php
}
add_action( 'admin_notices', 'lanton_smtp_admin_notice' );
