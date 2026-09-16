<?php

// Lyris form screen — adopts the design-system .fz-* form primitives.
// The container carries the label-mode modifier so individual fields don't
// hardcode it. Density is deliberately left at the design system's default
// (`.fz-form` = 38px controls): issue #113 dropped the `.fz-form--compact`
// modifier from form screens because compact was too tight to be the default.
// The modifier is still defined in themes/Lyris/css/style.css so it can be
// re-applied later as a per-screen configuration option.
// `.form-container` is kept as an additive alias for backward compatibility.

print "
<div class='card fz-form-screen'>

<div class='card__header'>
	<h3 class='card__title'>".$formTitle."</h3>
</div>

<div class='card__body'>
<div class='fz-form fz-form--label-top form-container'>
";