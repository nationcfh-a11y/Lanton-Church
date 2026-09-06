<?php
/**
 * Title: About — Content
 * Slug: lanton-church/about
 * Categories: lanton
 * Inserter: true
 *
 * Full-bleed sections in the homepage's `lh-*` idiom. Rendered through
 * templates/page-about.html, which drops the constrained wrapper so these
 * sections can span edge to edge.
 */
?>
<!-- HERO — centered type over the warm cream gradient (shares .lh-hero so the
     load fades in parallax.js, scoped to `.lh-hero .lanton-fade`, still run) -->
<!-- wp:group {"tagName":"section","className":"lh-hero lh-about-hero","layout":{"type":"constrained","contentSize":"820px"}} -->
<section class="wp-block-group lh-hero lh-about-hero"><!-- wp:paragraph {"align":"center","className":"lanton-eyebrow lanton-fade"} -->
<p class="has-text-align-center lanton-eyebrow lanton-fade">About Lanton Church of Christ</p>
<!-- /wp:paragraph -->

<!-- wp:heading {"textAlign":"center","level":1,"className":"lh-hero__title lanton-animate-letters","fontSize":"xx-large"} -->
<h1 class="wp-block-heading has-text-align-center lh-hero__title lanton-animate-letters has-xx-large-font-size">Christians, plain and simple.</h1>
<!-- /wp:heading -->

<!-- wp:paragraph {"align":"center","className":"lh-lede lanton-fade","fontSize":"large"} -->
<p class="has-text-align-center lh-lede lanton-fade has-large-font-size">We're a family of believers in Columbia, Tennessee, doing our best to worship and live the way the first-century church did — nothing added, nothing taken away.</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"align":"center","className":"lh-hero__meta lanton-fade"} -->
<p class="has-text-align-center lh-hero__meta lanton-fade">Meeting on Greens Mill Road since [ year ] · Sundays at 10:30 AM &amp; 2:00 PM</p>
<!-- /wp:paragraph --></section>
<!-- /wp:group -->

<!-- OUR STORY — the one photo on the page, in a teal stained-glass frame -->
<!-- wp:group {"tagName":"section","className":"lh-sec lh-story","layout":{"type":"constrained","contentSize":"1160px"}} -->
<section class="wp-block-group lh-sec lh-story"><!-- wp:columns {"verticalAlignment":"center","className":"lanton-about-intro lh-split"} -->
<div class="wp-block-columns are-vertically-aligned-center lanton-about-intro lh-split"><!-- wp:column {"verticalAlignment":"center","width":"52%","className":"lanton-reveal"} -->
<div class="wp-block-column is-vertically-aligned-center lanton-reveal" style="flex-basis:52%"><!-- wp:image {"className":"lanton-about-photo"} -->
<figure class="wp-block-image lanton-about-photo"><img src="<?php echo esc_url( get_theme_file_uri( 'assets/images/hero-congregation.webp' ) ); ?>" alt="The Lanton Church of Christ family gathered together"/></figure>
<!-- /wp:image --></div>
<!-- /wp:column -->

<!-- wp:column {"verticalAlignment":"center","width":"48%","className":"lanton-reveal"} -->
<div class="wp-block-column is-vertically-aligned-center lanton-reveal" style="flex-basis:48%"><!-- wp:paragraph {"className":"lanton-eyebrow"} -->
<p class="lanton-eyebrow">Our story</p>
<!-- /wp:paragraph -->

<!-- wp:heading {"level":2,"fontSize":"x-large"} -->
<h2 class="wp-block-heading has-x-large-font-size">A congregation rooted on this hill.</h2>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>Lanton Church of Christ has gathered on Greens Mill Road since [ year ], when a handful of families began meeting to worship in the simple pattern of the New Testament. <em>[ Add a sentence about how the congregation began — who started it and where they first met. ]</em></p>
<!-- /wp:paragraph -->

<!-- wp:paragraph -->
<p>A great deal about Columbia has changed since then. What fills this building on a Sunday has not: singing without instruments, prayer, the Lord's Supper, and plain preaching straight from an open Bible.</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph -->
<p>Today we're grandparents and grandchildren, lifelong members and first-time visitors, folks who farm and folks who commute. What holds us together isn't a program or a personality — it's Christ, and the people He's put beside us.</p>
<!-- /wp:paragraph -->
</div>
<!-- /wp:column --></div>
<!-- /wp:columns -->

<!-- wp:list {"className":"lh-facts lanton-reveal"} -->
<ul class="wp-block-list lh-facts lanton-reveal"><!-- wp:list-item --><li><strong>Since [ year ]</strong><span>gathering on Greens Mill Road</span></li><!-- /wp:list-item -->
<!-- wp:list-item --><li><strong>Autonomous</strong><span>no headquarters but Scripture</span></li><!-- /wp:list-item -->
<!-- wp:list-item --><li><strong>A cappella</strong><span>every voice in the song</span></li><!-- /wp:list-item -->
<!-- wp:list-item --><li><strong>Every Lord&#8217;s Day</strong><span>the Supper, together</span></li><!-- /wp:list-item --></ul>
<!-- /wp:list --></section>
<!-- /wp:group -->

<!-- WHAT WE BELIEVE — six plain cards, each anchored to a passage -->
<!-- wp:group {"tagName":"section","anchor":"beliefs","className":"lh-sec lh-beliefs","layout":{"type":"constrained","contentSize":"1160px"}} -->
<section id="beliefs" class="wp-block-group lh-sec lh-beliefs"><!-- wp:group {"className":"lh-beliefs__head","layout":{"type":"constrained","contentSize":"760px"}} -->
<div class="wp-block-group lh-beliefs__head"><!-- wp:paragraph {"align":"center","className":"lanton-eyebrow lanton-reveal"} -->
<p class="has-text-align-center lanton-eyebrow lanton-reveal">What we believe</p>
<!-- /wp:paragraph -->

<!-- wp:heading {"textAlign":"center","className":"lanton-reveal","fontSize":"x-large"} -->
<h2 class="wp-block-heading has-text-align-center lanton-reveal has-x-large-font-size">No creed but the Bible.</h2>
<!-- /wp:heading -->

<!-- wp:paragraph {"align":"center","className":"lanton-reveal lh-muted","fontSize":"medium"} -->
<p class="has-text-align-center lanton-reveal lh-muted has-medium-font-size">We wear no name but Christ's and follow no book but His. Everything below is simply what we read in Scripture — read it for yourself and hold us to it.</p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->

<!-- wp:group {"className":"lh-grid lh-beliefs__grid","layout":{"type":"default"}} -->
<div class="wp-block-group lh-grid lh-beliefs__grid"><!-- wp:group {"className":"lh-note lh-belief lanton-reveal","layout":{"type":"default"}} -->
<div class="wp-block-group lh-note lh-belief lanton-reveal"><!-- wp:heading {"level":3,"fontSize":"large"} -->
<h3 class="wp-block-heading has-large-font-size">The Word of God</h3>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Scripture is inspired, sufficient, and enough. We aim to speak where the Bible speaks and to stay quiet where it doesn't.</p>
<!-- /wp:paragraph -->
<!-- wp:paragraph {"className":"lh-belief__ref"} -->
<p class="lh-belief__ref">2 Timothy 3:16–17</p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->

<!-- wp:group {"className":"lh-note lh-belief lanton-reveal","layout":{"type":"default"}} -->
<div class="wp-block-group lh-note lh-belief lanton-reveal"><!-- wp:heading {"level":3,"fontSize":"large"} -->
<h3 class="wp-block-heading has-large-font-size">One Lord, One Faith</h3>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>We worship God the Father, follow His Son Jesus Christ, and are helped by the Holy Spirit given to every Christian.</p>
<!-- /wp:paragraph -->
<!-- wp:paragraph {"className":"lh-belief__ref"} -->
<p class="lh-belief__ref">Ephesians 4:4–6</p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->

<!-- wp:group {"className":"lh-note lh-belief lanton-reveal","layout":{"type":"default"}} -->
<div class="wp-block-group lh-note lh-belief lanton-reveal"><!-- wp:heading {"level":3,"fontSize":"large"} -->
<h3 class="wp-block-heading has-large-font-size">Salvation in Christ</h3>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Saved by God's grace through faith — believing in Jesus, turning from sin, confessing His name, and being baptized for the forgiveness of sins.</p>
<!-- /wp:paragraph -->
<!-- wp:paragraph {"className":"lh-belief__ref"} -->
<p class="lh-belief__ref">Acts 2:38</p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->

<!-- wp:group {"className":"lh-note lh-belief lanton-reveal","layout":{"type":"default"}} -->
<div class="wp-block-group lh-note lh-belief lanton-reveal"><!-- wp:heading {"level":3,"fontSize":"large"} -->
<h3 class="wp-block-heading has-large-font-size">The Lord's Supper</h3>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Every Lord's Day we share the bread and the cup together, remembering the death of Jesus until He comes again.</p>
<!-- /wp:paragraph -->
<!-- wp:paragraph {"className":"lh-belief__ref"} -->
<p class="lh-belief__ref">Acts 20:7</p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->

<!-- wp:group {"className":"lh-note lh-belief lanton-reveal","layout":{"type":"default"}} -->
<div class="wp-block-group lh-note lh-belief lanton-reveal"><!-- wp:heading {"level":3,"fontSize":"large"} -->
<h3 class="wp-block-heading has-large-font-size">Singing from the Heart</h3>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>We sing with our voices alone, as the New Testament church did. There's no choir here because everyone is in it.</p>
<!-- /wp:paragraph -->
<!-- wp:paragraph {"className":"lh-belief__ref"} -->
<p class="lh-belief__ref">Ephesians 5:19</p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->

<!-- wp:group {"className":"lh-note lh-belief lanton-reveal","layout":{"type":"default"}} -->
<div class="wp-block-group lh-note lh-belief lanton-reveal"><!-- wp:heading {"level":3,"fontSize":"large"} -->
<h3 class="wp-block-heading has-large-font-size">The Local Church</h3>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Each congregation stands on its own, shepherded by its elders and served by its deacons, with Christ as the only head.</p>
<!-- /wp:paragraph -->
<!-- wp:paragraph {"className":"lh-belief__ref"} -->
<p class="lh-belief__ref">Titus 1:5 · Colossians 1:18</p>
<!-- /wp:paragraph --></div>
<!-- /wp:group --></div>
<!-- /wp:group -->

<!-- wp:paragraph {"align":"center","className":"lh-beliefs__foot lanton-reveal"} -->
<p class="has-text-align-center lh-beliefs__foot lanton-reveal">Questions about any of this? <a href="/contact/">We'd love to sit down with you</a> — no pressure, just a Bible and a conversation.</p>
<!-- /wp:paragraph --></section>
<!-- /wp:group -->

<!-- SCRIPTURE — solid sage band, matching the homepage verse (different class
     names on purpose so the homepage verse carousel leaves this one alone) -->
<!-- wp:group {"tagName":"section","className":"lh-verse lh-about-verse","layout":{"type":"constrained","contentSize":"860px"}} -->
<section class="wp-block-group lh-verse lh-about-verse"><!-- wp:paragraph {"align":"center","className":"lanton-eyebrow lh-verse__eyebrow lanton-reveal"} -->
<p class="has-text-align-center lanton-eyebrow lh-verse__eyebrow lanton-reveal">The pattern we follow</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"align":"center","className":"lanton-verse lh-verse__quote","fontSize":"x-large"} -->
<p class="has-text-align-center lanton-verse lh-verse__quote has-x-large-font-size">They devoted themselves to the apostles' teaching and to fellowship, to the breaking of bread and to prayer.</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"align":"center","className":"lh-verse__ref lh-verse__cite lanton-reveal"} -->
<p class="has-text-align-center lh-verse__ref lh-verse__cite lanton-reveal">Acts 2:42</p>
<!-- /wp:paragraph --></section>
<!-- /wp:group -->

<!-- WHAT TO EXPECT — four numbered steps through a first visit -->
<!-- wp:group {"tagName":"section","anchor":"what-to-expect","className":"lh-sec lh-visit","layout":{"type":"constrained","contentSize":"1160px"}} -->
<section id="what-to-expect" class="wp-block-group lh-sec lh-visit"><!-- wp:group {"className":"lh-visit__head","layout":{"type":"constrained","contentSize":"760px"}} -->
<div class="wp-block-group lh-visit__head"><!-- wp:paragraph {"align":"center","className":"lanton-eyebrow lanton-reveal"} -->
<p class="has-text-align-center lanton-eyebrow lanton-reveal">Your first visit</p>
<!-- /wp:paragraph -->

<!-- wp:heading {"textAlign":"center","className":"lanton-reveal","fontSize":"x-large"} -->
<h2 class="wp-block-heading has-text-align-center lanton-reveal has-x-large-font-size">What a Sunday looks like.</h2>
<!-- /wp:heading -->

<!-- wp:paragraph {"align":"center","className":"lanton-reveal lh-muted","fontSize":"medium"} -->
<p class="has-text-align-center lanton-reveal lh-muted has-medium-font-size">Walking into a church for the first time can feel like a lot. Here's exactly what happens, so nothing catches you off guard.</p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->

<!-- wp:group {"className":"lh-grid lh-steps","layout":{"type":"default"}} -->
<div class="wp-block-group lh-grid lh-steps"><!-- wp:group {"className":"lh-step lanton-reveal","layout":{"type":"default"}} -->
<div class="wp-block-group lh-step lanton-reveal"><!-- wp:paragraph {"className":"lh-step__num"} -->
<p class="lh-step__num">01</p>
<!-- /wp:paragraph -->
<!-- wp:heading {"level":3,"fontSize":"large"} -->
<h3 class="wp-block-heading has-large-font-size">Pulling in</h3>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Park anywhere out front — the doors face the lot. Someone will meet you with a handshake and a bulletin, and very likely ask where you're from.</p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->

<!-- wp:group {"className":"lh-step lanton-reveal","layout":{"type":"default"}} -->
<div class="wp-block-group lh-step lanton-reveal"><!-- wp:paragraph {"className":"lh-step__num"} -->
<p class="lh-step__num">02</p>
<!-- /wp:paragraph -->
<!-- wp:heading {"level":3,"fontSize":"large"} -->
<h3 class="wp-block-heading has-large-font-size">Worship together</h3>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Singing led from the floor with no instruments, prayer, and the Lord's Supper. A collection is taken for the work here — visitors, please just let it pass.</p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->

<!-- wp:group {"className":"lh-step lanton-reveal","layout":{"type":"default"}} -->
<div class="wp-block-group lh-step lanton-reveal"><!-- wp:paragraph {"className":"lh-step__num"} -->
<p class="lh-step__num">03</p>
<!-- /wp:paragraph -->
<!-- wp:heading {"level":3,"fontSize":"large"} -->
<h3 class="wp-block-heading has-large-font-size">The sermon</h3>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>About [ 30 ] minutes of straightforward preaching from an open Bible. Bring your own or borrow one from the pew — the text is always announced.</p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->

<!-- wp:group {"className":"lh-step lanton-reveal","layout":{"type":"default"}} -->
<div class="wp-block-group lh-step lanton-reveal"><!-- wp:paragraph {"className":"lh-step__num"} -->
<p class="lh-step__num">04</p>
<!-- /wp:paragraph -->
<!-- wp:heading {"level":3,"fontSize":"large"} -->
<h3 class="wp-block-heading has-large-font-size">Afterward</h3>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Nobody's in a hurry to leave. Stay and visit, ask the hard questions, and tell us how we can be praying for you this week.</p>
<!-- /wp:paragraph --></div>
<!-- /wp:group --></div>
<!-- /wp:group -->

<!-- wp:list {"className":"lh-visit__notes lanton-reveal"} -->
<ul class="wp-block-list lh-visit__notes lanton-reveal"><!-- wp:list-item --><li><strong>Come as you are.</strong> Some folks wear a tie, some wear jeans. Nobody is counting.</li><!-- /wp:list-item -->
<!-- wp:list-item --><li><strong>Children are welcome.</strong> Little ones worship right alongside us — wiggles and all. <em>[ Note your nursery or cry room here. ]</em></li><!-- /wp:list-item -->
<!-- wp:list-item --><li><strong>You'll be out by [ 11:45 ].</strong> Morning worship runs a little over an hour.</li><!-- /wp:list-item --></ul>
<!-- /wp:list --></section>
<!-- /wp:group -->

<!-- LEADERSHIP -->
<!-- wp:group {"tagName":"section","anchor":"leadership","className":"lh-sec lh-leaders","layout":{"type":"constrained","contentSize":"1160px"}} -->
<section id="leadership" class="wp-block-group lh-sec lh-leaders"><!-- wp:group {"className":"lh-leaders__head","layout":{"type":"constrained","contentSize":"760px"}} -->
<div class="wp-block-group lh-leaders__head"><!-- wp:paragraph {"align":"center","className":"lanton-eyebrow lanton-reveal"} -->
<p class="has-text-align-center lanton-eyebrow lanton-reveal">Who serves here</p>
<!-- /wp:paragraph -->

<!-- wp:heading {"textAlign":"center","className":"lanton-reveal","fontSize":"x-large"} -->
<h2 class="wp-block-heading has-text-align-center lanton-reveal has-x-large-font-size">Shepherds, servants, and a preacher.</h2>
<!-- /wp:heading -->

<!-- wp:paragraph {"align":"center","className":"lanton-reveal lh-muted","fontSize":"medium"} -->
<p class="has-text-align-center lanton-reveal lh-muted has-medium-font-size">Our elders oversee the flock, our deacons carry out the work, and our minister preaches the Word. Every one of them is a phone call away. <em>[ Add each name, photo, and a line of bio. ]</em></p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->

<!-- wp:group {"className":"lh-grid lh-leaders__grid","layout":{"type":"default"}} -->
<div class="wp-block-group lh-grid lh-leaders__grid"><!-- wp:group {"className":"lh-note lh-leader lanton-reveal","layout":{"type":"default"}} -->
<div class="wp-block-group lh-note lh-leader lanton-reveal"><!-- wp:paragraph {"className":"lanton-bio__role"} -->
<p class="lanton-bio__role">Minister</p>
<!-- /wp:paragraph -->
<!-- wp:heading {"level":3,"fontSize":"large"} -->
<h3 class="wp-block-heading has-large-font-size">[ Name ]</h3>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>[ A few lines about his background, his family, and a word of welcome to anyone visiting. ]</p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->

<!-- wp:group {"className":"lh-note lh-leader lanton-reveal","layout":{"type":"default"}} -->
<div class="wp-block-group lh-note lh-leader lanton-reveal"><!-- wp:paragraph {"className":"lanton-bio__role"} -->
<p class="lanton-bio__role">Elder</p>
<!-- /wp:paragraph -->
<!-- wp:heading {"level":3,"fontSize":"large"} -->
<h3 class="wp-block-heading has-large-font-size">[ Name ]</h3>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>[ Years of service, his family, and the part of the flock he loves to look after. ]</p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->

<!-- wp:group {"className":"lh-note lh-leader lanton-reveal","layout":{"type":"default"}} -->
<div class="wp-block-group lh-note lh-leader lanton-reveal"><!-- wp:paragraph {"className":"lanton-bio__role"} -->
<p class="lanton-bio__role">Elder</p>
<!-- /wp:paragraph -->
<!-- wp:heading {"level":3,"fontSize":"large"} -->
<h3 class="wp-block-heading has-large-font-size">[ Name ]</h3>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>[ Years of service, his family, and the part of the flock he loves to look after. ]</p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->

<!-- wp:group {"className":"lh-note lh-leader lanton-reveal","layout":{"type":"default"}} -->
<div class="wp-block-group lh-note lh-leader lanton-reveal"><!-- wp:paragraph {"className":"lanton-bio__role"} -->
<p class="lanton-bio__role">Deacon</p>
<!-- /wp:paragraph -->
<!-- wp:heading {"level":3,"fontSize":"large"} -->
<h3 class="wp-block-heading has-large-font-size">[ Name ]</h3>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>[ His area of service — benevolence, building, education — and how to reach him. ]</p>
<!-- /wp:paragraph --></div>
<!-- /wp:group --></div>
<!-- /wp:group --></section>
<!-- /wp:group -->

<!-- CTA -->
<!-- wp:group {"tagName":"section","className":"lh-sec lh-about-cta","layout":{"type":"constrained","contentSize":"720px"}} -->
<section class="wp-block-group lh-sec lh-about-cta"><!-- wp:paragraph {"align":"center","className":"lanton-eyebrow lanton-reveal"} -->
<p class="has-text-align-center lanton-eyebrow lanton-reveal">Come see us</p>
<!-- /wp:paragraph -->

<!-- wp:heading {"textAlign":"center","className":"lanton-reveal","fontSize":"x-large"} -->
<h2 class="wp-block-heading has-text-align-center lanton-reveal has-x-large-font-size">There's a seat waiting for you.</h2>
<!-- /wp:heading -->

<!-- wp:paragraph {"align":"center","className":"lanton-reveal lh-muted","fontSize":"medium"} -->
<p class="has-text-align-center lanton-reveal lh-muted has-medium-font-size">2898 Greens Mill Rd, Columbia, TN 38401 — Sundays at 10:30 AM and 2:00 PM. Come early, stay late, and let us get to know you.</p>
<!-- /wp:paragraph -->

<!-- wp:buttons {"className":"lanton-reveal","layout":{"type":"flex","justifyContent":"center"},"style":{"spacing":{"margin":{"top":"var:preset|spacing|40"}}}} -->
<div class="wp-block-buttons lanton-reveal" style="margin-top:var(--wp--preset--spacing--40)"><!-- wp:button {"className":"lanton-btn"} -->
<div class="wp-block-button lanton-btn"><a class="wp-block-button__link wp-element-button" href="/service-times/">Plan Your Visit</a></div>
<!-- /wp:button -->

<!-- wp:button {"className":"lanton-btn is-style-ghost"} -->
<div class="wp-block-button lanton-btn is-style-ghost"><a class="wp-block-button__link wp-element-button" href="/contact/">Ask a Question</a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons --></section>
<!-- /wp:group -->
