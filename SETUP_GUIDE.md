# Setup Guide

## Environment Variables for Database Connection

This project uses environment variables to configure the database connection. This is a more secure practice than hardcoding credentials directly in the source code.

The following environment variables are used by `dbconnect.php`:

- `DB_HOST`: The hostname or IP address of your database server (e.g., `localhost`, `127.0.0.1`).
- `DB_NAME`: The name of the database to connect to (e.g., `mat`).
- `DB_USER`: The username for database authentication (e.g., `mat_user`).
- `DB_PASS`: The password for the specified database user.

### How to Set Environment Variables

There are several ways to set these environment variables, depending on your development or deployment environment:

#### 1. Using `.env` files (Recommended for Local Development)

You can use a `.env` file to store your environment variables locally. This file should be placed in the root of your project and **should not be committed to version control** (add `.env` to your `.gitignore` file).

A library like `phpdotenv` can be used to load these variables automatically.

**Example `.env` file:**

```
DB_HOST=localhost
DB_NAME=mydatabase
DB_USER=myuser
DB_PASS=mypassword
```

To use `phpdotenv`:
1. Install it via Composer: `composer require vlucas/phpdotenv`
2. Add the following to the beginning of your PHP script (e.g., at the top of `dbconnect.php` or a central bootstrap file):

   ```php
   <?php
   require_once __DIR__ . '/vendor/autoload.php'; // If you're using Composer

   $dotenv = Dotenv\Dotenv::createImmutable(__DIR__); // Specify the directory where .env is located
   $dotenv->load();
   // ... rest of your dbconnect.php or application logic
   ?>
   ```

#### 2. Web Server Configuration

For production or staging environments, it's often better to configure environment variables directly in your web server.

**Apache:**

You can use the `SetEnv` directive in your Apache configuration file (e.g., within a `<Directory>` block in your virtual host configuration) or in an `.htaccess` file (if `AllowOverride` is configured appropriately).

Example in `.htaccess` or Apache config:

```apache
SetEnv DB_HOST "your_db_host"
SetEnv DB_NAME "your_db_name"
SetEnv DB_USER "your_db_user"
SetEnv DB_PASS "your_db_password"
```

**Nginx:**

For Nginx with PHP-FPM, you can pass environment variables using the `fastcgi_param` directive in your Nginx server block configuration.

Example in Nginx site configuration:

```nginx
server {
    # ... other configurations ...
    location ~ \.php$ {
        # ... other fastcgi settings ...
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;

        fastcgi_param DB_HOST "your_db_host";
        fastcgi_param DB_NAME "your_db_name";
        fastcgi_param DB_USER "your_db_user";
        fastcgi_param DB_PASS "your_db_password";
    }
}
```

#### 3. System-Level Environment Variables

You can also set environment variables at the operating system level. The method varies depending on your OS (e.g., using `export` in `.bashrc` or `.profile` on Linux/macOS, or via System Properties on Windows). This approach makes the variables available to any process run by the user.

**Example (Linux/macOS - add to `.bashrc` or `.zshrc`):**

```bash
export DB_HOST="your_db_host"
export DB_NAME="your_db_name"
export DB_USER="your_db_user"
export DB_PASS="your_db_password"
```
Remember to `source ~/.bashrc` or open a new terminal session for these changes to take effect.

### Fallback Mechanism

If these environment variables are not set, `dbconnect.php` will fall back to using the hardcoded credentials. **This fallback is intended for development or debugging purposes ONLY and should NOT be relied upon in a production environment.** Always configure environment variables for production deployments to ensure security and proper configuration.
```
