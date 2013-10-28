<?php
// $Id: images.php 20551 2010-12-19 03:30:11Z skenow $
//%%%%%% Image Manager %%%%%

define('_MD_IMGMAIN','Image Manager Main');

define('_MD_ADDIMGCAT','Add Image Category:');
define('_MD_EDITIMGCAT','Edit Image Category:');
define('_MD_IMGCATNAME','Category Name:');
define('_MD_IMGCATRGRP','Select groups for image manager use:<br /><br /><span style="font-weight: normal;">These are groups allowed to use the image manager for selecting images but not uploading. Webmaster has automatic access.</span>');
define('_MD_IMGCATWGRP','Select groups allowed to upload images:<br /><br /><span style="font-weight: normal;">Typical usage is for moderator and admin groups.</span>');
define('_MD_IMGCATWEIGHT','Display order in image manager:');
define('_MD_IMGCATDISPLAY','Display this category?');
define('_MD_IMGCATSTRTYPE','Images are uploaded to:');
define('_MD_STRTYOPENG','This can not be changed afterwards!');
define('_MD_INDB',' Store in the database (as binary "blob" data)');
define('_MD_ASFILE',' Store as files (in the directory %s)<br />');
define('_MD_RUDELIMGCAT','Are you sure that you want to delete this category and all of its images files?');
define('_MD_RUDELIMG','Are you sure that you want to delete this images file?');

define('_MD_FAILDEL', 'Failed deleting image %s from the database');
define('_MD_FAILDELCAT', 'Failed deleting image category %s from the database');
define('_MD_FAILUNLINK', 'Failed deleting image %s from the server directory');

######################## Added in 1.2 ###################################
define('_MD_FAILADDCAT', 'Failed adding image category');
define('_MD_FAILEDIT', 'Failed update image');
define('_MD_FAILEDITCAT', 'Failed update category');
define('_MD_IMGCATPARENT','Parent Category:');
define('_MD_DELETEIMGCAT','Delete Image Category');

define('_MD_ADDIMGCATBTN','Add new category');
define('_MD_ADDIMGBTN','Add new image');

define('_MD_IMAGESIN', 'Images in %s');
define('_MD_IMAGESTOT', '<b>Total Images:</b> %s');

define('_MD_IMAGECATID', 'ID');
define('_MD_IMAGECATNAME', 'Title');
define('_MD_IMGCATFOLDERNAME', 'Folder Name');
define('_MD_IMGCATFOLDERNAME_DESC', 'Do not use spaces or special chars!');
define('_MD_IMAGECATMSIZE', 'Max Size');
define('_MD_IMAGECATMWIDTH', 'Max Width');
define('_MD_IMAGECATMHEIGHT', 'Max Height');
define('_MD_IMAGECATDISP', 'Display');
define('_MD_IMAGECATSTYPE', 'Store Type');
define('_MD_IMAGECATATUORESIZE', 'Auto Resize');
define('_MD_IMAGECATWEIGHT', 'Weight');
define('_MD_IMAGECATOPTIONS', 'Options');
define('_MD_IMAGECATQTDE', '# Images');
define('_IMAGEFILTERS', 'Select a filter:');
define('_IMAGEAPPLYFILTERS', 'Apply filters in image');
define('_IMAGEFILTERSSAVE', 'Overwrite original image?');
define('_IMGCROP', 'Crop Tool');
define('_IMGFILTER', 'Filter Tool');

define('_MD_IMAGECATSUBS', 'Subcategories');

define('_WIDTH', 'Width');
define('_HEIGHT', 'Height');
define('_DIMENSION', 'Dimension');
define('_CROPTOOL', 'Crop inspector');
define('_IMGDETAILS', 'Image details');
define('_INSTRUCTIONS', 'Instructions');
define('_INSTRUCTIONS_DSC', 'To select crop area, drag and move the dotted rectangle or type in values directly into the form.');

define('_MD_IMAGE_EDITORTITLE', 'DHTML Image Editor');
define('_MD_IMAGE_VIEWSUBS', 'View Sub-categories');
define('_MD_IMAGE_COPYOF', 'Copy of ');

define('IMANAGER_FILE', 'File');
define('IMANAGER_WIDTH', 'Width');
define('IMANAGER_HEIGHT', 'Height');
define('IMANAGER_SIZE', 'Size');
define('IMANAGER_ORIGINAL', 'Original Image');
define('IMANAGER_EDITED', 'Edited Image');
define('IMANAGER_FOLDER_NOT_WRITABLE', 'Folder is not writeable by the server.');

// added in 1.3
define('IMANAGER_NOPERM', 'You are not authorised to access this area!');