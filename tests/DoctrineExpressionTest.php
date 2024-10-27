<?php

namespace Ucscode\Tests\DoctrineExpression;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Platforms\MySQLPlatform;
use Doctrine\DBAL\Platforms\PostgreSQL120Platform;
use Doctrine\DBAL\Platforms\SqlitePlatform;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Ucscode\DoctrineExpression\DoctrineExpression;
use Ucscode\DoctrineExpression\DriverEnum;

class DoctrineExpressionTest extends TestCase
{
    public function testQuerySelectionForSpecifiedPlatform(): void
    {
        // Test entity manager using pdo_pgsql driver
        $entityManagerPostgres = $this->createEntityManagerMockeryFor(new PostgreSQL120Platform());
        $expressionPostgres = new DoctrineExpression($entityManagerPostgres);
        $this->configureDoctrineExpression($expressionPostgres);

        $this->assertSame(DriverEnum::PDO_PGSQL, $expressionPostgres->getCompatibleResult());

        // Test entity manager using pdo_mysql driver
        $entityManagerMysql = $this->createEntityManagerMockeryFor(new MySQLPlatform());
        $expressionMysql = new DoctrineExpression($entityManagerMysql);
        $this->configureDoctrineExpression($expressionMysql);

        $this->assertSame(DriverEnum::PDO_MYSQL, $expressionMysql->getCompatibleResult());

        // Test entity manager using pdo_sqlite driver
        $entityManagerSqlite = $this->createEntityManagerMockeryFor(new SqlitePlatform());
        $expressionSqlite = new DoctrineExpression($entityManagerSqlite);
        $this->configureDoctrineExpression($expressionSqlite);

        $this->assertSame(DriverEnum::PDO_SQLITE, $expressionSqlite->getCompatibleResult());

        $this->assertTrue($expressionMysql->getDriverEnum() === DriverEnum::PDO_MYSQL);
    }

    /**
     * We create an entity manager mockery that returns the chosen platform to run our test
     *
     * @param AbstractPlatform $platform
     * @return EntityManagerInterface
     */
    protected function createEntityManagerMockeryFor(AbstractPlatform $platform): EntityManagerInterface
    {
        $connection = $this->createMock(Connection::class);
        $entityManager = $this->createMock(EntityManagerInterface::class);

        $entityManager
            ->method('getConnection')
            ->willReturn($connection)
        ;

        $connection
            ->method('getDatabasePlatform')
            ->willReturn(new $platform())
        ;

        /**
         * @var EntityManagerInterface $entityManager
         */
        return $entityManager;
    }

    /**
     * @param DoctrineExpression $expression
     * @return void
     */
    protected function configureDoctrineExpression(DoctrineExpression $expression): void
    {
        /*
         * In real life, each define query (callable) would most likely return a doctrine result.
         * But for this test, we only care about which definition will be selected by the compactibility checker
         */
        $expression->defineQuery(DriverEnum::PDO_MYSQL, function() {
            return DriverEnum::PDO_MYSQL;
        });

        $expression->defineQuery(DriverEnum::PDO_PGSQL, function() {
            return DriverEnum::PDO_PGSQL;
        });

        $expression->defineQuery(DriverEnum::PDO_SQLITE, function() {
            return DriverEnum::PDO_SQLITE;
        });

        $expression->defineQuery(DriverEnum::PDO_SQLSRV, function() {
            return DriverEnum::PDO_SQLSRV;
        });

        $expression->defineQuery(DriverEnum::PDO_OCI, function() {
            return DriverEnum::PDO_OCI;
        });
    }
}