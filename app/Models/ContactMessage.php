<?php

namespace App\Models;

class ContactMessage extends BaseModel
{
    protected $table = 'contact_messages';

    const UPDATED_AT = null;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'subject',
        'message',
        'isRead',
    ];

    protected function casts(): array
    {
        return [
            'isRead' => 'boolean',
        ];
    }
}
