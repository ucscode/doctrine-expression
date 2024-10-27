# DoctrineExpression

<img src="./assets/design.webp" width="300"/>

`DoctrineExpression` is a PHP library designed to facilitate cross-database compatibility when constructing SQL queries using Doctrine ORM. By abstracting database-specific syntax, developers can write more maintainable code that seamlessly adapts to different database drivers.

## Table of Contents

- [Features](#features)
- [Requirements](#requirements)
- [Installation](#installation)
- [Usage](#usage)
- [License](#license)
- [Contributing](#contributing)
- [Acknowledgments](#acknowledgments)

## Features

- Write database-agnostic queries that adapt to MySQL, PostgreSQL, SQLite, and more.
- Define SQL queries for each supported database platform using simple callable functions.
- Easily integrates into your existing Doctrine setup.

## Requirements

- PHP 8.2 or higher
- Composer

## Installation

To install `DoctrineExpression`, run the following command in your terminal:

```bash
composer require ucscode/doctrine-expression
```

## Usage

### Scenario: Changing Database Drivers

Consider a scenario where your application is initially configured to use MySQL. The following configuration might be present in your Symfony `.env` file:

```dotenv
DATABASE_URL="mysql://app:!ChangeMe!@127.0.0.1:3306/app"
```

You might have a query that leverages JSON capabilities specific to MySQL:

```php
$queryBuilder = $entityManager->createQueryBuilder();

$result = $queryBuilder->select('u')
    ->from('App\Entity\User', 'u')
    ->where('JSON_CONTAINS(u.roles, :roles) = 1')
    ->setParameter('roles', json_encode('ROLE_USER'))
    ->getQuery()
    ->getResult();
```

This query works well with MySQL. However, if you decide to switch to PostgreSQL, you might change your `.env` configuration to:

```dotenv
DATABASE_URL="postgresql://app:!ChangeMe!@127.0.0.1:5432/app"
```

### Adapting the Query for PostgreSQL

Switching to PostgreSQL requires some modifications due to syntax differences. For example, PostgreSQL handles JSON operations differently:

```php
$queryBuilder = $entityManager->createQueryBuilder();

$result = $queryBuilder->select('u')
    ->from('App\Entity\User', 'u')
    ->where('u.roles @> :role')
    ->setParameter('role', json_encode(['ROLE_USER']))
    ->getQuery()
    ->getResult();
```

### Leveraging DoctrineExpression

Instead of rewriting queries for each database driver, you can utilize `DoctrineExpression` to manage this complexity efficiently.

#### Example Usage:

```php
use Ucscode\DoctrineExpression\DoctrineExpression;
use Ucscode\DoctrineExpression\DriverEnum;

// Initialize the DoctrineExpression with the EntityManager
$expression = new DoctrineExpression($entityManager);

// Define the MySQL query
$expression->defineQuery(DriverEnum::PDO_MYSQL, function($entityManager) {
    $queryBuilder = $entityManager->createQueryBuilder();

    return $queryBuilder->select('u')
        ->from('App\Entity\User', 'u')
        ->where('JSON_CONTAINS(u.roles, :roles) = 1')
        ->setParameter('roles', json_encode('ROLE_USER'))
        ->getQuery()
        ->getResult();
});

// Define the PostgreSQL query
$expression->defineQuery(DriverEnum::PDO_PGSQL, function($entityManager) {
    $queryBuilder = $entityManager->createQueryBuilder();

    return $queryBuilder->select('u')
        ->from('App\Entity\User', 'u')
        ->where('u.roles @> :role')
        ->setParameter('role', json_encode(['ROLE_USER']))
        ->getQuery()
        ->getResult();
});

// Execute the compatible query based on the current database platform
$result = $expression->getCompatibleResult();
```

The `getCompatibleResult()` method checks which platform is active (e.g., MySQL or PostgreSQL) and selects the corresponding query that was previously defined using `defineQuery()`.

### Conclusion

By using `DoctrineExpression`, you can maintain a clean and consistent codebase while easily adapting to changes in database platforms. This library not only saves time but also enhances collaboration among developers familiar with different SQL dialects.

## License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## Contributing

Contributions are welcome!

## Acknowledgments

- [Doctrine ORM](https://github.com/doctrine/orm/) for providing a powerful and flexible object-relational mapping layer for PHP.

