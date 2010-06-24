# get the arguments from the command line (which corresponds to the new directory name and the readable name you hope!)

my $nn = shift;
my $realname = shift;

# if no argument was passed, then stop

if (!$nn) {
	die "No name passed for the cloned copy.\nScript execution terminated";
}

if (!$realname) {
	$realname = $nn;
}

# set any variables used below

my $nnforminc = $nn . "form.inc.php";
my $nnblock = $nn . "_block.html";

# modify xoops_version.php

$/ = undef;
open (TEMP, "<xoops_version.php") or print "Error: xoops_version.php file open failed.";
$xv = <TEMP>;
close TEMP;

$xv =~ s/_IM_IMENU_NAME;/"$realname";/g;
$xv =~ s/"iMenu";/"$nn";/g;
$xv =~ s/imenu/$nn/g;

open(OUTF,">xoops_version.php") or print "Error: xoops_version.php file save failed.";
print OUTF $xv;
close(OUTF);

#modify the .sql file

$/ = undef;
open (TEMP, "<./sql/mysql.sql") or print "Error: mysql.sql file open failed.";
$xv = <TEMP>;
close TEMP;

$xv =~ s/imenu/$nn/g;

open(OUTF,">./sql/mysql.sql") or print "Error: mysql.sql file save failed.";
print OUTF $xv;
close(OUTF);

#modify the imenuform.inc.php file

$/ = undef;
open (TEMP, "<imenuform.inc.php") or print "Error: imenuform.inc.php file open failed.";
$xv = <TEMP>;
close TEMP;

$xv =~ s/imenu/$nn/g;

open(OUTF,">imenuform.inc.php") or print "Error: imenuform.inc.php file save failed.";
print OUTF $xv;
close(OUTF);

#modify the templates/blocks/imenu_block.html file

$/ = undef;
open (TEMP, "<./templates/blocks/imenu_block.html") or print "Error: templates/blocks/imenu_block.html file open failed.";
$xv = <TEMP>;
close TEMP;

$xv =~ s/imenu/$nn/g;

open(OUTF,">./templates/blocks/imenu_block.html") or print "Error: templates/blocks/imenu_block.html file save failed.";
print OUTF $xv;
close(OUTF);

#modify the blocks/imenu.php file

$/ = undef;
open (TEMP, "<./blocks/imenu.php") or print "Error: blocks/imenu.php file open failed.";
$xv = <TEMP>;
close TEMP;

$xv =~ s/imenu/$nn/g;
$xv =~ s/iMenu/$nn/g;
$xv =~ s/IMENU/$nn/g;
$xv =~ s/drawLink/drawLink_$nn/g;

open(OUTF,">./blocks/imenu.php") or print "Error: blocks/imenu.php file save failed.";
print OUTF $xv;
close(OUTF);

#modify the admin/admin_header.php file

$/ = undef;
open (TEMP, "<./admin/admin_header.php") or print "Error: admin/admin_header.php file open failed.";
$xv = <TEMP>;
close TEMP;

$xv =~ s/iMenu/$nn/g;

open(OUTF,">./admin/admin_header.php") or print "Error: admin/admin_header.php file save failed.";
print OUTF $xv;
close(OUTF);

#modify the admin/index.php file

$/ = undef;
open (TEMP, "<./admin/index.php") or print "Error: admin/index.php file open failed.";
$xv = <TEMP>;
close TEMP;

$xv =~ s/imenuform.inc.php/$nnforminc/g;
$xv =~ s/iMenu/$realname/g;
$xv =~ s/imenu/$nn/g;

open(OUTF,">./admin/index.php") or print "Error: admin/index.php file save failed.";
print OUTF $xv;
close(OUTF);

#modify the language/english/admin.php file

$/ = undef;
open (TEMP, "<./language/english/admin.php") or print "Error: language/english/admin.php file open failed.";
$xv = <TEMP>;
close TEMP;

$xv =~ s/iMenu/$realname/g;

open(OUTF,">./language/english/admin.php") or print "Error: language/english/admin.php file save failed.";
print OUTF $xv;
close(OUTF);

#modify the language/english/blocks.php file

$/ = undef;
open (TEMP, "<./language/english/blocks.php") or print "Error: language/english/blocks.php file open failed.";
$xv = <TEMP>;
close TEMP;

$xv =~ s/IMENU/$nn/g;
$xv =~ s/iMenu/$realname/g;

open(OUTF,">./language/english/blocks.php") or print "Error: language/english/blocks.php file save failed.";
print OUTF $xv;
close(OUTF);

#modify the language/english/modinfo.php file

$/ = undef;
open (TEMP, "<./language/english/modinfo.php") or print "Error: language/english/modinfo.php file open failed.";
$xv = <TEMP>;
close TEMP;

$xv =~ s/iMenu/$realname/g;

open(OUTF,">./language/english/modinfo.php") or print "Error: language/english/modinfo.php file save failed.";
print OUTF $xv;
close(OUTF);

# rename files

rename("./imenuform.inc.php", "./$nnforminc");
rename("./icon_imenu.gif", "./icon_$nn.gif");
rename("./templates/blocks/imenu_block.html", "./templates/blocks/$nnblock");
rename("./blocks/imenu.php", "./blocks/$nn.php");

print "Cloning complete.  $realname module ready for upload.";


