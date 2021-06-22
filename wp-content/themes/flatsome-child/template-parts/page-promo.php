<?php
/*
Template name: Promotions
*/
get_header(); ?>

<link href="https://fonts.googleapis.com/css?family=Playfair+Display+SC" rel="stylesheet">
<style type="text/css">
@font-face {
  font-family: 'FuturaBT-Book';
  src: url('/wp-content/themes/flatsome-child/fonts/FuturaBT-Book.eot?#iefix') format('embedded-opentype'),  
  	   url('/wp-content/themes/flatsome-child/fonts/FuturaBT-Book.otf')  format('opentype'),
	   url('/wp-content/themes/flatsome-child/fonts/FuturaBT-Book.woff') format('woff'), 
	   url('/wp-content/themes/flatsome-child/fonts/FuturaBT-Book.ttf')  format('truetype'), 
	   url('/wp-content/themes/flatsome-child/fonts/FuturaBT-Book.svg#FuturaBT-Book') format('svg');
  font-weight: normal;
  font-style: normal;
}


.banner_form h2{color: #ffffff !important; font-family: 'FuturaBT-Book' !important; letter-spacing: 2px;}
.banner_tex_box h2{font-family: 'Playfair Display SC', serif; color: #ffffff !important; font-size: 50px; line-height: 100px;    letter-spacing: 2px; }
.banner_tex_box h2 span{font-family: 'FuturaBT-Book' !important; text-transform: lowercase; font-size: 90px;}
.contentBx {
    text-align: center;
}
.banner_row_section_top h1{
	line-height: 65px;
}
.banner_row_section_top p{
	color: #ffffff;
}
.contentBx span{
	font-family: 'Poppins', sans-serif;
    font-size: 28px;
    font-weight: 700;
    line-height: 32px;
    text-transform: uppercase;
        color: #000000;
        position: relative;
}
.contentBx span:before{
	position: absolute;
    width: 50px;
    height: 2px;
    content: "";
    background-color: #232724;
    left: -70px;
    right: 0px;
    top: 16px;
}

.contentBx span:after{
	position: absolute;
	width: 50px;
	height: 2px;
	content: "";
	background-color:#232724;
    left: 32px;
    top: 16px;
}
.contentBx h2{
	font-weight: 400;
	font-size: 20px;
	padding: 15px 0px 10px 0px;
}

.contentBx p,
.contentBx h2{
	color: #232724;
}
.banner_row_section_top .col-inner label {
    color: #ffffff;
}

section.add_top_padding {
    padding-top: 108px !important;

}
.add_top_padding h2{
	color: #232724;
	text-align: center;
	margin-bottom: 40px;
}
.gallery-col.col {
    padding-bottom: 0px;
}
.gallery_row_slides .row {
    padding: 0 30px;
}

.banner_row_section_top input.wpcf7-form-control.wpcf7-submit{float: right;}
.wpcf7 .wpcf7-response-output {
    margin: 0px 0 0 0;
    border-radius: 6px;
    float: left;
    color: #ffffff;
    padding: 0px 10px;
}
a.button.primary.enquire_now span {
    line-height: 1.4 !important;
}
.wpcf7 .wpcf7-not-valid-tip {
    margin-top: -10px;
    position: relative;
    padding: 2px 8px;
    line-height: 1.2em;
    border-radius: 3px;
    opacity: .8;
    background-color: #f1f1f1;
    color: #b20000;
    font-size: 11px;
    margin-bottom: 4px;
}

@media(max-width: 667px){

.banner_tex_box h2{font-size: 40px; line-height: 65px; }
.banner_tex_box h2 span{font-size: 70px;}

}

</style>

<?php do_action( 'flatsome_before_page' ); ?>

<div id="content" role="main" class="content-area">


	<?php while ( have_posts() ) : the_post(); ?>

				<?php the_content(); ?>
			
			<?php endwhile; // end of the loop. ?>

</div>

<?php do_action( 'flatsome_after_page' ); ?>

<?php get_footer(); ?>
<script type="text/javascript">
    
jQuery(".enquire_now").click(function() {
    jQuery('html, body').animate({
        scrollTop: jQuery("#get_in_touch").offset().top - 200
    }, 2000);
});

</script>
