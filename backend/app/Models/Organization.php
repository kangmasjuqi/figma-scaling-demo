<?php 

// app/Models/Organization.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use App\Models\User;
use App\Models\File;

class Organization extends Model
{
    use HasUuids;
    
    protected $fillable = ['name', 'plan'];
    
    public function users()
    {
        return $this->hasMany(User::class);
    }
    
    public function files()
    {
        return $this->hasMany(File::class);
    }
}
