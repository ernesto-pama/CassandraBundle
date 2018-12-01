<?php

namespace CassandraBundle\Cassandra\ORM\Tools;

use CassandraBundle\Cassandra\ORM\EntityManager;
use CassandraBundle\Cassandra\ORM\SchemaManager;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

class SchemaCreate
{
    /** @var Container */
    private $container;

    public function __construct($container)
    {
        $this->container = $container;
    }

    public function execute($connection = 'default')
    {
        /** @var EntityManager $em */
        $em = $this->container->get(sprintf('cassandra.%s_entity_manager', $connection));

        /** @var SchemaManager $schemaManager */
        $schemaManager = $em->getSchemaManager();

        $finder = new Finder();
        $finder->in('src/Entity');

        /** @var SplFileInfo $file */
        foreach ($finder->files() as $file) {
            preg_match('/namespace (.*);/', $file->getContents(), $match);
            $namespace = array_pop($match);
            $className = sprintf('%s\%s', $namespace, str_replace('.php', '', $file->getFilename()));

            $metadata = $em->getClassMetadata($className);
            $tableName = $metadata->table['name'];
            $indexes = $metadata->table['indexes'];
            $primaryKeys = $metadata->table['primaryKeys'];

            if ($tableName) {
                $schemaManager->dropTable($tableName);
                $schemaManager->createTable($tableName, $metadata->fieldMappings, $primaryKeys);
                $schemaManager->createIndexes($tableName, $indexes);
            }
        }

        $em->closeAsync();
    }
}
