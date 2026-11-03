<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Address;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AddressController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $addresses = Address::where('user_id', $request->user()->id)
            ->with(['division:id,name,name_bn', 'district:id,name,name_bn', 'upazila:id,name,name_bn'])
            ->orderBy('is_default', 'desc')
            ->get();

        return $this->success($addresses);
    }

    public function store(Request $request): JsonResponse
    {
        $v = Validator::make($request->all(), [
            'name'         => 'required|string',
            'phone'        => 'required|string',
            'address_line' => 'required|string',
            'division_id'  => 'nullable|exists:divisions,id',
            'district_id'  => 'required|exists:districts,id',
            'upazila_id'   => 'nullable|exists:upazilas,id',
            'postal_code'  => 'nullable|string|max:10',
            'type'         => 'nullable|in:home,office,other',
            'is_default'   => 'nullable|boolean',
        ]);
        if ($v->fails()) return $this->error($v->errors(), 422);

        // The division is no longer asked for — derive it from the district; the upazila must belong to the district.
        $err = $this->resolveGeo($request);
        if ($err) return $this->error(['upazila_id' => [$err]], 422);

        // A user's first address is automatically the default one
        if (!Address::where('user_id', $request->user()->id)->exists()) {
            $request->merge(['is_default' => true]);
        }
        if ($request->is_default) {
            Address::where('user_id', $request->user()->id)->update(['is_default' => false]);
        }

        $address = Address::create(array_merge(
            $request->only('name', 'phone', 'address_line', 'division_id', 'district_id', 'upazila_id', 'postal_code', 'is_default'),
            ['type' => $request->input('type', 'home'), 'user_id' => $request->user()->id]
        ));

        return $this->success(
            $address->load(['division:id,name,name_bn', 'district:id,name,name_bn', 'upazila:id,name,name_bn']),
            'Address added.',
            201
        );
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $address = Address::where('id', $id)->where('user_id', $request->user()->id)->firstOrFail();

        if ($request->filled('district_id') || $request->filled('upazila_id')) {
            $err = $this->resolveGeo($request, $address);
            if ($err) return $this->error(['upazila_id' => [$err]], 422);
        }

        if ($request->is_default) {
            Address::where('user_id', $request->user()->id)->update(['is_default' => false]);
        }

        $address->update($request->only(
            'name', 'phone', 'address_line',
            'division_id', 'district_id', 'upazila_id',
            'postal_code', 'type', 'is_default'
        ));

        return $this->success(
            $address->load(['division:id,name,name_bn', 'district:id,name,name_bn', 'upazila:id,name,name_bn']),
            'Updated.'
        );
    }

    /**
     * Sets division_id from the district and checks the upazila belongs to it. Returns an error message or null.
     */
    private function resolveGeo(Request $request, ?Address $existing = null): ?string
    {
        $districtId = $request->input('district_id', $existing?->district_id);
        $district   = $districtId ? \App\Models\District::find($districtId) : null;
        if ($district) {
            $request->merge(['district_id' => $district->id, 'division_id' => $district->division_id]);
        }
        if ($request->filled('upazila_id') && $district) {
            $ok = \App\Models\Upazila::where('id', $request->upazila_id)->where('district_id', $district->id)->exists();
            if (!$ok) return 'Selected upazila does not belong to the selected district.';
        }
        return null;
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        Address::where('id', $id)->where('user_id', $request->user()->id)->delete();
        return $this->success([], 'Deleted.');
    }

    public function setDefault(Request $request, int $id): JsonResponse
    {
        Address::where('user_id', $request->user()->id)->update(['is_default' => false]);
        Address::where('id', $id)->where('user_id', $request->user()->id)->update(['is_default' => true]);
        return $this->success([], 'Default address set.');
    }

    /*private function success(mixed $d, string $m = 'Success', int $c = 200): JsonResponse
    {
        return response()->json(['success' => true, 'message' => $m, 'data' => $d], $c);
    }

    private function error(mixed $m, int $c = 400): JsonResponse
    {
        return response()->json(['success' => false, 'message' => $m, 'data' => null], $c);
    }*/
}
