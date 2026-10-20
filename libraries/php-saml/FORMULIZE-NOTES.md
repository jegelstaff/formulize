# php-saml in Formulize

Vendored copy of OneLogin php-saml **v2.18.1**, from https://github.com/onelogin/php-saml.
It bundles xmlseclibs **3.0.4** (the copy in `extlib/xmlseclibs/`, as shipped by upstream).

Only the runtime files are kept. Removed from the upstream release:
`composer.json`, `phpunit.xml`, `phpdoc.xml`, `demo1/`, `demo2/`, `demo-old/`, `tests/`, `docs/`.

The library was added to Formulize in July 2020 (commit 3971d5d532). Formulize uses it from `samllogin.php` and
`libraries/icms/core/Session.php`. A site enables it by placing a completed `settings.php` here.

This library is not tracked by Dependabot, so check upstream for security releases by hand.
