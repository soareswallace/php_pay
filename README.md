# PHP Pay
This is a project build from the ground up with Laravel Framework.

To get start and up the server, go to the root folder and them run:

`docker-compose build && docker-compose up`

This command will bring the application up, perform migrations and populate the test database. As configured in `UsersTableSeeder.php` this project is populating the test database with 30 users. The 30th user is considered a "Lojista".

To run the tests, you need to run on the repository root folder the command below:

`docker-compose exec php php /var/www/html/artisan test`

To test the API, POST this JSON in `/transaction` endpoint:

````
{
    "value" : 100.00,
    "payer" : 4,
    "payee" : 15
}
