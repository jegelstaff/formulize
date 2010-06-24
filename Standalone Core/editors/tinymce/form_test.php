<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>Integration With Form</title>
<script type="text/javascript">
	/**
	*	these function is called to open up the ajax file/image manager 
	* it passes the id of the element which will holds the selected file url.
	* you may have seen another variable (editor) is passed along the url.
	* which is introduced here and let you know that you could use a copy of ajax file manager working multiple editors
	*  
	*/
	function setFile(elementId)
	{
		var win = window.open('/ajaxfilemanager/ajaxfilemanager.php?editor=form&elementId='+elementId, 'ajaxFileImageManager', 'width=782,height=500');		
		return false;
	}
</script>
</head>

<body>
<div id="body">
<table class="tableForm" cellpadding="0" cellspacing="0">
	<thead>
  	<tr>
    	<th colspan="2">Integration With Form</th>
    </tr>
  </thead>
  <tbody>
  	<tr>
    	<th><label>Your Name:</label></th>
      <td><input type="text" class="input" name="name" value="" /></td>
    </tr>
    <tr>
    	<th><label>Your Photo1:</label></th>
      <td><input type="text" class="input" name="photo[1]" value="" id="photo1" />&nbsp;<button onclick="return setFile('photo1');">Browse</button></td>
    </tr>
    <tr>
    	<th><label>Your Photo2:</label></th>
      <td><input type="text" class="input" name="photo[2]" value="" id="photo2" />&nbsp;<button  onclick="return setFile('photo2');">Browse</button></td>
    </tr>    
  </tbody>
  <tfoot>
  	<th>&nbsp;</th>
    <td><input type="submit" value="Submit" /></td>
  </tfoot>
</table>

<p>
<h2>Ajax File/Image Manager Integration With Normal Form</h2>
here is the steps you should follow to get it work with your form<br />
	<ol>
  <li>create a folder for your website, e.g. C:/Inetpub/wwwroot/ajaxfilemanager/</li>
  <li>download the lastest ajax file manager file and unzip it, you should see *_test.php, ajaxfilemanager and uploaded folders</li>
  <li>move the ajaxfilemanager folder to C:/Inetpub/wwwroot/ajaxfilemanager/</li>
  <li> move the uploaded folder to C:/Inetpub/wwwroot/ajaxfilemanager/</li>
  <li> make sure you have read and write permissions to the folders of uploaded and session (within ajaxfilemanager folder)</li>
  <li> change CONFIG_SYS_ROOT_PATH and CONFIG_SYS_DEFAULT_PATH to be ../uploaded/ in config.base.php</li>
  <li> you should have the next directory structure .</li>
  C:/Inetpub/wwwroot/ajaxfilemanager/<br />
  |___ ajaxfilemanager<br />
  |___ uploaded<br />
  |___ form_test.html<br />
  <li> now visit the test.php via http://localhost/ajaxfilemanger/form_test.html</li>
  </ol>
  </p>
</div>
<style type="text/css">
html, body
{
    margin:0;
    padding:0;
}
body
{
    font: 11px Arial, Helvetica, sans-serif;
    color:#444444;
}
#body{
width:760px;
margin:0 auto;
}

form
{
    padding:0;
    margin:0;
}

.input{font: 11px Arial, Helvetica, sans-serif; width:300px;}
button
{
    font: bold 11px Arial, Helvetica, sans-serif;
    background-color: #ccc;
    color: #334D64;
}

table.tableForm
{
		width:98%;
    border: 1px solid #2F3030;
    margin: 1em auto;
    border-collapse: collapse;
}
#windowSaveClose{
position:relative;
left:360px;
}
table.tableForm thead th
{
    background-color: #000;
    font-size:12px;
    font-weight: bold;
    text-transform : uppercase;
    text-align: left;
    color: #fff;
    padding: 4px 8px;
    border: 1px solid #2F3030;
    white-space: nowrap;
}
table.tableForm thead th.center{text-align: center;}
table.tableForm thead th.right{text-align: right;}
table.tableForm tbody th
{
    text-align: right;
    white-space: nowrap;
    width: 1%;
    font-weight: normal;
    color: #000;
    padding: 4px 4px 4px 12px;
}
table.tableForm tbody td{padding: 4px 8px;}
table.tableForm tfoot th{}
table.tableForm tfoot td{padding: 4px 8px 12px;}

	
</style>
</body>
</html>
