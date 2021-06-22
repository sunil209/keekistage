<?php 

global $wpdb;
$err_msg = "";

if(isset($_POST['createpromotion'])){
	$title	        =	"";
	$start_date		=	"";
	$end_date		=	"";
	$start_date1	=	"";
	$end_date1		=	"";
	$start_date2	=	"";
	$end_date2		=	"";
	$color 			=	"";
	$txtcolor       =   "";
	$content		=	"";
	$content1		=	"";
	$content2		=	"";
	$promotion_anchor = "";
	$promotion_anchor1 = "";
	$promotion_anchor2 = "";
	
	if(isset($_POST['promotion_name'])){
		$title = $_POST['promotion_name'];
	}
	if(isset($_POST['start_date'])){
		$start_date = $_POST['start_date'];
		//date("Y-m-d", strtotime($_POST['start_date']))." ".date("H:i:s");
	}
	if(isset($_POST['end_date'])){
		$end_date = $_POST['end_date'];
		//date("Y-m-d", strtotime($_POST['end_date']))." ".date("H:i:s");
	}

	if(isset($_POST['start_date1'])){
		$start_date1 = $_POST['start_date1'];
	}
	if(isset($_POST['end_date1'])){
		$end_date1 = $_POST['end_date1'];
	}

	if(isset($_POST['start_date2'])){
		$start_date2 = $_POST['start_date2'];
	}
	if(isset($_POST['end_date2'])){
		$end_date2 = $_POST['end_date2'];
	}



	if(isset($_POST['color'])){
		$color = $_POST['color'];
	}
	if(isset($_POST['bgcolor'])){
		$txtcolor = $_POST['bgcolor'];
	}
	if(isset($_POST['promotionText'])){
		$content = $_POST['promotionText'];
	}
	if(isset($_POST['promotionText1'])){
		$content1 = $_POST['promotionText1'];
	}
	if(isset($_POST['promotionText2'])){
		$content2 = $_POST['promotionText2'];
	}


	if(isset($_POST['promotion_anchor'])){
		$promotion_anchor = $_POST['promotion_anchor'];
	}
	if(isset($_POST['promotion_anchor1'])){
		$promotion_anchor1 = $_POST['promotion_anchor1'];
	}
	if(isset($_POST['promotion_anchor2'])){
		$promotion_anchor2 = $_POST['promotion_anchor2'];
	}

	$check = $wpdb->insert( 
				$wpdb->prefix.'kp_promotion', 
				array( 
				  'promotion_title'		=> $title,
				  'promotion_startdate'	=> $start_date,
				  'promotion_enddate'	=> $end_date,
				  'promotion_startdate1'=> $start_date1,
				  'promotion_enddate1'	=> $end_date1,
				  'promotion_startdate2'=> $start_date2,
				  'promotion_enddate2'	=> $end_date2,
				  'promotion_bgcolor'   => $color,
				  'promotion_textcolor'	=> $txtcolor,
				  'promotion_text'		=> $content,
				  'promotion_text1'		=> $content1,
				  'promotion_text2'		=> $content2,
				  'promotion_anchor'    => $promotion_anchor,
				  'promotion_anchor1'   => $promotion_anchor1,
				  'promotion_anchor2'   => $promotion_anchor2,
				  'promotion_status'	=> 1
				), 
				array( 
					'%s', 
					'%s', 
					'%s',
					'%s',
					'%s',
					'%s',
					'%s',
					'%s',
					'%s',
					'%s',
					'%s',
					'%s',
					'%s',
					'%s',
					'%s',
					'%s'
				) 
			);


	if(!$check){
		$err_msg = "Could not insert right now please try again later.";
	}
	else{
	
		    ?>
			<script>
			window.location.href="admin.php?page=kpromotion";
			</script>
			<?php 
	}

}

if(isset($_POST['updatepromotion'])){

    $id  = $_POST['eid'];
	$title	        =	"";
	$start_date		=	"";
	$end_date		=	"";
	$start_date1	=	"";
	$end_date1		=	"";
	$start_date2	=	"";
	$end_date2		=	"";
	$color 			=	"";
	$txtcolor       =   "";
	$content		=	"";
	$content1		=	"";
	$content2		=	"";
	$promotion_anchor = "";
	$promotion_anchor1 = "";
	$promotion_anchor2 = "";
	
	if(isset($_POST['promotion_name'])){
		$title = $_POST['promotion_name'];
	}
	if(isset($_POST['start_date'])){
		$start_date = $_POST['start_date'];
		//date("Y-m-d", strtotime($_POST['start_date']))." ".date("H:i:s");
	}
	if(isset($_POST['end_date'])){
		$end_date = $_POST['end_date'];
		//date("Y-m-d", strtotime($_POST['end_date']))." ".date("H:i:s");
	}

	if(isset($_POST['start_date1'])){
		$start_date1 = $_POST['start_date1'];
	}
	if(isset($_POST['end_date1'])){
		$end_date1 = $_POST['end_date1'];
	}

	if(isset($_POST['start_date2'])){
		$start_date2 = $_POST['start_date2'];
	}
	if(isset($_POST['end_date2'])){
		$end_date2 = $_POST['end_date2'];
	}



	if(isset($_POST['color'])){
		$color = $_POST['color'];
	}
	if(isset($_POST['bgcolor'])){
		$txtcolor = $_POST['bgcolor'];
	}
	if(isset($_POST['promotionText'])){
		$content = $_POST['promotionText'];
	}
	if(isset($_POST['promotionText1'])){
		$content1 = $_POST['promotionText1'];
	}
	if(isset($_POST['promotionText2'])){
		$content2 = $_POST['promotionText2'];
	}


	if(isset($_POST['promotion_anchor'])){
		$promotion_anchor = $_POST['promotion_anchor'];
	}
	if(isset($_POST['promotion_anchor1'])){
		$promotion_anchor1 = $_POST['promotion_anchor1'];
	}
	if(isset($_POST['promotion_anchor2'])){
		$promotion_anchor2 = $_POST['promotion_anchor2'];
	}


			    $check = $wpdb->update( 
							 $wpdb->prefix.'kp_promotion', 
							 array( 
									     'promotion_title'		=> $title,
										  'promotion_startdate'	=> $start_date,
										  'promotion_enddate'	=> $end_date,
										  'promotion_startdate1'=> $start_date1,
										  'promotion_enddate1'	=> $end_date1,
										  'promotion_startdate2'=> $start_date2,
										  'promotion_enddate2'	=> $end_date2,
										  'promotion_bgcolor'   => $color,
										  'promotion_textcolor'	=> $txtcolor,
										  'promotion_text'		=> $content,
										  'promotion_text1'		=> $content1,
										  'promotion_text2'		=> $content2,
										  'promotion_anchor'    => $promotion_anchor,
										  'promotion_anchor1'   => $promotion_anchor1,
										  'promotion_anchor2'   => $promotion_anchor2,
										  'promotion_status'	=> 1
													    ), 
							    array( 'id' => $id )
							);

				
			?>
						<script>
						window.location.href="admin.php?page=kpromotion";
						</script>
<?php 
		
}


	$getpomotionTitle = '';
	$getBgcolor = '';
	$getTextcolor = '';
	$getStartdate = '';

	$getEnddate = '';
	$getPromotionTxt = '';
	$promotion_anchor = '';
	$getStartdate1 = '';
	$getEnddate1 = '';
	$getPromotionTxt1 = '';
	$promotion_anchor1 = '';

	$getStartdate2 = '';
	$getEnddate2 = '';
	$getPromotionTxt2 = '';
	$promotion_anchor2 = '';


	$records = $wpdb->get_row("SELECT * FROM `".$wpdb->prefix."kp_promotion` LIMIT 0, 1");

	if($records)
	{
	$getpomotionTitle = ($records->promotion_title != '') ? $records->promotion_title : '';
	$getBgcolor = ($records->promotion_bgcolor != '') ? $records->promotion_bgcolor : ''; 
	$getTextcolor = ($records->promotion_textcolor != '') ? $records->promotion_textcolor : ''; 

	$getStartdate = ($records->promotion_startdate != '') ? $records->promotion_startdate : '';
	$getEnddate = ($records->promotion_enddate != '') ? $records->promotion_enddate : ''; 
	$getPromotionTxt = ($records->promotion_text != '') ? $records->promotion_text : ''; 
	$promotion_anchor = ($records->promotion_anchor != '') ? $records->promotion_anchor : ''; 

	$getStartdate1 = ($records->promotion_startdate1 != '') ? $records->promotion_startdate1 : '';
	$getEnddate1 = ($records->promotion_enddate1 != '') ? $records->promotion_enddate1 : ''; 
	$getPromotionTxt1 = ($records->promotion_text1 != '') ? $records->promotion_text1 : ''; 
	$promotion_anchor1 = ($records->promotion_anchor1 != '') ? $records->promotion_anchor1 : ''; 

	$getStartdate2 = ($records->promotion_startdate2 != '') ? $records->promotion_startdate2 : '';
	$getEnddate2 = ($records->promotion_enddate2 != '') ? $records->promotion_enddate2 : ''; 
	$getPromotionTxt2 = ($records->promotion_text2 != '') ? $records->promotion_text2 : ''; 
	$promotion_anchor2 = ($records->promotion_anchor2 != '') ? $records->promotion_anchor2 : ''; 
	
	$Status = ($records->promotion_status != '') ? $records->promotion_status : ''; 

  }

?>

<div class="wrap">
<h3><?php _e('New Promotion'); ?></h3>
<?php if($err_msg != ""){ ?>
<div class="error below-h2">
	<p><strong>ERROR</strong>: <?php echo $err_msg; ?></p>
</div>
<?php } ?>
<form id="createcustomer" class="validatecustomer" name="createcustomer" method="post" action="<?php echo admin_url(); ?>admin.php?page=kpromotion">
	<table class="form-table">
		<tr id="tr-fn">
			<th scope="row">
				<label for="cs-fn"><?php _e('Promotion Name'); ?>
			</label><span class="description"> ( Required ) </span></th>
			<td colspan="4">
				<input trid="tr-fn" type="text" name="promotion_name" id="cs-fn" value="<?php echo $getpomotionTitle; ?>" class="regular-text" /><br />
				<span class="description"><?php _e('Please enter promotion name.'); ?></span>
			</td>
		</tr>
		<tr id="tr-color">
			<th scope="row">
				<label for="cs-city"><?php _e('Background Color'); ?>
			</label><span class="description"> ( Required ) </span></th>
			<td colspan="4">
				<input trid="tr-color" type="text" name="color" class="jscolor" id="cs-color" value="<?php echo $getBgcolor;  ?>" class="regular-text" /><br />
				<span class="description"><?php _e('Please Select Color.'); ?></span>
			</td>
		</tr>
      	<tr id="tr-txtcolor">
			<th scope="row">
				<label for="cs-city"><?php _e('Text Color'); ?>
			</label><span class="description"> ( Required ) </span></th>
			<td>
				<input trid="tr-color" type="text" name="bgcolor" class="jscolor" id="txt-color" value="<?php echo $getTextcolor;  ?>" class="regular-text" /><br />
				<span class="description"><?php _e('Please Select Color.'); ?></span>
			</td>
		</tr>



		<tr id="tr-txtcolor2">
			<th scope="row">
				<label for="cs-city"><?php _e('Content'); ?>
			</label><span class="description"> ( Required ) </span></th>
			<td colspan="4">
			</td>
		</tr>

		<tr id="tr-ln">
			<td>
				<input trid="tr-ln" type="text" name="start_date" id="start_date" placeholder="Start Date" value="<?php echo $getStartdate;  ?>" class="regular-text" /><br />
			</td>
			<td>
				<input trid="tr-add" type="text" name="end_date" id="end_date" placeholder="End Date" value="<?php echo $getEnddate;  ?>" class="regular-text" /><br />
			</td>
			<td>
				<input trid="tr-add" type="text" name="promotionText" id="promotion-content" placeholder="Enter Text" value="<?php echo $getPromotionTxt;  ?>" class="regular-text" /><br />
			</td>
			<td>
				<input trid="tr-add" type="text" name="promotion_anchor" id="promotion_anchor" placeholder="Enter Link" value="<?php echo $promotion_anchor;  ?>" class="regular-text" /><br />
			</td>
		</tr>



		<tr id="tr-lna">
			<td>
				<input trid="tr-ln" type="text" name="start_date1" id="start_date1" placeholder="Start Date" value="<?php echo $getStartdate1;  ?>" class="regular-text" /><br />
			</td>
			<td>
				<input trid="tr-add" type="text" name="end_date1" id="end_date1" placeholder="End Date" value="<?php echo $getEnddate1;  ?>" class="regular-text" /><br />
			</td>
			<td>
				<input trid="tr-add" type="text" name="promotionText1" id="promotion-content1" placeholder="Enter Text" value="<?php echo $getPromotionTxt1;  ?>" class="regular-text" /><br />
			</td>
			<td>
				<input trid="tr-add" type="text" name="promotion_anchor1" id="promotion_anchor1"  placeholder="Enter Link" value="<?php echo $promotion_anchor1;  ?>" class="regular-text" /><br />
			</td>
		</tr>

		<tr id="tr-lnb">
			<td>
				<input trid="tr-ln" type="text" name="start_date2" id="start_date2" placeholder="Start Date" value="<?php echo $getStartdate2;  ?>" class="regular-text" /><br />
			</td>
			<td>
				<input trid="tr-add" type="text" name="end_date2" id="end_date2" placeholder="End Date" value="<?php echo $getEnddate2;  ?>" class="regular-text" /><br />
			</td>
			<td>
				<input trid="tr-add" type="text" name="promotionText2" id="promotion-content2" placeholder="Enter Text" value="<?php 
				echo $getPromotionTxt2;  ?>" class="regular-text" /><br />
			</td>
			<td>
				<input trid="tr-add" type="text" name="promotion_anchor2" id="promotion_anchor2" placeholder="Enter Link" value="<?php 
				echo $promotion_anchor2;  ?>" class="regular-text" /><br />
			</td>
		</tr>


		<tr>
			<th scope="row">
                <?php if($records){ ?>
                            <input type="hidden" name="eid" value="<?php if($records->id) echo $records->id; ?>" />
                            <input id="createcustomersub" class="button button-primary" type="submit" value="Update Promotion" name="updatepromotion">
                <?php } else { ?>
                            <input id="createcustomersub" class="button button-primary" type="submit" value="Add New Promotion" name="createpromotion">
                <?php } ?>
			</th>
			<td>
			</td>
		</tr>
	</table>
</form>
</div>