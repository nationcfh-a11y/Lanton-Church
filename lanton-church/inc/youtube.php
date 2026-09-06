<?php
/**
 * YouTube video gallery for Lanton Church of Christ.
 *
 * Pulls *every* upload from the church's YouTube channel via the free
 * YouTube Data API v3, caches the result so the API is hit rarely, and
 * exposes a `[lanton_youtube]` shortcode that renders a searchable card
 * grid. Search and click-to-play are handled client-side (assets/js/
 * youtube-search.js) so the page loads fast even with hundreds of videos.
 *
 * ---------------------------------------------------------------------------
 * ONE-TIME SETUP — get a free API key (takes ~3 minutes):
 *   1. Go to https://console.cloud.google.com/ and create a project.
 *   2. APIs & Services → Library → enable "YouTube Data API v3".
 *   3. APIs & Services → Credentials → Create credentials → API key.
 *   4. Add it to the site, EITHER:
 *        a) wp-admin → Settings → "Sermon Videos (YouTube)" — paste and save; or
 *        b) In wp-config.php:  define( 'LANTON_YT_API_KEY', 'your-key' );
 *        c) Or paste it into LANTON_YT_API_KEY_FALLBACK just below.
 *   (Optional but recommended: in the key's settings, restrict it to the
 *    "YouTube Data API v3" so it can't be abused elsewhere.)
 * Until a key is present, the Sermons page shows a friendly setup notice.
 *
 * STAYING UP TO DATE: an hourly WP-Cron job re-reads the channel in the
 * background, so a newly posted video shows up on the Sermons page within about
 * an hour with no one touching the site. The last good list is also kept in an
 * option, so a YouTube hiccup or an expired key never blanks the page.
 * ---------------------------------------------------------------------------
 *
 * @package lanton-church
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** The channel handle (the part after @ in the channel URL). */
const LANTON_YT_HANDLE = 'LantonChurchofChrist';

/** Paste a key here if you'd rather not edit wp-config.php (option (b) above). */
const LANTON_YT_API_KEY_FALLBACK = '';

/** How long to cache the video list before re-checking YouTube (seconds). */
const LANTON_YT_CACHE_TTL = HOUR_IN_SECONDS;

/** Option holding the last successful fetch, used if YouTube is unreachable. */
const LANTON_YT_BACKUP_OPTION = 'lanton_yt_videos_backup';

/**
 * Bump when the shape of a cached video record changes, so old caches (which
 * would be missing the new fields) are ignored instead of rendering broken.
 */
const LANTON_YT_CACHE_VERSION = 3;

/**
 * A video this long or shorter counts as a "Short".
 *
 * YouTube's API has no is-a-Short flag, so runtime is the practical test.
 * YouTube itself caps Shorts at 3 minutes, which is the line we use.
 */
const LANTON_YT_SHORT_MAX_SECONDS = 180;

/**
 * Cache key for the video list (versioned — see LANTON_YT_CACHE_VERSION).
 *
 * @return string
 */
function lanton_yt_cache_key() {
	return 'lanton_yt_videos_v' . LANTON_YT_CACHE_VERSION;
}

/**
 * Convert an ISO-8601 duration ("PT1H2M3S") to whole seconds.
 *
 * @param string $iso Duration as YouTube returns it.
 * @return int Seconds (0 if unparseable).
 */
function lanton_yt_parse_duration( $iso ) {
	if ( ! is_string( $iso ) || ! preg_match( '/^P(?:([\d.]+)D)?T?(?:([\d.]+)H)?(?:([\d.]+)M)?(?:([\d.]+)S)?$/i', $iso, $m ) ) {
		return 0;
	}
	return (int) round(
		( isset( $m[1] ) && '' !== $m[1] ? (float) $m[1] * DAY_IN_SECONDS : 0 )
		+ ( isset( $m[2] ) && '' !== $m[2] ? (float) $m[2] * HOUR_IN_SECONDS : 0 )
		+ ( isset( $m[3] ) && '' !== $m[3] ? (float) $m[3] * MINUTE_IN_SECONDS : 0 )
		+ ( isset( $m[4] ) && '' !== $m[4] ? (float) $m[4] : 0 )
	);
}

/**
 * Format seconds the way YouTube labels a thumbnail: 9:07, or 1:02:03.
 *
 * @param int $secs Duration in seconds.
 * @return string Empty string if unknown.
 */
function lanton_yt_format_duration( $secs ) {
	$secs = (int) $secs;
	if ( $secs <= 0 ) {
		return '';
	}
	$h = (int) floor( $secs / HOUR_IN_SECONDS );
	$m = (int) floor( ( $secs % HOUR_IN_SECONDS ) / MINUTE_IN_SECONDS );
	$s = $secs % MINUTE_IN_SECONDS;

	return $h
		? sprintf( '%d:%02d:%02d', $h, $m, $s )
		: sprintf( '%d:%02d', $m, $s );
}

/**
 * Look up runtimes for a batch of video IDs.
 *
 * playlistItems doesn't carry duration, so this is a second pass over the
 * `videos` endpoint — 50 IDs per call, 1 quota unit each.
 *
 * @param string[] $ids Video IDs.
 * @return array Map of video ID => duration in seconds.
 */
function lanton_yt_fetch_durations( $ids ) {
	$out = array();

	foreach ( array_chunk( array_values( array_unique( $ids ) ), 50 ) as $chunk ) {
		$data = lanton_yt_request(
			'videos',
			array(
				'part' => 'contentDetails',
				'id'   => implode( ',', $chunk ),
			)
		);
		if ( is_wp_error( $data ) ) {
			// Durations are a nice-to-have; a failure here shouldn't lose the
			// video list. Anything unresolved just stays unknown.
			break;
		}

		foreach ( ( $data['items'] ?? array() ) as $item ) {
			if ( ! empty( $item['id'] ) ) {
				$out[ $item['id'] ] = lanton_yt_parse_duration( $item['contentDetails']['duration'] ?? '' );
			}
		}
	}

	return $out;
}

/** Safety cap on how many videos to pull (50 per API page). */
const LANTON_YT_MAX_VIDEOS = 1000;

/**
 * Resolve the configured API key from a constant or stored option.
 *
 * @return string The key, or '' if none is configured.
 */
function lanton_yt_api_key() {
	if ( defined( 'LANTON_YT_API_KEY' ) && LANTON_YT_API_KEY ) {
		return (string) LANTON_YT_API_KEY;
	}
	if ( LANTON_YT_API_KEY_FALLBACK ) {
		return LANTON_YT_API_KEY_FALLBACK;
	}
	$opt = get_option( 'lanton_yt_api_key' );
	return $opt ? (string) $opt : '';
}

/**
 * Call a YouTube Data API endpoint and return the decoded body.
 *
 * @param string $endpoint API path, e.g. 'channels'.
 * @param array  $params   Query args (api key is added automatically).
 * @return array|WP_Error Decoded response, or WP_Error on failure.
 */
function lanton_yt_request( $endpoint, $params ) {
	$key = lanton_yt_api_key();
	if ( ! $key ) {
		return new WP_Error( 'lanton_yt_no_key', 'No YouTube API key configured.' );
	}

	$params['key'] = $key;
	$url           = add_query_arg( rawurlencode_deep( $params ), 'https://www.googleapis.com/youtube/v3/' . $endpoint );

	$response = wp_remote_get( $url, array( 'timeout' => 15 ) );
	if ( is_wp_error( $response ) ) {
		return $response;
	}

	$code = wp_remote_retrieve_response_code( $response );
	$body = json_decode( wp_remote_retrieve_body( $response ), true );

	if ( 200 !== (int) $code ) {
		$message = isset( $body['error']['message'] ) ? $body['error']['message'] : 'HTTP ' . $code;
		return new WP_Error( 'lanton_yt_http', $message );
	}

	return is_array( $body ) ? $body : new WP_Error( 'lanton_yt_bad_json', 'Unexpected API response.' );
}

/**
 * Resolve the channel's "uploads" playlist ID from its @handle.
 * Cached for a week — channel IDs don't change.
 *
 * @return string|WP_Error Uploads playlist ID (UU…), or WP_Error.
 */
function lanton_yt_uploads_playlist_id() {
	$cached = get_transient( 'lanton_yt_uploads_id' );
	if ( $cached ) {
		return $cached;
	}

	$data = lanton_yt_request(
		'channels',
		array(
			'part'      => 'contentDetails',
			'forHandle' => LANTON_YT_HANDLE,
		)
	);
	if ( is_wp_error( $data ) ) {
		return $data;
	}

	if ( empty( $data['items'][0]['contentDetails']['relatedPlaylists']['uploads'] ) ) {
		return new WP_Error( 'lanton_yt_no_channel', 'Could not find the channel for @' . LANTON_YT_HANDLE . '.' );
	}

	$uploads = $data['items'][0]['contentDetails']['relatedPlaylists']['uploads'];
	set_transient( 'lanton_yt_uploads_id', $uploads, WEEK_IN_SECONDS );
	return $uploads;
}

/**
 * Fetch every video on the channel (newest first), normalized for display.
 * Cached for LANTON_YT_CACHE_TTL. Returns an array of:
 *   [ id, title, description, date (Y-m-d), thumb ]
 *
 * @param bool $force Skip the cache and re-fetch.
 * @return array|WP_Error List of videos, or WP_Error on failure.
 */
function lanton_yt_get_videos( $force = false ) {
	if ( ! $force ) {
		$cached = get_transient( lanton_yt_cache_key() );
		if ( false !== $cached ) {
			return $cached;
		}
	}

	$playlist = lanton_yt_uploads_playlist_id();
	if ( is_wp_error( $playlist ) ) {
		return lanton_yt_backup_or_error( $playlist );
	}

	$videos = array();
	$page   = '';

	do {
		$params = array(
			'part'       => 'snippet,contentDetails',
			'playlistId' => $playlist,
			'maxResults' => 50,
		);
		if ( $page ) {
			$params['pageToken'] = $page;
		}

		$data = lanton_yt_request( 'playlistItems', $params );
		if ( is_wp_error( $data ) ) {
			// A partial fetch is still better than nothing; otherwise fall back
			// to the last good list rather than showing an error.
			return ! empty( $videos ) ? $videos : lanton_yt_backup_or_error( $data );
		}

		foreach ( ( $data['items'] ?? array() ) as $item ) {
			$snippet = $item['snippet'] ?? array();
			$vid     = $item['contentDetails']['videoId'] ?? ( $snippet['resourceId']['videoId'] ?? '' );
			if ( ! $vid ) {
				continue;
			}

			$thumbs = $snippet['thumbnails'] ?? array();
			$thumb  = $thumbs['medium']['url'] ?? ( $thumbs['high']['url'] ?? ( $thumbs['default']['url'] ?? '' ) );
			// The featured player is displayed far larger than a grid card, so
			// it needs the biggest still YouTube offers.
			$thumb_large = $thumbs['maxres']['url']
				?? ( $thumbs['standard']['url'] ?? ( $thumbs['high']['url'] ?? $thumb ) );

			$videos[] = array(
				'id'          => $vid,
				'title'       => $snippet['title'] ?? '',
				'description' => $snippet['description'] ?? '',
				'date'        => ! empty( $snippet['publishedAt'] ) ? gmdate( 'Y-m-d', strtotime( $snippet['publishedAt'] ) ) : '',
				'thumb'       => $thumb,
				'thumb_large' => $thumb_large,
			);
		}

		$page = $data['nextPageToken'] ?? '';
	} while ( $page && count( $videos ) < LANTON_YT_MAX_VIDEOS );

	// YouTube's "Private video" / "Deleted video" placeholders have no real title.
	$videos = array_values(
		array_filter(
			$videos,
			static function ( $v ) {
				return '' !== $v['title'] && ! in_array( $v['title'], array( 'Private video', 'Deleted video' ), true );
			}
		)
	);

	// Second pass for runtimes, which is the only reliable way to tell a Short
	// from a full-length message.
	$durations = lanton_yt_fetch_durations( wp_list_pluck( $videos, 'id' ) );
	foreach ( $videos as &$video ) {
		$secs                = isset( $durations[ $video['id'] ] ) ? (int) $durations[ $video['id'] ] : 0;
		$video['duration']   = $secs;
		$video['is_short']   = $secs > 0 && $secs <= LANTON_YT_SHORT_MAX_SECONDS;
	}
	unset( $video );

	set_transient( lanton_yt_cache_key(), $videos, LANTON_YT_CACHE_TTL );

	// Keep a durable copy so the page survives an outage, quota trip, or a key
	// that stops working — the sermons stay visible until a good fetch replaces
	// them. (Only overwrite with a non-empty list.)
	if ( ! empty( $videos ) ) {
		update_option(
			LANTON_YT_BACKUP_OPTION,
			array(
				'videos'  => $videos,
				'fetched' => time(),
			),
			false
		);
	}

	return $videos;
}

/**
 * Serve the last successful video list when a live fetch fails.
 *
 * @param WP_Error $error The failure to return if there's no backup.
 * @return array|WP_Error Cached videos, or the original error.
 */
function lanton_yt_backup_or_error( $error ) {
	$backup = get_option( LANTON_YT_BACKUP_OPTION );
	if ( ! empty( $backup['videos'] ) && is_array( $backup['videos'] ) ) {
		// Short transient so we retry soon rather than hammering a failing API.
		set_transient( lanton_yt_cache_key(), $backup['videos'], 15 * MINUTE_IN_SECONDS );
		return $backup['videos'];
	}
	return $error;
}

/**
 * Clear every cached piece of channel data (used by "Refresh now" and after
 * the API key changes).
 */
function lanton_yt_flush_cache() {
	delete_transient( lanton_yt_cache_key() );
	delete_transient( 'lanton_yt_uploads_id' );
}

/* -------------------------------------------------------------------------
   AUTO-UPDATE — hourly background refresh
   ------------------------------------------------------------------------- */

/**
 * Make sure the hourly refresh is scheduled (and stop scheduling it if the
 * theme is ever switched away — see the deactivation hook below).
 */
function lanton_yt_schedule_refresh() {
	if ( ! wp_next_scheduled( 'lanton_yt_refresh' ) ) {
		wp_schedule_event( time() + MINUTE_IN_SECONDS, 'hourly', 'lanton_yt_refresh' );
	}
}
add_action( 'wp_loaded', 'lanton_yt_schedule_refresh' );

/**
 * Re-read the channel in the background so visitors always hit a warm cache
 * and new uploads appear on their own.
 */
function lanton_yt_do_refresh() {
	if ( ! lanton_yt_api_key() ) {
		return;
	}
	lanton_yt_get_videos( true );
}
add_action( 'lanton_yt_refresh', 'lanton_yt_do_refresh' );

/**
 * Don't leave the cron event behind if the theme is switched.
 */
function lanton_yt_unschedule_refresh() {
	$timestamp = wp_next_scheduled( 'lanton_yt_refresh' );
	if ( $timestamp ) {
		wp_unschedule_event( $timestamp, 'lanton_yt_refresh' );
	}
}
add_action( 'switch_theme', 'lanton_yt_unschedule_refresh' );

/**
 * Render a single video card (click-to-play facade for fast page loads).
 *
 * @param array $v Normalized video array.
 * @return string HTML.
 */
function lanton_yt_render_card( $v ) {
	$title    = $v['title'];
	$haystack = strtolower( $title . ' ' . $v['description'] );
	$date     = $v['date'] ? gmdate( 'M j, Y', strtotime( $v['date'] ) ) : '';
	$kind     = ! empty( $v['is_short'] ) ? 'short' : 'full';
	$runtime  = lanton_yt_format_duration( $v['duration'] ?? 0 );

	ob_start();
	?>
	<article class="lanton-video" data-kind="<?php echo esc_attr( $kind ); ?>" data-search="<?php echo esc_attr( $haystack ); ?>">
		<button type="button" class="lanton-video__thumb" data-video-id="<?php echo esc_attr( $v['id'] ); ?>"
			aria-label="<?php echo esc_attr( 'Play video: ' . $title ); ?>">
			<?php if ( $v['thumb'] ) : ?>
				<img src="<?php echo esc_url( $v['thumb'] ); ?>" alt="" loading="lazy" decoding="async">
			<?php endif; ?>
			<span class="lanton-video__play" aria-hidden="true"></span>
			<?php if ( $runtime ) : ?>
				<span class="lanton-video__time"><?php echo esc_html( $runtime ); ?></span>
			<?php endif; ?>
		</button>
		<div class="lanton-video__body">
			<?php if ( $date ) : ?>
				<p class="lanton-video__date"><?php echo esc_html( $date ); ?></p>
			<?php endif; ?>
			<h3 class="lanton-video__title"><?php echo esc_html( $title ); ?></h3>
		</div>
	</article>
	<?php
	return ob_get_clean();
}

/**
 * Render the "Most Recent Sermon" spotlight above the archive.
 *
 * Features the newest upload, whatever its length — $videos already arrives
 * newest-first from the channel's uploads playlist, so that's simply the
 * first entry. Short clips are eligible here on purpose.
 *
 * @param array $videos Normalized videos, newest first.
 * @return string HTML ('' if there's nothing to feature).
 */
function lanton_yt_render_featured( $videos ) {
	$featured = $videos[0] ?? null;
	if ( ! $featured ) {
		return '';
	}

	$title   = $featured['title'];
	$date    = $featured['date'] ? gmdate( 'F j, Y', strtotime( $featured['date'] ) ) : '';
	$runtime = lanton_yt_format_duration( $featured['duration'] ?? 0 );
	$thumb   = $featured['thumb_large'] ?? ( $featured['thumb'] ?? '' );

	ob_start();
	?>
	<section class="lanton-featured" aria-labelledby="lanton-featured-heading">
		<p class="lanton-featured__eyebrow" id="lanton-featured-heading">Most Recent Sermon</p>
		<article class="lanton-video lanton-featured__card" data-kind="<?php echo esc_attr( empty( $featured['is_short'] ) ? 'full' : 'short' ); ?>">
			<button type="button" class="lanton-video__thumb" data-video-id="<?php echo esc_attr( $featured['id'] ); ?>"
				aria-label="<?php echo esc_attr( 'Play video: ' . $title ); ?>">
				<?php if ( $thumb ) : ?>
					<img src="<?php echo esc_url( $thumb ); ?>" alt="" fetchpriority="high" decoding="async">
				<?php endif; ?>
				<span class="lanton-video__play" aria-hidden="true"></span>
				<?php if ( $runtime ) : ?>
					<span class="lanton-video__time"><?php echo esc_html( $runtime ); ?></span>
				<?php endif; ?>
			</button>
			<div class="lanton-featured__body">
				<h2 class="lanton-featured__title"><?php echo esc_html( $title ); ?></h2>
				<?php if ( $date ) : ?>
					<p class="lanton-featured__date"><?php echo esc_html( $date ); ?></p>
				<?php endif; ?>
			</div>
		</article>
	</section>
	<?php
	return ob_get_clean();
}

/**
 * `[lanton_youtube]` — searchable grid of all channel videos.
 *
 * @return string HTML.
 */
function lanton_yt_shortcode() {
	$videos = lanton_yt_get_videos();

	// No key, or the API errored — show a friendly, on-brand notice.
	if ( is_wp_error( $videos ) || ! lanton_yt_api_key() ) {
		$is_setup = ! lanton_yt_api_key();
		ob_start();
		?>
		<div class="lanton-note lanton-videos-notice">
			<?php if ( $is_setup ) : ?>
				<strong>Almost there.</strong> To pull in the church's videos automatically,
				add a free YouTube Data API key
				<?php if ( current_user_can( 'manage_options' ) ) : ?>
					under <a href="<?php echo esc_url( admin_url( 'options-general.php?page=lanton-youtube' ) ); ?>">Settings → Sermon Videos (YouTube)</a>
					(about a 3-minute, one-time task).
				<?php else : ?>
					(a one-time setup step).
				<?php endif; ?>
				Until then, you can watch every message on
				<a href="https://www.youtube.com/@<?php echo esc_attr( LANTON_YT_HANDLE ); ?>/videos" target="_blank" rel="noopener">our YouTube channel</a>.
			<?php else : ?>
				<strong>Videos are taking a moment.</strong> We couldn't reach YouTube just now —
				please try again shortly, or visit
				<a href="https://www.youtube.com/@<?php echo esc_attr( LANTON_YT_HANDLE ); ?>/videos" target="_blank" rel="noopener">our channel</a> directly.
			<?php endif; ?>
		</div>
		<?php
		return ob_get_clean();
	}

	if ( empty( $videos ) ) {
		return '<div class="lanton-note lanton-videos-notice">No videos found yet — check back soon!</div>';
	}

	wp_enqueue_script( 'lanton-youtube-search' );

	$count = count( $videos );

	ob_start();
	?>
	<div class="lanton-videos" data-total="<?php echo esc_attr( $count ); ?>">
		<?php echo lanton_yt_render_featured( $videos ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped inside. ?>
		<div class="lanton-videos__search">
			<label class="screen-reader-text" for="lanton-video-search">Search videos</label>
			<input type="search" id="lanton-video-search" class="lanton-videos__input"
				placeholder="Search sermons, series, topics&hellip;" autocomplete="off">

			<?php
			$shorts = 0;
			foreach ( $videos as $v ) {
				if ( ! empty( $v['is_short'] ) ) {
					$shorts++;
				}
			}
			$full = $count - $shorts;

			$tabs = array(
				'all'   => array( 'All Videos', $count ),
				'full'  => array( 'Full Messages', $full ),
				'short' => array( 'Short Clips', $shorts ),
			);
			?>
			<div class="lanton-videos__tabs" role="tablist" aria-label="Filter videos by length">
				<?php foreach ( $tabs as $slug => $tab ) : ?>
					<?php
					list( $label, $n ) = $tab;
					if ( 'all' !== $slug && ! $n ) {
						continue; // Don't offer an empty filter.
					}
					$is_active = ( 'all' === $slug );
					?>
					<button type="button" role="tab" class="lanton-videos__tab<?php echo $is_active ? ' is-active' : ''; ?>"
						data-kind="<?php echo esc_attr( $slug ); ?>"
						aria-selected="<?php echo $is_active ? 'true' : 'false'; ?>">
						<?php echo esc_html( $label ); ?>
						<span class="lanton-videos__tabcount"><?php echo esc_html( number_format_i18n( $n ) ); ?></span>
					</button>
				<?php endforeach; ?>
			</div>

			<p class="lanton-videos__count" aria-live="polite" data-singular="video" data-plural="videos">
				<?php echo esc_html( number_format_i18n( $count ) . ' ' . _n( 'video', 'videos', $count, 'lanton-church' ) ); ?>
			</p>
		</div>
		<div class="lanton-videos__grid">
			<?php
			foreach ( $videos as $v ) {
				echo lanton_yt_render_card( $v ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped inside.
			}
			?>
		</div>
		<p class="lanton-videos__empty" hidden>No videos match &ldquo;<span class="lanton-videos__query"></span>&rdquo;. Try a different word.</p>
	</div>
	<?php
	return ob_get_clean();
}
add_shortcode( 'lanton_youtube', 'lanton_yt_shortcode' );

/**
 * Register (but don't enqueue) the search script; the shortcode enqueues it
 * only on pages that actually use the gallery.
 */
function lanton_yt_register_assets() {
	wp_register_script(
		'lanton-youtube-search',
		get_theme_file_uri( 'assets/js/youtube-search.js' ),
		array(),
		LANTON_VERSION,
		array( 'strategy' => 'defer', 'in_footer' => true )
	);
}
add_action( 'wp_enqueue_scripts', 'lanton_yt_register_assets' );

/* -------------------------------------------------------------------------
   ADMIN — paste the key, see the status, refresh on demand
   ------------------------------------------------------------------------- */

/**
 * Add Settings → "Sermon Videos (YouTube)".
 */
function lanton_yt_admin_menu() {
	add_options_page(
		'Sermon Videos (YouTube)',
		'Sermon Videos (YouTube)',
		'manage_options',
		'lanton-youtube',
		'lanton_yt_settings_page'
	);
}
add_action( 'admin_menu', 'lanton_yt_admin_menu' );

/**
 * Render the settings screen: API key field, current status, refresh button.
 */
function lanton_yt_settings_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$in_config = defined( 'LANTON_YT_API_KEY' ) && LANTON_YT_API_KEY;
	$notice    = '';

	if ( isset( $_POST['lanton_yt_nonce'] ) && wp_verify_nonce( sanitize_key( wp_unslash( $_POST['lanton_yt_nonce'] ) ), 'lanton_yt_settings' ) ) {
		if ( isset( $_POST['lanton_yt_save'] ) ) {
			$key = sanitize_text_field( wp_unslash( $_POST['lanton_yt_api_key'] ?? '' ) );
			update_option( 'lanton_yt_api_key', $key );
			lanton_yt_flush_cache();
			$notice = $key ? 'Key saved. Loading videos from YouTube&hellip;' : 'Key cleared.';
		} elseif ( isset( $_POST['lanton_yt_refresh'] ) ) {
			lanton_yt_flush_cache();
			$notice = 'Cache cleared — re-reading the channel now.';
		}
	}

	$videos = lanton_yt_api_key() ? lanton_yt_get_videos() : array();
	$backup = get_option( LANTON_YT_BACKUP_OPTION );
	$next   = wp_next_scheduled( 'lanton_yt_refresh' );
	?>
	<div class="wrap">
		<h1>Sermon Videos (YouTube)</h1>
		<p>The <a href="<?php echo esc_url( home_url( '/sermons/' ) ); ?>">Sermons page</a> lists every video
			from <a href="https://www.youtube.com/@<?php echo esc_attr( LANTON_YT_HANDLE ); ?>" target="_blank" rel="noopener">@<?php echo esc_html( LANTON_YT_HANDLE ); ?></a>,
			newest first. It refreshes itself every hour — post a video to YouTube and it appears here on its own.</p>

		<?php if ( $notice ) : ?>
			<div class="notice notice-success"><p><?php echo wp_kses_post( $notice ); ?></p></div>
		<?php endif; ?>

		<h2>Status</h2>
		<table class="widefat striped" style="max-width:760px">
			<tbody>
				<tr>
					<td style="width:200px"><strong>API key</strong></td>
					<td>
						<?php if ( $in_config ) : ?>
							Set in <code>wp-config.php</code> (that wins over the field below).
						<?php elseif ( lanton_yt_api_key() ) : ?>
							Saved. ✅
						<?php else : ?>
							<strong>Not set yet</strong> — the Sermons page shows a setup notice until you add one.
						<?php endif; ?>
					</td>
				</tr>
				<tr>
					<td><strong>Videos loaded</strong></td>
					<td>
						<?php if ( is_wp_error( $videos ) ) : ?>
							<span style="color:#b32d2e">Error: <?php echo esc_html( $videos->get_error_message() ); ?></span>
						<?php else : ?>
							<?php echo esc_html( number_format_i18n( count( $videos ) ) ); ?>
						<?php endif; ?>
					</td>
				</tr>
				<tr>
					<td><strong>Last successful read</strong></td>
					<td>
						<?php
						echo ! empty( $backup['fetched'] )
							? esc_html( human_time_diff( (int) $backup['fetched'] ) . ' ago' )
							: '—';
						?>
					</td>
				</tr>
				<tr>
					<td><strong>Next automatic check</strong></td>
					<td>
						<?php
						if ( ! $next ) {
							echo 'not scheduled';
						} elseif ( $next <= time() ) {
							// human_time_diff() is an absolute distance, so an overdue
							// event would otherwise read as if it were still upcoming.
							echo esc_html( 'due now (' . human_time_diff( $next ) . ' overdue) — runs on the next page load' );
						} else {
							echo esc_html( 'in ' . human_time_diff( $next ) );
						}
						?>
					</td>
				</tr>
			</tbody>
		</table>

		<form method="post" style="margin-top:1.5rem;max-width:760px">
			<?php wp_nonce_field( 'lanton_yt_settings', 'lanton_yt_nonce' ); ?>
			<h2>YouTube Data API key</h2>
			<p>
				Free, one-time setup: create a project at
				<a href="https://console.cloud.google.com/" target="_blank" rel="noopener">console.cloud.google.com</a>,
				enable <strong>YouTube Data API v3</strong> under APIs &amp; Services → Library, then
				APIs &amp; Services → Credentials → <strong>Create credentials → API key</strong>. Paste it here.
			</p>
			<p>
				<input type="text" class="regular-text code" name="lanton_yt_api_key"
					value="<?php echo esc_attr( (string) get_option( 'lanton_yt_api_key', '' ) ); ?>"
					placeholder="AIza&hellip;" style="width:100%;max-width:520px"
					<?php disabled( $in_config ); ?>>
			</p>
			<p class="description">Read-only access to public video listings — it can't post or change anything on the channel.</p>
			<p>
				<button type="submit" name="lanton_yt_save" class="button button-primary" <?php disabled( $in_config ); ?>>Save key</button>
				<button type="submit" name="lanton_yt_refresh" class="button">Refresh videos now</button>
			</p>
		</form>
	</div>
	<?php
}

/**
 * Nudge an admin until the key is in place — otherwise the Sermons page is
 * quietly empty and nobody notices.
 */
function lanton_yt_admin_notice() {
	if ( ! current_user_can( 'manage_options' ) || lanton_yt_api_key() ) {
		return;
	}

	$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
	if ( $screen && in_array( $screen->id, array( 'settings_page_lanton-youtube' ), true ) ) {
		return;
	}
	?>
	<div class="notice notice-warning">
		<p>
			<strong>Lanton Church:</strong> the Sermons page can't load the church's YouTube videos yet.
			<a href="<?php echo esc_url( admin_url( 'options-general.php?page=lanton-youtube' ) ); ?>">Add a free YouTube API key</a>
			(about 3 minutes, once).
		</p>
	</div>
	<?php
}
add_action( 'admin_notices', 'lanton_yt_admin_notice' );
