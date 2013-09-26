<?php

$file_name=$_FILES['file']['name'];
$file_ext=strtolower(end(explode('.', $file_name)));
$file_size=$_FILES['file']['size'];
$file_tmp=$_FILES['file']['tmp_name'];

move_uploaded_file ($file_tmp,"upload/".$file_name);

defined('UploadFile') ? NULL : define('UploadFile',$file_name);


?>

<form action="" method="POST" enctype="multipart/form-data">
<p>
<input type="file" name="file"/>
<input type="submit" value="Upload"/>
</p>
</form>