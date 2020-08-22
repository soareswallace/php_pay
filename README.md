# PHP Pay
This is a project build from the ground up with Laravel Framework.

To get start and up the server, go to the root folder and them run:

`docker-compose build && docker-compose up`

This command will bring the application up. There tables already committed to this project, but if you want to migrate
and regenerate the test base you may want to run this on the project root folder:

`docker-compose exec php php /var/www/html/artisan migrate:fresh --seed`

The command above will regenerate a testing database with 30 instances as configured at `UsersTableSeeder.php`.

To run the tests, you need to run on the repository root folder the command below:

`docker-compose exec php php /var/www/html/artisan test`

To test the API, POST this JSON in `/api/transaction` endpoint:

````
{
    "value" : 100.00,
    "payer" : 4,
    "payee" : 15
}
