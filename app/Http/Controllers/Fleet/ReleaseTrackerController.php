<?php

namespace App\Http\Controllers\Fleet;

use App\Models\Fleet\FleetRelease;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ReleaseTrackerController extends FleetBaseController
{
    protected string $activeMenu = 'release-tracker';
    protected string $view = 'fleetman.system.release-tracker';
    protected string $page = 'release-tracker';

    public function index(): View
    {
        /** @var Request $request */
        $request = request();
        $search = trim((string) $request->query('q', ''));
        $status = trim((string) $request->query('status', ''));
        $environment = trim((string) $request->query('environment', ''));

        $releases = FleetRelease::query()
            ->with(['createdBy:id,name', 'updatedBy:id,name'])
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($searchQuery) use ($search): void {
                    $searchQuery
                        ->where('version', 'like', "%{$search}%")
                        ->orWhere('title', 'like', "%{$search}%")
                        ->orWhere('summary', 'like', "%{$search}%")
                        ->orWhere('changes', 'like', "%{$search}%")
                        ->orWhere('known_issues', 'like', "%{$search}%");
                });
            })
            ->when(array_key_exists($status, FleetRelease::statusOptions()), fn ($query) => $query->where('status', $status))
            ->when(array_key_exists($environment, FleetRelease::environmentOptions()), fn ($query) => $query->where('environment', $environment))
            ->orderByDesc('release_date')
            ->orderByDesc('id')
            ->get();

        $counts = [
            'total' => FleetRelease::query()->count(),
            'released' => FleetRelease::query()->where('status', FleetRelease::STATUS_RELEASED)->count(),
            'scheduled' => FleetRelease::query()->where('status', FleetRelease::STATUS_SCHEDULED)->count(),
            'draft' => FleetRelease::query()->where('status', FleetRelease::STATUS_DRAFT)->count(),
        ];

        return view($this->view, array_merge($this->shared($this->activeMenu, [
            'page' => $this->page,
        ]), [
            'releases' => $releases,
            'counts' => $counts,
            'statusOptions' => FleetRelease::statusOptions(),
            'environmentOptions' => FleetRelease::environmentOptions(),
            'filters' => compact('search', 'status', 'environment'),
        ]));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validatedRelease($request);
        $userId = (int) $request->user()->id;

        FleetRelease::query()->create(array_merge($validated, [
            'created_by_user_id' => $userId,
            'updated_by_user_id' => $userId,
        ]));

        return redirect()
            ->route('fleet.release-tracker')
            ->with('status', 'Release entry added successfully.');
    }

    public function update(Request $request, FleetRelease $release): RedirectResponse
    {
        $validated = $this->validatedRelease($request, $release);

        $release->fill(array_merge($validated, [
            'updated_by_user_id' => (int) $request->user()->id,
        ]))->save();

        return redirect()
            ->route('fleet.release-tracker')
            ->with('status', 'Release entry updated successfully.');
    }

    public function destroy(FleetRelease $release): RedirectResponse
    {
        $version = $release->version;
        $release->delete();

        return redirect()
            ->route('fleet.release-tracker')
            ->with('status', "Release {$version} deleted successfully.");
    }

    private function validatedRelease(Request $request, ?FleetRelease $release = null): array
    {
        $validated = $request->validate([
            '_release_id' => ['nullable', 'integer'],
            'version' => [
                'required',
                'string',
                'max:60',
                Rule::unique('fleet_releases', 'version')->ignore($release?->id),
            ],
            'title' => ['required', 'string', 'max:255'],
            'release_date' => ['required', 'date'],
            'environment' => ['required', 'string', Rule::in(array_keys(FleetRelease::environmentOptions()))],
            'status' => ['required', 'string', Rule::in(array_keys(FleetRelease::statusOptions()))],
            'summary' => ['nullable', 'string', 'max:2000'],
            'changes' => ['nullable', 'string', 'max:20000'],
            'known_issues' => ['nullable', 'string', 'max:20000'],
        ]);

        unset($validated['_release_id']);

        return $validated;
    }
}
