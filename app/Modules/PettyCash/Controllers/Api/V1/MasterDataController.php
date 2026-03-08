<?php

namespace App\Modules\PettyCash\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Modules\PettyCash\Models\Bike;
use App\Modules\PettyCash\Models\BikeService;
use App\Modules\PettyCash\Models\Respondent;
use App\Modules\PettyCash\Models\Spending;
use App\Modules\PettyCash\Support\ApiResponder;
use Illuminate\Http\Request;

class MasterDataController extends Controller
{
    use ApiResponder;

    public function bikes(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        $status = trim((string) $request->query('status', ''));
        $perPageOptions = [15, 25, 30, 50, 100];
        $perPage = (int) $request->integer('per_page', 25);
        if (!in_array($perPage, $perPageOptions, true)) {
            $perPage = 25;
        }

        $bikes = Bike::query()
            ->when($status !== '', fn ($query) => $query->where('status', $status))
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($nested) use ($q) {
                    $nested->where('plate_no', 'like', '%' . $q . '%')
                        ->orWhere('model', 'like', '%' . $q . '%')
                        ->orWhere('status', 'like', '%' . $q . '%');
                });
            })
            ->orderBy('plate_no')
            ->paginate($perPage)
            ->withQueryString();

        return $this->successResponse([
            'bikes' => collect($bikes->items())
                ->map(fn (Bike $bike) => $this->mapBike($bike))
                ->values(),
            'filters' => [
                'q' => $q,
                'status' => $status,
                'per_page' => $perPage,
            ],
        ], 'Bikes fetched.', 200, [
            'pagination' => $this->paginationMeta($bikes),
        ]);
    }

    public function storeBike(Request $request)
    {
        if ($deny = $this->denyIfRoleNotIn($request, ['admin', 'accountant', 'finance'])) {
            return $deny;
        }

        $data = $request->validate([
            'plate_no' => ['required', 'string', 'max:50', 'unique:petty_bikes,plate_no'],
            'model' => ['nullable', 'string', 'max:120'],
            'status' => ['nullable', 'string', 'max:50'],
        ]);

        $bike = Bike::query()->create($data);

        return $this->successResponse([
            'bike' => $this->mapBike($bike),
        ], 'Bike created.', 201);
    }

    public function updateBike(Request $request, Bike $bike)
    {
        if ($deny = $this->denyIfRoleNotIn($request, ['admin', 'accountant', 'finance'])) {
            return $deny;
        }

        $data = $request->validate([
            'plate_no' => ['sometimes', 'required', 'string', 'max:50', 'unique:petty_bikes,plate_no,' . $bike->id],
            'model' => ['sometimes', 'nullable', 'string', 'max:120'],
            'status' => ['sometimes', 'nullable', 'string', 'max:50'],
            'is_unroadworthy' => ['sometimes', 'boolean'],
            'unroadworthy_notes' => ['sometimes', 'nullable', 'string'],
        ]);

        if (empty($data)) {
            return $this->errorResponse('No update fields supplied.', 422, [
                'payload' => ['Provide at least one field to update.'],
            ]);
        }

        if (array_key_exists('is_unroadworthy', $data)) {
            $isUnroadworthy = (bool) $data['is_unroadworthy'];
            $data['unroadworthy_at'] = $isUnroadworthy ? now() : null;
            $data['flagged_at'] = $isUnroadworthy ? now() : null;
            if (!$isUnroadworthy && !array_key_exists('unroadworthy_notes', $data)) {
                $data['unroadworthy_notes'] = null;
            }
        }

        $bike->fill($data);
        $bike->save();

        return $this->successResponse([
            'bike' => $this->mapBike($bike->fresh()),
        ], 'Bike updated.');
    }

    public function destroyBike(Request $request, Bike $bike)
    {
        if ($deny = $this->denyIfRoleNotIn($request, ['admin'])) {
            return $deny;
        }

        $hasSpendings = Spending::query()
            ->where('type', 'bike')
            ->where('related_id', $bike->id)
            ->exists();
        $hasServices = BikeService::query()
            ->where('bike_id', $bike->id)
            ->exists();

        if ($hasSpendings || $hasServices) {
            return $this->errorResponse('Cannot delete bike with related records.', 409);
        }

        $bike->delete();

        return $this->successResponse([], 'Bike deleted.');
    }

    public function respondents(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        $category = trim((string) $request->query('category', ''));
        $perPageOptions = [15, 25, 30, 50, 100];
        $perPage = (int) $request->integer('per_page', 25);
        if (!in_array($perPage, $perPageOptions, true)) {
            $perPage = 25;
        }

        $respondents = Respondent::query()
            ->when($category !== '', fn ($query) => $query->where('category', $category))
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($nested) use ($q) {
                    $nested->where('name', 'like', '%' . $q . '%')
                        ->orWhere('phone', 'like', '%' . $q . '%')
                        ->orWhere('category', 'like', '%' . $q . '%');
                });
            })
            ->orderBy('name')
            ->paginate($perPage)
            ->withQueryString();

        return $this->successResponse([
            'respondents' => collect($respondents->items())
                ->map(fn (Respondent $respondent) => $this->mapRespondent($respondent))
                ->values(),
            'filters' => [
                'q' => $q,
                'category' => $category,
                'per_page' => $perPage,
            ],
        ], 'Respondents fetched.', 200, [
            'pagination' => $this->paginationMeta($respondents),
        ]);
    }

    public function storeRespondent(Request $request)
    {
        if ($deny = $this->denyIfRoleNotIn($request, ['admin', 'accountant', 'finance'])) {
            return $deny;
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'phone' => ['nullable', 'string', 'max:50'],
            'category' => ['nullable', 'string', 'max:80'],
        ]);

        $respondent = Respondent::query()->create($data);

        return $this->successResponse([
            'respondent' => $this->mapRespondent($respondent),
        ], 'Respondent created.', 201);
    }

    public function updateRespondent(Request $request, Respondent $respondent)
    {
        if ($deny = $this->denyIfRoleNotIn($request, ['admin', 'accountant', 'finance'])) {
            return $deny;
        }

        $data = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:150'],
            'phone' => ['sometimes', 'nullable', 'string', 'max:50'],
            'category' => ['sometimes', 'nullable', 'string', 'max:80'],
        ]);

        if (empty($data)) {
            return $this->errorResponse('No update fields supplied.', 422, [
                'payload' => ['Provide at least one field to update.'],
            ]);
        }

        $respondent->fill($data);
        $respondent->save();

        return $this->successResponse([
            'respondent' => $this->mapRespondent($respondent->fresh()),
        ], 'Respondent updated.');
    }

    public function destroyRespondent(Request $request, Respondent $respondent)
    {
        if ($deny = $this->denyIfRoleNotIn($request, ['admin'])) {
            return $deny;
        }

        $hasSpendings = Spending::query()->where('respondent_id', $respondent->id)->exists();
        if ($hasSpendings) {
            return $this->errorResponse('Cannot delete respondent with related spendings.', 409);
        }

        $respondent->delete();

        return $this->successResponse([], 'Respondent deleted.');
    }

    private function mapBike(Bike $bike): array
    {
        return [
            'id' => $bike->id,
            'plate_no' => $bike->plate_no,
            'model' => $bike->model,
            'status' => $bike->status,
            'is_unroadworthy' => (bool) $bike->is_unroadworthy,
            'unroadworthy_notes' => $bike->unroadworthy_notes,
            'unroadworthy_at' => optional($bike->unroadworthy_at)->format('Y-m-d H:i:s'),
            'last_service_date' => optional($bike->last_service_date)->format('Y-m-d'),
            'next_service_due_date' => optional($bike->next_service_due_date)->format('Y-m-d'),
        ];
    }

    private function mapRespondent(Respondent $respondent): array
    {
        return [
            'id' => $respondent->id,
            'name' => $respondent->name,
            'phone' => $respondent->phone,
            'category' => $respondent->category,
        ];
    }

    private function denyIfRoleNotIn(Request $request, array $roles)
    {
        $user = $request->attributes->get('pettyUser');
        $role = (string) ($user?->role ?? '');

        if (!in_array($role, $roles, true)) {
            return $this->errorResponse('Forbidden.', 403);
        }

        return null;
    }
}
