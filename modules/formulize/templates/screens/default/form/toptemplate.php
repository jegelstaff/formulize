<?php

print "
<div class='card'>

<div class='card__header'>
	<h3 class='card__title'>".$formTitle."</h3>
</div>

<div class='card__body'>".($introductoryText ? "<div class='formulize-screen-intro ck-content'>$introductoryText</div>" : "")."
<div class='form-container'>
";