<?php

namespace Bakery\Types;

use Bakery\Utils\Utils;
use GraphQL\Type\Definition\Type;
use Bakery\Support\Facades\Bakery;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;

class CreateInputType extends ModelAwareInputType
{
    /**
     * Get the name of the Create Input Type.
     *
     * @return string
     */
    protected function name(): string
    {
        return 'Create'.Utils::typename($this->model->getModel()).'Input';
    }

    /**
     * Return the fields for the Create Input Type.
     *
     * @return array
     */
    public function fields(): array
    {
        $fields = array_merge(
            $this->model->getFillableFields()->toArray(),
            $this->getRelationFields()
        );

        Utils::invariant(
            count($fields) > 0,
            'There are no fields defined for '.class_basename($this->model)
        );

        return $fields;
    }

    /**
     * Get the fields for the relations of the model.
     *
     * @return array
     */
    protected function getRelationFields(): array
    {
        return collect($this->model->getRelations())->keys()->reduce(function ($fields, $relation) {
            $model = $this->model->getModel();

            Utils::invariant(
                method_exists($model, $relation),
                'Relation '.$relation.' does not exist as method on model '.class_basename($model)
            );

            $relationship = $model->{$relation}();

            Utils::invariant(
                $relationship instanceof Relation,
                'Relation '.$relation.' on '.class_basename($model).' does not return an instance of '.Relation::class
            );

            return $fields->merge($this->getFieldsForRelation($relation, $relationship));
        }, collect())->toArray();
    }

    /**
     * Set the relation fields.
     *
     * @param string $relation
     * @param Relation $relationship
     * @param array $fields
     * @return void
     */
    protected function getFieldsForRelation(string $relation, Relation $relationship): array
    {
        $fields = [];
        $inputType = $this->inputTypeName($relationship);

        if (Utils::pluralRelationship($relationship)) {
            $name = str_singular($relation).'Ids';
            $fields[$name] = Bakery::listOf(Bakery::ID());

            if (Bakery::hasType($inputType)) {
                $fields[$relation] = Bakery::listOf(Bakery::type($inputType));
            }
        }

        if (Utils::singularRelationship($relationship)) {
            $name = str_singular($relation).'Id';
            $fields[$name] = Bakery::ID();

            if (Bakery::hasType($inputType)) {
                $fields[$relation] = Bakery::type($inputType);
            }
        }

        return $fields;
    }

    /**
     * Generate the input type name for a relationship.
     *
     * @param Relation $relationship
     * @return string
     */
    protected function inputTypeName(Relation $relationship): string
    {
        return 'Create'.class_basename($relationship->getRelated()).'Input';
    }
}
