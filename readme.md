## Description

Monitorizes basic web functions by polling the home page and reporting an error if status code is other than 200; the front end needs to be opened in the browser in order for the monitor to work. Also reports broken links if blc is installed.

## Requirements

PHP and MySQL -must hardcode credentials in config.php-. Optionally, you will need blc if you want broken links reported too. Get it here: [Broken Link Checker] (https://github.com/stevenvachon/broken-link-checker).