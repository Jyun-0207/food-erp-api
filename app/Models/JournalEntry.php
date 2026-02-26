<?php

namespace App\Models;

class JournalEntry extends BaseModel
{
    protected $table = 'journal_entries';

    const UPDATED_AT = null;

    protected $fillable = [
        'date',
        'description',
        'entries',
        'reference',
        'createdBy',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'entries' => 'array',
        ];
    }
}
