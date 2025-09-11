<?php

// DB connection
define("DB_HOST", "Your_database_server");
define("DB_USER", "Your_username");
define("DB_PASS", "Your_password");
define("DB_NAME", "Your_database_name");

// public url for Nginx
define('BASEURL', 'http:/{name}.test:8080/'); // base url
define('ABSOLUTURL', 'http:/{name}.test:8080/public/'); // asset file, like js and css