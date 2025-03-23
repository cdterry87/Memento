# Memento

Keep track of your memories with Memento! (Based on the Reflectly app)

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

3. Login to the application with the following credentials:

```
username: admin
password: password
```

## 2025 Update - Using Docker Development Environment

-   Clone the project as mentioned above
-   Copy the .env.example as .env as mentioned above
-   Build the docker image using this command:

```
docker compose up -d --build
# NOTE: If `docker compose` doesn't work, try `docker-compose`
```

-   Go into the terminal of the container and install dependencies and perform the rest of the setup:

```
docker exec -it laravel_app bash
composer install
php artisan key:generate
php artisan migrate:fresh --seed
npm install
npm run watch # Starts watching for js changes
```

-   View the site at http://localhost:8000

### You may also need to do this for file uploads:

```
php artisan storage:link
chmod -R 777 /var/www/storage /var/www/bootstrap/cache
```

### To start and stop the container after initial setup:

```
# Start container
docker compose up -d

# Go to the container terminal
docker exec -it laravel_app bash

# Run npm to watch for changes
npm run watch

# Stop container
docker compose down
```
