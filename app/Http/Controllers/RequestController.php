<?php

namespace App\Http\Controllers;

use App\Enums\RequestStatus;
use App\Http\Requests\CreateRequestRequest;
use App\Http\Requests\UpdateRequestRequest;
use App\Http\Requests\ValidateRequestRequest;
use App\Http\Resources\RequestResource;
use App\Models\AuditLog;
use App\Models\Request;
use App\Contracts\MessagingServiceInterface;
use App\Contracts\RequestServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request as HttpRequest;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use OpenApi\Attributes as OA;

class RequestController extends Controller
{
    public function __construct(
        private RequestServiceInterface $requestService,
        private MessagingServiceInterface $whapiService,
    ) {}

    #[OA\Get(
        path: "/requests",
        summary: "Liste des demandes",
        description: "Récupère la liste des demandes de bypass avec filtres optionnels.",
        tags: ["Demandes"],
        security: [["sanctum" => []]],
        parameters: [
            new OA\Parameter(name: "status", in: "query", description: "Filtrer par statut", schema: new OA\Schema(type: "string", enum: ["draft", "pending", "approved", "active", "closed", "expired", "rejected"])),
            new OA\Parameter(name: "priority", in: "query", description: "Filtrer par priorité", schema: new OA\Schema(type: "string", enum: ["low", "normal", "high", "critical", "emergency"])),
            new OA\Parameter(name: "search", in: "query", description: "Rechercher dans titre, description ou code", schema: new OA\Schema(type: "string")),
            new OA\Parameter(name: "page", in: "query", description: "Numéro de page", schema: new OA\Schema(type: "integer", example: 1)),
        ],
        responses: [
            new OA\Response(response: 200, description: "Liste des demandes paginée"),
            new OA\Response(response: 401, description: "Non authentifié"),
        ]
    )]
    public function index(HttpRequest $request): AnonymousResourceCollection|JsonResponse
    {
        $query = Request::with(['requester', 'validator', 'equipment.zone', 'sensor', 'ora', 'approvals']);

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }
        if ($request->has('priority')) {
            $query->where('priority', $request->priority);
        }
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('request_code', 'like', "%{$search}%");
            });
        }

        if (!auth()->user()->hasPermissionTo('requests.view.all')) {
            $query->where('requester_id', auth()->id());
        }

        return RequestResource::collection($query->orderBy('created_at', 'desc')->paginate(15));
    }

    #[OA\Post(
        path: "/requests",
        summary: "Créer une demande de bypass",
        tags: ["Demandes"],
        security: [["sanctum" => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: "application/json",
                schema: new OA\Schema(
                    required: ["reason", "detailedJustification", "urgencyLevel", "equipmentId", "sensorId", "plannedStartDate", "estimatedDuration", "safetyImpact", "operationalImpact", "environmentalImpact", "mitigationMeasures"],
                    properties: [
                        new OA\Property(property: "reason", type: "string"),
                        new OA\Property(property: "detailedJustification", type: "string"),
                        new OA\Property(property: "urgencyLevel", type: "string", enum: ["low", "normal", "high", "critical", "emergency"]),
                        new OA\Property(property: "equipmentId", type: "integer"),
                        new OA\Property(property: "sensorId", type: "integer"),
                        new OA\Property(property: "plannedStartDate", type: "string", format: "date-time"),
                        new OA\Property(property: "estimatedDuration", type: "integer"),
                        new OA\Property(property: "bypassType", type: "string", enum: ["maintenance", "operationnel", "permissif"], nullable: true),
                        new OA\Property(property: "isDraft", type: "boolean"),
                        new OA\Property(property: "safetyImpact", type: "string"),
                        new OA\Property(property: "operationalImpact", type: "string"),
                        new OA\Property(property: "environmentalImpact", type: "string"),
                        new OA\Property(property: "mitigationMeasures", type: "array", items: new OA\Items(type: "string")),
                        new OA\Property(property: "contingencyPlan", type: "string", nullable: true),
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(response: 201, description: "Demande créée avec succès"),
            new OA\Response(response: 422, description: "Erreur de validation"),
        ]
    )]
    public function store(CreateRequestRequest $request): JsonResponse
    {
        $bypassRequest = $this->requestService->create(
            $request->validated(),
            auth()->user()
        );

        return (new RequestResource(
            $bypassRequest->load(['requester', 'validator', 'equipment.zone', 'sensor', 'ora', 'approvals'])
        ))->response()->setStatusCode(201);
    }

    public function show(Request $request): RequestResource|JsonResponse
    {
        $user = auth()->user();

        if (!$user->hasPermissionTo('requests.view.all') && $request->requester_id !== $user->id) {
            return response()->json(['message' => 'Non autorisé'], 403);
        }

        return new RequestResource($request->load(['requester', 'validator', 'equipment.zone', 'sensor', 'ora', 'approvals.approvedBy']));
    }

    public function validate(ValidateRequestRequest $httpRequest, Request $request): RequestResource|JsonResponse
    {
        $updatedRequest = $this->requestService->validateRequest(
            $request->load('approvals'),
            $httpRequest->validated(),
            auth()->user()
        );

        return new RequestResource(
            $updatedRequest->load(['requester', 'validator', 'validatorLevel1', 'validatorLevel2', 'equipment.zone', 'sensor', 'ora', 'approvals.approvedBy'])
        );
    }

    public function mine(): AnonymousResourceCollection
    {
        $requests = Request::with(['requester', 'equipment.zone', 'sensor', 'validator', 'ora', 'approvals'])
            ->where('requester_id', auth()->id())
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return RequestResource::collection($requests);
    }

    public function pending(): AnonymousResourceCollection|JsonResponse
    {
        $user = auth()->user();

        if (!$user->canValidateRequests()) {
            return response()->json(['message' => 'Non autorisé'], 403);
        }

        $query = Request::with(['requester', 'equipment.zone', 'sensor', 'validator', 'validatorLevel1', 'validatorLevel2', 'ora', 'approvals.approvedBy'])
            ->where('status', RequestStatus::Pending->value);

        // CDC: filter by role-based approvals
        if ($user->canValidateLevel1() && !$user->canValidateLevel2()) {
            $query->where(function ($q) use ($user) {
                // CDC: requests with pending approval matching user role
                $q->whereHas('approvals', function ($aq) use ($user) {
                    $aq->where('status', 'pending')
                       ->where('required_role', $user->role)
                       ->where('level', function ($sub) {
                           $sub->selectRaw('MIN(level)')
                               ->from('request_approvals as ra2')
                               ->whereColumn('ra2.request_id', 'request_approvals.request_id')
                               ->where('ra2.status', 'pending');
                       });
                })
                // Legacy fallback
                ->orWhere(function ($legacyQ) {
                    $legacyQ->doesntHave('approvals')
                        ->where(function ($subQ) {
                            $subQ->where(function ($q2) {
                                $q2->whereIn('priority', ['low', 'normal', 'high'])
                                    ->where(function ($roleQ) {
                                        $roleQ->where('validation_required_by_role', 'supervisor')
                                              ->orWhereNull('validation_required_by_role');
                                    });
                            })
                            ->orWhere(function ($q2) {
                                $q2->whereIn('priority', ['critical', 'emergency'])
                                    ->where(function ($statusQ) {
                                        $statusQ->where('validation_status_level1', 'pending')
                                                ->orWhereNull('validation_status_level1');
                                    });
                            });
                        });
                });
            });
        } elseif ($user->canValidateLevel2()) {
            $query->where(function ($q) use ($user) {
                // CDC: requests with pending approval matching user role at lowest pending level
                $q->whereHas('approvals', function ($aq) use ($user) {
                    $aq->where('status', 'pending')
                       ->where(function ($roleQ) use ($user) {
                           $roleQ->where('required_role', $user->role);
                       })
                       ->where('level', function ($sub) {
                           $sub->selectRaw('MIN(level)')
                               ->from('request_approvals as ra2')
                               ->whereColumn('ra2.request_id', 'request_approvals.request_id')
                               ->where('ra2.status', 'pending');
                       });
                })
                // Legacy fallback
                ->orWhere(function ($legacyQ) {
                    $legacyQ->doesntHave('approvals')
                        ->where(function ($subQ) {
                            $subQ->whereIn('priority', ['low', 'normal', 'high'])
                                ->orWhere(function ($q2) {
                                    $q2->whereIn('priority', ['critical', 'emergency'])
                                        ->where('validation_status_level1', 'approved')
                                        ->where(function ($statusQ) {
                                            $statusQ->where('validation_status_level2', 'pending')
                                                    ->orWhereNull('validation_status_level2');
                                        });
                                })
                                ->orWhere(function ($q2) {
                                    $q2->whereIn('priority', ['critical', 'emergency'])
                                        ->where(function ($statusQ) {
                                            $statusQ->where('validation_status_level1', 'pending')
                                                    ->orWhereNull('validation_status_level1');
                                        });
                                });
                        });
                });
            });
        }

        return RequestResource::collection($query->orderBy('created_at', 'desc')->paginate(15));
    }

    public function validate_list(): AnonymousResourceCollection|JsonResponse
    {
        if (!auth()->user()->canValidateRequests()) {
            return response()->json(['message' => 'Non autorisé'], 403);
        }

        $requests = Request::with(['requester', 'equipment.zone', 'sensor', 'ora', 'approvals'])
            ->active()
            ->orderBy('created_at', 'asc')
            ->paginate(15);

        return RequestResource::collection($requests);
    }

    /**
     * CDC: submit a draft
     */
    public function submit(Request $request): RequestResource|JsonResponse
    {
        $updatedRequest = $this->requestService->submitDraft($request, auth()->user());

        return new RequestResource(
            $updatedRequest->load(['requester', 'equipment.zone', 'sensor', 'ora', 'approvals'])
        );
    }

    /**
     * CDC: activate an approved bypass
     */
    public function activate(Request $request): RequestResource|JsonResponse
    {
        $updatedRequest = $this->requestService->activateApprovedBypass($request, auth()->user());

        return new RequestResource(
            $updatedRequest->load(['requester', 'equipment.zone', 'sensor', 'ora', 'approvals'])
        );
    }

    /**
     * CDC: close an active bypass
     */
    public function close(Request $request): RequestResource|JsonResponse
    {
        $updatedRequest = $this->requestService->closeBypass($request, auth()->user());

        return new RequestResource(
            $updatedRequest->load(['requester', 'equipment.zone', 'sensor', 'ora', 'approvals'])
        );
    }

    public function update(UpdateRequestRequest $httpRequest, Request $request): RequestResource|JsonResponse
    {
        $request->update($httpRequest->validated());
        $request->refresh();

        AuditLog::log('Request Updated', auth()->user(), 'Request', $request->id, ['title' => $request->title]);

        $this->requestService->notifyUpdate($request);

        return new RequestResource($request->load(['equipment', 'sensor', 'requester', 'ora', 'approvals']));
    }

    public function destroy(Request $request): JsonResponse
    {
        $user = auth()->user();

        if (!$user->hasPermissionTo('requests.view.all') && $request->requester_id !== $user->id) {
            return response()->json(['message' => 'Non autorisé'], 403);
        }

        if (!in_array($request->status, [RequestStatus::Pending->value, RequestStatus::Draft->value])) {
            return response()->json(['message' => 'Impossible de supprimer une demande déjà traitée'], 422);
        }

        AuditLog::log('Request Deleted', $user, 'Request', $request->id, ['title' => $request->title]);
        $request->delete();

        return response()->json(['message' => 'Demande supprimée avec succès']);
    }

    public function markAsRead(Request $request, $id): JsonResponse
    {
        $notification = auth()->user()->notifications()->find($id);

        if (!$notification) {
            return response()->json(['message' => 'Notification non trouvée'], 404);
        }

        if (!$notification->read_at) {
            $notification->markAsRead();
        }

        return response()->json([
            'message' => 'Notification marquée comme lue',
            'notification' => $notification,
        ]);
    }

    public function sendMessage(Request $request): JsonResponse
    {
        $request->validate([
            'to' => 'required|string',
            'message' => 'required|string|max:4096',
        ]);

        try {
            $to = $this->whapiService->formatPhoneNumber($request->to);
            $result = $this->whapiService->sendTextMessage($to, $request->message);
            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}
