<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Cviebrock\EloquentSluggable\Sluggable;

class Categorie extends Model
{
    use Sluggable;

    protected $table = 'rec_categories';

    protected $fillable = ['name'];

    public function sluggable()
    {
        return [
            'slug' => [
                'source' => 'name'
            ]
        ];
    }

    public function recipes()
    {
        return $this->belongsToMany(
            'App\Models\Recipe',
            'rec_recipes_categories',
            'categorie_id',
            'recipe_id'
        );
    }
}
