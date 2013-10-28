<?php
// $Id: welcome.php 19118 2010-03-27 17:46:23Z skenow $
// ALTERED BY FREEFORM SOLUTIONS FOR THE STANDALONE INSTALLER
$content .= '
<p>
	Formulize is a data management and reporting system that lets you easily create forms on the web, and interact with the data people have submitted.  It uses the ImpressCMS platform for some operations.  This installer will setup Formulize and ImpressCMS for you at the same time.
</p>
<p>
	Formulize and ImpressCMS are released under the terms of the
	<a href="http://www.gnu.org/copyleft/gpl.html" rel="external">GNU General Public License (GPL)</a>
	and are free to use and modify.
	You are free to redistribute as long as you abide by the distribution terms of the GPL.
</p>
<h3>Requirements</h3>
<ul>
	<li>WWW Server (<a href="http://www.apache.org/" rel="external">Apache</a>, IIS, Roxen, etc)</li>
	<li><a href="http://www.php.net/" rel="external">PHP</a> 5.2 or higher (5.2.8 or higher recommended, <strong>5.3 is now supported</strong>) and 16mb minimum memory allocation</li>
	<li><a href="http://www.mysql.com/" rel="external">MySQL</a> 4.1.0 or higher</li>
</ul>
<h3>Before you install</h3>
<ul>
	<li>Setup the web server, PHP and database server properly.</li>
	<li>Prepare a database for ImpressCMS. This can be an existing database as well as a newly created one.</li>
	<li>Prepare a user account and grant this user access to the database (all rights).</li>
	<li>Make the directories of uploads/, cache/, templates_c/, modules/ writable (chmod 777 or 755 - depending on servers).</li>
	<li>Make the file mainfile.php writable (chmod 666 depending on server).</li>
	<li>In your internet browser settings turn on the allowance of cookies and JavaScript.</li>
</ul>
';
?>