<?php
/**
 * Title: Sermons — Content
 * Slug: lanton-church/sermons
 * Categories: lanton
 * Inserter: true
 *
 * The sermon archive is pulled live from the church's YouTube channel by the
 * [lanton_youtube] shortcode (inc/youtube.php) — every upload, newest first,
 * searchable, with click-to-play. Nothing here needs editing when a new video
 * is posted; it appears on its own.
 */
?>
<!-- wp:paragraph {"align":"center","fontSize":"medium","style":{"spacing":{"margin":{"bottom":"var:preset|spacing|40"}}}} -->
<p class="has-text-align-center has-medium-font-size" style="margin-bottom:var(--wp--preset--spacing--40)">Watch sermons from Lanton Church of Christ in Columbia, Tennessee. New messages are added every week. Search by topic, passage, or speaker.</p>
<!-- /wp:paragraph -->

<!-- wp:group {"align":"wide","layout":{"type":"default"}} -->
<div class="wp-block-group alignwide"><!-- wp:shortcode -->
[lanton_youtube]
<!-- /wp:shortcode --></div>
<!-- /wp:group -->

<!-- wp:paragraph {"align":"center","className":"lanton-note","style":{"spacing":{"margin":{"top":"var:preset|spacing|60"}}}} -->
<p class="has-text-align-center lanton-note" style="margin-top:var(--wp--preset--spacing--60)">Subscribe on <a href="https://www.youtube.com/@LantonChurchofChrist" target="_blank" rel="noopener">our YouTube channel</a> to be notified whenever a new message is posted.</p>
<!-- /wp:paragraph -->
