# PayPoint Sendgrid Notification

PayPoint sendgrid notification package

## Installation

The recommended method to install LaravelRepository is with composer

```bash
php composer require mblsolutions/paypoint-sendgrid-notification
```
### Laravel without auto-discovery

If you don't use auto-discovery, add the ServiceProvider to the providers array in config/app.php

```php
\MBLSolutions\SendgridNotification\SendgridNotificationServiceProvider::class,
```

### Package configuration

```php
Copy the package configuration to your local config directory.
```

```bash
php artisan vendor:publish --tag=sendgrid-notification-config
```

### Database Driver

If you would like to use the Database driver to store your notification logs, you will first need to create and run the database
driver migration.

```bash
php artisan paypoint-sendgrid-notification:database:table
```

This will create a new migration in `database/migrations`, after creating this migration run the database migrations to
create the new table.

````bash
php artisan migrate
````

## Usage

The configuration and setup can be adjusted in the notification config file located in `config/notification.php`. We 
recommend reading through the config file before enabling notification to ensure you have the optimum setup. 

### Enable Notification Service

In environment setting, you need to change MAIL_MAILER from smtp to sendgrid to enable the service. The credentials is neede to add in your .env file.

```dotenv
MAIL_MAILER=sendgrid
SENDGRID_DSN=sendgrid+api://[SECRET_KEY]@default
#mblsolutions will be sat by default if .env is not sat in the below
SENDGRID_EMAIL_IDENTIFIER=mblsolutions
```

In mail setting, you need to add sendgrid in mailer.
```php
'mailers' => [
    //... other mailer
    'sendgrid' => [
        'transport' => 'sendgrid',
        'dsn' => env('SENDGRID_DSN'),
    ],
```

## License

Notification is free software distributed under the terms of the MIT license.