# ruimtes-api

## Building

Run `composer install` to build the required libraries for the project.

When using MySQL as database make sure you have the correct database and user defined in the `.env`-file.
In the same file also update the `APP_KEY` to make sure all traffic is encrypted correctly.

To run the application you can use:
`php -S localhost:8000 -t public`
