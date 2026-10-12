<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A person a laptop is assigned to. Unique by email.
 */
#[Fillable(['email', 'name', 'job_title', 'phone', 'username'])]
class Employee extends Model
{
    /**
     * Laptops this person has now.
     */
    public function assets(): HasMany
    {
        return $this->hasMany(Asset::class);
    }
}
