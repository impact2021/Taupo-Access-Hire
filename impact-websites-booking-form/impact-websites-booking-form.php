<?php
/**
 * Plugin Name:       Impact Websites Booking Form
 * Plugin URI:        https://impactwebsites.co.nz/
 * Description:       Combined WooCommerce product-category layout and booking form with multi-product booking list (localStorage), safety confirmation, product designation, spec-sheet download, and optional auto-scrolling multi-image display. Shortcodes: [impact_product_category_layout] and [impact-websites-booking-form].
 * Version:           1.0.0
 * Requires at least: 5.9
 * Requires PHP:      7.4
 * Author:            Impact Websites
 * Author URI:        https://impactwebsites.co.nz/
 * License:           GPL-2.0+
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       impact-websites-booking-form
 *
 * @package ImpactWebsitesBookingForm
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register booking CPT.
 */
add_action( 'init', function () {
	$labels = array(
		'name'           => 'Booking Requests',
		'singular_name'  => 'Booking Request',
		'menu_name'      => 'Booking Requests',
		'name_admin_bar' => 'Booking Request',
		'add_new'        => 'Add New',
		'add_new_item'   => 'Add New Booking Request',
		'new_item'       => 'New Booking Request',
		'edit_item'      => 'Edit Booking Request',
		'view_item'      => 'View Booking Request',
		'all_items'      => 'All Booking Requests',
		'search_items'   => 'Search Booking Requests',
		'not_found'      => 'No booking requests found.',
	);

	$args = array(
		'labels'          => $labels,
		'public'          => false,
		'show_ui'         => true,
		'show_in_menu'    => true,
		'capability_type' => 'post',
		'supports'        => array( 'title', 'editor' ),
		'menu_position'   => 25,
		'menu_icon'       => 'dashicons-clipboard',
	);

	register_post_type( 'impact_booking', $args );
} );

/**
 * Enqueue inline styles and JS (single handler for all features).
 */
add_action( 'wp_enqueue_scripts', function () {
	if ( ! function_exists( 'is_product_category' ) ) {
		return;
	}

	$should_load = false;

	// Load on product category archives.
	if ( is_product_category() ) {
		$should_load = true;
	}

	// Also check if current post content has either shortcode.
	if ( ! $should_load && is_singular() ) {
		$post = get_post();
		if ( $post && (
			has_shortcode( $post->post_content, 'impact_product_category_layout' ) ||
			has_shortcode( $post->post_content, 'impact-websites-booking-form' )
		) ) {
			$should_load = true;
		}
	}

	if ( ! $should_load ) {
		return;
	}

	// Register a blank style handle and add inline CSS.
	wp_register_style( 'impact-product-cat', false );
	wp_enqueue_style( 'impact-product-cat' );

	$custom_css = "
:root{
  --impact-accent:#2d9cdb;
  --impact-accent-contrast:#fff;
  --impact-border:#e6e6e6;
  --impact-bg:#ffffff;
  --impact-muted:#999;
}

/* Product list layout */
.impact-websites-product-list{display:flex;flex-direction:column;gap:1.5rem}
.impact-websites-product{display:flex;gap:1rem;align-items:flex-start;border-bottom:1px solid var(--impact-border);padding-bottom:1.5rem;flex-wrap:nowrap}
.impact-websites-product-image{position:relative;flex:0 0 300px;max-width:300px}
.impact-websites-product-image img{width:100%;height:auto;border-radius:8px;display:block}
.impact-book-now-overlay{display:block;position:absolute;left:0;right:0;bottom:0;padding:10px 8px;background:rgba(0,0,0,.6);color:var(--impact-accent-contrast);text-align:center;font-size:14px;border-bottom-left-radius:8px;border-bottom-right-radius:8px;opacity:0;transition:opacity .25s ease}
.impact-websites-product-image:hover .impact-book-now-overlay{opacity:1}

/* Content column (next to image) */
.impact-websites-product-content{flex:1;min-width:200px;display:flex;flex-direction:column}
.impact-websites-title-row{display:flex;flex-direction:column;gap:4px;flex-wrap:wrap}
.impact-websites-product-title{font-size:1.25rem;margin:0;line-height:1.2}
.impact-websites-designation{color:var(--impact-muted);font-weight:700;font-size:0.85rem;text-transform:uppercase;margin:0}

/* product excerpt - no explicit font-size so it inherits site typography */
.impact-websites-product-excerpt{margin:0.6rem 0 1rem;line-height:1.5}
.impact-websites-product-price{font-weight:700;margin-bottom:0.6rem;font-size:1.1rem}
.impact-websites-product-actions{display:flex;align-items:center;gap:10px;margin-top:auto}

/* Buttons */
.impact-add-to-booking-btn{display:inline-block;background:#f4f4f4;border:1px solid var(--impact-border);padding:8px 12px;border-radius:20px;font-size:14px;cursor:pointer}
.impact-add-to-booking-btn.added{background:var(--impact-accent);color:var(--impact-accent-contrast);border-color:var(--impact-accent)}
.impact-spec-btn{display:inline-block;background:#fff;border:1px solid var(--impact-border);padding:8px 12px;border-radius:20px;font-size:14px;cursor:pointer;color:#333;text-decoration:none}
.impact-spec-btn:hover{background:#f7f9fb;border-color:var(--impact-accent)}

/* Multiscroll container: preserve original image box dimensions, overflow hidden */
.impact-multiscroll{position:relative;display:block;overflow:hidden;border-radius:8px}
.impact-multiscroll .impact-multiscroll-track{display:flex;gap:0;align-items:stretch;transition:transform 0.5s linear}
.impact-multiscroll-slide{flex:0 0 100%;display:block}
.impact-multiscroll-slide img{display:block;width:100%;height:auto}

/* Mini booking bar bottom-right */
.impact-booking-mini{position:fixed;right:18px;bottom:18px;background:var(--impact-bg);border:1px solid var(--impact-border);padding:8px 12px;border-radius:8px;display:flex;gap:10px;align-items:center;box-shadow:0 8px 30px rgba(0,0,0,.12);z-index:99999}
.impact-booking-mini .count{background:var(--impact-accent);color:var(--impact-accent-contrast);padding:6px 8px;border-radius:999px;font-weight:700;font-size:13px}
.impact-booking-mini .items{font-size:14px;color:var(--impact-muted)}
.impact-booking-mini img.thumb{width:36px;height:36px;border-radius:6px;object-fit:cover;border:1px solid var(--impact-border);}

/* Booking form improvements */
.impact-booking-form{max-width:880px;margin:0 auto;background:transparent}
.impact-booking-selected-list{margin-bottom:16px;border:1px solid var(--impact-border);padding:12px;border-radius:8px;background:#fbfcfe}
.impact-booking-selected-item{display:flex;align-items:center;justify-content:space-between;gap:12px;padding:8px 0;border-bottom:1px solid #f0f0f0}
.impact-booking-selected-item:last-child{border-bottom:none}
.impact-booking-selected-item .left{display:flex;gap:12px;align-items:center}
.impact-booking-selected-item img{width:52px;height:52px;object-fit:cover;border-radius:6px;border:1px solid var(--impact-border)}
.impact-booking-selected-item .title{font-weight:600}
.impact-remove-booking-item{background:transparent;border:none;color:#c0392b;cursor:pointer;font-size:13px;padding:6px}

/* Form grid */
.impact-form-grid{display:grid;grid-template-columns:repeat(2,1fr);gap:12px}
.impact-form-grid .full{grid-column:1/-1}

/* Inputs styling */
.impact-booking-form input[type='text'], .impact-booking-form input[type='email'], .impact-booking-form textarea{width:100%;padding:10px;border:1px solid var(--impact-border);border-radius:6px;background:#fff;font-size:14px}
.impact-booking-form label{display:block;margin-bottom:6px;font-weight:600;font-size:13px}
.impact-booking-form .required{color:#c0392b;margin-left:6px;font-weight:700}

/* Safety checkbox */
.impact-safety-checkbox{display:flex;align-items:center;gap:10px;margin:10px 0;font-size:14px}
.impact-safety-checkbox a{color:var(--impact-accent);text-decoration:underline}

/* Primary button */
.impact-booking-submit{background:var(--impact-accent);color:var(--impact-accent-contrast);border:none;padding:12px 18px;border-radius:8px;font-weight:700;cursor:pointer}

/* Responsive */
@media (max-width:900px){
  .impact-websites-product{flex-direction:column;align-items:flex-start}
  .impact-websites-product-image{flex:0 0 auto;max-width:100%}
  .impact-websites-product-content{width:100%}
  .impact-websites-title-row{gap:6px}
}
";

	wp_add_inline_style( 'impact-product-cat', $custom_css );

	// Register script handle and add inline JS (includes multiscroll init).
	wp_register_script( 'impact-product-cat-js', false );
	wp_enqueue_script( 'impact-product-cat-js' );

	$inline_js = "
(function(){
var storageKey = 'impact_booking_list';

function readList(){
	try{ var raw = localStorage.getItem(storageKey); if(!raw) return []; return JSON.parse(raw) || []; }catch(e){ return []; }
}
function writeList(list){ try{ localStorage.setItem(storageKey, JSON.stringify(list)); }catch(e){}; updateMini(); }
function addToList(item){ var list = readList(); if(item.product_id){ var exists = list.some(function(i){ return String(i.product_id) === String(item.product_id); }); if(!exists) list.push(item); } else { var exists = list.some(function(i){ return i.product_name && i.product_name === item.product_name; }); if(!exists) list.push(item); } writeList(list); }
function removeFromList(product_id){ var list = readList(); list = list.filter(function(i){ return String(i.product_id) !== String(product_id); }); writeList(list); }
function clearList(){ localStorage.removeItem(storageKey); updateMini(); }
function updateMini(){ var list = readList(); var mini = document.querySelector('.impact-booking-mini'); if(!mini) return; var count = mini.querySelector('.count'); var label = mini.querySelector('.items'); var thumb = mini.querySelector('img.thumb'); count.textContent = String(list.length); if(list.length === 0){ label.textContent = 'No products'; if(thumb) thumb.style.display = 'none'; } else { label.textContent = list.length === 1 ? (list[0].product_name || '1 product') : (list.length + ' products'); if(thumb){ var first = list[0]; if(first && first.product_thumb){ thumb.src = first.product_thumb; thumb.style.display = ''; } else { thumb.style.display = 'none'; } } } }

// UI handlers
document.addEventListener('click', function(e){
	var el = e.target;
	// Add-to-booking button
	if(el.classList && el.classList.contains('impact-add-to-booking-btn')){
		e.preventDefault();
		var pid = el.getAttribute('data-product-id') || el.dataset.productId || '';
		var pname = el.getAttribute('data-product-name') || el.dataset.productName || el.getAttribute('title') || '';
		var pthumb = el.getAttribute('data-product-thumb') || el.dataset.productThumb || '';
		addToList({ product_id: pid, product_name: pname, product_thumb: pthumb });
		el.classList.add('added');
		el.textContent = 'Added';
		return;
	}

	// Book now overlay or multiscroll anchor - add product then navigate
	var anchor = el.closest && el.closest('a.impact-book-now-link');
	if(anchor && anchor.classList.contains('impact-book-now-link')){
		var pid = anchor.getAttribute('data-product-id') || anchor.dataset.productId || '';
		var pname = anchor.getAttribute('data-product-name') || anchor.dataset.productName || anchor.getAttribute('title') || '';
		var pthumb = anchor.getAttribute('data-product-thumb') || anchor.dataset.productThumb || '';
		addToList({ product_id: pid, product_name: pname, product_thumb: pthumb });
		var href = anchor.getAttribute('href') || '/book-now/';
		window.location.href = href;
		return;
	}

	// Mini bar clear
	if(el.classList && el.classList.contains('impact-booking-clear')){
		e.preventDefault();
		clearList();
		return;
	}

	// Remove item inside booking page (delegated)
	if(el.classList && el.classList.contains('impact-remove-booking-item')){
		e.preventDefault();
		var pid = el.getAttribute('data-product-id') || el.dataset.productId || '';
		removeFromList(pid);
		var row = el.closest('.impact-booking-selected-item');
		if(row) row.parentNode.removeChild(row);
		return;
	}
}, false);

// Mini bar builder
function ensureMiniBar(){
	if(document.querySelector('.impact-booking-mini')) return;
	var mini = document.createElement('div');
	mini.className = 'impact-booking-mini';
	mini.innerHTML = '<img class=\"thumb\" src=\"\" alt=\"\" style=\"display:none\"><div class=\"count\">0</div><div class=\"items\">No products</div><a class=\"go\" href=\"/book-now/\">Book now</a><button class=\"impact-booking-clear\" title=\"Clear\">\u2715</button>';
	document.body.appendChild(mini);
	updateMini();
	var el = document.querySelector('meta[name=\"impact-booking-page-url\"]');
	if(el && el.content){
		var a = mini.querySelector('a.go');
		if(a) a.href = el.content;
	}
}

// Multiscroll init (auto horizontal loop, preserves image box height)
function initMultiscroll(el){
	try{
		var images = el.getAttribute('data-images');
		if(images){
			images = JSON.parse(images);
		} else {
			var imgs = el.querySelectorAll('.impact-multiscroll-slide img');
			if(imgs.length < 2) return;
			images = [];
			imgs.forEach(function(i){ images.push(i.src); });
		}
		if(!Array.isArray(images) || images.length < 2) return;

		var track = el.querySelector('.impact-multiscroll-track');
		if(!track){
			track = document.createElement('div');
			track.className = 'impact-multiscroll-track';
			el.appendChild(track);
		}
		track.innerHTML = '';
		images.forEach(function(src){ var slide = document.createElement('div'); slide.className = 'impact-multiscroll-slide'; var img = document.createElement('img'); img.src = src; img.alt = ''; slide.appendChild(img); track.appendChild(slide); });

		var originalSlides = track.querySelectorAll('.impact-multiscroll-slide');
		originalSlides.forEach(function(s){ track.appendChild(s.cloneNode(true)); });

		var firstImg = track.querySelector('img');
		if(firstImg && firstImg.complete){
			el.style.height = firstImg.getBoundingClientRect().height + 'px';
		} else if(firstImg){
			firstImg.addEventListener('load', function(){ el.style.height = firstImg.getBoundingClientRect().height + 'px'; });
		}

		var idx = 0;
		var totalSlides = track.querySelectorAll('.impact-multiscroll-slide').length;
		var speed = el.dataset.msSpeed ? parseFloat(el.dataset.msSpeed) : 2500;

		function next(){
			idx++;
			track.style.transition = 'transform 600ms linear';
			track.style.transform = 'translateX(' + (-idx * 100) + '%)';
			if(idx >= (totalSlides/2)){
				setTimeout(function(){ track.style.transition = 'none'; idx = 0; track.style.transform = 'translateX(0)'; }, 610);
			}
		}
		var timer = setInterval(next, speed);
		el.addEventListener('mouseenter', function(){ clearInterval(timer); });
		el.addEventListener('mouseleave', function(){ timer = setInterval(next, speed); });

	} catch(e){
		console && console.error && console.error('multiscroll init error', e);
	}
}

document.addEventListener('DOMContentLoaded', function(){
	ensureMiniBar();
	updateMini();
	var els = document.querySelectorAll('.impact-multiscroll');
	els.forEach(function(el){ initMultiscroll(el); });
});
})();
";

	wp_add_inline_script( 'impact-product-cat-js', $inline_js );
}, 20 );

/**
 * Get gallery image URLs from WooCommerce gallery or ACF fields.
 *
 * @param int $product_id Post ID of the product.
 * @return string[]
 */
function impact_websites_get_gallery_urls( $product_id ) {
	$urls = array();

	// WooCommerce gallery (CSV of attachment IDs).
	$gallery_csv = get_post_meta( $product_id, '_product_image_gallery', true );
	if ( $gallery_csv ) {
		$ids = array_filter( array_map( 'absint', explode( ',', $gallery_csv ) ) );
		foreach ( $ids as $aid ) {
			$src = wp_get_attachment_image_url( $aid, 'large' );
			if ( $src ) {
				$urls[] = $src;
			}
		}
	}

	// ACF fallback: gallery or images fields (common names).
	if ( empty( $urls ) && function_exists( 'get_field' ) ) {
		$acf = get_field( 'gallery', $product_id );
		if ( empty( $acf ) ) {
			$acf = get_field( 'images', $product_id );
		}
		if ( $acf && is_array( $acf ) ) {
			foreach ( $acf as $item ) {
				if ( is_array( $item ) && ! empty( $item['url'] ) ) {
					$urls[] = $item['url'];
				} elseif ( is_numeric( $item ) ) {
					$u = wp_get_attachment_image_url( intval( $item ), 'large' );
					if ( $u ) {
						$urls[] = $u;
					}
				} elseif ( is_string( $item ) && filter_var( $item, FILTER_VALIDATE_URL ) ) {
					$urls[] = $item;
				}
			}
		}
	}

	return $urls;
}

/**
 * Render product image area: single image or multiscroll container if multiple images exist.
 *
 * @param int    $product_id  Post ID of the product.
 * @param string $book_target URL of the booking page.
 */
function impact_websites_render_product_image_with_multiscroll( $product_id, $book_target ) {
	$featured_html = get_the_post_thumbnail( $product_id, 'large', array( 'alt' => esc_attr( get_the_title( $product_id ) ) ) );
	$gallery       = impact_websites_get_gallery_urls( $product_id );

	// If we have at least one gallery image, create multiscroll (featured + gallery images).
	if ( is_array( $gallery ) && count( $gallery ) >= 1 ) {
		$images   = array();
		$feat_url = get_the_post_thumbnail_url( $product_id, 'large' );
		if ( $feat_url ) {
			$images[] = $feat_url;
		}
		$take = array_slice( $gallery, 0, 3 );
		foreach ( $take as $g ) {
			$images[] = $g;
		}

		$data = esc_attr( wp_json_encode( $images ) );

		$html  = '<div class="impact-multiscroll" data-images=\'' . $data . '\'>';
		$html .= '<a href="' . esc_url( $book_target ) . '" class="impact-book-now-link" data-product-id="' . esc_attr( $product_id ) . '" data-product-name="' . esc_attr( get_the_title( $product_id ) ) . '" data-product-thumb="' . esc_attr( $feat_url ) . '">';
		$html .= '<div class="impact-multiscroll-track" aria-hidden="true">';
		foreach ( $images as $img ) {
			$html .= '<div class="impact-multiscroll-slide"><img src="' . esc_url( $img ) . '" alt="' . esc_attr( get_the_title( $product_id ) ) . '"></div>';
		}
		$html .= '</div>'; // track
		$html .= '<noscript>' . $featured_html . '</noscript>';
		$html .= '</a>';
		$html .= '</div>';

		echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- all values already escaped above
		return;
	}

	// Fallback to single featured image link.
	printf(
		'<a href="%1$s" class="impact-book-now-link" data-product-id="%2$s" data-product-name="%3$s" data-product-thumb="%4$s" aria-label="Book %3$s">%5$s</a>',
		esc_url( $book_target ),
		esc_attr( $product_id ),
		esc_attr( get_the_title( $product_id ) ),
		esc_attr( get_the_post_thumbnail_url( $product_id, 'thumbnail' ) ),
		$featured_html // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- WP function, safe HTML
	);
}

/**
 * Remove any "Read more" add-to-cart display from WooCommerce loop.
 */
add_filter( 'woocommerce_loop_add_to_cart_link', function ( $html, $product, $args ) {
	if ( stripos( $html, '>Read more<' ) !== false ) {
		return '';
	}
	return $html;
}, 10, 3 );

/**
 * Shortcode: [impact_product_category_layout]
 *
 * @param array $atts Shortcode attributes.
 * @return string
 */
add_shortcode( 'impact_product_category_layout', function ( $atts ) {
	if ( ! function_exists( 'wc_get_product' ) ) {
		return '<p>WooCommerce is not active.</p>';
	}

	$atts = shortcode_atts(
		array(
			'category'       => '',
			'posts_per_page' => -1,
			'orderby'        => 'date',
			'order'          => 'DESC',
			'paginate'       => 'false',
			'paged'          => 0,
			'book_now_page'  => '',
		),
		$atts,
		'impact_product_category_layout'
	);

	if ( empty( $atts['category'] ) && function_exists( 'is_product_category' ) && is_product_category() ) {
		$queried = get_queried_object();
		if ( isset( $queried->slug ) ) {
			$atts['category'] = $queried->slug;
		}
	}

	if ( empty( $atts['category'] ) ) {
		return '<p>No product category specified.</p>';
	}

	$paged = (int) $atts['paged'];
	if ( $paged <= 0 ) {
		if ( filter_var( $atts['paginate'], FILTER_VALIDATE_BOOLEAN ) ) {
			$paged = max( 1, get_query_var( 'paged', get_query_var( 'page', 1 ) ) );
		} else {
			$paged = 1;
		}
	}

	$posts_per_page = intval( $atts['posts_per_page'] );

	$args = array(
		'post_type'      => 'product',
		'posts_per_page' => $posts_per_page,
		'post_status'    => 'publish',
		'orderby'        => sanitize_text_field( $atts['orderby'] ),
		'order'          => sanitize_text_field( $atts['order'] ),
		'tax_query'      => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
			array(
				'taxonomy' => 'product_cat',
				'field'    => 'slug',
				'terms'    => sanitize_text_field( $atts['category'] ),
			),
		),
	);

	if ( filter_var( $atts['paginate'], FILTER_VALIDATE_BOOLEAN ) && $posts_per_page > 0 ) {
		$args['paged'] = $paged;
	} else {
		if ( $posts_per_page <= 0 ) {
			$args['posts_per_page'] = -1;
		}
	}

	$book_now_url = '';
	if ( ! empty( $atts['book_now_page'] ) ) {
		$book_now_url = esc_url( $atts['book_now_page'] );
	} else {
		$found = get_posts(
			array(
				'post_type'   => 'page',
				's'           => '[impact-websites-booking-form',
				'post_status' => 'publish',
				'numberposts' => 1,
			)
		);
		if ( ! empty( $found ) ) {
			$book_now_url = get_permalink( $found[0] );
		}
	}

	$query = new WP_Query( $args );

	ob_start();

	// Expose book-now URL to client via meta tag.
	if ( $book_now_url ) {
		echo '<meta name="impact-booking-page-url" content="' . esc_url( $book_now_url ) . '">';
	}

	if ( $query->have_posts() ) {
		echo '<div class="impact-websites-product-list">';
		while ( $query->have_posts() ) {
			$query->the_post();
			$post_id     = get_the_ID();
			$product_obj = wc_get_product( $post_id );

			echo '<div class="impact-websites-product" data-product-id="' . esc_attr( $post_id ) . '">';

			$book_target = $book_now_url ? esc_url( $book_now_url ) : '/book-now/';

			// Render image area (multiscroll-aware).
			echo '<div class="impact-websites-product-image">';
			impact_websites_render_product_image_with_multiscroll( $post_id, $book_target );
			echo '</div>';

			// Content column.
			echo '<div class="impact-websites-product-content">';

			// Designation: ACF first, then post meta.
			$designation = '';
			if ( function_exists( 'get_field' ) ) {
				$field = get_field( 'designation', $post_id );
				if ( is_string( $field ) || is_numeric( $field ) ) {
					$designation = (string) $field;
				} elseif ( is_array( $field ) ) {
					if ( ! empty( $field['value'] ) ) {
						$designation = $field['value'];
					} elseif ( ! empty( $field['label'] ) ) {
						$designation = $field['label'];
					}
				}
			}
			if ( empty( $designation ) ) {
				$meta_val = get_post_meta( $post_id, 'designation', true );
				if ( $meta_val ) {
					$designation = $meta_val;
				}
			}

			// Title row: title then designation below.
			echo '<div class="impact-websites-title-row">';
			echo '<h2 class="impact-websites-product-title">' . esc_html( get_the_title( $post_id ) ) . '</h2>';
			if ( ! empty( $designation ) ) {
				echo '<div class="impact-websites-designation">' . esc_html( strtoupper( $designation ) ) . '</div>';
			}
			echo '</div>'; // .impact-websites-title-row

			// Description: short or full.
			$short_desc = $product_obj ? $product_obj->get_short_description() : '';
			if ( ! empty( $short_desc ) ) {
				echo '<div class="impact-websites-product-excerpt">' . wp_kses_post( wpautop( $short_desc ) ) . '</div>';
			} else {
				$full_desc = $product_obj ? $product_obj->get_description() : get_post_field( 'post_content', $post_id );
				if ( ! empty( $full_desc ) ) {
					$full_desc = apply_filters( 'the_content', $full_desc );
					echo '<div class="impact-websites-product-excerpt">' . wp_kses_post( $full_desc ) . '</div>';
				} else {
					echo '<div class="impact-websites-product-excerpt"></div>';
				}
			}

			// Price.
			$price_html = $product_obj ? $product_obj->get_price_html() : '';
			if ( $price_html ) {
				echo '<div class="impact-websites-product-price">' . wp_kses_post( $price_html ) . '</div>';
			}

			// Actions.
			echo '<div class="impact-websites-product-actions">';

			// Add to booking.
			echo '<button type="button" class="impact-add-to-booking-btn" data-product-id="' . esc_attr( $post_id ) . '" data-product-name="' . esc_attr( get_the_title( $post_id ) ) . '" data-product-thumb="' . esc_attr( get_the_post_thumbnail_url( $post_id, 'thumbnail' ) ) . '">Add to booking</button>';

			// Spec file: ACF first, then post meta.
			$spec_url = '';
			if ( function_exists( 'get_field' ) ) {
				$spec_field = get_field( 'download_the_spec_sheet', $post_id );
				if ( $spec_field ) {
					if ( is_array( $spec_field ) && ! empty( $spec_field['url'] ) ) {
						$spec_url = $spec_field['url'];
					} elseif ( is_numeric( $spec_field ) ) {
						$spec_url = wp_get_attachment_url( intval( $spec_field ) );
					} elseif ( is_string( $spec_field ) ) {
						$spec_url = $spec_field;
					}
				}
			}
			if ( empty( $spec_url ) ) {
				$meta_val = get_post_meta( $post_id, 'download_the_spec_sheet', true );
				if ( $meta_val ) {
					if ( is_numeric( $meta_val ) ) {
						$spec_url = wp_get_attachment_url( intval( $meta_val ) );
					} else {
						$spec_url = $meta_val;
					}
				}
			}
			if ( ! empty( $spec_url ) ) {
				echo '<a class="impact-spec-btn" href="' . esc_url( $spec_url ) . '" target="_blank" rel="noopener noreferrer" aria-label="Download the spec sheet for ' . esc_attr( get_the_title( $post_id ) ) . '">Download the spec sheet</a>';
			}

			// Add to cart (WooCommerce loop template).
			echo '<span class="impact-add-to-cart">';
			global $product;
			$previous_product = isset( $product ) ? $product : null;
			$product          = $product_obj; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- required to pass product to template
			if ( function_exists( 'woocommerce_template_loop_add_to_cart' ) ) {
				woocommerce_template_loop_add_to_cart();
			}
			$product = $previous_product; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
			echo '</span>';

			echo '</div>'; // actions
			echo '</div>'; // content
			echo '</div>'; // product
		}
		echo '</div>'; // list

		// Pagination.
		if ( filter_var( $atts['paginate'], FILTER_VALIDATE_BOOLEAN ) && $query->max_num_pages > 1 ) {
			$big          = 999999999;
			$current_page = max( 1, $paged );
			$links        = paginate_links(
				array(
					'base'      => str_replace( $big, '%#%', esc_url( get_pagenum_link( $big ) ) ),
					'format'    => '?paged=%#%',
					'current'   => $current_page,
					'total'     => $query->max_num_pages,
					'type'      => 'list',
					'prev_text' => '&laquo;',
					'next_text' => '&raquo;',
				)
			);
			if ( $links ) {
				echo '<nav class="impact-websites-product-pagination" aria-label="Product category pagination">' . $links . '</nav>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- paginate_links() returns safe HTML
			}
		}
	} else {
		echo '<p>No products found in this category.</p>';
	}

	wp_reset_postdata();

	return ob_get_clean();
} );

/**
 * Shortcode: [impact-websites-booking-form]
 *
 * @param array $atts Shortcode attributes.
 * @return string
 */
add_shortcode( 'impact-websites-booking-form', 'impact_websites_booking_form_shortcode' );
function impact_websites_booking_form_shortcode( $atts = array() ) {
	$atts = shortcode_atts(
		array( 'submit_text' => 'Send Booking Request' ),
		$atts,
		'impact-websites-booking-form'
	);

	ob_start();

	if ( isset( $_GET['impact_booking'] ) && 'success' === $_GET['impact_booking'] ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		echo '<div class="impact-booking-success" style="padding:12px;border-left:4px solid #2ecc71;background:#f1fbf6;margin-bottom:20px;">Thank you &mdash; your booking request has been sent. We will contact you shortly.</div>';
		echo '<script>try{ localStorage.removeItem("impact_booking_list"); }catch(e){};</script>';
	} elseif ( isset( $_GET['impact_booking'] ) && 'error' === $_GET['impact_booking'] ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		echo '<div class="impact-booking-error" style="padding:12px;border-left:4px solid #e74c3c;background:#fff4f4;margin-bottom:20px;">There was an error sending your request. Please try again or contact us directly.</div>';
	} elseif ( isset( $_GET['impact_booking'] ) && 'error_safety' === $_GET['impact_booking'] ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		echo '<div class="impact-booking-error" style="padding:12px;border-left:4px solid #e74c3c;background:#fff4f4;margin-bottom:20px;">Please confirm you have read the safety sheet before submitting the booking request.</div>';
	}

	echo '<div class="impact-websites-booking-standalone">';
	echo impact_websites_render_booking_form_html( $atts['submit_text'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- function returns safe HTML
	?>
	<script>
	(function(){
		var storageKey = 'impact_booking_list';
		function readList(){ try{ var raw = localStorage.getItem(storageKey); if(!raw) return []; return JSON.parse(raw) || []; }catch(e){ return []; } }
		function writeList(list){ try{ localStorage.setItem(storageKey, JSON.stringify(list)); }catch(e){} }
		function renderSelectedList(){
			var list = readList();
			var container = document.querySelector('.impact-booking-selected-list');
			if(!container) return;
			container.innerHTML = '';
			if(list.length === 0){
				container.innerHTML = '<div>No products selected yet.</div>';
			} else {
				list.forEach(function(item){
					var row = document.createElement('div');
					row.className = 'impact-booking-selected-item';
					var left = document.createElement('div'); left.className = 'left';
					if(item.product_thumb){
						var img = document.createElement('img');
						img.src = item.product_thumb;
						img.alt = item.product_name || '';
						left.appendChild(img);
					}
					var title = document.createElement('div');
					title.className = 'title';
					title.textContent = item.product_name || '(no name)';
					left.appendChild(title);
					row.appendChild(left);
					var right = document.createElement('div');
					var remove = document.createElement('button');
					remove.type = 'button';
					remove.className = 'impact-remove-booking-item';
					remove.setAttribute('data-product-id', item.product_id || '');
					remove.textContent = 'Remove';
					right.appendChild(remove);
					row.appendChild(right);
					container.appendChild(row);
				});
			}
			renderHiddenInputs();
		}
		function renderHiddenInputs(){
			var form = document.querySelector('.impact-booking-form');
			if(!form) return;
			var existing = form.querySelector('.impact-booking-hidden-inputs');
			if(existing) existing.parentNode.removeChild(existing);
			var list = readList();
			var wrapper = document.createElement('div');
			wrapper.className = 'impact-booking-hidden-inputs';
			list.forEach(function(item){
				var pi = document.createElement('input');
				pi.type = 'hidden'; pi.name = 'product_ids[]'; pi.value = item.product_id || ''; wrapper.appendChild(pi);
				var pn = document.createElement('input');
				pn.type = 'hidden'; pn.name = 'product_names[]'; pn.value = item.product_name || ''; wrapper.appendChild(pn);
			});
			form.appendChild(wrapper);
		}
		document.addEventListener('click', function(e){
			var t = e.target;
			if(t.classList && t.classList.contains('impact-remove-booking-item')){
				var pid = t.getAttribute('data-product-id') || '';
				var list = readList();
				list = list.filter(function(i){ return String(i.product_id) !== String(pid); });
				writeList(list);
				renderSelectedList();
			}
		}, false);
		function addFromQuery(){
			try{
				var params = new URLSearchParams(location.search);
				var pid = params.get('product_id'); var pname = params.get('product_name');
				if(pid || pname){
					var list = readList();
					var exists = list.some(function(i){ return String(i.product_id) === String(pid); });
					if(!exists){ list.push({ product_id: pid || '', product_name: pname || '' }); writeList(list); }
				}
			}catch(e){}
		}
		document.addEventListener('DOMContentLoaded', function(){ addFromQuery(); renderSelectedList(); });
	})();
	</script>
	<?php
	echo '</div>';
	return ob_get_clean();
}

/**
 * Render booking form HTML fragment.
 *
 * @param string $submit_text Label for the submit button.
 * @return string
 */
function impact_websites_render_booking_form_html( $submit_text = 'Send Booking Request' ) {
	$safety_url = get_option( 'impact_booking_safety_sheet_url', 'https://impactwebsitesdevelopment.co.nz/project723ksl/wp-content/uploads/2025/10/TAH-Safety-Sheet.pdf' );
	ob_start();
	?>
	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="impact-booking-form" novalidate>
		<?php wp_nonce_field( 'impact_websites_booking_form_action', 'impact_websites_booking_form_nonce' ); ?>
		<input type="hidden" name="action" value="impact_websites_booking_form_submit">
		<div class="impact-booking-selected-list" aria-live="polite">
			<?php
			// phpcs:disable WordPress.Security.NonceVerification.Recommended
			if ( isset( $_GET['product_id'] ) || isset( $_GET['product_name'] ) ) {
				$prefill_pid = isset( $_GET['product_id'] ) ? esc_attr( absint( $_GET['product_id'] ) ) : '';
				$prefill_pn  = isset( $_GET['product_name'] ) ? esc_attr( sanitize_text_field( wp_unslash( $_GET['product_name'] ) ) ) : '';
				if ( $prefill_pid || $prefill_pn ) {
					if ( $prefill_pid ) {
						echo '<input type="hidden" name="product_ids[]" value="' . $prefill_pid . '">';
					}
					if ( $prefill_pn ) {
						echo '<input type="hidden" name="product_names[]" value="' . $prefill_pn . '">';
					}
					echo '<div>' . ( $prefill_pn ? esc_html( $prefill_pn ) : 'Product ID ' . $prefill_pid ) . '</div>';
				}
			} else {
				echo '<div>No products selected yet.</div>';
			}
			// phpcs:enable WordPress.Security.NonceVerification.Recommended
			?>
		</div>

		<label class="full">Add / edit details</label>
		<div class="impact-form-grid">
			<div>
				<label>First name <span class="required">*</span></label>
				<input type="text" name="first_name" required>
			</div>
			<div>
				<label>Last name <span class="required">*</span></label>
				<input type="text" name="last_name" required>
			</div>

			<div>
				<label>Email <span class="required">*</span></label>
				<input type="email" name="email" required>
			</div>
			<div>
				<label>Contact number <span class="required">*</span></label>
				<input type="text" name="contact_number" required>
			</div>

			<div class="full">
				<label>Company (optional)</label>
				<input type="text" name="company">
			</div>

			<div class="full">
				<label>Any other details / Message</label>
				<textarea name="details" rows="6"></textarea>
			</div>

			<div class="full">
				<div class="impact-safety-checkbox">
					<input type="checkbox" id="confirm_safety_sheet" name="confirm_safety_sheet" value="1" required>
					<label for="confirm_safety_sheet">I confirm I have read the <a href="<?php echo esc_url( $safety_url ); ?>" target="_blank" rel="noopener noreferrer">safety sheet</a>.</label>
				</div>
			</div>

			<div class="full">
				<button type="submit" class="impact-booking-submit"><?php echo esc_html( $submit_text ); ?></button>
			</div>
		</div>
	</form>
	<?php
	return ob_get_clean();
}

/**
 * Form submission handler (both logged-in and guest users).
 */
add_action( 'admin_post_nopriv_impact_websites_booking_form_submit', 'impact_websites_handle_booking_form' );
add_action( 'admin_post_impact_websites_booking_form_submit', 'impact_websites_handle_booking_form' );

function impact_websites_handle_booking_form() {
	// Nonce verification.
	if ( empty( $_POST['impact_websites_booking_form_nonce'] ) ||
		! wp_verify_nonce( wp_unslash( $_POST['impact_websites_booking_form_nonce'] ), 'impact_websites_booking_form_action' ) ) {
		wp_safe_redirect( add_query_arg( 'impact_booking', 'error', wp_get_referer() ?: site_url() ) );
		exit;
	}

	// Enforce safety checkbox.
	if ( empty( $_POST['confirm_safety_sheet'] ) || '1' !== (string) $_POST['confirm_safety_sheet'] ) {
		$redirect_to = wp_get_referer() ? wp_get_referer() : home_url();
		wp_safe_redirect( add_query_arg( 'impact_booking', 'error_safety', $redirect_to ) );
		exit;
	}

	$product_ids_raw   = isset( $_POST['product_ids'] ) ? wp_unslash( $_POST['product_ids'] ) : array();
	$product_names_raw = isset( $_POST['product_names'] ) ? wp_unslash( $_POST['product_names'] ) : array();

	$product_ids   = is_array( $product_ids_raw )
		? array_map( 'absint', $product_ids_raw )
		: ( $product_ids_raw ? array( absint( $product_ids_raw ) ) : array() );
	$product_names = is_array( $product_names_raw )
		? array_map( 'sanitize_text_field', $product_names_raw )
		: ( $product_names_raw ? array( sanitize_text_field( $product_names_raw ) ) : array() );

	$product_names = array_values( array_filter( array_unique( array_map( 'trim', $product_names ) ) ) );
	$product_ids   = array_values( array_unique( $product_ids ) );

	$first_name = isset( $_POST['first_name'] ) ? sanitize_text_field( wp_unslash( $_POST['first_name'] ) ) : '';
	$last_name  = isset( $_POST['last_name'] ) ? sanitize_text_field( wp_unslash( $_POST['last_name'] ) ) : '';
	$email      = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
	$company    = isset( $_POST['company'] ) ? sanitize_text_field( wp_unslash( $_POST['company'] ) ) : '';
	$contact    = isset( $_POST['contact_number'] ) ? sanitize_text_field( wp_unslash( $_POST['contact_number'] ) ) : '';
	$details    = isset( $_POST['details'] ) ? wp_kses_post( wp_unslash( $_POST['details'] ) ) : '';

	// Required field validation.
	if ( empty( $first_name ) || empty( $last_name ) || empty( $email ) || empty( $contact ) ) {
		wp_safe_redirect( add_query_arg( 'impact_booking', 'error', wp_get_referer() ?: site_url() ) );
		exit;
	}

	if ( ! is_email( $email ) ) {
		wp_safe_redirect( add_query_arg( 'impact_booking', 'error', wp_get_referer() ?: site_url() ) );
		exit;
	}

	// Resolve product names from IDs if names are missing.
	if ( empty( $product_names ) && ! empty( $product_ids ) ) {
		foreach ( $product_ids as $pid ) {
			$p = get_post( $pid );
			if ( $p ) {
				$product_names[] = $p->post_title;
			}
		}
	}

	$post_title = ! empty( $product_names )
		? implode( ', ', $product_names )
		: ( ! empty( $product_ids ) ? 'Products: ' . implode( ', ', $product_ids ) : 'Booking Request' );

	$post_args = array(
		'post_title'   => wp_strip_all_tags( $post_title ),
		'post_content' => wp_strip_all_tags( $details ),
		'post_status'  => 'private',
		'post_type'    => 'impact_booking',
	);

	$post_id = wp_insert_post( $post_args );

	if ( ! is_wp_error( $post_id ) && $post_id ) {
		update_post_meta( $post_id, '_impact_product_ids', $product_ids );
		update_post_meta( $post_id, '_impact_product_names', $product_names );
		update_post_meta( $post_id, '_impact_first_name', $first_name );
		update_post_meta( $post_id, '_impact_last_name', $last_name );
		update_post_meta( $post_id, '_impact_email', $email );
		update_post_meta( $post_id, '_impact_company', $company );
		update_post_meta( $post_id, '_impact_contact', $contact );
		update_post_meta( $post_id, '_impact_details', $details );
		update_post_meta( $post_id, '_impact_submitted_at', current_time( 'mysql' ) );
		update_post_meta( $post_id, '_impact_confirmed_safety_sheet', 1 );
	}

	// Email notification.
	$saved_emails = get_option( 'impact_booking_recipient_emails', '' );
	if ( ! empty( $saved_emails ) ) {
		// Stored as a newline-separated list; flatten to a comma-separated string for wp_mail.
		$recipients = implode( ',', array_filter( array_map( 'trim', explode( "\n", $saved_emails ) ) ) );
	} else {
		$recipients = get_option( 'admin_email' );
	}
	$admin_email = apply_filters( 'impact_booking_email_recipient', $recipients );
	$subject     = sprintf( 'New booking request: %s', $post_title );
	$body_lines  = array(
		'Products: ' . ( ! empty( $product_names ) ? implode( ', ', $product_names ) : ( ! empty( $product_ids ) ? implode( ', ', $product_ids ) : '—' ) ),
		'Product IDs: ' . ( ! empty( $product_ids ) ? implode( ', ', $product_ids ) : '—' ),
		'Name: ' . $first_name . ' ' . $last_name,
		'Email: ' . $email,
		'Company: ' . ( $company ? $company : '—' ),
		'Contact number: ' . $contact,
		"Details:\n" . ( $details ? $details : '—' ),
		'Confirmed safety sheet: Yes',
		'Submitted from: ' . ( isset( $_SERVER['HTTP_REFERER'] ) ? esc_url_raw( wp_unslash( $_SERVER['HTTP_REFERER'] ) ) : 'direct' ),
	);
	$body = implode( "\n\n", $body_lines );

	$headers   = array( 'Content-Type: text/plain; charset=UTF-8' );
	$headers[] = 'Reply-To: ' . $first_name . ' ' . $last_name . ' <' . $email . '>';

	wp_mail( $admin_email, $subject, $body, $headers );

	$redirect_to = wp_get_referer() ? wp_get_referer() : home_url();
	wp_safe_redirect( add_query_arg( 'impact_booking', 'success', $redirect_to ) );
	exit;
}

/**
 * Build the URL to the booking page, optionally with a product pre-selected.
 *
 * @param int    $product_id Post ID of the product to pre-select (0 = none).
 * @param string $page_url   Override the booking page URL.
 * @return string
 */
if ( ! function_exists( 'impact_websites_get_book_now_url' ) ) {
	function impact_websites_get_book_now_url( $product_id = 0, $page_url = '' ) {
		if ( ! empty( $page_url ) ) {
			$base = $page_url;
		} else {
			$found = get_posts(
				array(
					'post_type'   => 'page',
					's'           => '[impact-websites-booking-form',
					'post_status' => 'publish',
					'numberposts' => 1,
				)
			);
			if ( ! empty( $found ) ) {
				$base = get_permalink( $found[0] );
			} else {
				$base = home_url( '/book-now/' );
			}
		}

		if ( $product_id ) {
			return add_query_arg( 'product_id', absint( $product_id ), $base );
		}
		return $base;
	}
}

/**
 * Backwards-compatible alias for impact_websites_get_book_now_url().
 *
 * @param int    $product_id Post ID of the product to pre-select.
 * @param string $page_url   Override the booking page URL.
 * @return string
 */
if ( ! function_exists( 'impact_get_book_now_url' ) ) {
	function impact_get_book_now_url( $product_id = 0, $page_url = '' ) {
		return impact_websites_get_book_now_url( $product_id, $page_url );
	}
}

// ---------------------------------------------------------------------------
// Admin menu & settings page
// ---------------------------------------------------------------------------

/**
 * Register the top-level admin menu and the Settings sub-page.
 */
add_action( 'admin_menu', function () {
	// Top-level menu — points directly to the settings page.
	add_menu_page(
		__( 'Booking Form', 'impact-websites-booking-form' ),
		__( 'Booking Form', 'impact-websites-booking-form' ),
		'manage_options',
		'impact-booking-settings',
		'impact_websites_render_settings_page',
		'dashicons-clipboard',
		26
	);

	// Rename the auto-created sub-menu so it reads "Settings" rather than repeating the top-level title.
	add_submenu_page(
		'impact-booking-settings',
		__( 'Booking Form Settings', 'impact-websites-booking-form' ),
		__( 'Settings', 'impact-websites-booking-form' ),
		'manage_options',
		'impact-booking-settings',
		'impact_websites_render_settings_page'
	);

	// Quick link to the Booking Requests CPT list.
	add_submenu_page(
		'impact-booking-settings',
		__( 'Booking Requests', 'impact-websites-booking-form' ),
		__( 'Booking Requests', 'impact-websites-booking-form' ),
		'manage_options',
		'edit.php?post_type=impact_booking'
	);
} );

/**
 * Register the plugin settings.
 */
add_action( 'admin_init', function () {
	register_setting(
		'impact_booking_settings_group',
		'impact_booking_recipient_emails',
		array(
			'type'              => 'string',
			'sanitize_callback' => 'impact_websites_sanitize_recipient_emails',
			'default'           => '',
		)
	);

	register_setting(
		'impact_booking_settings_group',
		'impact_booking_safety_sheet_url',
		array(
			'type'              => 'string',
			'sanitize_callback' => 'esc_url_raw',
			'default'           => '',
		)
	);

	add_settings_section(
		'impact_booking_main_section',
		__( 'Email Notifications', 'impact-websites-booking-form' ),
		'__return_false',
		'impact-booking-settings'
	);

	add_settings_field(
		'impact_booking_recipient_emails',
		__( 'Recipient email addresses', 'impact-websites-booking-form' ),
		'impact_websites_render_recipient_emails_field',
		'impact-booking-settings',
		'impact_booking_main_section'
	);

	add_settings_section(
		'impact_booking_form_section',
		__( 'Booking Form', 'impact-websites-booking-form' ),
		'__return_false',
		'impact-booking-settings'
	);

	add_settings_field(
		'impact_booking_safety_sheet_url',
		__( 'Safety sheet URL', 'impact-websites-booking-form' ),
		'impact_websites_render_safety_sheet_url_field',
		'impact-booking-settings',
		'impact_booking_form_section'
	);
} );

/**
 * Sanitize the recipient emails option: one valid email per line.
 *
 * @param string $raw Raw textarea value.
 * @return string
 */
function impact_websites_sanitize_recipient_emails( $raw ) {
	$lines  = explode( "\n", (string) $raw );
	$clean  = array();
	foreach ( $lines as $line ) {
		$line = sanitize_email( trim( $line ) );
		if ( is_email( $line ) ) {
			$clean[] = $line;
		}
	}
	return implode( "\n", $clean );
}

/**
 * Render the recipient emails textarea field.
 */
function impact_websites_render_recipient_emails_field() {
	$value = get_option( 'impact_booking_recipient_emails', '' );
	?>
	<textarea
		id="impact_booking_recipient_emails"
		name="impact_booking_recipient_emails"
		rows="5"
		cols="50"
		class="large-text"
		placeholder="<?php esc_attr_e( 'One email address per line', 'impact-websites-booking-form' ); ?>"
	><?php echo esc_textarea( $value ); ?></textarea>
	<p class="description">
		<?php esc_html_e( 'Enter one email address per line. Every address listed here will receive a copy of each new booking request. Leave blank to fall back to the site admin email.', 'impact-websites-booking-form' ); ?>
	</p>
	<?php
}

/**
 * Render the safety sheet URL field.
 */
function impact_websites_render_safety_sheet_url_field() {
	$value = get_option( 'impact_booking_safety_sheet_url', '' );
	?>
	<input
		id="impact_booking_safety_sheet_url"
		name="impact_booking_safety_sheet_url"
		type="url"
		value="<?php echo esc_attr( $value ); ?>"
		class="regular-text"
		placeholder="https://example.com/safety-sheet.pdf"
	>
	<p class="description">
		<?php esc_html_e( 'URL to the safety sheet PDF shown on the booking form. Leave blank to use the built-in default.', 'impact-websites-booking-form' ); ?>
	</p>
	<?php
}

/**
 * Render the settings page.
 */
function impact_websites_render_settings_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	?>
	<div class="wrap">
		<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
		<form method="post" action="options.php">
			<?php
			settings_fields( 'impact_booking_settings_group' );
			do_settings_sections( 'impact-booking-settings' );
			submit_button( __( 'Save Settings', 'impact-websites-booking-form' ) );
			?>
		</form>
	</div>
	<?php
}
