# Slack Dones


####How do I run it?
You need at least php **5.4.*** with **SQLite extension** enabled and **Composer**

    composer install 
    sqlite3 app.db < resources/sql/schema.sql
    php -S 127.0.0.1:9001 -t web/

You can install the project also as a composer project

    composer create-project vesparny/silex-simple-rest

Your api is now available at http://localhost:9001/api/v1.


####Run tests
Some tests were written, and all CRUD operations are fully tested :)

From the root folder run the following command to run tests.

    vendor/bin/phpunit 


####What you will get
The api will respond to

    POST ->   http://localhost:9001/api/v1/user/get-dones

Your request should have 'Content-Type: application/json' header.
Your api is CORS compliant out of the box, so it's capable of cross-domain communication.

Try with curl:

    #POST
    curl -X POST http://localhost:9001/api/v1/user/get-dones -d '{"token" : "r8NaMS0aiTWkS4yl6EwaE2ln", "text" : "@mezencio"}' -H 'Content-Type: application/json' -w "\n"


## License

see LICENSE file.
