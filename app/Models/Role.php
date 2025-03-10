<?php

namespace App\Models;

use Spatie\Permission\Models\Role as SpatieRole;

class Role extends SpatieRole
{
    protected $fillable = ['name', 'guard_name', 'team_id'];
    
    public function team()
    {
        return $this->belongsTo(Team::class, 'team_id', 'team_id');
    }
}
