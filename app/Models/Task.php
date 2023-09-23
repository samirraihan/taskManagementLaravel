<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Task extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'title',
        'description',
        'created_by',
        'deleted_at'
    ];

    public function users()
    {
        return $this->belongsToMany(User::class);
    }
    
    public function comments()
    {
        return $this->hasMany(Comment::class);
    }
}
