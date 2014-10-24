---
layout: default
permalink: developers/github_pages/
---

# GitHub Pages and Jekyll Configuration

GitHub Pages is a conveninent system for hosting publically available web pages. You can generate a GitHub Pages site for your project by starting at [https://pages.github.com/](https://pages.github.com/).

Once you have created the site, it will add a branch to your GitHub repo called **gh-pages**.  That branch will contain a series of files that are used by the Jekyll installation at GitHub, to create the website for your project.  The address of the site is based on the user who has the repository and the repository name.  In Formulize's case, the website is accessible here: [http://jegelstaff.github.io/formulize/](http://jegelstaff.github.io/formulize/)

## Deploying documentation

Any pushes to the gh-pages branch will update the documentation available at your GitHub Pages site.

We maintain a working copy of the gh-pages branch, inside the **/formulize-docs/** folder of the master branch.  This allows us to make changes to the docs as part of regular commits.  Later, someone can easily deploy the contents of the /formulize-docs/ folder to the gh-pages branch, by using the following script **from the root of their local git repository**:

    git checkout gh-pages
    git read-tree master:formulize-docs
    git commit -m "Publish docs from master branch"
    git push origin gh-pages
    git checkout master
    
Bad things will happen if you run that from a folder other than the root of your local git repository.

The script switches to the gh-pages branch, updates it with the contents of the /formulize-docs/ folder from the master branch, and then commits the changes and pushes them to GitHub.  Then it switches back to the master branch.

The Jekyll site relies on one main configuration file called **_config.yml**.  That file looks like this:

    markdown: kramdown
    baseurl: /formulize
    url: http://jegelstaff.github.io/formulize/
    permalink: formulize/:title/
    
That file is critical for making sure the navigation and everything else works as expected.  Most pages of the site are written in Markdown, but regular HTML can be used as well.  For more details about writing documentation and the structure of the pages, [see the Documentation page](../version_control/documentation)