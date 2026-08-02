<?php
/**
 * First-run content setup for Lanton Church of Christ.
 *
 * Creates the site's pages (each rendering its matching block pattern),
 * sets the static front page, site identity, and pretty permalinks — so a
 * fresh install (local wp-now or a live host) shows a complete site with
 * zero manual clicks. Runs once, guarded by the `lanton_seeded` option.
 *
 * Remove this include from functions.php once your real content is in and
 * you no longer need the scaffolding.
 *
 * @package lanton-church
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register the light "outline" button style used on dark sections.
 */
function lanton_register_block_styles() {
	register_block_style(
		'core/button',
		array(
			'name'  => 'ghost',
			'label' => __( 'Ghost (dark outline)', 'lanton-church' ),
		)
	);
	register_block_style(
		'core/button',
		array(
			'name'  => 'ghost-light',
			'label' => __( 'Ghost (light outline)', 'lanton-church' ),
		)
	);
}
add_action( 'init', 'lanton_register_block_styles' );

/**
 * Register the theme's stained-glass logo as the site's Custom Logo.
 *
 * The Site Logo block reads the `custom_logo` theme mod, which points at a
 * media-library attachment — a theme file path won't do. So on first load we
 * sideload assets/images/lanton-logo.png into the media library and set it.
 * Idempotent and self-healing: it returns early once a valid logo is set, so
 * it works on an already-seeded database too (not tied to `lanton_seeded`).
 */
function lanton_set_custom_logo() {
	$existing = get_theme_mod( 'custom_logo' );
	if ( $existing && ( $att = get_post( $existing ) ) && 'attachment' === $att->post_type ) {
		return;
	}

	$src = get_template_directory() . '/assets/images/lanton-logo.png';
	if ( ! file_exists( $src ) ) {
		return;
	}

	if ( ! function_exists( 'media_handle_sideload' ) ) {
		require_once ABSPATH . 'wp-admin/includes/image.php';
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/media.php';
	}

	$tmp = wp_tempnam( 'lanton-logo.png' );
	if ( ! $tmp || ! copy( $src, $tmp ) ) {
		return;
	}

	$id = media_handle_sideload(
		array(
			'name'     => 'lanton-logo.png',
			'tmp_name' => $tmp,
		),
		0,
		'Lanton Church of Christ logo'
	);

	if ( is_wp_error( $id ) ) {
		if ( file_exists( $tmp ) ) {
			wp_delete_file( $tmp );
		}
		return;
	}

	set_theme_mod( 'custom_logo', $id );
}
add_action( 'wp_loaded', 'lanton_set_custom_logo' );

/**
 * The pages to create: slug => array( title, pattern slug ).
 */
function lanton_default_pages() {
	return array(
		'home'          => array( 'Home', null ),
		'about'         => array( 'About Us', 'lanton-church/about' ),
		'service-times' => array( 'Service Times & Location', 'lanton-church/service-times' ),
		'sermons'       => array( 'Sermons', 'lanton-church/sermons' ),
		'videos'        => array( 'Videos', 'lanton-church/videos' ),
		'events'        => array( 'Events', 'lanton-church/events' ),
		'get-involved'  => array( 'Get Involved', 'lanton-church/get-involved' ),
		'give'          => array( 'Give', 'lanton-church/give' ),
		'contact'       => array( 'Contact', 'lanton-church/contact' ),
	);
}

/**
 * Ensure every default page exists, creating only the missing ones.
 *
 * Idempotent and NOT tied to the `lanton_seeded` flag, so pages added to
 * lanton_default_pages() in a theme update (e.g. the Videos page) appear on
 * already-seeded installs too. Returns the Home page ID (0 if none).
 *
 * @return int Home page ID.
 */
function lanton_ensure_pages() {
	$home_id = 0;
	$created = 0;

	foreach ( lanton_default_pages() as $slug => $info ) {
		list( $title, $pattern ) = $info;

		$existing = get_page_by_path( $slug );
		if ( $existing instanceof WP_Post ) {
			if ( 'home' === $slug ) {
				$home_id = $existing->ID;
			}
			continue;
		}

		if ( 'home' === $slug ) {
			$content = '<!-- wp:paragraph --><p>This is the home page. The theme renders the parallax front page (front-page.html) here automatically.</p><!-- /wp:paragraph -->';
		} else {
			$content = '<!-- wp:pattern {"slug":"' . $pattern . '"} /-->';
		}

		$page_id = wp_insert_post(
			array(
				'post_title'   => $title,
				'post_name'    => $slug,
				'post_status'  => 'publish',
				'post_type'    => 'page',
				'post_content' => $content,
			)
		);

		if ( $page_id && ! is_wp_error( $page_id ) ) {
			$created++;
			if ( 'home' === $slug ) {
				$home_id = $page_id;
			}
		}
	}

	// Only flush rewrite rules when we actually added a page, so new
	// permalinks (e.g. /videos/) resolve without a manual permalink resave.
	if ( $created ) {
		flush_rewrite_rules();
	}

	return $home_id;
}

/**
 * Seed pages, identity, and settings. Pages are ensured on every load
 * (cheap, idempotent); the one-time identity/permalink setup is guarded by
 * the `lanton_seeded` flag.
 */
function lanton_seed_content() {
	$home_id = lanton_ensure_pages();

	if ( get_option( 'lanton_seeded' ) ) {
		return;
	}

	// Site identity.
	update_option( 'blogname', 'Lanton Church of Christ' );
	update_option( 'blogdescription', 'A New Testament congregation in Columbia, TN' );

	// Pretty permalinks so /about/, /give/, etc. resolve.
	if ( '' === get_option( 'permalink_structure' ) ) {
		update_option( 'permalink_structure', '/%postname%/' );
	}

	// Static front page = Home.
	if ( $home_id ) {
		update_option( 'show_on_front', 'page' );
		update_option( 'page_on_front', $home_id );
	}

	flush_rewrite_rules();
	update_option( 'lanton_seeded', 1 );
}
add_action( 'wp_loaded', 'lanton_seed_content' );
