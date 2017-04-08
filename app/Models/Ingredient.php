<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Ingredient extends Model
{
    protected $table = 'rec_ingredients';

    protected $fillable = ['text'];

    public function recipes()
    {
        return $this->belongsTo(
            'App\Models\Recipe',
            'recipe_id'
        );
    }
}
