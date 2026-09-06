<?php
/**
 * Lanton Church of Christ — theme functions.
 *
 * Keep this lean. Design tokens live in theme.json; layout lives in
 * templates/ and parts/; reusable sections live in patterns/.
 *
 * @package lanton-church
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'LANTON_VERSION', '1.12.0' );

/**
 * Theme supports + nav menu location.
 */
function lanton_setup() {
	add_theme_support( 'wp-block-styles' );
	add_theme_support( 'editor-styles' );
	add_theme_support( 'responsive-embeds' );
	add_theme_support( 'html5', array( 'search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script' ) );
	add_theme_support(
		'custom-logo',
		array(
			'height'      => 851,
			'width'       => 1950,
			'flex-width'  => true,
			'flex-height' => true,
		)
	);

	// Classic-style menu location kept for portability; block themes use the
	// Navigation block, but this lets the seeder build a real menu object.
	register_nav_menus(
		array(
			'primary' => __( 'Primary Menu', 'lanton-church' ),
			'footer'  => __( 'Footer Menu', 'lanton-church' ),
		)
	);

	load_theme_textdomain( 'lanton-church', get_template_directory() . '/languages' );
}
add_action( 'after_setup_theme', 'lanton_setup' );

/**
 * Front-end styles & scripts.
 */
function lanton_assets() {
	// Google Fonts — Playfair Display (headings) + Plus Jakarta Sans (body).
	// Degrades to system serif/sans gracefully if offline.
	wp_enqueue_style(
		'lanton-fonts',
		'https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:ital,wght@0,400;0,500;0,600;0,700;0,800;1,400&family=Playfair+Display:ital,wght@0,400;0,500;0,600;0,700;0,800;1,400;1,500&display=swap',
		array(),
		null
	);

	wp_enqueue_style(
		'lanton-theme',
		get_theme_file_uri( 'assets/css/theme.css' ),
		array(),
		LANTON_VERSION
	);

	wp_enqueue_script(
		'lanton-parallax',
		get_theme_file_uri( 'assets/js/parallax.js' ),
		array(),
		LANTON_VERSION,
		array( 'strategy' => 'defer', 'in_footer' => true )
	);
}
add_action( 'wp_enqueue_scripts', 'lanton_assets' );

/**
 * Preconnect to Google Fonts for faster loads.
 */
function lanton_resource_hints( $hints, $relation ) {
	if ( 'preconnect' === $relation ) {
		$hints[] = array( 'href' => 'https://fonts.googleapis.com' );
		$hints[] = array( 'href' => 'https://fonts.gstatic.com', 'crossorigin' );
	}
	return $hints;
}
add_filter( 'wp_resource_hints', 'lanton_resource_hints', 10, 2 );

/**
 * Editor styles so the block editor matches the front end.
 */
function lanton_editor_assets() {
	add_editor_style( 'assets/css/theme.css' );
}
add_action( 'after_setup_theme', 'lanton_editor_assets' );

/**
 * Register a pattern category so our sections are grouped in the inserter.
 */
function lanton_pattern_categories() {
	register_block_pattern_category(
		'lanton',
		array( 'label' => __( 'Lanton Church', 'lanton-church' ) )
	);
}
add_action( 'init', 'lanton_pattern_categories' );

/**
 * First-run content seeding: creates the pages, builds the primary &
 * footer nav menus, and sets the static front page. Runs once, guarded by
 * an option flag, and never overwrites pages you've already created.
 *
 * This makes local preview (wp-now) and a fresh live install both show a
 * complete site out of the box. Safe to delete this include before launch
 * once your real content is in place.
 */
require_once get_template_directory() . '/inc/page-setup.php';

/**
 * YouTube video gallery: the [lanton_youtube] shortcode that powers the
 * searchable Videos page. See the setup notes at the top of inc/youtube.php
 * for the one-time API key step.
 */
require_once get_template_directory() . '/inc/youtube.php';

/**
 * Contact form: the [lanton_contact_form] shortcode (used on the Contact
 * page and the home page) plus its submission handler, which validates the
 * message, checks Google reCAPTCHA v2, and emails it to the church. See the
 * one-time reCAPTCHA key setup notes at the top of inc/contact-form.php.
 */
require_once get_template_directory() . '/inc/contact-form.php';

/**
 * Reliable email sending: routes wp_mail() through a real SMTP mailbox so the
 * contact-form messages actually arrive (Gmail/Outlook otherwise spam or drop
 * server-default mail). Adds a Tools → "Email (SMTP) Test" page. Configure it
 * with the constants documented at the top of inc/smtp.php; defers to the WP
 * Mail SMTP plugin if that's ever installed.
 */
require_once get_template_directory() . '/inc/smtp.php';
