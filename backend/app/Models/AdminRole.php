<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdminRole extends Model
{
    protected $fillable = ['name', 'description', 'is_super', 'permissions'];
    protected $casts = ['is_super' => 'boolean', 'permissions' => 'array'];

    public function admins()
    {
        return $this->hasMany(AdminUser::class, 'role_id');
    }
}
