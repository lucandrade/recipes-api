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

        if (array_key_exists('categories', $options) && !empty($options['categories'])) {
            $options['categories'] = explode(',', $options['categories']);
        } else {
            $options['categories'] = [];
        }

        if (!array_key_exists('text', $options)) {
            $options['text'] = null;
        }

        return $options;
    }

    public function filter($options)
    {
        $options = $this->getDefaultFilterParams($options);

        $resultSet = $this->model->with([
            'ingredients' => function ($query) {
                $query->select('name');
            },
            'categories' => function ($query) {
                $query->select('name');
            }
        ]);

        $this->applyOptions($resultSet, $options);
        return $resultSet->paginate($options['perPage']);
    }

    protected function applyOptions($resultSet, $options)
    {
        $this->applyFields($resultSet);
        if (!empty($options['categories'])) {
            $this->applyCategories($resultSet, $options['categories']);
        }

        if (!empty($options['text'])) {
            $this->applyTexts($resultSet, $options['text']);
        }

        $dir = strpos($options['sort'], '-') !== false ? 'ASC' : 'DESC';
        $options['sort'] = str_replace('-', '', $options['sort']);

        $resultSet->orderBy("rec_recipes.{$options['sort']}", $dir);
    }

    protected function applyFields($resultSet)
    {
        $resultSet
            ->select(
                'rec_recipes.id',
                'rec_recipes.title',
                'rec_recipes.image',
                'rec_recipes.release_at',
                'rec_recipes.directions'
            )
            ->groupBy('rec_recipes.id');
    }

    protected function applyCategories($resultSet, $categories)
    {
        $resultSet
            ->join('rec_recipes_categories', 'rec_recipes_categories.recipe_id', '=', 'rec_recipes.id')
            ->join('rec_categories', 'rec_recipes_categories.categorie_id', '=', 'rec_categories.id')
            ->whereIn('rec_categories.slug', $categories);
    }

    protected function applyTexts($resultSet, $text)
    {
        $text = strtolower($text);
        $texts = explode(' ', $text);

        $resultSet
            ->join('rec_recipes_ingredients', 'rec_recipes_ingredients.recipe_id', '=', 'rec_recipes.id')
            ->join('rec_ingredients', 'rec_recipes_ingredients.ingredient_id', '=', 'rec_ingredients.id');
        $resultSet->where(function ($query) use ($texts) {
            array_map(function ($text) use ($query) {
                $query->orWhereRaw(\DB::raw("lower(rec_recipes.title) like('%{$text}%')"))
                ->orWhereRaw(\DB::raw("lower(rec_recipes.directions) like('%{$text}%')"))
                ->orWhereRaw(\DB::raw("lower(rec_ingredients.slug) like('%{$text}%')"))
                ->orWhereRaw(\DB::raw("lower(rec_ingredients.name) like('%{$text}%')"));
            }, $texts);
        });
    }
}
