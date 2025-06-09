# MAT - 郵件追蹤與寄送系統

## Introduction

MAT (Mail Analytics and Tracking) is a web-based application designed for managing email marketing campaigns, tracking email opens and clicks, and scheduling email delivery. It provides a platform for users to create projects, manage email templates, upload recipient lists, and monitor the performance of their email campaigns.

## Prerequisites

To run this project, you will need the following:

*   **PHP**: PHP 7.4 or newer is recommended (ideally PHP 8.0+).
    *   Required PHP extensions: `mbstring`, `pdo_mysql`, `fileinfo`, `xml`, `zip`.
*   **MySQL Database**: A MySQL or MariaDB database server.
*   **Composer**: For managing PHP dependencies.
*   **Web Server**: Apache or Nginx with PHP support.

## Installation & Setup

1.  **Clone/Download**:
    *   Clone the repository: `git clone <repository_url>`
    *   Or download the project files and extract them to your web server's directory.

2.  **Install Dependencies**:
    *   Navigate to the project root directory in your terminal.
    *   Install PHP dependencies using Composer:
        ```bash
        php composer.phar install 
        ```
        (If you installed Composer globally, use `composer install`). This will install PHPMailer, HTMLPurifier, and potentially other libraries like phpdotenv.

3.  **Database Setup**:
    *   Create a new MySQL database for the project (e.g., `mat`).
    *   You will need to manually create the following tables in your database. The basic schema structure involves these tables:
        *   `users`: Stores user account information (id, email, password, role type, status).
        *   `projects`: Stores project details (id, user_id, name, description).
        *   `templates`: Stores email templates (id, project_id, subject, content).
        *   `recipients`: Stores recipient information (id, project_id, name, email).
        *   `mail_queue`: Manages the queue of emails to be sent (id, project_id, template_id, recipient_id, scheduled_at, status, sent_at, error_message).
        *   `mail_settings`: Stores SMTP server settings for users (id, user_id, type, smtp_host, smtp_port, smtp_user, smtp_pass, sender_name, sender_email, use_tls).
        *   `login_log`: Records user login attempts (id, user_id, email, ip, login_time, status).
        *   `mail_open_log`: Tracks email open events (id, project_id, recipient_id, template_id, opened_at, ip, user_agent).
        *   `mail_click_log`: Tracks email click events (id, project_id, recipient_id, template_id, clicked_at, clicked_url, ip, user_agent).
    *   *(Ideally, a `schema.sql` file would be provided for easier setup. For now, table creation is manual based on application needs.)*

4.  **Environment Variables**:
    *   It is highly recommended to use environment variables for configuration, especially for database credentials.
    *   If an `.env.example` file exists in the project root, copy it to a new file named `.env`:
        ```bash
        cp .env.example .env
        ```
    *   Edit the `.env` file to set your database host, name, user, and password.
    *   Refer to `SETUP_GUIDE.md` for detailed instructions on setting up environment variables and how they are used by `dbconnect.php`.

5.  **Web Server Configuration**:
    *   Configure your web server (Apache or Nginx) to use the project's root directory as the document root (or a subdirectory if you place the project within one).
    *   Ensure URL rewriting is enabled if `.htaccess` (for Apache) or specific Nginx rules are provided for clean URLs (though this project currently uses direct PHP file access).

6.  **Permissions**:
    *   The `purifier_cache/` directory (located in the project root) needs to be writable by the web server user. This directory is used by HTMLPurifier for caching.
        ```bash
        sudo chmod -R 775 purifier_cache/
        sudo chown -R www-data:www-data purifier_cache/  # Adjust www-data if your server uses a different user
        ```

## Running the Mail Cron Job

The application uses a cron job to process the email queue and send scheduled emails.

*   The script responsible for this is `mail_cron.php`.
*   You need to set up a cron job on your server to execute this script periodically. For example, to run it every 5 minutes:
    ```cron
    */5 * * * * /usr/bin/php /path/to/your/project/root/mail_cron.php >> /path/to/your/project/root/cron_log.txt 2>&1
    ```
    *   Replace `/usr/bin/php` with the actual path to your PHP executable if different.
    *   Replace `/path/to/your/project/root/` with the absolute path to the project's root directory.
    *   It's recommended to log the output of the cron job.

## Security Notes

*   **Database Credentials**: Handled via environment variables (see `SETUP_GUIDE.md` and `.env` configuration).
*   **XSS Protection**: HTMLPurifier is used to sanitize HTML email templates before they are stored and displayed, mitigating Cross-Site Scripting risks (`insert_template.php`, `update_template.php`).
*   **CSRF Protection**: The application implements CSRF (Cross-Site Request Forgery) tokens in forms to protect against malicious state-changing requests (`csrf_guard.php`).
*   **Error Handling**: A custom error handler (`error_handler.php`) is in place to manage errors gracefully and prevent sensitive information leakage.

---
*This README provides a basic guide. Further details on specific configurations or advanced usage might be found in other documentation files or code comments.*
