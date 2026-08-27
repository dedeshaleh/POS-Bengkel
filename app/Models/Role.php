<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    public $timestamps = false;

    protected $guarded = [];

    public function permissions()
    {
        return $this->hasMany(RolePermission::class);
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'role_user');
    }
}
