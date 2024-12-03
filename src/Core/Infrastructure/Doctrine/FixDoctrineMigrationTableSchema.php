<?php

declare(strict_types=1);

namespace App\Core\Infrastructure\Doctrine;

use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Schema\SchemaException;
use Doctrine\Migrations\DependencyFactory;
use Doctrine\Migrations\Metadata\Storage\TableMetadataStorageConfiguration;
use Doctrine\ORM\Tools\Event\GenerateSchemaEventArgs;
use Webmozart\Assert\Assert;

/** @see https://github.com/doctrine/migrations/issues/1406 */
final class FixDoctrineMigrationTableSchema
{
    private TableMetadataStorageConfiguration $configuration;

    public function __construct(
        private readonly DependencyFactory $dependencyFactory,
    ) {
        $configuration = $this->dependencyFactory->getConfiguration()->getMetadataStorageConfiguration();

        Assert::notNull($configuration);
        Assert::isInstanceOf($configuration, TableMetadataStorageConfiguration::class);

        $this->configuration = $configuration;
    }

    /**
     * @throws SchemaException
     * @throws Exception
     */
    public function postGenerateSchema(GenerateSchemaEventArgs $args): void
    {
        $schema = $args->getSchema();
        $table = $schema->createTable($this->configuration->getTableName());
        $table->addColumn(
            $this->configuration->getVersionColumnName(),
            'string',
            ['notnull' => true, 'length' => $this->configuration->getVersionColumnLength()],
        );
        $table->addColumn($this->configuration->getExecutedAtColumnName(), 'datetime', ['notnull' => false]);
        $table->addColumn($this->configuration->getExecutionTimeColumnName(), 'integer', ['notnull' => false]);

        $table->setPrimaryKey([$this->configuration->getVersionColumnName()]);
    }
}