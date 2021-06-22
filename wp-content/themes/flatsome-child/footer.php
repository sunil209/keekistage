<?php
/**
 * The template for displaying the footer.
 *
 * @package flatsome
 */

global $flatsome_opt;
?>

</main><!-- #main -->

<footer id="footer" class="footer-wrapper">

	<?php do_action('flatsome_footer'); ?>

</footer><!-- .footer-wrapper -->

</div><!-- #wrapper -->

<?php wp_footer(); ?>


<?php 

if( is_product() )
  {
?>
<style type="text/css">
  .hideme { display: none !important; }  
</style>
<script type="text/javascript">

    jQuery('#pa_fabric').on('change', function() {
        
        
        if(this.value !='')
        {
            var getChaneValue = this.value;
            var getStr = '';
            jQuery(".variable-items-wrapper li").each(function(n) {
                  
                  getStr = jQuery(this).attr('data-value') ;
                  if(getStr.indexOf(getChaneValue) == 0)
                    {
                        jQuery(this).removeClass("hideme");
                    }
                  else
                    {
                        jQuery(this).addClass("hideme");
                    }
            });
        }
        else
        {
           jQuery('.variable-items-wrapper li').removeClass( "hideme" );
        }



    });

</script>

<?php 

  }
?>


<?php 
if(is_cart() || is_checkout())
  {
  ?>
    <script type="text/javascript">

     jQuery(document).ajaxComplete(function( event, xhr, settings ) {
        var string = settings.url;
        console.log(string);
        if (string.indexOf('wc-ajax=get_refreshed_fragments') !== -1 || string.indexOf('cart') !== -1)
          {
             var value = 'keeki_shipping';
             jQuery("#shipping_method input[type=radio][value=" + value + "]").attr('checked', 'checked');
              
              
                var ajax_url = '<?php echo admin_url( 'admin-ajax.php' ); ?>';
              	jQuery.ajax({
                        url : ajax_url,
                        type : 'post',
                        data : {
                            action : 'post_shipping_method',
                            shipping_method : 'keeki_shipping'
                        },
                        success : function( response ) {
                                /* nothing to do */
                        }
                    });
              
              
              
          }


      });



    </script>

  <?php
  }
?>


<?php 
if(is_checkout())
  {
  ?>
    <script type="text/javascript">

        jQuery(window).load(function() {
         // executes when complete page is fully loaded, including all frames, objects and images
         jQuery("#shipping_address_2_field label[for='shipping_address_2']").text("Apartment, suite etc.");
        });

        jQuery(function(){
        jQuery("#shipping_address_2_field label[for='shipping_address_2']").text("Apartment, suite etc.");

        });



        /* Custom variations */

    


    </script>

  <?php
  }
?>

<style type="text/css">
  
.selectbox>span {
  padding: 10px;
  border: 2px solid #fff;
  display: inline-block;
  vertical-align: middle;
}

.selectbox {
  border: 1px solid #dddddd;
  display: inline-block;
  cursor: pointer;
}

.selectbox.active {
  border: 1px solid #333;
}

#pa_color{display: none;}

</style>


<script type="text/javascript">
        /* Custom variations */
          var ajaxurl = '<?php echo admin_url('admin-ajax.php'); ?>';
          jQuery( ".single_variation_wrap" ).on( "show_variation", function ( event, variation ) {
           // alert( variation.variation_id+ 'box' );
          //   console.log( variation );

              /*

                var variationId = variation.variation_id;
                     jQuery.ajax({
                          dataType: 'json',
                          type: "POST",
                          url: ajaxurl,
                          data: 'variation_id='+variationId+'&action=get_variations_custom_options',
                          beforeSend: function(){
                             // jQuery("#redirectLoader").show();
                          },
                          success: function(responseData){
                            //alert(responseData);
                             if(responseData.status == true)
                                {
                                    var select = jQuery('#pa_design');
                                    select.empty().append(responseData.desing);

                                    var select_color = jQuery('#pa_color');
                                    select_color.empty().append(responseData.color);


                                    jQuery("td.fabricBoxVal .selectbox").each(function() {
                                          jQuery(this).remove();
                                    });


                                    jQuery("#pa_color option").each(function() {
                                          var val = jQuery(this).val();
                                          var imgURL = jQuery(this).data('src');
                                          if(val != ''){
                                            jQuery("td.fabricBoxVal").append('<div class="selectbox" data-color="' + val + '"><span style="background-image:url('+imgURL+');"></span></div>');
                                          }

                                    });
                                    
                                      jQuery('.selectbox[data-color=""]').remove();
                                      //change select box on click
                                      jQuery(".selectbox").click(function() {
                                        //remove selected from others
                                        jQuery(".selectbox").removeClass("active");
                                        //do active to selected
                                        jQuery(this).addClass("active");
                                        //get value
                                        var optVal = jQuery(this).data("color");
                                        jQuery("#pa_color").val(optVal) 
                                        
                                      });

                                      //change select box on dropdown change
                                      jQuery("#pa_color").change(function(){
                                        var optVal = jQuery(this).val();
                                        jQuery(".selectbox").removeClass("active");
                                        jQuery(".selectbox[data-color='"+optVal+"']").addClass("active");
                                      })


                                    //alert(responseData.desing);
                                }
                          
                          },
                          error: function (responseData) {
                                          return false;
                                      }

                    });

                */

          });


          jQuery( document ).on( "found_variation.first", function ( e, v ) {


          } );

          jQuery( ".variations_form" ).on( "woocommerce_variation_select_change", function () {
               // alert( "Options changed" );
          } );
    





        jQuery("#pa_color option").each(function() {
          //get values of all option
          var val = jQuery(this).val();
          var imgURL = jQuery(this).data('src');

          //do magic create boxes like checkbox
         /* $("td.value").append('<div class="selectbox" data-color="' + val + '"><span style="background-color:' + val + '"></span></div>'); */
             if(val != ''){
              jQuery("td.fabricBoxVal").append('<div class="selectbox" data-color="' + val + '"><span style="background-image:url('+imgURL+');"></span></div>');
            }

        });

        //remove empty selectbox
        jQuery('.selectbox[data-color=""]').remove();

        //change select box on click
        jQuery(".selectbox").click(function() {
          //remove selected from others
          jQuery(".selectbox").removeClass("active");
          //do active to selected
          jQuery(this).addClass("active");
          //get value
          var optVal = jQuery(this).data("color");

          jQuery("#pa_color").val(optVal) 
          
        });

        //change select box on dropdown change
        jQuery("#pa_color").change(function(){
          var optVal = jQuery(this).val();
          jQuery(".selectbox").removeClass("active");
          jQuery(".selectbox[data-color='"+optVal+"']").addClass("active");
        })



      jQuery("#pa_design" ).change(function() {     
             var mid = jQuery(this).val();
             var productId = <?php the_ID(); ?>;
             
             jQuery.ajax({
                          dataType: 'json',
                          type: "POST",
                          url: ajaxurl,
                          data: 'p_id='+productId+'&mid='+mid+'&action=get_associated_fabric',
                          beforeSend: function(){
                             // jQuery("#redirectLoader").show();
                          },
                          success: function(responseData){
                            //alert(responseData);
                             if(responseData.status == true)
                                {

                                    var select_color = jQuery('#pa_color');
                                    select_color.empty().append(responseData.color);


                                    jQuery("td.fabricBoxVal .selectbox").each(function() {
                                          jQuery(this).remove();
                                    });


                                    jQuery("#pa_color option").each(function() {
                                          var val = jQuery(this).val();
                                          var imgURL = jQuery(this).data('src');
                                          if(val != ''){
                                            jQuery("td.fabricBoxVal").append('<div class="selectbox" data-color="' + val + '"><span style="background-image:url('+imgURL+');"></span></div>');
                                          }

                                    });
                                    
                                      jQuery('.selectbox[data-color=""]').remove();
                                      //change select box on click
                                      jQuery(".selectbox").click(function() {
                                        //remove selected from others
                                        jQuery(".selectbox").removeClass("active");
                                        //do active to selected
                                        jQuery(this).addClass("active");
                                        //get value
                                        var optVal = jQuery(this).data("color");
                                        jQuery("#pa_color").val(optVal) 
                                        
                                      });

                                      //change select box on dropdown change
                                      jQuery("#pa_color").change(function(){
                                        var optVal = jQuery(this).val();
                                        jQuery(".selectbox").removeClass("active");
                                        jQuery(".selectbox[data-color='"+optVal+"']").addClass("active");
                                      })


                                    //alert(responseData.desing);
                                }
                          
                          },
                          error: function (responseData) {
                                          return false;
                                      }

                    });





      });





    </script>

</body>
</html>
