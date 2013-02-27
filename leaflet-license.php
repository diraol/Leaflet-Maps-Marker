<?php
/*
    License - Leaflet Maps Marker Plugin
*/
//info prevent file from being accessed directly
if (basename($_SERVER['SCRIPT_FILENAME']) == 'leaflet-license.php') { die ("Please do not access this file directly. Thanks!<br/><a href='http://www.mapsmarker.com/go'>www.mapsmarker.com</a>"); }
?>
<div class="wrap">
<?php include('inc' . DIRECTORY_SEPARATOR . 'admin-header.php'); ?>
<h3 style="font-size:23px;"><?php _e('Pro License','lmm'); ?></h3>
<p>
License stuff gets here:<br />
- Trial version yes/no, if yes - how many days left?<br/>
- Pro version key<br />
- update license valid yes/no, if yes - valid until when?
</p>
<!--wrap-->
<?php
include('inc' . DIRECTORY_SEPARATOR . 'admin-footer.php');
?>