---
layout: default
permalink: developers/github_pages/
---

# GitHub Pages and Jekyll Configuration

GitHub Pages is a conveninent system for hosting publically available web pages. You can generate a GitHub Pages site for your project by starting at [https://pages.github.com/](https://pages.github.com/).

You can manage the files for your GitHub Pages site through a **/docs** folder, or through a special branch called **gh-pages**. We use the **/docs** folder. The default address of the site is based on the user who has the repository and the repository name.  In Formulize's case, we use the GitHub Pages site as the basis for [https://www.formulize.org/](https://www.formulize.org/)

The Jekyll site relies on one main configuration file called **_config.yml**.  That file looks like this:

    markdown: kramdown
    baseurl: /formulize
    url: http://jegelstaff.github.io/formulize/
    permalink: formulize/:title/

That file is critical for making sure the navigation and everything else works as expected.  Most pages of the site are written in Markdown, but regular HTML can be used as well.  For more details about writing documentation and the structure of the pages, [see the Documentation page](../version_control/documentation)

## Deploying documentation

Deployment of documentation is automatic as soon as you commit changes to the **/docs** folder.

### For the record, if you use gh-pages... (not necessary in Formulize anymore)

If you use a **gh-pages** branch, then you can still keep documentation changes in your master branch, so your docs and code can be unified in the same commits. However, you then have to do a separate step to deploy documentation.

In the past, we maintained a working copy of the gh-pages branch, inside a **/formulize-docs/** folder of the master branch. This allowed us to make changes to the docs as part of regular commits. After commits, someone had to deploy the contents of the /formulize-docs/ folder to the gh-pages branch, by using the following script **from the root of their local git repository**:

    git checkout gh-pages
    git read-tree master:formulize-docs
    git commit -m "Publish docs from master branch"
    git push origin gh-pages
    git checkout -- .
    git checkout master

Bad things will happen if you run that script from a folder other than the root of your local git repository.

The script switches to the gh-pages branch, updates it with the contents of the /formulize-docs/ folder from the master branch, and then commits the changes and pushes them to GitHub.  Then it switches back to the master branch.
