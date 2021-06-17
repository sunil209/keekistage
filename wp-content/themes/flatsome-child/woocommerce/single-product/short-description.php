<?php
/**
 * Single product short description
 *
 * @author  Automattic
 * @package WooCommerce/Templates
 * @version 3.3.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

global $post;

$short_description = apply_filters( 'woocommerce_short_description', $post->post_content );

if ( ! $short_description ) {
	return;
}

?>
<div class="product-short-description">
<div itemprop="description"  id="myText" class="short-description" style="overflow:hidden; height:225px;">
	<?php echo $short_description; // WPCS: XSS ok. ?>
</div>
</div>

<div id="more_less">
	<a id="morelink" href="javascript:;" onclick="if(this.innerHTML =='(...more)'){showMore()}else{showLess()}">(...more)</a>
</div>

<script type="text/javascript">

var mydiv = document.getElementById('myText');

function showMore() {
	    mydiv.style.height = mydiv.scrollHeight + "px"; 
	    document.getElementById('morelink').innerHTML = '(...less)';
}

function showLess() {
    mydiv.style.height = "225px"; 
    document.getElementById('morelink').innerHTML = '(...more)';
}

function loadpage() {
	    if (mydiv.scrollHeight > 250 ) {
	    	document.getElementById('morelink').innerHTML = '(...more)';
	    } else {
		document.getElementById('morelink').innerHTML = '';
	} 

}

loadpage();

</script>
