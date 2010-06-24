<?php
// $Id: welcome.php 9674 2009-12-23 17:14:26Z Phoenyx $
$content .= '
<p>
	Formulize is a data management and reporting system written in PHP. It is an ideal tool for creating custom forms that are tailored
	to your specific workflows and requirements.  Applications that you create in Formulize can be deployed natively in this system, or in any other PHP-based software on this web server.
</p>
<p>
	Formulize is released under the terms of the <a href="http://www.gnu.org/licenses/old-licenses/gpl-2.0.html" rel="external">GNU General Public License (GPL) version 2</a> and is free to use and modify.  This standalone version of Formulize relies on some parts of the ImpressCMS website management system, which is also open source software, and is released under the newer 
	<a href="http://www.gnu.org/copyleft/gpl.html" rel="external">GPL version 3</a>.  This entire system is free to redistribute as long as you abide by the distribution terms of the GPL.
</p>
<h3>Requirements</h3>
<ul>
	<li>WWW Server (<a href="http://www.apache.org/" rel="external">Apache</a>, IIS, Roxen, etc)</li>
	<li><a href="http://www.php.net/" rel="external">PHP</a> 5.2 or higher (5.2.8 or higher recommended, <strong>5.3 not yet suppored</strong>) and 16mb minimum memory allocation</li>
	<li><a href="http://www.mysql.com/" rel="external">MySQL</a> 4.1.0 or higher</li>
  <li><a href="https://developer.mozilla.org/en/About_JavaScript" rel="external">Javascript</a> must be enabled in visitor\'s web browsers</li>
</ul>
<h3>Before you install</h3>
<ul>
	<li>Setup the web server, PHP and database server properly.</li>
	<li>Prepare a database for Formulize. This can be an existing database or a newly created one.</li>
	<li>Prepare a user account and grant this user access to the database (all rights).</li>
	<li>Make the directories of uploads/, cache/, templates_c/, modules/ writable (chmod 777 or 755 - depending on servers).</li>
	<li>Make the file mainfile.php writable (chmod 666 depending on server).</li>
	<li>In your internet browser settings turn on cookies and JavaScript.</li>
</ul>
';
?>