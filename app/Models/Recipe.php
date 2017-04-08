<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Recipe extends Model
{

    protected $table = 'rec_recipes';

    protected $fillable = ['title', 'directions', 'release_at', 'image', 'url'];

    protected $sortable = ['id', 'title', 'release_at'];

    public function getSortable()
    {
        return $this->sortable;
    }

    public function ingredients()
    {
        return $this->hasMany(
            'App\Models\Ingredient',
            'recipe_id'
        );
    }

    public function categories()
    {
        return $this->belongsToMany(
            'App\Models\Categorie',
            'rec_recipes_categories',
            'recipe_id',
            'categorie_id'
        );
    }
}
