<?php

declare(strict_types=1);

namespace Akeneo\Catalogs\Test\Integration\Infrastructure\Persistence;

use Akeneo\Catalogs\Infrastructure\Persistence\DeleteCatalogQuery;
use Akeneo\Catalogs\ServiceAPI\Command\CreateCatalogCommand;
use Akeneo\Catalogs\ServiceAPI\Messenger\CommandBus;
use Akeneo\Catalogs\Test\Integration\IntegrationTestCase;
use Doctrine\DBAL\Connection;
use Ramsey\Uuid\Uuid;

/**
 * @copyright 2022 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class DeleteCatalogQueryTest extends IntegrationTestCase
{
    private ?DeleteCatalogQuery $query;
    private ?Connection $connection;
    private ?CommandBus $commandBus;

    public function setUp(): void
    {
        parent::setUp();

        $this->purgeDataAndLoadMinimalCatalog();

        $this->connection = self::getContainer()->get(Connection::class);
        $this->query = self::getContainer()->get(DeleteCatalogQuery::class);
        $this->commandBus = self::getContainer()->get(CommandBus::class);
    }

    public function testItDeletesACatalog(): void
    {
        $id = 'db1079b6-f397-4a6a-bae4-8658e64ad47c';
        $this->createUser('test');
        $this->commandBus->execute(new CreateCatalogCommand(
            $id,
            'Store US',
            'test',
        ));

        $this->assertCatalogExists($id);

        $this->query->execute($id);

        $this->assertCatalogDoesNotExists($id);
    }

    private function assertCatalogExists(string $id): void
    {
        $query = <<<SQL
        SELECT COUNT(*)
        FROM akeneo_catalog catalog
        WHERE id = :id
        SQL;

        $count = (int) $this->connection->executeQuery($query, [
            'id' => Uuid::fromString($id)->getBytes(),
        ])->fetchOne();

        $this->assertSame(1, $count);
    }

    private function assertCatalogDoesNotExists(string $id): void
    {
        $query = <<<SQL
        SELECT COUNT(*)
        FROM akeneo_catalog catalog
        WHERE id = :id
        SQL;

        $count = (int) $this->connection->executeQuery($query, [
            'id' => Uuid::fromString($id)->getBytes(),
        ])->fetchOne();

        $this->assertSame(0, $count);
    }
}
