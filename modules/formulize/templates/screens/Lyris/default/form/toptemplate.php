<?php

// Lyris form screen — adopts the design-system .fz-* form primitives.
// The container carries the label-mode + density modifiers so individual
// fields don't hardcode them. Owner decision: default = label-top + compact.
// `.form-container` is kept as an additive alias for backward compatibility.

print "
<div class='card fz-form-screen'>

<div class='card__header'>
	<h3 class='card__title'>".$formTitle."</h3>
</div>

<div class='card__body'>
<div class='fz-form fz-form--label-top fz-form--compact form-container'>
";