<div class="wrap">
    <?php screen_icon(); ?>
    <h2>Convert ACM Mods to HBI Ad Manager</h2>
    <p>This page is an all in one package that will convert your ACM Mods ads over to the new HBI Ad Manager. Please note, there is no going back.</p>
    <p>I <strong><em>STRONGLY</em></strong> suggest that you make any backups needed before running this script. <em>Again, there is no going back</em>.</p>
    <div id="convert_acm_ads" class="button button-primary">Convert ACM Ads</div>
</div>
<script>
    jQuery(document).ready(function($) {
        $('#convert_acm_ads').click(function() {
            var data = {
                'action' : 'process_acm_ad_conversion',
                'security' : '<?php echo wp_create_nonce( 'process_acm_ad_conversion' ); ?>'
            }
            
            $.post( ajaxurl, data, function(response) {
                if( response == 'Successfully converted from ACM Mods to HBI Ad Manager' ) {
                    alert( "Congratulations! Everything has been converted. Click Okay to go to your new Ad Units");
                    window.location = "<?php echo admin_url('edit.php?post_type=ad_unit'); ?>";
                } else {
                    alert( response );
                }
            });
        });
    })
</script>