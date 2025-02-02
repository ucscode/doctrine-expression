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

To install `Doctrine\Expression`, run the following command in your terminal:

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
$result = $queryBuilder
    ->where('JSON_CONTAINS(u.roles, :roles) = 1')
    ->setParameter('roles', json_encode('ROLE_USER'))
;
```

This query works well with MySQL. However, if you decide to switch to PostgreSQL, you might change your `.env` configuration to:

```dotenv
DATABASE_URL="postgresql://app:!ChangeMe!@127.0.0.1:5432/app"
```

### Adapting the Query for PostgreSQL

Switching to PostgreSQL requires some modifications due to syntax differences. For example, PostgreSQL handles JSON operations differently:

```php
$result = $queryBuilder
    ->where('u.roles @> :role')
    ->setParameter('role', json_encode(['ROLE_USER']))
;
```

### Leveraging DoctrineExpression

Instead of rewriting queries for each database driver, you can utilize `Doctrine\Expression` to manage this complexity efficiently.

#### Example Usage:

```php
use Ucscode\Doctrine\Expression\Expression;
use Ucscode\Doctrine\Expression\DriverEnum;

// Initialize the Doctrine\Expression with the EntityManager
$expression = new Expression($entityManager);

// Define the MySQL query
$expression->defineQuery(DriverEnum::PDO_MYSQL, function(Expression $expr) {
    return $expr->getEntityManager()
        ->createQueryBuilder()
            ->select('u')
            ->from('App\Entity\User', 'u')
            ->where('JSON_CONTAINS(u.roles, :roles) = 1')
    ;
});

// Define the PostgreSQL query
$expression->defineQuery(DriverEnum::PDO_PGSQL, function(Expression $expr) {
    return $expr->getEntityManger()
        ->createQueryBuilder();
            ->select('u')
            ->from('App\Entity\User', 'u')
            ->where('u.roles @> :roles')
    ;
});

// Fetch the compatible query builder based on the current database platform
$result = $expression->getCompatibleResult()
    ->setParameter('roles', json_encode(['ROLE_USER']))
    ->getQuery()
    ->getResult();
```

The `getCompatibleResult()` method checks which platform is active (e.g., MySQL or PostgreSQL) and selects the corresponding query that was previously defined using `defineQuery()`.

### Providing extra Parameters to the query

You might want a set of data to be available when defining your query in a closure. For that, you can pass an array with the parameter as second argument of the `Doctrine\Expression` or you can call the `Doctrine\Expression::set()` method

#### Example:

```php
use Ucscode\Doctrine\Expression\Expression;
use Ucscode\Doctrine\Expression\DriverEnum;

// setting with __construct() argument
$expression = new Expression($entityManager, [
    'amount' => 30000,
    'roles' => ['ROLE_ADMIN', 'ROLE_USER'],
    'entity' => new stdClass(),
])

// using the setter
$expression->set('dev.name', 'Ucscode');

$expression->defineQuery(DriverEnum::PDO_PGSQL, function(Expression $expr) {
    $expr->get('dev.name'); // Ucscode
    $expr->get('entity'); // stdClass instance
});
```

For drivers that might use the same syntax, the `getDefinedQuery()` can be used to eliminate the need to write repetitive patterns. 

```php
$expression->defineQuery(DriverEnum::PDO_SQLITE, function($em, $self) {
    return $self->getDefinedQuery(DriverEnum::PDO_MYSQL);
})
```

### Conclusion

By using `Doctrine\Expression`, you can maintain a clean and consistent codebase while easily adapting to changes in database platforms. This library not only saves time but also enhances collaboration among developers familiar with different SQL dialects.

----

### Helpful Snippet for Common Use Case

How to find users by one or more roles in Symfony using `Doctrine\Expression`

##### Note:

You can handle PostgreSQL JSON/JSONB using libraries like one of the following:

- [https://github.com/martin-georgiev/postgresql-for-doctrine](https://github.com/martin-georgiev/postgresql-for-doctrine)
- [https://github.com/ScientaNL/DoctrineJsonFunctions](https://github.com/ScientaNL/DoctrineJsonFunctions)

The snippet below does not employ any external libraries and works without doctrine extension dependencies but only for this simple case.

```php
class UserRepository extends ServiceEntityRepository
{   
    /**
     * Find all users matching any of the provided role(s)
     *
     * @param string|array $roles
     * @return array
     */
    public function findByRoles(string|array $roles): array
    {
        $roles = array_unique(array_values(is_array($roles) ? $roles : [$roles]));

        $expression = (new DoctrineExpression($this->getEntityManager()))
            ->defineQuery(DriverEnum::PDO_MYSQL, fn () => $this->mysqlExpression($roles)) // When using MySQL
            ->defineQuery(DriverEnum::PDO_PGSQL, fn () => $this->pgsqlExpression($roles)) // When using PostgreSQL
        ;

        return $expression->getCompatibleResult();
    }

    /**
     * Expression used if database engine is MYSQLI
     *
     * @param array $roles
     * @return array
     */
    private function mysqlExpression(array $roles): array 
    {
        $condition = implode(' OR ', array_map(
            fn (int $key, string $value) => sprintf('entity.roles LIKE :%s%d', $value, $key), 
            array_keys($roles), 
            $roles
        ));

        $builder = $this->createQueryBuilder('entity')->where($condition);

        foreach ($roles as $key => $role) {
            $builder->setParameter(sprintf('%s%d', $role, $key), str_replace(':role', $role, '%":role"%'));
        }

        return $builder->getQuery()->getResult();
    }

    /**
     * Expression used if database engine is PostgreSQL
     *
     * @param array $roles
     * @return array
     */
    private function pgsqlExpression(array $roles): array 
    {
        // Get the table name from the entity's metadata
        $tableName = $this->getEntityManager()->getClassMetadata(User::class)->getTableName();

        $sql = <<<SQL
            SELECT "$tableName".id
            FROM "$tableName"
            WHERE %s
        SQL;

        $condition = implode(' OR ', array_map(function (string $value) use ($tableName) {
            return sprintf(
                '"%s".roles::jsonb @> \'%s\'::jsonb', 
                $tableName, 
                json_encode([$value])
            );
        }, $roles));

        $nativeSQL = sprintf($sql, $condition);
        $result = $this->getEntityManager()->getConnection()->executeQuery($nativeSQL);

        return $this->findBy([
            'id' => array_map(
                fn (array $user) => $user['id'], 
                $result->fetchAllAssociative()
            )
        ]);
    }
}
```

---

## License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## Contributing

Contributions are welcome!

## Acknowledgments

- [Doctrine ORM](https://github.com/doctrine/orm/) for providing a powerful and flexible object-relational mapping layer for PHP.

