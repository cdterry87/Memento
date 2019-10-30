# Memento
Keep track of your memories with Memento!

## Requirements:
PHP 7.2

## Installation:
1. Clone the project to your local PC.
```
git clone https://github.com/cdterry87/Memento.git
```

2. Setup the application:
```
composer install              # Install dependencies with composer
cp .env.example .env          # Create your .env file based on the .env.example file and change the appropriate values
php artisan key:generate      # Generate an application key for your project
npm install                   # Installs dependencies with npm
npm run prod                  # Builds the javascript part of the application for production

php artisan migrate --seed    # Run database migrations and seed with default data
php artisan storage:link      # Allow file uploads

php artisan serve             # Runs the application on a local web server
```

### Login to the application with the following credentials:
```
username: admin
password: password
```
