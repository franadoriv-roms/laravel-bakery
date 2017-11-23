<?php

namespace Bakery;

use Auth;
use GraphQL\GraphQL;
use GraphQL\Type\Schema;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Executor\ExecutionResult;

use Bakery\Types;
use Bakery\Traits\BakeryTypes;
use Bakery\Exceptions\TypeNotFound;
use Bakery\Support\Schema as BakerySchema;

class Bakery
{
    use BakeryTypes;

    /**
     * The schemas
     *
     * @var array
     */
    protected $schemas = [];

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

    public function addTypes(array $classes)
    {
        foreach ($classes as $class) {
            $class = is_object($class) ? $class : resolve($class);
            $this->addType($class);
        }
    }

    /**
     * Add a type to the registry.
     *
     * @param $class
     * @param string|null $name
     */
    public function addType($class, string $name = null)
    {
        $name = $this->getTypeName($class, $name);
        $this->types[$name] = $class;
    }

    /**
     * Return the name of the type.
     *
     * @param $class
     * @param null|string $name
     * @return string
     */
    protected function getTypeName($class, string $name = null): string
    {
        return $name ? $name : (is_object($class) ? $class : resolve($class))->name;
    }

    /**
     * Return if the name is registered as a type.
     *
     * @param string $name
     * @return boolean
     */
    public function hasType(string $name): bool
    {
        return array_key_exists($name, $this->types);
    }


    public function getStandardTypes()
    {
        return [
            new Types\PaginationType(),
        ];
    }

    /**
     * Get the default GraphQL schema
     *
     * @return Schema
     */
    public function schema()
    {
        $schema = new Support\DefaultSchema();
        return $schema->toGraphQLSchema();
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
        $type = $class->toGraphQLType();
        $this->typeInstances[$name] = $type;

        return $type;
    }

    public function type($name)
    {
        return $this->getType($name);
    }

    /**
     * Execute the GraphQL query.
     *
     * @param array $input
     * @param Schema $schema
     * @return ExecutionResult
     */
    public function executeQuery($input, $schema = null): ExecutionResult
    {
        if (!$schema) {
            $schema = $this->schema();
        } elseif ($schema instanceof BakerySchema) {
            $schema = $schema->toGraphQLSchema();
        }

        $root = null;
        $context = Auth::user();
        $query = array_get($input, 'query');
        $variables = array_get($input, 'variables');
        if (is_string($variables)) {
            $variables = json_decode($variables, true);
        }
        $operationName = array_get($input, 'operationName');

        return GraphQL::executeQuery($schema, $query, $root, $context, $variables, $operationName);
    }

    public function graphiql($route, $headers = [])
    {
        return view(
            'bakery::graphiql',
            ['endpoint' => route($route), 'headers' => $headers]
        );
    }
}
