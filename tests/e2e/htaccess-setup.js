// global-setup.js
const fs = require('fs');
const path = require('path');

module.exports = async () => {
  const htaccessContent = `
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteCond %{REQUEST_FILENAME} !-l
RewriteRule ^(.*)$ /modules/formulize/index.php?formulizeRewriteRuleAddress=$1 [L]
`;

  const webrootPath = path.join(__dirname, '../../');
  const htaccessPath = path.join(webrootPath, '.htaccess');

  fs.writeFileSync(htaccessPath, htaccessContent.trim());
  console.log('.htaccess file created at:', htaccessPath);
};
