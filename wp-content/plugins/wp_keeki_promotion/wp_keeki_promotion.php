<?php
/*
*Plugin Name: KeekiPromotion
*Plugin URI: http://webdesignmarket.com.au/
*Description: It allow admin to Insert Keeki Promotion Data.
*Version: 1.0
*Author: WDM Visit us at http://webdesignmarket.com.au/ 
*/

$users_db_version = "3.0";

function wp_keeki_promotion_install(){
    global $wpdb;
		$kp_promotion_table = 'CREATE TABLE IF NOT EXISTS `'.$wpdb->prefix.'kp_promotion` (
						  `id` int(50) NOT NULL AUTO_INCREMENT,
						  `promotion_title` varchar(250) NOT NULL,
                          `promotion_startdate` varchar(250) NOT NULL,
                          `promotion_enddate` varchar(250) NOT NULL,
                          `promotion_startdate1` varchar(250) NOT NULL,
                          `promotion_enddate1` varchar(250) NOT NULL,
                          `promotion_startdate2` varchar(250) NOT NULL,
                          `promotion_enddate2` varchar(250) NOT NULL,
                          `promotion_bgcolor` varchar(250) NOT NULL,
                          `promotion_textcolor` varchar(250) NOT NULL,
                          `promotion_text` varchar(250) NOT NULL,
                          `promotion_text1` varchar(250) NOT NULL,
                          `promotion_text2` varchar(250) NOT NULL,
                          `promotion_anchor` varchar(250) NOT NULL,
                          `promotion_anchor1` varchar(250) NOT NULL,
                          `promotion_anchor2` varchar(250) NOT NULL,
						  `promotion_status` varchar(50) NOT NULL,
						  PRIMARY KEY (`id`)
						) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;';

		
		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
		dbDelta($kp_promotion_table);

}

function wp_keeki_promotion_uninstall(){
		global $wpdb;
		$delete_kp_promotion_table = 'DROP TABLE IF EXISTS `'.$wpdb->prefix.'kp_promotion`';
		$wpdb->query(
			$wpdb->prepare($delete_kp_promotion_table)
		);
		
}

// call when plugin is activated by admin
register_activation_hook(__FILE__, 'wp_keeki_promotion_install');

//call when plugin is deactivated
register_deactivation_hook(__FILE__, 'wp_keeki_promotion_uninstall');

//add administrative menu 
add_action('admin_menu', 'wp_keeki_promotion_admin_menu');

function wp_keeki_promotion_admin_menu(){
	global $current_user, $wpdb;
	$current_user = wp_get_current_user();
    $role = $current_user->roles;   
	if( $role[0] == "administrator"){
        
         add_menu_page('All | KeekiPromotion', 'Keeki Promotion', 'manage_options' ,'lexxo-managment', 'wp_keeki_promotion_results', plugins_url('images/user-group-new.png', __FILE__), 90);

            add_menu_page('All | KeekiPromotion', 'Update', 'manage_options' ,'kpromotion', 'wp_keeki_promotion_results', plugins_url('images/user-group-new.png', __FILE__), 90);

	}
}



function wp_keeki_promotion_results(){
	require_once("new_promotion.php");
}



function wp_wdm_user_meta($key){
	if(isset($_REQUEST['eid'])){ 
		$meta = get_user_meta($_REQUEST['eid'], $key);
		if($meta[0] != null) {return $meta[0];}
		else{return $meta[1];}
	}
	else{
		return false;
	}
}

function promotion_form()
	{
		global $wpdb;

		$records = $wpdb->get_row("SELECT * FROM `".$wpdb->prefix."kp_promotion` LIMIT 0, 1");
		$getpomotionTitle = ($records->promotion_title != '') ? $records->promotion_title : '';
		$getStartdate = ($records->promotion_startdate != '') ? $records->promotion_startdate : '';
		$getEnddate = ($records->promotion_enddate != '') ? $records->promotion_enddate : ''; 
		$getBgcolor = ($records->promotion_bgcolor != '') ? $records->promotion_bgcolor : ''; 
		$getTextcolor = ($records->promotion_textcolor != '') ? $records->promotion_textcolor : ''; 
		$getPromotionTxt = ($records->promotion_text != '') ? $records->promotion_text : ''; 
		$Status = ($records->promotion_status != '') ? $records->promotion_status : '';

		echo '<div class="promotionFrontBox"> Title "'.$getpomotionTitle.'"</div>'; 


	}
add_shortcode('promotion', 'promotion_form');


function wp_keeki_head(){
	echo '<link rel="stylesheet" href="//code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">
  			<link rel="stylesheet" href="/resources/demos/style.css">
  		
  			<script src="https://code.jquery.com/ui/1.12.1/jquery-ui.js"></script>';
    echo "<link rel='stylesheet' href='".plugins_url( 'css/customer.css' , __FILE__ )."' type='text/css' media='all' />";
    echo "<script src='".plugins_url( 'js/jscolor.js' , __FILE__ )."'></script>";
    echo '<script>
			  jQuery( function() {
			    jQuery( "#start_date, #end_date, #start_date1, #end_date1, #start_date2, #end_date2" ).datepicker();
			  } );
			  </script>';
}
add_action('admin_head', 'wp_keeki_head');

?>