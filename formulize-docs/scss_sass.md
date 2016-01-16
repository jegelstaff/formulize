---
layout: default
permalink: developers/scss-sass
---

# SCSS and Sass

## What are SCSS and Sass?

SCSS is a superset of CSS3 which, when run through the Sass pre-compiler, produces CSS. The basics of Sass and SCSS usage can be found [here](http://sass-lang.com/guide).

## Installing Sass

It is necessary to install Ruby before installing Sass. Once Ruby is installed, Sass can be installed via command line with the command:
    
    gem install sass
    
Successful installation can be confirmed by running:
    
    sass -v

## Compiling SCSS Files Using Sass

Sass is used to make CSS files from SCSS files. In order to do so, simply run the command:
    
    sass input.scss output.css