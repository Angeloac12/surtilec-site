<?php
/**
 * Plugin Name: Surtilec — Mobile Header Polish
 * Description: Compact mobile header polish for faster first-viewport access to the hero and catalog search.
 * Version:     0.1.0
 * Author:      Surtilec
 *
 * @package Surtilec
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'wp_enqueue_scripts', 'surtilec_mobile_header_polish_styles', 120 );

/**
 * Add a small mobile-only CSS override without deploying the dirty child theme.
 */
function surtilec_mobile_header_polish_styles() {
	wp_register_style( 'surtilec-mobile-header-polish', false, array(), '0.1.0' );
	wp_enqueue_style( 'surtilec-mobile-header-polish' );
	wp_add_inline_style( 'surtilec-mobile-header-polish', surtilec_mobile_header_polish_css() );
}

/**
 * Mobile header CSS.
 *
 * @return string
 */
function surtilec_mobile_header_polish_css() {
	return <<<'CSS'
@media (max-width: 880px) {
  .surtilec-utilitybar {
    font-size: 0.78rem;
  }

  .surtilec-utilitybar-inner {
    min-height: 42px;
    padding: 0.35rem 1rem;
    gap: 0.9rem;
    flex-wrap: nowrap;
    justify-content: center;
  }

  .surtilec-utilitybar .su-metals,
  .surtilec-utilitybar .su-util-sep,
  .surtilec-utilitybar .su-util-claim,
  .surtilec-utilitybar .su-util-strong {
    display: none !important;
  }

  .surtilec-utilitybar .su-util-phone,
  .surtilec-utilitybar .su-util-wa {
    white-space: nowrap;
  }

  .inside-header {
    display: grid !important;
    grid-template-columns: minmax(0, 1fr) auto;
    grid-template-areas:
      "brand cta"
      "search search";
    align-items: center;
    gap: 0.65rem 0.85rem;
    padding: 0.75rem 1rem 0.85rem !important;
  }

  .inside-header .site-branding {
    grid-area: brand;
    min-width: 0;
  }

  .site-description {
    display: none;
  }

  .main-title {
    margin: 0;
    font-size: clamp(1.8rem, 8vw, 2.15rem);
    line-height: 1;
  }

  .su-header-tools {
    display: contents;
  }

  .su-header-wa {
    display: none !important;
  }

  .su-header-tools .surtilec-nav-search {
    grid-area: search;
    min-width: 0;
    margin: 0 !important;
    padding: 0 !important;
  }

  .su-header-tools .surtilec-product-search {
    max-width: none !important;
    margin: 0 !important;
  }

  .su-header-tools .surtilec-product-search input[type="search"] {
    min-height: 44px !important;
    padding: 0 0.9rem !important;
    font-size: 0.92rem !important;
  }

  .su-header-tools .surtilec-product-search button {
    flex: 0 0 48px;
    width: 48px;
    min-height: 44px !important;
    padding: 0 !important;
  }

  .su-header-tools .su-header-cta {
    grid-area: cta;
    width: auto !important;
    min-height: 38px !important;
    margin: 0 !important;
    padding: 0.65em 0.85em !important;
    align-self: center;
    justify-content: center;
    font-size: 0.9rem;
    flex: 0 0 auto !important;
  }

  .su-header-tools .su-header-cta svg {
    width: 14px;
    height: 14px;
  }

  .main-navigation .inside-navigation {
    min-height: 42px !important;
    justify-content: center !important;
  }

  .main-navigation .menu-toggle {
    width: auto !important;
    min-height: 42px !important;
    padding: 0 1rem !important;
    font-size: 0.95rem !important;
    line-height: 42px !important;
  }

  .su-hero-inner {
    padding-block: clamp(3rem, 10vh, 5.5rem) !important;
  }

  .su-hero-title {
    font-size: clamp(2rem, 9vw, 2.65rem) !important;
    line-height: 1.04;
    max-width: 13ch;
  }

  .su-hero-sub {
    font-size: 1rem !important;
    line-height: 1.45;
    margin-bottom: 1.35rem;
  }
}

@media (max-width: 380px) {
  .surtilec-utilitybar-inner {
    gap: 0.65rem;
    padding-inline: 0.85rem;
  }

  .surtilec-utilitybar .su-util-phone,
  .surtilec-utilitybar .su-util-wa {
    font-size: 0.74rem;
  }

  .main-title {
    font-size: 1.7rem;
  }

  .su-header-tools .su-header-cta {
    padding-inline: 0.7rem !important;
  }
}
CSS;
}
