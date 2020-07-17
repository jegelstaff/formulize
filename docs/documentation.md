---
layout: default
permalink: developers/version_control/documentation/
---

# Documentation

All documentation for Formulize is kept in the /docs/ folder.  This folder contains a Jekyll based website.  The website is publically accessible at [http://www.formulize.org/](http://www.formulize.org/)

[Learn more about the configuration of the Jekyll site and deploying to GitHub Pages](../../github_pages). 

Our intention is that documentation will be committed as part of each branch, so that the code changes come with relevant explanations and updated documentation.  The easiest way to write the documentation, is to install Jekyll on your local development machine.  This makes it easy to then review the documentation, and make changes to it, without having to deploy the entire set of docs to GitHub.

Jekyll is written in Ruby, so you will need Ruby installed in order to use it.  [Learn more about installing Jekyll here](http://jekyllrb.com/docs/installation/)

Once you have Jekyll installed, you can open up a command line and go to the /formulize-docs/ folder.  Type this command to start up a localhost server that will show you the documentation, the same as it would appear on GitHub Pages:

    jekyll serve --watch
    
The **--watch** switch ensures that as you make changes, they will be reflected in what you see in your web browser.  To see the documentation, go to this URL:

    http://localhost:4000/
    
Pages can be written in Markdown syntax.  All pages must start with the following snippet:

    ---
    layout: default
    permalink: version_control/developers/documentation/
    ---
    
The **permalink** is the URL for this page, relative to the root of the documentation.  The **layout** is simply a reference to the page template that is being used for the contents of the page outside the main body section.

You can link to other pages in the documentation using standard Markdown syntax:

    [This text will be clickable and will go to the permalink in parens](/developers/github_pages)
    [This link will resolve to a permalink that is relative to the current page's permalink](../documentation)
    
Note that when using a fully qualified permalink as the destination, there is a preceding slash and no ending slash, which is the opposite format from how you must specify the permalink in the snippet at the top of each page!  Nothing is ever simple.

All other syntax that you can use is based on the kramdown flavour of Markdown.

