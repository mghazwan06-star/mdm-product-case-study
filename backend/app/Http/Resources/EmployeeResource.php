<?php

namespace App\Http\Resources;

use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A person in the people list. assets_count is the number of laptops they
 * have now (used by the delete warning).
 *
 * @mixin Employee
 */
class EmployeeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'job_title' => $this->job_title,
            'assets_count' => $this->assets_count,
        ];
    }
}
