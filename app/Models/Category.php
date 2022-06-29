<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    protected $guarded = ['id'];

    public function surveys()
    {
        return $this->belongsTo(Survey::class);
    }
}
