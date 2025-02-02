<?php

namespace Ucscode\Doctrine\Expression;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\DBAL\Exception;

/**
 * The Doctrine Expression class provides an abstraction layer for executing driver-specific queries
 * within a Doctrine ORM setup.
 *
 * This allows for defining multiple query expressions tailored to different database
 * drivers (e.g., MySQL, PostgreSQL, SQLite) and dynamically selecting and executing
 * the appropriate one based on the active database connection.
 *
 * This class is particularly useful in applications where database engines might change, or where
 * cross-database compatibility is necessary, avoiding SQL errors by selecting syntax-specific queries.
 *
 * @author Uchenna Ajah<uche23mail@gmail.com>
 */
class Expression
{
    /**
     * The queries for different db-engines
     *
     * @var array<string, mixed>
     */
    protected array $queries = [];

    /**
     * Random values to use for simplified querying
     *
     * @var array<string, mixed>
     */
    protected array $container = [];

    public function __construct(protected EntityManagerInterface $entityManager, array $container = [])
    {
        foreach ($container as $key => $value) {
            $this->set($key, $value);
        }
    }

    /**
     * Get the entity manager instance
     *
     * @return EntityManagerInterface
     */
    public function getEntityManager(): EntityManagerInterface
    {
        return $this->entityManager;
    }

    /**
     * Sets a value in the container.
     *
     * @param string $name  The key name.
     * @param mixed  $value The value to store.
     * @return static
     */
    public function set(string $name, mixed $value): static
    {
        $this->container[$name] = $value;

        return $this;
    }

    /**
     * Retrieves a value from the container.
     *
     * @param string $name The key name.
     * @return mixed The stored value.
     * @throws \InvalidArgumentException If the key does not exist.
     */
    public function get(string $name): mixed
    {
        if (!$this->has($name)) {
            throw new \InvalidArgumentException("The key '{$name}' does not exist in the container.");
        }

        return $this->container[$name];
    }

    /**
     * Checks if a key exists in the container.
     *
     * @param string $name The key name.
     * @return bool
     */
    public function has(string $name): bool
    {
        return array_key_exists($name, $this->container);
    }

    /**
     * Removes a key from the container if it exists.
     *
     * @param string $name The key name.
     * @return static
     */
    public function remove(string $name): static
    {
        if ($this->has($name)) {
            unset($this->container[$name]);
        }

        return $this;
    }

    /**
     * Define query or expression logic for different drivers.
     *
     * @param DriverEnum $driver The enum of the database driver
     * @param callable $callback The callback to execute for the driver (e.g., complex query logic)
     * @return static
     */
    public function defineQuery(DriverEnum $driver, callable $callback): static
    {
        $this->queries[$driver->value] = $callback;

        return $this;
    }

    /**
     * Get the result of the query defined for a specific driver
     *
     * @param DriverEnum $driver
     * @return mixed
     */
    public function getDefinedQuery(DriverEnum $driver): mixed
    {
        $callback = $this->queries[$driver->value] ?? null;

        return is_callable($callback) ? call_user_func($callback, $this) : null;
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

        foreach (array_keys($this->queries) as $platformFqcn) {
            if (is_a($connection->getDatabasePlatform(), $platformFqcn)) {
                $driverEnum = $this->getDriverEnumByPlatformFqcn($platformFqcn);

                return $this->getDefinedQuery($driverEnum);
            }
        }

        return null;
    }

    /**
     * Returns an enumerator representing the driver that doctrine is currently using
     *
     * @return DriverEnum|null
     */
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

    /**
     * Returns an enumerator designated to the platform passed as argument
     *
     * @param string $platformFqcn  A valid database platform (FQCN) defined by doctrine which is a subclass of AbstractPlatform
     * @return DriverEnum
     * @throws \InvalidArgumentException If the database platform does not exist
     * @see \Doctrine\DBAL\Platforms\AbstractPlatform
     * @internal
     */
    protected function getDriverEnumByPlatformFqcn(string $platformFqcn): DriverEnum
    {
        foreach (DriverEnum::cases() as $driver) {
            if (is_a($platformFqcn, $driver->value, true)) {
                return $driver;
            }
        }

        throw new \InvalidArgumentException(sprintf('Unrecognized doctrine platform "%s"', $platformFqcn));
    }
}
