# Memento

Memento is a private, short-form journal. Each day you record your mood and what caused it as emoji, and you can add a few photos, a short voice message, and a message of up to 255 characters. You can scroll back through past days, see "on this day" reminders, and read a yearly recap of your good memories.

It's for you alone, with no friends, followers, or sharing.

The full product and technical specification is in [docs/spec.md](docs/spec.md). The original 2019 version of the app is preserved on the [`archive-2019`](https://github.com/cdterry87/Memento/tree/archive-2019) branch.

## Stack

- Laravel 13 on PHP 8.5
- Vue 3.5 with TypeScript, connected through Inertia 3
- Vuetify 4, with light and dark themes
- MySQL 8.4
- Laravel Sail for local development, including Mailpit for email and RustFS as local S3-compatible storage
- Pest, Larastan, and Pint for PHP; Vitest, ESLint, Prettier, and `vue-tsc` for the frontend

## Requirements

- Docker
- Git

You don't need PHP, Composer, or Node installed locally. Everything runs inside Sail's containers.

## First-time setup

1. Clone the repository:

    ```bash
    git clone https://github.com/cdterry87/Memento.git
    cd Memento
    ```

2. Install the PHP dependencies, using a temporary container that includes Composer:

    ```bash
    docker run --rm -u "$(id -u):$(id -g)" -v "$(pwd):/var/www/html" -w /var/www/html laravelsail/php85-composer:latest composer install --ignore-platform-reqs
    ```

3. Create your environment file:

    ```bash
    cp .env.example .env
    ```

4. Start the containers. The first run builds the images and takes a few minutes:

    ```bash
    ./vendor/bin/sail up -d
    ```

5. Generate the app key, run migrations, and install and build the frontend:

    ```bash
    ./vendor/bin/sail artisan key:generate
    ./vendor/bin/sail artisan migrate
    ./vendor/bin/sail npm ci
    ./vendor/bin/sail npm run build
    ```

6. Open http://localhost.

### Port conflicts

If another project already uses one of Sail's default ports, `sail up` fails with "port is already allocated". Override the port in `.env`, then run `./vendor/bin/sail up -d` again:

| Service | Variable | Default |
|---|---|---|
| App | `APP_PORT` | 80 |
| MySQL | `FORWARD_DB_PORT` | 3306 |
| Mailpit SMTP | `FORWARD_MAILPIT_PORT` | 1025 |
| Mailpit dashboard | `FORWARD_MAILPIT_DASHBOARD_PORT` | 8025 |
| Vite | `VITE_PORT` | 5173 |
| RustFS API | `FORWARD_RUSTFS_PORT` | 9000 |
| RustFS console | `FORWARD_RUSTFS_CONSOLE_PORT` | 9001 |

If you change `APP_PORT`, update `APP_URL` to match.

## Everyday development

Tip: add an alias, such as `alias sail='./vendor/bin/sail'`, to shorten these commands.

| Task | Command |
|---|---|
| Start the containers | `./vendor/bin/sail up -d` |
| Stop the containers | `./vendor/bin/sail stop` |
| Run the Vite dev server with hot reload | `./vendor/bin/sail npm run dev` |
| Run Artisan commands | `./vendor/bin/sail artisan <command>` |
| Read email sent by the app | Open the Mailpit dashboard, at http://localhost:8025 by default |

## Quality checks

CI runs all of these on every pull request.

| Check | Command |
|---|---|
| PHP tests (Pest) | `./vendor/bin/sail artisan test` |
| PHP formatting (Pint) | `./vendor/bin/sail composer lint` to fix, `lint:check` to check |
| PHP static analysis (Larastan) | `./vendor/bin/sail composer analyse` |
| Frontend tests (Vitest) | `./vendor/bin/sail npm run test` |
| Frontend linting (ESLint) | `./vendor/bin/sail npm run lint` to fix, `lint:check` to check |
| Frontend formatting (Prettier) | `./vendor/bin/sail npm run format` to fix, `format:check` to check |
| Type checking (`vue-tsc`) | `./vendor/bin/sail npm run typecheck` |

PHP tests run against a separate `testing` database, which Sail's MySQL container creates automatically.
