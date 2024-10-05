<?php

function __autoload($class) {
        return HTMLPurifier_Bootstrap::autoload($class);
}