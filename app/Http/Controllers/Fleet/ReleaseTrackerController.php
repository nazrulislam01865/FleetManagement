<?php

namespace App\Http\Controllers\Fleet;

use App\Models\Fleet\FleetRelease;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ReleaseTrackerController extends FleetBaseController
{
    protected string $activeMenu = 'release-tracker-list';
    protected string $view = 'fleetman.system.release-tracker-list';
    protected string $page = 'release-tracker';

    public function index(): View
    {
        /** @var Request $request */
        $request = request();

        $search = trim((string) $request->query('q', ''));
        $status = trim((string) $request->query('status', ''));
        $environment = trim((string) $request->query('environment', ''));
        $issueType = trim((string) $request->query('issue_type', ''));

        $releases = FleetRelease::query()
            ->with([
                'initiatedBy:id,name,email',
                'createdBy:id,name',
                'updatedBy:id,name',
            ])
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($searchQuery) use ($search): void {
                    $searchQuery
                        ->where('version', 'like', "%{$search}%")
                        ->orWhere('title', 'like', "%{$search}%")
                        ->orWhere('issue_type', 'like', "%{$search}%")
                        ->orWhere('summary', 'like', "%{$search}%")
                        ->orWhere('changes', 'like', "%{$search}%")
                        ->orWhere('known_issues', 'like', "%{$search}%")
                        ->orWhereHas('initiatedBy', fn ($userQuery) => $userQuery->where('name', 'like', "%{$search}%"));
                });
            })
            ->when(array_key_exists($status, FleetRelease::statusOptions()), fn ($query) => $query->where('status', $status))
            ->when(array_key_exists($environment, FleetRelease::environmentOptions()), fn ($query) => $query->where('environment', $environment))
            ->when(array_key_exists($issueType, FleetRelease::issueTypeOptions()), fn ($query) => $query->where('issue_type', $issueType))
            ->orderByDesc('release_date')
            ->orderByDesc('id')
            ->get();

        return view($this->view, array_merge($this->shared($this->activeMenu, [
            'page' => $this->page,
        ]), [
            'releases' => $releases,
            'counts' => $this->releaseCounts(),
            'issueTypeOptions' => FleetRelease::issueTypeOptions(),
            'statusOptions' => FleetRelease::statusOptions(),
            'environmentOptions' => FleetRelease::environmentOptions(),
            'filters' => compact('search', 'status', 'environment', 'issueType'),
        ]));
    }

    public function create(): View
    {
        return $this->releaseFormView();
    }

    public function edit(FleetRelease $release): View
    {
        return $this->releaseFormView($release);
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
            ->route('fleet.release-tracker.form')
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

    private function releaseFormView(?FleetRelease $release = null): View
    {
        $activeUsers = User::query()
            ->with('fleetRole:id,name,slug,is_active')
            ->where('account_status', User::ACCOUNT_STATUS_ACTIVE)
            ->whereHas('fleetRole', fn ($query) => $query->where('is_active', true))
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'fleet_role_id']);

        return view('fleetman.system.release-tracker-form', array_merge($this->shared('release-tracker-form', [
            'page' => $this->page,
        ]), [
            'release' => $release,
            'counts' => $this->releaseCounts(),
            'activeUsers' => $activeUsers,
            'issueTypeOptions' => FleetRelease::issueTypeOptions(),
            'statusOptions' => FleetRelease::statusOptions(),
            'environmentOptions' => FleetRelease::environmentOptions(),
        ]));
    }

    private function releaseCounts(): array
    {
        return [
            'total' => FleetRelease::query()->count(),
            'released' => FleetRelease::query()->where('status', FleetRelease::STATUS_RELEASED)->count(),
            'scheduled' => FleetRelease::query()->where('status', FleetRelease::STATUS_SCHEDULED)->count(),
            'draft' => FleetRelease::query()->where('status', FleetRelease::STATUS_DRAFT)->count(),
        ];
    }

    private function validatedRelease(Request $request, ?FleetRelease $release = null): array
    {
        return $request->validate([
            'version' => [
                'required',
                'string',
                'max:60',
                Rule::unique('fleet_releases', 'version')->ignore($release?->id),
            ],
            'title' => ['required', 'string', 'max:255'],
            'issue_type' => ['required', 'string', Rule::in(array_keys(FleetRelease::issueTypeOptions()))],
            'initiated_by_user_id' => [
                'required',
                'integer',
                Rule::exists('users', 'id')->where(fn ($query) => $query->where('account_status', User::ACCOUNT_STATUS_ACTIVE)),
            ],
            'release_date' => ['required', 'date'],
            'environment' => ['required', 'string', Rule::in(array_keys(FleetRelease::environmentOptions()))],
            'status' => ['required', 'string', Rule::in(array_keys(FleetRelease::statusOptions()))],
            'summary' => ['nullable', 'string', 'max:2000'],
            'changes' => ['nullable', 'string', 'max:20000'],
            'known_issues' => ['nullable', 'string', 'max:20000'],
        ]);
    }
}
