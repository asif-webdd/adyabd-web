<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Division;
use App\Models\District;
use App\Models\Upazila;
use Illuminate\Http\JsonResponse;

class GeoController extends Controller
{
    /** GET /api/v1/geo/divisions */
    public function divisions(): JsonResponse
    {
        $divisions = Division::orderBy('name')->get(['id', 'name', 'name_bn']);
        return $this->success($divisions);
    }

    /** GET /api/v1/geo/districts?division_id=1 */
    public function districts(): JsonResponse
    {
        $query = District::orderBy('name')->with('division:id,name,name_bn');

        if (request('division_id')) {
            $query->where('division_id', request('division_id'));
        }

        return $this->success($query->get(['id', 'division_id', 'name', 'name_bn']));
    }

    /** GET /api/v1/geo/upazilas?district_id=1 */
    public function upazilas(): JsonResponse
    {
        $query = Upazila::orderBy('name');

        if (request('district_id')) {
            $query->where('district_id', request('district_id'));
        }

        return $this->success($query->get(['id', 'district_id', 'name', 'name_bn']));
    }
}
