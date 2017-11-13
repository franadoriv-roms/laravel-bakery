<?php

namespace Scrn\Bakery;

use GraphQL\GraphQL;
use GraphQL\Type\Schema;
use GraphQL\Executor\ExecutionResult;

use GraphQL\Type\Definition\Type;
use GraphQL\Type\Definition\ObjectType;

use Scrn\Bakery\Types\EntityType;
use Scrn\Bakery\Queries\EntityQuery;
use Scrn\Bakery\Exceptions\TypeNotFound;
use Scrn\Bakery\Queries\CollectionQuery;
use Scrn\Bakery\Types\EntityCollectionType;
use Scrn\Bakery\Types\CollectionFilterType;
use Scrn\Bakery\Types\CollectionOrderByType;

class Bakery
{
    /**
     * The registered models.
     *
     * @var array
     */
    protected $models = [];

    /**
     * The registered types.
     *
     * @var array
     */
    protected $types = [];

    /**
     * The GraphQL type instances.
     *
     * @var array
     */
    protected $typeInstances = [];

    /**
     * The queries.
     *
     * @var array
     */
    protected $queries = [];

    public function addModel($class)
    {
        $this->models[] = $class;
        $this->registerEntityTypes($class);
        $this->registerEntityQuery($class);
        $this->registerCollectionQuery($class);
        return $this;
    }

    public function getModels()
    {
        return $this->models;
    }

    public function addType($type, $name)
    {
        $this->types[$name] = $type;
    }

    public function getQueries()
    {
        return array_map(function ($query) {
            return $query->toArray();
        }, $this->queries);
    }

    protected function registerEntityQuery($class)
    {
        $entityQuery = new EntityQuery($class);
        $this->queries[$entityQuery->name] = $entityQuery;
    }

    protected function registerCollectionQuery($class)
    {
        $collectionQuery = new CollectionQuery($class);
        $this->queries[$collectionQuery->name] = $collectionQuery;
    }

    protected function registerEntityTypes($class)
    {
        $entityType = new EntityType($class);
        $this->types[$entityType->name] = $entityType;

        $entityCollectionType = new EntityCollectionType($class);
        $this->types[$entityCollectionType->name] = $entityCollectionType;

        $collectionFilterType = new CollectionFilterType($class);
        $this->types[$collectionFilterType->name] = $collectionFilterType; 

        $collectionOrderByType = new CollectionOrderByType($class);
        $this->types[$collectionOrderByType->name] = $collectionOrderByType; 
    }

    /**
     * Get the GraphQL schema
     *
     * @return Schema
     */
    public function schema()
    {
        $types = [];
        foreach ($this->types as $name => $type) {
            $types[] = $this->getType($name);
        }

        $query = $this->makeObjectType($this->getQueries(), [
            'name' => 'Query',
        ]);

        $mutation = $this->makeObjectType(['mutation' => Type::boolean()], [
            'name' => 'Mutation',
        ]);

        $schema = new Schema([
            'query' => $query,
            'mutation' => $mutation,
            'subscription' => null,
            'types' => $types,
        ]);

        $schema->assertValid();

        return $schema;
    }

    public function getType($name)
    {
        if (!isset($this->types[$name])) {
            throw new TypeNotFound('Type ' . $name . ' not found.');
        }

        if (isset($this->typeInstances[$name])) {
            return $this->typeInstances[$name];
        }

        $class = $this->types[$name];
        $type = $this->makeObjectType($class);
        $this->typeInstances[$name] = $type;

        return $type;
    }

    public function makeObjectType($type, $options = [])
    {
        $objectType = null;
        if (is_array($type)) {
            $objectType = $this->makeObjectTypeFromFields($type, $options);
        } else {
            $objectType = $this->makeObjectTypeFromClass($type, $options);
        }
        return $objectType;
    }

    protected function makeObjectTypeFromFields($fields, $options = [])
    {
        return new ObjectType(array_merge([
            'fields' => $fields,
        ], $options));
    }

    protected function makeObjectTypeFromClass($class, $options = [])
    {
        return $class->toGraphQLType();
    }

    /**
     * Execute the GraphQL query.
     *
     * @param array $input
     * @return ExecutionResult
     */
    public function executeQuery($input): ExecutionResult
    {
        $schema = $this->schema();

        $root = null;
        $context = null;
        $query = array_get($input, 'query');
        $variables = json_decode(array_get($input, 'variables'));
        $operationName = array_get($input, 'operationName');

        return GraphQL::executeQuery($schema, $query, $root, $context, $variables, $operationName);
    }

    public function id()
    {
        return Type::ID();
    }

    public function string()
    {
        return Type::string();
    }

    public function int()
    {
        return Type::int();
    }

    public function boolean()
    {
        return Type::boolean();
    }

    public function float()
    {
        return Type::float();
    }

    public function listOf($wrappedType)
    {
        return Type::listOf($wrappedType);
    }

    public function nonNull($wrappedType)
    {
        return Type::nonNull($wrappedType);
    }

    public function type($name)
    {
        return $this->getType($name);
    }
}
