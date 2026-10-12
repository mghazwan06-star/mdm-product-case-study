<?php

namespace App\Http\Controllers;

use App\Actions\DeleteEmployee;
use App\Http\Resources\EmployeeResource;
use App\Models\Employee;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class EmployeeController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        return EmployeeResource::collection(Employee::withCount('assets')->orderBy('name')->get());
    }

    public function destroy(Employee $employee, DeleteEmployee $deleteEmployee): JsonResponse
    {
        $laptopsAffected = $deleteEmployee($employee);

        return response()->json([
            'message' => "{$employee->name} was deleted from WorkWize.",
            'laptops_affected' => $laptopsAffected,
        ]);
    }
}
