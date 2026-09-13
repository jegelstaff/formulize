// global-setup.js
const fs = require('fs');
const path = require('path');

module.exports = async () => {
  // This overwrites the site's .htaccess outright, so it has to carry every rule the suite
  // or the developer's site needs - anything missing here is silently removed from their dev
  // site by any test run. The Public API block must stay above the alternate URL block: the
  // second rule matches everything that is not a real file, so on its own it swallows
  // /formulize-public-api/ requests and hands them to the screens handler, which answers 404.
  //
  // The Public API specs address the endpoint through index.php?apiPath=... directly, so they
  // pass either way. That is the whole reason this went unnoticed: nothing in the suite
  // exercises the rewritten URL that the setup instructions actually tell administrators to
  // configure.
  const htaccessContent = `
RewriteEngine On

# Pass the Authorization header through to PHP, which is what makes API keys work. Kept here
# so the dev site matches the .htaccess the setup instructions give administrators, rather
# than only working because this container happens to run mod_php, where the header survives
# anyway. formulize_publicApiGetAuthorizationHeader() reads the REDIRECT_HTTP_AUTHORIZATION
# this sets. The mod_rewrite form is used rather than CGIPassAuth because it needs nothing
# beyond mod_rewrite, which the Public API rule below already requires, and it cannot 500 on
# an older Apache or under a stricter AllowOverride.
RewriteCond %{HTTP:Authorization} .
RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]

# Public API
RewriteCond %{REQUEST_URI} ^/formulize-public-api/ [NC]
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteCond %{REQUEST_FILENAME} !-l
RewriteRule ^(.*)$ /modules/formulize/public_api/index.php?apiPath=$1 [L,B,QSA]

# Alternate URLs for screens
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteCond %{REQUEST_FILENAME} !-l
RewriteRule ^(.*)$ /modules/formulize/index.php?formulizeRewriteRuleAddress=$1 [L,B,QSA]
`;

  const webrootPath = path.join(__dirname, '../../');
  const htaccessPath = path.join(webrootPath, '.htaccess');

  try {
    fs.writeFileSync(htaccessPath, htaccessContent.trim() + '\n');
    console.log('.htaccess file created successfully at:', htaccessPath);
  } catch (error) {
    console.error('.htaccess file creation failed at:', htaccessPath);
    console.error(error);
  }
};
