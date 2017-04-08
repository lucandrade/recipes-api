<?php

namespace App\Repositories;

use App\Models\Recipe;
use App\Models\Ingredient;
use App\Models\Categorie;
use App\Exceptions\GenericException;

class RecipeRepository
{

    protected $model;
    protected $ingredient;
    protected $categorie;

    public function __construct(
        Recipe $recipe,
        Ingredient $ingredient,
        Categorie $categorie
    ) {
        $this->model = $recipe;
        $this->ingredient = $ingredient;
        $this->categorie = $categorie;
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
            'categories' => function ($query) {
                $query->select('name');
            },
            'ingredients' => function ($query) {
                $query->where('status', true)
                    ->select('recipe_id', 'text as name');
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
            ->join('rec_ingredients', 'rec_recipes.id', '=', 'rec_ingredients.recipe_id');
        $resultSet->where(function ($query) use ($texts) {
            array_map(function ($text) use ($query) {
                $query->orWhereRaw(\DB::raw("lower(rec_recipes.title) like('%{$text}%')"))
                ->orWhereRaw(\DB::raw("lower(rec_recipes.directions) like('%{$text}%')"))
                ->orWhereRaw(\DB::raw("lower(rec_ingredients.text) like('%{$text}%')"));
            }, $texts);
        });
    }

    public function getByUrl($url)
    {
        return $this->model->where('url', $url)->first();
    }

    protected function getFillable()
    {
        return $this->model->getFillable();
    }

    protected function validate(array $data)
    {
        if (!(array_key_exists('title', $data) && !empty($data['title']))) {
            throw new GenericException("Campo title é obrigatório");
        }

        if (!(array_key_exists('directions', $data) && !empty($data['directions']))) {
            throw new GenericException("Campo directions é obrigatório");
        }

        if (!(
            array_key_exists('ingredients', $data) &&
            is_array($data['ingredients']) &&
            !empty($data['ingredients'])
        )) {
            throw new GenericException("Campo ingredients é obrigatório");
        }

        if (!(
            array_key_exists('categories', $data) &&
            is_array($data['categories']) &&
            !empty($data['categories'])
        )) {
            throw new GenericException("Campo categories é obrigatório");
        }
    }

    public function save(array $data)
    {
        $this->validate($data);
        $fields = $this->getFillable();
        $formData = array_only($data, $fields);

        if (array_key_exists('url', $formData) && !empty($formData['url'])) {
            $recipe = $this->getByUrl($formData['url']);
            $this->saveImported($formData['url']);

            if ($recipe) {
                return $recipe;
            }
        }

        $recipe = $this->model->create($formData);
        $this->saveIngredients($recipe, $data['ingredients']);
        $this->saveCategories($recipe, $data['categories']);
        return $recipe;
    }

    protected function saveImported($url)
    {
        $db = \DB::table('rec_urls');
        $imported = $db->where('url', $url)->first();

        if (!$imported) {
            $db->insert(['url' => $url, 'imported' => true]);
        }

        if (!$imported->imported) {
            $db->where('url', $url)->update(['imported' => true]);
        }
    }

    protected function saveIngredients(Recipe $recipe, $ingredients)
    {
        array_map(function ($ingredient) use ($recipe) {
            $obj = $this->getIngredient($ingredient);
            $recipe->ingredients()->save($obj);
        }, $ingredients);
    }

    protected function saveCategories(Recipe $recipe, $categories)
    {
        array_map(function ($categorie) use ($recipe) {
            $obj = $this->getCategorie($categorie);
            $recipe->categories()->attach($obj->id);
            $recipe->save();
        }, $categories);
    }

    protected function getIngredient($text)
    {
        return new Ingredient([
            'text' => $text
        ]);
    }

    protected function getCategorie($name)
    {
        return $this->categorie->firstOrCreate([
            'name' => $name
        ]);
    }
}
