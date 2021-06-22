<!DOCTYPE html>
<!--[if IE 9 ]> <html <?php language_attributes(); ?> class="ie9 <?php flatsome_html_classes(); ?>"> <![endif]-->
<!--[if IE 8 ]> <html <?php language_attributes(); ?> class="ie8 <?php flatsome_html_classes(); ?>"> <![endif]-->
<!--[if (gte IE 9)|!(IE)]><!--><html <?php language_attributes(); ?> class="<?php flatsome_html_classes(); ?>"> <!--<![endif]-->
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>" />
	<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" />
	<meta name="robots" content="index, follow">
	<link rel="profile" href="http://gmpg.org/xfn/11" />
	<link rel="pingback" href="<?php bloginfo( 'pingback_url' ); ?>" />
	<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.5.0/css/font-awesome.min.css">
	<link href="https://fonts.googleapis.com/css?family=Over+the+Rainbow|Poppins:100,200,300,400,500,600,700,800,900|Work+Sans:100,300,400,500,600,700" rel="stylesheet">

	<?php wp_head(); ?>
	<link rel='stylesheet' id='flatsome-style-css'  href='/wp-content/themes/flatsome-child/redesign.css?ver=<?php echo rand(); ?>' type='text/css' media='all' />
	<link rel='stylesheet' id='flatsome-style-css'  href='/wp-content/themes/flatsome-child/css/responsive.css?ver=<?php echo rand(); ?>' type='text/css' media='all' />

	<meta name="google-site-verification" content="V7PTYN9G4dxyLOZagGry2I3Utts46jz5gT-joEIeV_A" />

	 <!-- Google Tag Manager -->
<script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
})(window,document,'script','dataLayer','GTM-WNV7GN9');</script>
<!-- End Google Tag Manager -->

<!-- Hotjar Tracking Code for www.keeki.com.au -->
<script>
    (function(h,o,t,j,a,r){
        h.hj=h.hj||function(){(h.hj.q=h.hj.q||[]).push(arguments)};
        h._hjSettings={hjid:1449567,hjsv:6};
        a=o.getElementsByTagName('head')[0];
        r=o.createElement('script');r.async=1;
        r.src=t+h._hjSettings.hjid+j+h._hjSettings.hjsv;
        a.appendChild(r);
    })(window,document,'https://static.hotjar.com/c/hotjar-','.js?sv=');
</script>

</head>


<style type="text/css">
	.promotionBannerActive #wrapper { margin-top: 50px;}
	.topPromotionStripe ul{margin: 0px; float: left;width: 100%}
	.topPromotionStripe li{margin: 0px; list-style: none; display: block; ;  }
	.promotionBannerActive .stuck{margin-top: 50px;}
	 .promotionBannerActive .wide-nav.move_down { top: 140px !important; }
	.promotionBannerActive #masthead.stuck.move_down{margin-top: 50px !important;}
	.topPromotionStripe{height: 50px; line-height: 50px;}
	.topPromotionStripe .owl-dots,
	.topPromotionStripe .owl-carousel .owl-nav,
	.topPromotionStripe .owl-carousel .owl-nav.disabled,
	.topPromotionStripe .owl-carousel .owl-dots.disabled{display: none !important; visibility: hidden !important;}
	@media(max-width: 667px){

		.promotionBannerActive #wrapper,
		body.full-width.promotionBannerActive #wrapper,
		.promotionBannerActive #masthead.stuck.move_down { margin-top: 66px !important;}
		.topPromotionStripe li{padding: 0px 0px;}
		.topPromotionStripe{height: 66px !important; line-height: 66px !important;}
	}

	@media (max-width: 480px){
		.topPromotionStripe {
			    height: auto !important;
			    line-height: 32px !important;
			}
		}
	/* #zip-tagline{display: none;} */
	
</style>
<?php 

	$records = $wpdb->get_row("SELECT * FROM `".$wpdb->prefix."kp_promotion` LIMIT 0, 1");
    if($records){ $extraclass = "promotionBannerActive";}else {$extraclass="";}
    $bgcolor = $records->promotion_bgcolor;
    $txtcolor = $records->promotion_textcolor;
    $startdate = $records->promotion_startdate;
	$enddate = $records->promotion_enddate;

	$startdate1 = $records->promotion_startdate1;
	$enddate1 = $records->promotion_enddate1;

	$startdate2 = $records->promotion_startdate2;
	$enddate2 = $records->promotion_enddate2;

	$proTxt = $records->promotion_text;
	$today = date("Y-m-d");
	$today=date('Y-m-d', strtotime($today));;
	$start_time = date('Y-m-d', strtotime($startdate)); 
	$end_time = date('Y-m-d', strtotime($enddate));

	$start_time1 = date('Y-m-d', strtotime($startdate1)); 
	$end_time1 = date('Y-m-d', strtotime($enddate1));

	$start_time2 = date('Y-m-d', strtotime($startdate2)); 
	$end_time2 = date('Y-m-d', strtotime($enddate2));

    if (($today >= $start_time) && ($today <= $end_time))
    {
      $extraclass = $extraclass;
    }
    else if(($today >= $start_time1) && ($today <= $end_time1))
    {
    	$extraclass = $extraclass;
    }
    else if(($today >= $start_time1) && ($today <= $end_time1))
    {
    		$extraclass = $extraclass;
    }
    else
    {
      $extraclass = ''; 
    }

    $promotion_text = 	$records->promotion_text;
    $promotion_text1 = 	$records->promotion_text1;
    $promotion_text2 = 	$records->promotion_text2;
    $promotion_anchor = $records->promotion_anchor;
    $promotion_anchor1 = $records->promotion_anchor1;
    $promotion_anchor2 = $records->promotion_anchor2;
?>
<script type="text/javascript">
	jQuery( document ).ready(function() { 
		// jQuery('#zip-tagline .text').text('Own it for $53 weekly for 6 months'); 
		
	})
</script>

<body <?php body_class($extraclass); // Body classes is added from inc/helpers-frontend.php ?>>

<!-- Google Tag Manager (noscript) -->
<noscript><iframe src="https://www.googletagmanager.com/ns.html?id=GTM-WNV7GN9"
height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
<!-- End Google Tag Manager (noscript) -->

<!-- Promotion text -->
<?php 
  if (($today >= $start_time) && ($today <= $end_time)) {  ?>
	<link rel="stylesheet" href="<?php echo site_url(); ?>/wp-content/themes/flatsome-child/owl/owl.carousel.css" />
	<link rel="stylesheet" href="<?php echo site_url(); ?>/wp-content/themes/flatsome-child/owl/owl.theme.default.css" />
	<style>
		.topPromotionStripe li a,.topPromotionStripe li{color: #<?php echo $txtcolor; ?>; font-weight: bold;}

	</style>
	<div class="topPromotionStripe" style="background: #<?php echo $bgcolor; ?>; color: #<?php echo $txtcolor; ?>;position: fixed;top: 0px;width: 100%;z-index: 999;     text-align: center;
	   ">
	    	<ul class="owl-carousel">
			
	    	<?php 
	    			if($promotion_text != ''){
	    	?>

	    		<li><a href="<?php echo $promotion_anchor; ?>"><?php echo $promotion_text; ?></a></li>

	    	<?php 

	    			}
	    	?>

	    	<?php 
	    			if( ($promotion_text1 != '') && ($today >= $start_time1) && ($today <= $end_time1)){
	    	?>

	    		<li><a href="<?php echo $promotion_anchor1; ?>"><?php echo $promotion_text1; ?></a></li>

	    	<?php 

	    			}
	    	?>

	    	<?php 
	    			if( ($promotion_text2 != '') && ($today >= $start_time2) && ($today <= $end_time2)){
	    	?>

	    		<li><a href="<?php echo $promotion_anchor2; ?>"><?php echo $promotion_text2; ?></a></li>

	    	<?php 

	    			}
	    	?>

			</ul>
	</div>	
<?php  } ?>
<!-- End of Promotion text -->


<a class="skip-link screen-reader-text" href="#main"><?php esc_html_e( 'Skip to content', 'flatsome' ); ?></a>

<div id="wrapper">

<?php do_action('flatsome_before_header'); ?>

<header id="header" class="header <?php flatsome_header_classes();  ?>">
   <div class="header-wrapper">
	<?php
		get_template_part('template-parts/header/header', 'wrapper');
	?>
   </div><!-- header-wrapper-->
</header>

<?php do_action('flatsome_after_header'); ?>

<main id="main" class="<?php flatsome_main_classes();  ?>">
