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
 *        a) In wp-config.php:  define( 'LANTON_YT_API_KEY', 'your-key' );
 *        b) Or paste it into LANTON_YT_API_KEY_FALLBACK just below.
 *   (Optional but recommended: in the key's settings, restrict it to the
 *    "YouTube Data API v3" so it can't be abused elsewhere.)
 * Until a key is present, the Videos page shows a friendly setup notice.
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
const LANTON_YT_CACHE_TTL = 6 * HOUR_IN_SECONDS;

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
		$cached = get_transient( 'lanton_yt_videos' );
		if ( false !== $cached ) {
			return $cached;
		}
	}

	$playlist = lanton_yt_uploads_playlist_id();
	if ( is_wp_error( $playlist ) ) {
		return $playlist;
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
			// If we already gathered some videos, serve what we have.
			return ! empty( $videos ) ? $videos : $data;
		}

		foreach ( ( $data['items'] ?? array() ) as $item ) {
			$snippet = $item['snippet'] ?? array();
			$vid     = $item['contentDetails']['videoId'] ?? ( $snippet['resourceId']['videoId'] ?? '' );
			if ( ! $vid ) {
				continue;
			}

			$thumbs = $snippet['thumbnails'] ?? array();
			$thumb  = $thumbs['medium']['url'] ?? ( $thumbs['high']['url'] ?? ( $thumbs['default']['url'] ?? '' ) );

			$videos[] = array(
				'id'          => $vid,
				'title'       => $snippet['title'] ?? '',
				'description' => $snippet['description'] ?? '',
				'date'        => ! empty( $snippet['publishedAt'] ) ? gmdate( 'Y-m-d', strtotime( $snippet['publishedAt'] ) ) : '',
				'thumb'       => $thumb,
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

	set_transient( 'lanton_yt_videos', $videos, LANTON_YT_CACHE_TTL );
	return $videos;
}

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

	ob_start();
	?>
	<article class="lanton-video" data-search="<?php echo esc_attr( $haystack ); ?>">
		<button type="button" class="lanton-video__thumb" data-video-id="<?php echo esc_attr( $v['id'] ); ?>"
			aria-label="<?php echo esc_attr( 'Play video: ' . $title ); ?>">
			<?php if ( $v['thumb'] ) : ?>
				<img src="<?php echo esc_url( $v['thumb'] ); ?>" alt="" loading="lazy" decoding="async">
			<?php endif; ?>
			<span class="lanton-video__play" aria-hidden="true"></span>
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
				add a free YouTube Data API key. Setup instructions are in
				<code>inc/youtube.php</code> (about a 3-minute, one-time task). Until then,
				you can still watch everything on
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
		<div class="lanton-videos__search">
			<label class="screen-reader-text" for="lanton-video-search">Search videos</label>
			<input type="search" id="lanton-video-search" class="lanton-videos__input"
				placeholder="Search sermons, series, topics&hellip;" autocomplete="off">
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
