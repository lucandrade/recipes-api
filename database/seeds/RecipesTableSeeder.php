<?php

use Illuminate\Database\Seeder;

use App\Models\Ingredient;
use App\Models\Categorie;
use App\Models\Recipe;

class RecipesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $recipes = $this->getRecipes();
        \DB::beginTransaction();
        try {
            $recipes->each(function ($item) {
                if (!array_key_exists('title', $item) || !array_key_exists('text', $item)) {
                    return;
                }

                $recipe = Recipe::create([
                    'title' => $item['title'],
                    'directions' => $item['text'],
                ]);

                if (array_key_exists('ingredients', $item) && is_array($item['ingredients'])) {
                    array_map(function ($ingredient) use ($recipe) {
                        if (!array_key_exists('text', $ingredient)) {
                            return;
                        }

                        $obj = $this->getIngredient($ingredient['text']);
                        $amount = array_key_exists('amount', $ingredient) ? $ingredient['amount'] : null;
                        $recipe->ingredients()->attach($obj->id, ['amount' => $amount]);
                        $recipe->save();
                    }, $item['ingredients']);
                }

                if (array_key_exists('categories', $item) && is_array($item['categories'])) {
                    $listCategories = [];
                    array_map(function ($categorie) use ($recipe, &$listCategories) {
                        if (!array_key_exists('name', $categorie) ||
                            in_array($categorie['name'], $listCategories)) {
                            return;
                        }

                        $listCategories[] = $categorie['name'];
                        $obj = $this->getCategorie($categorie['name']);
                        $recipe->categories()->attach($obj->id);
                        $recipe->save();
                    }, $item['categories']);
                }
            });
            \DB::commit();
        } catch (\Exception $e) {
            \DB::rollBack();
        }
    }

    protected function getIngredient($name)
    {
        return Ingredient::firstOrCreate([
            'name' => $name
        ]);
    }

    protected function getCategorie($name)
    {
        return Categorie::firstOrCreate([
            'name' => $name
        ]);
    }

    protected function getRecipes()
    {
        try {
            $data = file_get_contents(public_path('recipes.json'));
            $result = json_decode($data, true);
            return collect($result);
        } catch (\Exception $e) {
            \Log::error($e);
            dd('Erro ao buscar receitas');
        }
    }
}
