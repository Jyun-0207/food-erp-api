<?php

namespace App\Models;

class Category extends BaseModel
{
    protected $table = 'categories';

    protected $fillable = [
        'name',
        'parentId',
        'description',
        'image',
        'showOnFrontend',
    ];

    protected function casts(): array
    {
        return [
            'showOnFrontend' => 'boolean',
        ];
    }

    public function parent()
    {
        return $this->belongsTo(Category::class, 'parentId');
    }

    public function children()
    {
        return $this->hasMany(Category::class, 'parentId');
    }

    public function products()
    {
        return $this->hasMany(Product::class, 'categoryId');
    }
}
