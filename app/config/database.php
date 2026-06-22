<?php

return [
	"DB_HOST" => getenv('DB_HOST') ?? 'localhost',
	"DB_USER" => getenv('DB_USER') ?? 'root',
	"DB_PASSWORD" => getenv("DB_PASSWORD") ?? "",
	"DB_DATABASE" => getenv("DB_DATABASE") ?? 'php_database'
];