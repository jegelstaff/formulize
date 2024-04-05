<?php

if($showTabs) {
    print "
    <div id='pageNavTable' class='pill-tabs'>";
        if($saveAndLeave) {
            print "
            <a href='#' this-page='".($totalPages+1)."' class='icon-arrow-backward pill-tabs__item navtab'> $saveAndLeave</a>";
        }
        foreach($pageTitles as $i=>$title) {
            $activeClass = $i == $currentPage ? "pill-tabs__item--active" : "";
            $thisPage = $i != $currentPage ? "this-page='$i'" : "";
            print "
            <a href='#' class='pill-tabs__item navtab $activeClass' $thisPage>$title</a>";
        }
        print"
    </div>";
}

print "
    <div class='card'>";

        if($formTitle) {
            print "
            <div class='card__header'>
                <h3 class='card__title'>".$formTitle."</h3>
            </div>";
        }

        print "
        <div class='card__body'>
            <div class='form-container'>
";
