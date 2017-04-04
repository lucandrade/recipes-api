<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Recipe extends Model
{

    protected $table = 'rec_recipes';

    protected $fillable = ['title', 'directions', 'release_at'];

    protected $sortable = ['title', 'release_at', 'id'];

    public function getSortable()
    {
        return $this->sortable;
    }

    public function ingredients()
    {
        return $this->belongsToMany(
            'App\Models\Ingredient',
            'rec_recipes_ingredients',
            'recipe_id',
            'ingredient_id'
        )->withPivot('amount');
    }
}
