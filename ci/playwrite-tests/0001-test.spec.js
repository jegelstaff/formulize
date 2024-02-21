const { test, expect } = require('@playwright/test');

const web_root = '/var/www/html';
const test_domain = 'localhost:8080';
const db_user = 'root';
const db_pass = 'password';
const db_name = 'formulize';
const admin_email = 'formulize@example.com';
const admin_password = 'password';
const short_wait = 543;

test('Installer loads', async ({ page }) => {
  await page.goto(`http://${test_domain}/install/index.php`);

  // Expect a title "to contain" a substring.
  await expect(page).toHaveTitle(/Formulize 7/);
});

// (async () => {
//   // Launch a browser
//   const browser = await chromium.launch();
//   const page = await browser.newPage();

//   // Variables from the Selenium script
//   const web_root = '/var/www/html';
//   const test_domain = 'localhost:8080';
//   const db_user = 'root';
//   const db_pass = 'password';
//   const db_name = 'formulize';
//   const admin_email = 'formulize@example.com';
//   const admin_password = 'password';
//   const short_wait = 543; // This is assumed to be milliseconds

//   // Navigate to the installation page
//   await page.goto(`http://${test_domain}/install/index.php`);

//   // Click the submit button
//   await page.click('button[type="submit"]');
//   await page.waitForTimeout(short_wait);

//   // Continue through the installation steps
//   // This will click the "next" button a couple of times with waits in between
//   await page.click('button.next');
//   await page.waitForTimeout(short_wait);

//   await page.click('button.next');
//   await page.waitForTimeout(short_wait);

//   // Fill in the installation form
//   await page.fill('#trustpath', `${web_root}/selenium-848d24bb54d726d`);
//   await page.click('button[type="submit"]');
//   await page.waitForSelector('text="Server hostname"');

//   await page.fill('#DB_HOST', 'localhost');
//   await page.fill('#DB_USER', db_user);
//   await page.fill('#DB_PASS', db_pass);
//   await page.click('button[type="submit"]');
//   await page.waitForSelector('text="Password Salt Key"');

//   await page.fill('#DB_NAME', db_name);
//   await page.fill('#DB_PREFIX', 'selenium');
//   await page.fill('#DB_SALT', 's4RyHEWYxWN9OUAGvCdxljYRqqSgEf9qbsvVSvhWSumtfyI7SNx6ct1n5fypNFdi4');
//   await page.click('button[type="submit"]');
//   await page.waitForTimeout(short_wait);

//   // Final steps of the installation
//   // Assuming similar interactions as previous steps
//   // ...

//   // Close the browser
//   await browser.close();
// })();
