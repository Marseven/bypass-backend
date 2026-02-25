<?php

namespace App\Http\Controllers;

use OpenApi\Attributes as OA;

#[OA\Info(
    version: "1.0.0",
    title: "ByPass API",
    description: "API de gestion de bypass réalisée par JOBS_conseil. Cette API permet de gérer les demandes de bypass, les équipements, les zones, les capteurs et les utilisateurs."
)]
#[OA\Server(
    url: "/api",
    description: "Serveur API ByPass"
)]
#[OA\SecurityScheme(
    securityScheme: "sanctum",
    type: "apiKey",
    name: "Authorization",
    in: "header",
    description: "Token d'authentification Bearer obtenu via /api/auth/login. Format: 'Bearer {token}'"
)]
#[OA\Tag(
    name: "Authentification",
    description: "Endpoints pour l'authentification des utilisateurs"
)]
#[OA\Tag(
    name: "Dashboard",
    description: "Endpoints pour le tableau de bord"
)]
#[OA\Tag(
    name: "Demandes",
    description: "Endpoints pour la gestion des demandes de bypass"
)]
#[OA\Tag(
    name: "Zones",
    description: "Endpoints pour la gestion des zones"
)]
#[OA\Tag(
    name: "Équipements",
    description: "Endpoints pour la gestion des équipements"
)]
#[OA\Tag(
    name: "Capteurs",
    description: "Endpoints pour la gestion des capteurs"
)]
#[OA\Tag(
    name: "Utilisateurs",
    description: "Endpoints pour la gestion des utilisateurs"
)]
#[OA\Tag(
    name: "Système",
    description: "Endpoints d'administration système"
)]
#[OA\Schema(
    schema: "Error",
    type: "object",
    properties: [
        new OA\Property(property: "message", type: "string", description: "Message d'erreur"),
    ]
)]
#[OA\Schema(
    schema: "Success",
    type: "object",
    properties: [
        new OA\Property(property: "message", type: "string", description: "Message de succès"),
    ]
)]
#[OA\Schema(
    schema: "User",
    type: "object",
    properties: [
        new OA\Property(property: "id", type: "integer", example: 1),
        new OA\Property(property: "username", type: "string", example: "admin"),
        new OA\Property(property: "email", type: "string", format: "email", example: "admin@example.com"),
        new OA\Property(property: "full_name", type: "string", example: "Administrateur Système"),
        new OA\Property(property: "role", type: "string", enum: ["administrator", "supervisor", "operator"], example: "administrator"),
        new OA\Property(property: "phone", type: "string", nullable: true, example: "+33123456789"),
        new OA\Property(property: "is_active", type: "boolean", example: true),
    ]
)]
#[OA\Schema(
    schema: "Request",
    type: "object",
    properties: [
        new OA\Property(property: "id", type: "integer", example: 1),
        new OA\Property(property: "request_code", type: "string", example: "BR-2025-001"),
        new OA\Property(property: "requester_id", type: "integer", example: 1),
        new OA\Property(property: "title", type: "string", example: "Maintenance préventive"),
        new OA\Property(property: "description", type: "string", example: "Description détaillée"),
        new OA\Property(property: "priority", type: "string", enum: ["low", "normal", "high", "critical", "emergency"], example: "high"),
        new OA\Property(property: "status", type: "string", enum: ["pending", "approved", "rejected", "in_progress", "completed"], example: "pending"),
        new OA\Property(property: "equipment_id", type: "integer", example: 1),
        new OA\Property(property: "sensor_id", type: "integer", example: 1),
        new OA\Property(property: "validated_by_id", type: "integer", nullable: true, example: 2),
        new OA\Property(property: "submitted_at", type: "string", format: "date-time"),
        new OA\Property(property: "validated_at", type: "string", format: "date-time", nullable: true),
        new OA\Property(property: "start_time", type: "string", format: "date-time"),
        new OA\Property(property: "end_time", type: "string", format: "date-time"),
    ]
)]
#[OA\Schema(
    schema: "Equipment",
    type: "object",
    properties: [
        new OA\Property(property: "id", type: "integer", example: 1),
        new OA\Property(property: "code", type: "string", example: "EQ-ZA-001"),
        new OA\Property(property: "name", type: "string", example: "Équipement 1"),
        new OA\Property(property: "type", type: "string", example: "Capteur de température"),
        new OA\Property(property: "status", type: "string", enum: ["operational", "maintenance", "down", "standby"], example: "operational"),
        new OA\Property(property: "zone_id", type: "integer", example: 1),
    ]
)]
#[OA\Schema(
    schema: "Zone",
    type: "object",
    properties: [
        new OA\Property(property: "id", type: "integer", example: 1),
        new OA\Property(property: "name", type: "string", example: "Zone A"),
        new OA\Property(property: "description", type: "string", nullable: true, example: "Description de la zone"),
        new OA\Property(property: "status", type: "string", enum: ["active", "inactive", "maintenance"], example: "active"),
    ]
)]
#[OA\Schema(
    schema: "Sensor",
    type: "object",
    properties: [
        new OA\Property(property: "id", type: "integer", example: 1),
        new OA\Property(property: "equipment_id", type: "integer", example: 1),
        new OA\Property(property: "name", type: "string", example: "Capteur Température"),
        new OA\Property(property: "type", type: "string", example: "Température"),
        new OA\Property(property: "code", type: "string", example: "TEMP-001"),
        new OA\Property(property: "status", type: "string", example: "active"),
    ]
)]
#[OA\Schema(
    schema: "PaginatedResponse",
    type: "object",
    properties: [
        new OA\Property(property: "data", type: "array", items: new OA\Items(type: "object")),
        new OA\Property(property: "current_page", type: "integer", example: 1),
        new OA\Property(property: "last_page", type: "integer", example: 5),
        new OA\Property(property: "per_page", type: "integer", example: 15),
        new OA\Property(property: "total", type: "integer", example: 75),
        new OA\Property(property: "from", type: "integer", nullable: true, example: 1),
        new OA\Property(property: "to", type: "integer", nullable: true, example: 15),
    ]
)]
class Controller
{
    //
}