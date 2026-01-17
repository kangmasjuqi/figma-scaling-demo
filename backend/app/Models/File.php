<?php
// app/Models/File.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\User;
use App\Models\Organization;
use App\Models\Comment;

class File extends Model
{
    use HasUuids;
    
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'name',
        'owner_id',
        'organization_id',
        'thumbnail_blob',
        'version',
        'is_public',
        'view_count',
        'last_modified',
        'metadata'
    ];
    
    protected $casts = [
        'is_public' => 'boolean',
        'metadata' => 'array',
        'last_modified' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];
    
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }
    
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }
    
    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }
    
    public function activityLogs(): HasMany
    {
        return $this->hasMany(ActivityLog::class);
    }
}
