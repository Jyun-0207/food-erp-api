<?php

namespace App\Models;

class VisitorRecord extends BaseModel
{
    protected $table = 'visitor_records';

    const CREATED_AT = 'timestamp';
    const UPDATED_AT = null;

    protected $fillable = [
        'sessionId',
        'page',
        'referrer',
        'userAgent',
    ];
}
