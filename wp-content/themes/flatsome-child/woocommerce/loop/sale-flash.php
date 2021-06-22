<?php
/**
 * Product loop sale flash
 *
 * @author 		WooThemes
 * @package 	WooCommerce/Templates
 * @version     1.6.4
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

global $post, $product, $wc_cpdf;;

$badge_style = get_theme_mod('bubble_style','style1');

if($badge_style == 'style1') $badge_style = 'circle';
if($badge_style == 'style2') $badge_style = 'square';
if($badge_style == 'style3') $badge_style = 'frame';
?>

<div class="badge-container absolute left top z-1">
<?php if ( $product->is_on_sale() ) : ?>
	<?php
		// Default Sale Bubble Text
		$text = __( 'Sale!', 'woocommerce' );

		// Custom Sale Bubble text
		$custom_text = get_theme_mod('sale_bubble_text');
		if($custom_text){
			$text = $custom_text;
		}

		// Presentage Sale Bubble
		if(get_theme_mod('sale_bubble_percentage')){
			$text = flatsome_presentage_bubble( $product );
		}
	?>
	<?php echo apply_filters( 'woocommerce_sale_flash', '<div class="callout badge badge-'.$badge_style.'"><div class="badge-inner secondary on-sale"><span class="onsale">' .  $text . '</span></div></div>', $post, $product ); ?>

<?php endif; ?>
<?php echo apply_filters( 'flatsome_product_labels', '', $post, $product, $badge_style); ?>
</div>
<?php 
      $onlineExclusive  = get_post_meta( $post->ID, '_onlineExlusivecheckbox', true );
      if($onlineExclusive == 'yes')
        {
?>

<div class="callout large onLinveexclusive loopcase">
            <div class="inner callout-new-bg">
              <div class="inner-text ">
                  <div class="exclusiveBox">
                    <span>Online</span>
                    <span>Exclusive</span>
                  </div>
              </div>
            </div>
</div>


<?php
        }
?>
