laravel new media-vault
cd media-vault

# Select the React starter kit when prompted.
# larave's built-in authentication
# MySQL/MariaDB if asked

# Do NOT start local dev server yet

# Then install the two integrations needed for this project:
composer require php-amqplib/php-amqplib
composer require league/flysystem-aws-s3-v3

npm install
