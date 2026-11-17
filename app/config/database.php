<?php

return [
	"DB_HOST" =>$_ENV['DB_HOST'] ?: 'localhost',
	"DB_USER" =>$_ENV['DB_USER'] ?: 'root',
	"DB_PASSWORD" =>$_ENV["DB_PASSWORD"] ?: "",
	"DB_DATABASE" =>$_ENV["DB_DATABASE"] ?: 'php_database'
];