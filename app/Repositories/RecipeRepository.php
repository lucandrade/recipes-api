<?php

namespace App\Repositories;

use App\Models\Recipe;

class RecipeRepository
{

    protected $model;

    public function __construct(Recipe $recipe)
    {
        $this->model = $recipe;
    }

    protected function getDefaultFilterParams($options)
    {
        $sortable = $this->model->getSortable();

        if (!(array_key_exists('sort', $options) && in_array($options['sort'], $sortable))) {
            $options['sort'] = $sortable[0];
        }

        if (!(array_key_exists('page', $options) && is_numeric($options['page']))) {
            $options['page'] = 0;
        }

        if (!(array_key_exists('perPage', $options) && is_numeric($options['perPage']))) {
            $options['perPage'] = 10;
        }

        if (array_key_exists('ingredients', $options) && !empty($options['ingredients'])) {
            $options['ingredients'] = explode(',', $options['ingredients']);
        } else {
            $options['ingredients'] = [];
        }

        if (!array_key_exists('text', $options)) {
            $options['text'] = null;
        }

        return $options;
    }

    public function filter($options)
    {
        $options = $this->getDefaultFilterParams($options);

        $resultSet = $this->model->with(['ingredients' => function ($query) {
            $query->select('name');
        }]);

        $this->applyOptions($resultSet, $options);

        return $resultSet->paginate($options['perPage']);
    }

    protected function applyOptions($resultSet, $options)
    {
        if (!empty($options['ingredients'])) {
            $this->applyIngredients($resultSet, $options['ingredients']);
        }

        if (!empty($options['text'])) {
            $this->applyText($resultSet, $options['text']);
        }

        $dir = strpos($options['sort'], '-') !== false ? 'ASC' : 'DESC';
        $options['sort'] = str_replace('-', '', $options['sort']);

        $resultSet->orderBy($options['sort'], $dir);
    }

    protected function applyIngredients($resultSet, $ingredients)
    {
        $resultSet
            ->join('rec_recipes_ingredients', 'rec_recipes_ingredients.recipe_id', '=', 'rec_recipes.id')
            ->join('rec_ingredients', 'rec_recipes_ingredients.ingredient_id', '=', 'rec_ingredients.id')
            ->whereIn('rec_ingredients.slug', $ingredients);
    }

    protected function applyText($resultSet, $text)
    {
        $text = strtolower($text);
        $resultSet->where(function ($query) use ($text) {
            $query->whereRaw(\DB::raw("lower(rec_recipes.directions) like('%{$text}%')"))
                ->orWhere(function ($q) use ($text) {
                    $q->whereRaw(\DB::raw("lower(rec_recipes.title) like('%{$text}%')"));
                });
        });
    }
}
