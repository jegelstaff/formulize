<?php

if($showTabs) {
	print "
    <div id='pageNavTable'>
        <ul class='multi-page-list'>
            <li this-page='".($totalPages+1)."' class='icon-arrow-backward navtab'> $saveAndLeave</li>";
            
            foreach($pageTitles as $i=>$title) {
                $activeClass = $i == $currentPage ? "active-page" : "";
                $thisPage = $i != $currentPage ? "this-page='$i'" : "";
                print "
                <li class='$activeClass navtab' $thisPage>$title</li>";
            }
            
            print"
        </ul>    
    </div>";
}

print "
    <div class='xo-theme-form'>
        <table width='100%' class='outer' cellspacing='1'>
";
