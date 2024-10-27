<?php

namespace Ucscode\DoctrineExpression;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\DBAL\Exception;

/**
 * The DoctrineExpression class provides an abstraction layer for executing driver-specific queries
 * within a Doctrine ORM setup.
 *
 * This allows for defining multiple query expressions tailored to different database
 * drivers (e.g., MySQL, PostgreSQL, SQLite) and dynamically selecting and executing
 * the appropriate one based on the active database connection.
 *
 * This class is particularly useful in applications where database engines might change, or where
 * cross-database compatibility is necessary, avoiding SQL errors by selecting syntax-specific queries.
 *
 * @author Uchenna Ajah (Ucscode) <uche23mail@gmail.com>
 */
class DoctrineExpression
{
    private array $queries = [];

    public function __construct(protected EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * Define query or expression logic for different drivers.
     *
     * @param DriverEnum $driver The enum of the database driver
     * @param callable $callback The callback to execute for the driver (e.g., complex query logic)
     *
     * @return static
     */
    public function defineQuery(DriverEnum $driver, callable $callback): static
    {
        $this->queries[$driver->value] = $callback;

        return $this;
    }

    /**
     * Executes and returns the result of the query compatible with the current driver.
     *
     * @return mixed The result of the query
     * @throws Exception if there is no defined query for the current driver
     */
    public function getCompatibleResult(): mixed
    {
        // Get the current database driver
        $connection = $this->entityManager->getConnection();

        foreach ($this->queries as $platformFqcn => $callback) {
            if (is_a($connection->getDatabasePlatform(), $platformFqcn)) {
                return call_user_func($callback, $this->entityManager);
            }
        }

        return null;
    }

    public function getDriverEnum(): ?DriverEnum
    {
        $platform = $this->entityManager->getConnection()->getDatabasePlatform();

        foreach (DriverEnum::cases() as $driver) {
            if (is_a($platform, $driver->value)) {
                return $driver;
            }
        }

        return null;
    }
}
