<?php 
header('Content-Type: text/html; charset=UTF-8');
//info: construct path to wp-load.php and get $wp_path
while(!is_file('wp-load.php')){
  if(is_dir('../')) chdir('../');
  else die('Error: Could not construct path to wp-load.php - please check <a href="http://mapsmarker.com/path-error">http://mapsmarker.com/path-error</a> for more details');
}             
include( 'wp-load.php' );   
//info: security check
$wpnonceicon = isset($_GET['_wpnonceicon']) ? $_GET['_wpnonceicon'] : '';
//if (! wp_verify_nonce($wpnonceicon, 'icon-upload-nonce') ) { die(__('Security check failed - please call this function from the according Leaflet Maps Marker admin page!','lmm').''); };
include('wp-admin' . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'file.php');
?>
<!DOCTYPE html>
<html>
<head>
<link rel='stylesheet' href='<?php echo LEAFLET_PLUGIN_URL . 'inc/css/icon-upload.css' ?>' type='text/css' media='all' />
</head>
<body>
<?php _e('Allowed file types: png, gif','lmm'); ?><br>
<form enctype="multipart/form-data" action=""  method="post">
<input  type="hidden" name="MAX_FILE_SIZE" value="3000000"  />
<input  type="file" name="uploadFile"/>
<input  type="submit" name="upload-submit" class="button" value="<?php esc_attr_e('upload icon','lmm'); ?>"/>
</form>
<?php
if ( isset($_FILES['uploadFile']['name']) && ($_FILES['uploadFile']['name'] == TRUE) ){
	if($_FILES['uploadFile']['type'] == 'image/png'){
		WP_Filesystem();
		global $wp_filesystem; 
		$wp_filesystem->put_contents(
		LEAFLET_PLUGIN_ICONS_DIR . DIRECTORY_SEPARATOR . basename($_FILES['uploadFile']['name']),
		file_get_contents($_FILES['uploadFile']['tmp_name']),
		FS_CHMOD_FILE);
		_e('File upload successfully - please reload page','lmm');
	} else {
		_e('You have selected a file with a not supported file type','lmm');
	} 
}  
?>     
</body>
</html>