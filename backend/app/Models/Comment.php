<?php 

// app/Models/Comment.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Comment extends Model
{
    use HasUuids;
    
    protected $fillable = [
        'file_id',
        'user_id',
        'content',
        'position_x',
        'position_y',
        'is_resolved'
    ];
    
    protected $casts = [
        'is_resolved' => 'boolean'
    ];
    
    public function file()
    {
        return $this->belongsTo(File::class);
    }
    
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}