<?php
/**
 * Search-engine signals for Lanton Church of Christ.
 *
 * Rank Math is the site's one and only SEO source (titles, meta, schema,
 * sitemap). This file does NOT print any tags of its own — it only corrects
 * what Rank Math outputs, through Rank Math's own filters, so there is never a
 * second, conflicting schema graph or title. If Rank Math is ever removed,
 * these filters simply never fire.
 *
 * What it fixes (all aimed at Google showing "Lanton Church of Christ" as the
 * site name instead of "lantonchurch.com"):
 *   - Home page <title> leads with the brand: "Lanton Church of Christ | …"
 *     instead of "Home - Lanton Church of Christ".
 *   - Every page gets a real meta description (they were blank, or leftover
 *     "This is the home page…" / "This is an example of a page…" text).
 *     A description typed into Rank Math in the page editor always wins.
 *   - Schema: the publisher is an Organization (Rank Math's knowledge graph
 *     was set to "Person"), the WebSite "url" matches the canonical home URL
 *     exactly (trailing slash), and pages no longer claim to be an Article
 *     "written by" an admin user.
 *   - Sitemap: drops the retired /videos/ redirect, the default "Hello world!"
 *     post, the empty "Uncategorized" archive and MailPoet's system pages.
 */

defined( 'ABSPATH' ) || exit;

define( 'LANTON_SEO_BRAND', 'Lanton Church of Christ' );
define( 'LANTON_SEO_HOME_TITLE', 'Lanton Church of Christ | Church in Columbia, TN' );

/**
 * Fallback meta descriptions, keyed by page slug ('' = the front page).
 * Used only when no description has been set in Rank Math for that page.
 */
function lanton_seo_descriptions() {
	return array(
		''              => 'Lanton Church of Christ is a New Testament congregation at 2898 Greens Mill Rd in Columbia, Tennessee. Simple, Bible-centered worship. Join us this Sunday.',
		'about'         => 'Who we are at Lanton Church of Christ, a New Testament congregation in Columbia, TN: our story, what we believe, and what to expect on your first visit.',
		'service-times' => 'Service times and directions for Lanton Church of Christ, 2898 Greens Mill Rd, Columbia, TN 38401. Plenty of parking and a warm welcome at the door.',
		'sermons'       => 'Watch sermons from Lanton Church of Christ in Columbia, TN: the most recent message plus a searchable archive of full lessons and short clips.',
		'events'        => 'Upcoming events, Bible studies and gatherings at Lanton Church of Christ in Columbia, Tennessee. Everyone is welcome.',
		'get-involved'  => 'Ways to serve, study and grow together at Lanton Church of Christ in Columbia, TN. Find your place in our church family.',
		'give'          => 'Support the work of Lanton Church of Christ in Columbia, TN. Give in person during Sunday services or by mail to 2898 Greens Mill Rd.',
		'contact'       => 'Contact Lanton Church of Christ in Columbia, TN. Send us a message, ask a question, or request a Bible study. We would love to hear from you.',
	);
}

/**
 * The fallback description for the current request, or '' if none applies
 * (or if the editor already set one in Rank Math).
 */
function lanton_seo_current_description() {
	if ( is_front_page() ) {
		$slug = '';
	} elseif ( is_page() ) {
		$slug = get_post_field( 'post_name', get_queried_object_id() );
	} else {
		return '';
	}

	if ( get_post_meta( get_queried_object_id(), 'rank_math_description', true ) ) {
		return '';
	}

	$descriptions = lanton_seo_descriptions();
	return isset( $descriptions[ $slug ] ) ? $descriptions[ $slug ] : '';
}

/* ---------------------------------------------------------------------------
 * Titles
 * ------------------------------------------------------------------------- */

function lanton_seo_title( $title ) {
	return is_front_page() ? LANTON_SEO_HOME_TITLE : $title;
}
add_filter( 'rank_math/frontend/title', 'lanton_seo_title', 99 );
add_filter( 'rank_math/opengraph/facebook/og_title', 'lanton_seo_title', 99 );
add_filter( 'rank_math/opengraph/twitter/twitter_title', 'lanton_seo_title', 99 );

/* ---------------------------------------------------------------------------
 * Meta descriptions
 * ------------------------------------------------------------------------- */

function lanton_seo_description( $description ) {
	$fallback = lanton_seo_current_description();
	return $fallback ? $fallback : $description;
}
add_filter( 'rank_math/frontend/description', 'lanton_seo_description', 99 );
add_filter( 'rank_math/opengraph/facebook/og_description', 'lanton_seo_description', 99 );
add_filter( 'rank_math/opengraph/twitter/twitter_description', 'lanton_seo_description', 99 );

/* ---------------------------------------------------------------------------
 * Schema (JSON-LD)
 * ------------------------------------------------------------------------- */

/**
 * Reshape Rank Math's schema graph so it describes one Organization and one
 * WebSite whose url is exactly the canonical home URL.
 *
 * @param array $data Graph entities, keyed by Rank Math's internal names.
 */
function lanton_seo_json_ld( $data ) {
	if ( ! is_array( $data ) ) {
		return $data;
	}

	$home   = trailingslashit( home_url() );
	$org_id = $home . '#organization';
	$old_id = $home . '#person';

	foreach ( $data as $key => $entity ) {
		if ( ! is_array( $entity ) || empty( $entity['@type'] ) ) {
			continue;
		}
		$types = (array) $entity['@type'];

		// Pages aren't articles, and shouldn't name an admin as their author.
		if ( is_page() && array_intersect( $types, array( 'Article', 'BlogPosting', 'NewsArticle' ) ) ) {
			unset( $data[ $key ] );
			continue;
		}
		if ( is_page() && 'Person' === $entity['@type'] && false !== strpos( (string) ( $entity['@id'] ?? '' ), '/author/' ) ) {
			unset( $data[ $key ] );
			continue;
		}

		if ( in_array( 'Organization', $types, true ) ) {
			$data[ $key ] = array_merge(
				$entity,
				array(
					'@type'   => 'Organization',
					'@id'     => $org_id,
					'name'    => LANTON_SEO_BRAND,
					'url'     => $home,
					'address' => array(
						'@type'           => 'PostalAddress',
						'streetAddress'   => '2898 Greens Mill Rd',
						'addressLocality' => 'Columbia',
						'addressRegion'   => 'TN',
						'postalCode'      => '38401',
						'addressCountry'  => 'US',
					),
					'sameAs'  => array( 'https://www.youtube.com/@' . LANTON_YT_HANDLE ),
				)
			);
		}

		if ( in_array( 'WebSite', $types, true ) ) {
			$data[ $key ]['url']           = $home;
			$data[ $key ]['name']          = LANTON_SEO_BRAND;
			$data[ $key ]['alternateName'] = 'Lanton Church';
			$data[ $key ]['publisher']     = array( '@id' => $org_id );
		}

		if ( is_front_page() && in_array( 'WebPage', $types, true ) ) {
			$data[ $key ]['name'] = LANTON_SEO_HOME_TITLE;
			$description          = lanton_seo_current_description();
			if ( $description ) {
				$data[ $key ]['description'] = $description;
			}
		}
	}

	// Point every leftover reference at the Organization.
	array_walk_recursive(
		$data,
		function ( &$value ) use ( $old_id, $org_id ) {
			if ( $old_id === $value ) {
				$value = $org_id;
			}
		}
	);

	return $data;
}
add_filter( 'rank_math/json_ld', 'lanton_seo_json_ld', 99 );

/* ---------------------------------------------------------------------------
 * Sitemap + indexing of leftover default content
 * ------------------------------------------------------------------------- */

/** Is this the default "Hello world!" post or "Uncategorized" archive? */
function lanton_seo_is_stock_content( $object ) {
	if ( $object instanceof WP_Post ) {
		return ( 'post' === $object->post_type && 'hello-world' === $object->post_name )
			|| ( 'page' === $object->post_type && 'videos' === $object->post_name );
	}
	if ( $object instanceof WP_Term ) {
		return 'category' === $object->taxonomy && 'uncategorized' === $object->slug;
	}
	return false;
}

function lanton_seo_sitemap_entry( $url, $type, $object ) {
	return lanton_seo_is_stock_content( $object ) ? false : $url;
}
add_filter( 'rank_math/sitemap/entry', 'lanton_seo_sitemap_entry', 99, 3 );

function lanton_seo_sitemap_exclude_post_type( $exclude, $type ) {
	return 'mailpoet_page' === $type ? true : $exclude;
}
add_filter( 'rank_math/sitemap/exclude_post_type', 'lanton_seo_sitemap_exclude_post_type', 99, 2 );

// Rank Math caches built sitemaps and only rebuilds on content edits, so the
// exclusions above would otherwise never show up. The site is small enough to
// build the sitemap fresh on every request.
add_filter( 'rank_math/sitemap/enable_caching', '__return_false' );

function lanton_seo_robots( $robots ) {
	if ( is_singular() || is_category() ) {
		if ( lanton_seo_is_stock_content( get_queried_object() ) ) {
			$robots['index'] = 'noindex';
		}
	}
	return $robots;
}
add_filter( 'rank_math/frontend/robots', 'lanton_seo_robots', 99 );
