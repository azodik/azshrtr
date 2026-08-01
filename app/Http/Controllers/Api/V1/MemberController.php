<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\AuditAction;
use App\Enums\MemberRole;
use App\Http\Controllers\Concerns\DestroysMany;
use App\Http\Controllers\Concerns\EnsuresOrganizationMembership;
use App\Http\Controllers\Concerns\ResolvesListQuery;
use App\Http\Controllers\Controller;
use App\Models\OrganizationInvitation;
use App\Models\OrganizationMember;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Services\OrganizationService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MemberController extends Controller
{
    use DestroysMany;
    use EnsuresOrganizationMembership;
    use ResolvesListQuery;

    public function __construct(
        private readonly OrganizationService $organizations,
        private readonly AuditLogger $audit,
    ) {}

    public function index(Request $request, string $organizationId): JsonResponse
    {
        $organization = $this->organization($request, $organizationId);
        $filters = $this->listFilters(
            $request,
            ['created_at', 'joined_at', 'role', 'status'],
            'created_at',
            'asc',
        );

        $members = $this->filteredMembersQuery($organization->id, $filters)
            ->with('user:id,name,email')
            ->paginate($this->perPage($request))
            ->through(fn (OrganizationMember $member) => $this->serializeMember($member));

        $canManage = $this->memberRole($request)->canManageMembers();

        $invitations = $organization->invitations()
            ->whereNull('accepted_at')
            ->where(function ($query): void {
                $query->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->when($filters['q'] !== null, function ($query) use ($filters): void {
                $query->where('email', 'like', '%'.$filters['q'].'%');
            })
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (OrganizationInvitation $invite) => $this->serializeInvitation($invite, $canManage));

        return response()->json(array_merge($members->toArray(), [
            'invitations' => $invitations,
            'can_manage' => $canManage,
        ]));
    }

    public function export(Request $request, string $organizationId): StreamedResponse|JsonResponse
    {
        $organization = $this->organization($request, $organizationId);
        $filters = $this->listFilters(
            $request,
            ['created_at', 'joined_at', 'role', 'status'],
            'created_at',
            'asc',
        );
        $format = $request->validate(['format' => ['sometimes', 'in:csv,json']])['format'] ?? 'csv';

        $rows = $this->filteredMembersQuery($organization->id, $filters)
            ->with('user:id,name,email')
            ->limit(5000)
            ->get()
            ->map(fn (OrganizationMember $member) => $this->serializeMember($member));

        if ($format === 'json') {
            return response()->json(['data' => $rows])->withHeaders([
                'Content-Disposition' => 'attachment; filename="members.json"',
            ]);
        }

        return $this->streamCsv(
            'members.csv',
            ['id', 'name', 'email', 'role', 'status', 'joined_at', 'created_at'],
            $rows->map(fn (array $member) => [
                $member['id'],
                $member['user']['name'] ?? '',
                $member['user']['email'] ?? '',
                $member['role'],
                $member['status'],
                $member['joined_at'],
                $member['created_at'] ?? null,
            ]),
        );
    }

    public function show(Request $request, string $organizationId, string $memberId): JsonResponse
    {
        $organization = $this->organization($request, $organizationId);
        $member = OrganizationMember::query()
            ->with('user:id,name,email')
            ->where('organization_id', $organization->id)
            ->whereKey($memberId)
            ->firstOrFail();

        return response()->json([
            'member' => $this->serializeMember($member),
            'can_manage' => $this->memberRole($request)->canManageMembers(),
        ]);
    }

    public function invite(Request $request, string $organizationId): JsonResponse
    {
        $organization = $this->organization($request, $organizationId);
        abort_unless($this->memberRole($request)->canManageMembers(), 403);

        $data = $request->validate([
            'email' => ['required', 'email', 'max:255'],
            'role' => ['required', Rule::enum(MemberRole::class)->except([MemberRole::Owner])],
        ]);

        /** @var User $actor */
        $actor = $request->user();
        $invitation = $this->organizations->invite(
            $organization,
            $actor,
            $data['email'],
            MemberRole::from($data['role']),
        );

        return response()->json([
            'invitation' => $this->serializeInvitation($invitation),
        ], 201);
    }

    public function revokeInvitation(Request $request, string $organizationId, string $invitationId): JsonResponse
    {
        $organization = $this->organization($request, $organizationId);
        abort_unless($this->memberRole($request)->canManageMembers(), 403);

        $invitation = OrganizationInvitation::query()
            ->where('organization_id', $organization->id)
            ->whereKey($invitationId)
            ->whereNull('accepted_at')
            ->firstOrFail();

        /** @var User $actor */
        $actor = $request->user();
        $this->organizations->revokeInvitation($organization, $invitation, $actor);

        return response()->json(['ok' => true]);
    }

    public function updateRole(Request $request, string $organizationId, string $memberId): JsonResponse
    {
        $organization = $this->organization($request, $organizationId);
        abort_unless($this->memberRole($request)->canManageMembers(), 403);

        $data = $request->validate([
            'role' => ['required', Rule::enum(MemberRole::class)->except([MemberRole::Owner])],
        ]);

        $member = OrganizationMember::query()
            ->where('organization_id', $organization->id)
            ->whereKey($memberId)
            ->firstOrFail();

        if ($member->role === MemberRole::Owner) {
            return response()->json(['message' => 'Owner role cannot be changed this way.'], 422);
        }

        if ($member->user_id === $request->user()?->id) {
            return response()->json(['message' => 'You cannot change your own role.'], 422);
        }

        $member->update(['role' => MemberRole::from($data['role'])]);

        /** @var User $actor */
        $actor = $request->user();
        $this->audit->log(
            AuditAction::MemberRoleUpdated,
            $actor,
            $organization,
            'member',
            $member->id,
            ['role' => $data['role']],
        );

        return response()->json([
            'member' => [
                'id' => $member->id,
                'role' => $member->role->value,
            ],
        ]);
    }

    public function destroy(Request $request, string $organizationId, string $memberId): JsonResponse
    {
        $organization = $this->organization($request, $organizationId);
        abort_unless($this->memberRole($request)->canManageMembers(), 403);

        $member = OrganizationMember::query()
            ->where('organization_id', $organization->id)
            ->whereKey($memberId)
            ->firstOrFail();

        if ($member->role === MemberRole::Owner) {
            return response()->json(['message' => 'Owner cannot be removed.'], 422);
        }

        if ($member->user_id === $request->user()?->id) {
            return response()->json(['message' => 'You cannot remove yourself.'], 422);
        }

        /** @var User $actor */
        $actor = $request->user();
        $this->audit->log(
            AuditAction::MemberRemoved,
            $actor,
            $organization,
            'member',
            $member->id,
        );

        $member->delete();

        return response()->json(['ok' => true]);
    }

    public function destroyMany(Request $request, string $organizationId): JsonResponse
    {
        $organization = $this->organization($request, $organizationId);
        abort_unless($this->memberRole($request)->canManageMembers(), 403);

        $ids = $this->bulkIds($request);
        /** @var User $actor */
        $actor = $request->user();

        $members = OrganizationMember::query()
            ->where('organization_id', $organization->id)
            ->whereIn('id', $ids)
            ->where('role', '!=', MemberRole::Owner->value)
            ->where('user_id', '!=', $actor->id)
            ->get();

        foreach ($members as $member) {
            $this->audit->log(
                AuditAction::MemberRemoved,
                $actor,
                $organization,
                'member',
                $member->id,
            );
            $member->delete();
        }

        return response()->json(['ok' => true, 'deleted' => $members->count()]);
    }

    public function accept(Request $request): JsonResponse
    {
        $data = $request->validate([
            'token' => ['required', 'string', 'max:64'],
        ]);

        /** @var User $user */
        $user = $request->user();
        $member = $this->organizations->acceptInvitation($data['token'], $user);

        $user->load(['organizations' => fn ($q) => $q->orderBy('name')]);

        return response()->json([
            'organization' => [
                'id' => $member->organization_id,
                'role' => $member->role->value,
            ],
            'organizations' => $user->organizations->map(fn ($org) => [
                'id' => $org->id,
                'name' => $org->name,
                'slug' => $org->slug,
                'role' => $org->pivot->role instanceof \BackedEnum
                    ? $org->pivot->role->value
                    : $org->pivot->role,
            ])->values(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeMember(OrganizationMember $member): array
    {
        return [
            'id' => $member->id,
            'role' => $member->role->value,
            'status' => $member->status,
            'joined_at' => $member->joined_at?->toIso8601String(),
            'created_at' => $member->created_at?->toIso8601String(),
            'user' => $member->user ? [
                'id' => $member->user->id,
                'name' => $member->user->name,
                'email' => $member->user->email,
            ] : null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeInvitation(OrganizationInvitation $invite, bool $includeInviteUrl = true): array
    {
        $payload = [
            'id' => $invite->id,
            'email' => $invite->email,
            'role' => $invite->role->value,
            'expires_at' => $invite->expires_at?->toIso8601String(),
            'created_at' => $invite->created_at?->toIso8601String(),
        ];

        if ($includeInviteUrl) {
            $payload['invite_url'] = url('/console/invite/'.$invite->token);
        }

        return $payload;
    }

    /**
     * @param  array{sort: string, direction: string, q: string|null, from: string|null, to: string|null}  $filters
     * @return Builder<OrganizationMember>
     */
    private function filteredMembersQuery(string $organizationId, array $filters): Builder
    {
        $query = OrganizationMember::query()->where('organization_id', $organizationId);

        if ($filters['q'] !== null) {
            $q = $filters['q'];
            $query->where(function (Builder $builder) use ($q): void {
                $builder->where('role', 'like', '%'.$q.'%')
                    ->orWhere('status', 'like', '%'.$q.'%')
                    ->orWhereHas('user', function (Builder $userQuery) use ($q): void {
                        $userQuery->where('name', 'like', '%'.$q.'%')
                            ->orWhere('email', 'like', '%'.$q.'%');
                    });
            });
        }

        $dateColumn = in_array($filters['sort'], ['joined_at'], true) ? 'joined_at' : 'created_at';
        if (! empty($filters['from'])) {
            $query->where($dateColumn, '>=', $filters['from']);
        }
        if (! empty($filters['to'])) {
            $query->where($dateColumn, '<=', $filters['to'].' 23:59:59');
        }

        return $query->orderBy($filters['sort'], $filters['direction']);
    }
}
