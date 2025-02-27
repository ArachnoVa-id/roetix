<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Section extends Model
{
    use HasFactory;

    protected $fillable = [
        'id',
        'name'
    ];

    public $incrementing = false;
    protected $keyType = 'string';

    /**
     * Get the seats for the section.
     */
    public function seats(): HasMany
    {
        return $this->hasMany(Seat::class, 'section_id', 'id');
    }
}