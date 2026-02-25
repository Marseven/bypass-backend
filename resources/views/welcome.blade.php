<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>ByPass API</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Inter', system-ui, -apple-system, sans-serif; background: #0f172a; color: #e2e8f0; min-height: 100vh; display: flex; align-items: center; justify-content: center; }
        .container { max-width: 960px; width: 100%; padding: 2rem; }
        .header { text-align: center; margin-bottom: 3rem; }
        .logo { display: inline-flex; align-items: center; gap: 0.75rem; margin-bottom: 1.5rem; }
        .logo-icon { width: 48px; height: 48px; background: linear-gradient(135deg, #f59e0b, #d97706); border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; font-weight: 700; color: #0f172a; }
        .logo-text { font-size: 1.75rem; font-weight: 700; color: #f8fafc; }
        .logo-text span { color: #f59e0b; }
        .subtitle { color: #94a3b8; font-size: 1.1rem; line-height: 1.6; max-width: 600px; margin: 0 auto; }
        .badge { display: inline-block; background: #1e293b; border: 1px solid #334155; border-radius: 9999px; padding: 0.25rem 0.75rem; font-size: 0.75rem; color: #f59e0b; font-weight: 600; margin-bottom: 1rem; letter-spacing: 0.05em; text-transform: uppercase; }
        .grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1.25rem; margin-bottom: 2.5rem; }
        .card { background: #1e293b; border: 1px solid #334155; border-radius: 12px; padding: 1.5rem; transition: border-color 0.2s; }
        .card:hover { border-color: #475569; }
        .card-icon { width: 40px; height: 40px; border-radius: 8px; display: flex; align-items: center; justify-content: center; margin-bottom: 1rem; font-size: 1.25rem; }
        .card-icon.orange { background: rgba(245, 158, 11, 0.15); color: #f59e0b; }
        .card-icon.blue { background: rgba(59, 130, 246, 0.15); color: #3b82f6; }
        .card-icon.green { background: rgba(34, 197, 94, 0.15); color: #22c55e; }
        .card-icon.purple { background: rgba(168, 85, 247, 0.15); color: #a855f7; }
        .card-icon.red { background: rgba(239, 68, 68, 0.15); color: #ef4444; }
        .card-icon.cyan { background: rgba(6, 182, 212, 0.15); color: #06b6d4; }
        .card h3 { font-size: 1rem; font-weight: 600; color: #f1f5f9; margin-bottom: 0.5rem; }
        .card p { font-size: 0.875rem; color: #94a3b8; line-height: 1.5; }
        .endpoints { background: #1e293b; border: 1px solid #334155; border-radius: 12px; padding: 1.5rem; margin-bottom: 2.5rem; }
        .endpoints h3 { font-size: 1rem; font-weight: 600; color: #f1f5f9; margin-bottom: 1rem; }
        .endpoint { display: flex; align-items: center; gap: 0.75rem; padding: 0.5rem 0; border-bottom: 1px solid #334155; }
        .endpoint:last-child { border-bottom: none; }
        .method { font-size: 0.7rem; font-weight: 700; padding: 0.15rem 0.5rem; border-radius: 4px; min-width: 44px; text-align: center; letter-spacing: 0.03em; }
        .method.get { background: rgba(34, 197, 94, 0.15); color: #22c55e; }
        .method.post { background: rgba(59, 130, 246, 0.15); color: #3b82f6; }
        .endpoint-path { font-family: 'SF Mono', 'Fira Code', monospace; font-size: 0.85rem; color: #cbd5e1; }
        .endpoint-desc { font-size: 0.8rem; color: #64748b; margin-left: auto; }
        .tech { display: flex; flex-wrap: wrap; justify-content: center; gap: 0.5rem; margin-bottom: 2rem; }
        .tech-badge { background: #1e293b; border: 1px solid #334155; border-radius: 6px; padding: 0.35rem 0.75rem; font-size: 0.75rem; color: #94a3b8; font-weight: 500; }
        .footer { text-align: center; color: #475569; font-size: 0.8rem; }
        .footer a { color: #f59e0b; text-decoration: none; }
        .footer a:hover { text-decoration: underline; }
        .status { display: inline-flex; align-items: center; gap: 0.4rem; background: rgba(34, 197, 94, 0.1); border: 1px solid rgba(34, 197, 94, 0.2); border-radius: 9999px; padding: 0.25rem 0.75rem; font-size: 0.75rem; color: #22c55e; font-weight: 500; margin-top: 1rem; }
        .status-dot { width: 6px; height: 6px; background: #22c55e; border-radius: 50%; animation: pulse 2s infinite; }
        @keyframes pulse { 0%, 100% { opacity: 1; } 50% { opacity: 0.5; } }
        @media (max-width: 640px) { .endpoint-desc { display: none; } .grid { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="badge">API REST v1</div>
            <div class="logo">
                <div class="logo-icon">B</div>
                <div class="logo-text">By<span>Pass</span> API</div>
            </div>
            <p class="subtitle">
                Plateforme de gestion des demandes de bypass industriel.
                Permet de creer, valider et suivre les demandes de contournement
                de capteurs sur des equipements industriels.
            </p>
            <div class="status">
                <span class="status-dot"></span>
                Operationnel
            </div>
        </div>

        <div class="grid">
            <div class="card">
                <div class="card-icon orange">&#9881;</div>
                <h3>Gestion des Bypass</h3>
                <p>Cycle complet : creation, validation niveau 1 et 2, approbation ou rejet. Support des urgences avec double validation.</p>
            </div>
            <div class="card">
                <div class="card-icon blue">&#9878;</div>
                <h3>Zones & Equipements</h3>
                <p>Organisation hierarchique des installations : zones, equipements et capteurs avec import CSV en masse.</p>
            </div>
            <div class="card">
                <div class="card-icon green">&#9889;</div>
                <h3>Notifications Temps Reel</h3>
                <p>WebSocket via Pusher et alertes WhatsApp (WHAPI) pour informer les equipes a chaque etape du workflow.</p>
            </div>
            <div class="card">
                <div class="card-icon purple">&#128274;</div>
                <h3>Securite & RBAC</h3>
                <p>Authentification Sanctum, 8 roles metier, rate limiting, tokens a expiration et headers de securite.</p>
            </div>
            <div class="card">
                <div class="card-icon red">&#128202;</div>
                <h3>Dashboard & Statistiques</h3>
                <p>Tableau de bord avec indicateurs cles, tendances et top capteurs bypasses. Donnees mises en cache.</p>
            </div>
            <div class="card">
                <div class="card-icon cyan">&#128209;</div>
                <h3>Audit & Tracabilite</h3>
                <p>Journal d'audit complet de toutes les actions. Soft delete sur les donnees metier. Purge automatisee.</p>
            </div>
        </div>

        <div class="endpoints">
            <h3>Principaux Endpoints</h3>
            <div class="endpoint">
                <span class="method get">GET</span>
                <span class="endpoint-path">/api/health</span>
                <span class="endpoint-desc">Etat de sante de l'API</span>
            </div>
            <div class="endpoint">
                <span class="method post">POST</span>
                <span class="endpoint-path">/api/v1/auth/login</span>
                <span class="endpoint-desc">Authentification</span>
            </div>
            <div class="endpoint">
                <span class="method get">GET</span>
                <span class="endpoint-path">/api/v1/requests</span>
                <span class="endpoint-desc">Liste des demandes de bypass</span>
            </div>
            <div class="endpoint">
                <span class="method post">POST</span>
                <span class="endpoint-path">/api/v1/requests</span>
                <span class="endpoint-desc">Creer une demande</span>
            </div>
            <div class="endpoint">
                <span class="method get">GET</span>
                <span class="endpoint-path">/api/v1/zones</span>
                <span class="endpoint-desc">Zones industrielles</span>
            </div>
            <div class="endpoint">
                <span class="method get">GET</span>
                <span class="endpoint-path">/api/v1/equipment</span>
                <span class="endpoint-desc">Equipements</span>
            </div>
            <div class="endpoint">
                <span class="method get">GET</span>
                <span class="endpoint-path">/api/v1/sensors</span>
                <span class="endpoint-desc">Capteurs</span>
            </div>
            <div class="endpoint">
                <span class="method get">GET</span>
                <span class="endpoint-path">/api/v1/dashboard/summary</span>
                <span class="endpoint-desc">Statistiques globales</span>
            </div>
        </div>

        <div class="tech">
            <span class="tech-badge">Laravel 11</span>
            <span class="tech-badge">PHP 8.2</span>
            <span class="tech-badge">MySQL</span>
            <span class="tech-badge">Sanctum</span>
            <span class="tech-badge">Spatie Permissions</span>
            <span class="tech-badge">Pusher</span>
            <span class="tech-badge">WHAPI</span>
            <span class="tech-badge">OpenAPI / Swagger</span>
        </div>

        <div class="footer">
            <p>ByPass API &mdash; {{ date('Y') }} &mdash; <a href="/api/documentation">Documentation Swagger</a></p>
            <p style="margin-top: 0.5rem;">Laravel v{{ Illuminate\Foundation\Application::VERSION }} &middot; PHP v{{ PHP_VERSION }}</p>
        </div>
    </div>
</body>
</html>
