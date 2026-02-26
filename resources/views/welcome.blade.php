<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>ByPass API - Documentation</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --bg: hsl(220 20% 7%);
            --bg-card: hsl(220 18% 10%);
            --bg-elevated: hsl(215 15% 13%);
            --border: hsl(220 15% 15%);
            --border-hover: hsl(220 15% 22%);
            --text: hsl(210 20% 92%);
            --text-muted: hsl(215 10% 50%);
            --text-subtle: hsl(215 10% 38%);
            --primary: hsl(185 70% 42%);
            --primary-glow: hsl(185 70% 52%);
            --primary-dim: hsla(185, 70%, 42%, 0.12);
            --primary-border: hsla(185, 70%, 42%, 0.25);
            --success: hsl(155 65% 38%);
            --success-dim: hsla(155, 65%, 38%, 0.12);
            --success-border: hsla(155, 65%, 38%, 0.25);
            --warning: hsl(40 90% 55%);
            --warning-dim: hsla(40, 90%, 55%, 0.12);
            --warning-border: hsla(40, 90%, 55%, 0.25);
            --info: hsl(215 80% 58%);
            --info-dim: hsla(215, 80%, 58%, 0.12);
            --info-border: hsla(215, 80%, 58%, 0.25);
            --destructive: hsl(0 75% 55%);
            --destructive-dim: hsla(0, 75%, 55%, 0.12);
            --destructive-border: hsla(0, 75%, 55%, 0.25);
            --purple: hsl(270 60% 58%);
            --purple-dim: hsla(270, 60%, 58%, 0.12);
            --purple-border: hsla(270, 60%, 58%, 0.25);
            --radius: 6px;
            --font-body: 'DM Sans', system-ui, -apple-system, sans-serif;
            --font-mono: 'JetBrains Mono', ui-monospace, monospace;
        }

        body {
            font-family: var(--font-body);
            background: var(--bg);
            color: var(--text);
            min-height: 100vh;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
            color-scheme: dark;
        }

        /* Grid background pattern */
        body::before {
            content: '';
            position: fixed;
            inset: 0;
            background-image:
                linear-gradient(hsla(185, 70%, 42%, 0.02) 1px, transparent 1px),
                linear-gradient(90deg, hsla(185, 70%, 42%, 0.02) 1px, transparent 1px);
            background-size: 60px 60px;
            pointer-events: none;
            z-index: 0;
        }

        .page { position: relative; z-index: 1; }

        /* ── Top bar ────────────────────────────────────────── */
        .topbar {
            border-bottom: 1px solid var(--border);
            background: hsla(220, 18%, 10%, 0.8);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            position: sticky;
            top: 0;
            z-index: 50;
        }
        .topbar-inner {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 2rem;
            height: 56px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .logo { display: flex; align-items: center; gap: 0.75rem; text-decoration: none; }
        .logo-icon {
            width: 36px; height: 36px;
            background: linear-gradient(135deg, var(--primary), var(--primary-glow));
            border-radius: 8px;
            display: flex; align-items: center; justify-content: center;
            font-family: var(--font-mono); font-weight: 700; font-size: 1rem; color: var(--bg);
        }
        .logo-text { font-family: var(--font-mono); font-weight: 700; font-size: 1.1rem; color: var(--text); }
        .logo-text span { color: var(--primary); }
        .topbar-links { display: flex; align-items: center; gap: 1rem; }
        .topbar-links a {
            display: inline-flex; align-items: center; gap: 0.4rem;
            font-size: 0.8rem; font-weight: 500; color: var(--text-muted);
            text-decoration: none; padding: 0.4rem 0.75rem;
            border-radius: var(--radius); transition: all 0.2s;
        }
        .topbar-links a:hover { color: var(--text); background: var(--primary-dim); }
        .topbar-links a svg { width: 14px; height: 14px; }

        /* ── Hero ───────────────────────────────────────────── */
        .hero {
            max-width: 1200px; margin: 0 auto; padding: 3.5rem 2rem 2.5rem;
            text-align: center;
        }
        .version-badge {
            display: inline-flex; align-items: center; gap: 0.4rem;
            background: var(--primary-dim); border: 1px solid var(--primary-border);
            border-radius: 9999px; padding: 0.25rem 0.85rem;
            font-family: var(--font-mono); font-size: 0.7rem; font-weight: 600;
            color: var(--primary); letter-spacing: 0.04em; text-transform: uppercase;
            margin-bottom: 1.25rem;
        }
        .hero h1 {
            font-family: var(--font-mono); font-size: 2.25rem; font-weight: 700;
            letter-spacing: -0.03em; color: var(--text); margin-bottom: 0.75rem;
        }
        .hero h1 span {
            background: linear-gradient(135deg, var(--primary), var(--primary-glow));
            -webkit-background-clip: text; -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        .hero p {
            color: var(--text-muted); font-size: 1rem; line-height: 1.7;
            max-width: 640px; margin: 0 auto 1.25rem;
        }
        .status-row { display: flex; align-items: center; justify-content: center; gap: 1.5rem; margin-top: 0.5rem; }
        .status-pill {
            display: inline-flex; align-items: center; gap: 0.4rem;
            background: var(--success-dim); border: 1px solid var(--success-border);
            border-radius: 9999px; padding: 0.3rem 0.85rem;
            font-size: 0.75rem; font-weight: 500; color: var(--success);
        }
        .status-dot {
            width: 6px; height: 6px; background: var(--success);
            border-radius: 50%; animation: pulse 2s infinite;
        }
        .version-pill {
            font-family: var(--font-mono); font-size: 0.7rem;
            color: var(--text-subtle);
        }

        @keyframes pulse { 0%, 100% { opacity: 1; } 50% { opacity: 0.4; } }

        /* ── Feature cards ──────────────────────────────────── */
        .features {
            max-width: 1200px; margin: 0 auto; padding: 0 2rem 2.5rem;
        }
        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1rem;
        }
        .feature {
            background: var(--bg-card); border: 1px solid var(--border);
            border-radius: 10px; padding: 1.5rem;
            transition: border-color 0.2s, box-shadow 0.2s;
        }
        .feature:hover { border-color: var(--border-hover); box-shadow: 0 4px 24px hsla(0,0%,0%,0.3); }
        .feature-icon {
            width: 38px; height: 38px; border-radius: 8px;
            display: flex; align-items: center; justify-content: center;
            margin-bottom: 1rem;
        }
        .feature-icon svg { width: 20px; height: 20px; }
        .feature-icon.primary { background: var(--primary-dim); color: var(--primary); }
        .feature-icon.info { background: var(--info-dim); color: var(--info); }
        .feature-icon.success { background: var(--success-dim); color: var(--success); }
        .feature-icon.purple { background: var(--purple-dim); color: var(--purple); }
        .feature-icon.destructive { background: var(--destructive-dim); color: var(--destructive); }
        .feature-icon.warning { background: var(--warning-dim); color: var(--warning); }
        .feature h3 {
            font-family: var(--font-mono); font-size: 0.9rem; font-weight: 600;
            color: var(--text); margin-bottom: 0.4rem; letter-spacing: -0.01em;
        }
        .feature p { font-size: 0.82rem; color: var(--text-muted); line-height: 1.55; }

        /* ── Endpoints ──────────────────────────────────────── */
        .endpoints-section {
            max-width: 1200px; margin: 0 auto; padding: 0 2rem 2.5rem;
        }
        .section-header {
            display: flex; align-items: center; gap: 0.5rem;
            margin-bottom: 1.25rem;
        }
        .section-header h2 {
            font-family: var(--font-mono); font-size: 1.15rem; font-weight: 700;
            letter-spacing: -0.02em;
        }
        .section-header .count {
            font-family: var(--font-mono); font-size: 0.7rem; font-weight: 600;
            color: var(--text-muted); background: var(--bg-elevated);
            border: 1px solid var(--border); border-radius: 9999px;
            padding: 0.15rem 0.6rem;
        }

        .endpoint-group {
            background: var(--bg-card); border: 1px solid var(--border);
            border-radius: 10px; margin-bottom: 0.75rem;
            overflow: hidden;
        }
        .group-toggle {
            width: 100%; display: flex; align-items: center; gap: 0.75rem;
            padding: 0.85rem 1.25rem; border: none; background: none;
            color: var(--text); cursor: pointer; font-family: var(--font-body);
            text-align: left; transition: background 0.15s;
        }
        .group-toggle:hover { background: hsla(215, 15%, 18%, 0.5); }
        .group-toggle svg { width: 18px; height: 18px; flex-shrink: 0; }
        .group-label { font-family: var(--font-mono); font-size: 0.82rem; font-weight: 600; flex: 1; }
        .group-badge {
            font-family: var(--font-mono); font-size: 0.65rem; font-weight: 600;
            padding: 0.15rem 0.55rem; border-radius: 9999px;
        }
        .group-badge.public { background: var(--success-dim); color: var(--success); border: 1px solid var(--success-border); }
        .group-badge.auth { background: var(--warning-dim); color: var(--warning); border: 1px solid var(--warning-border); }
        .group-badge.admin { background: var(--destructive-dim); color: var(--destructive); border: 1px solid var(--destructive-border); }
        .chevron {
            width: 16px; height: 16px; color: var(--text-subtle);
            transition: transform 0.2s; flex-shrink: 0;
        }

        /* Pure CSS toggle */
        .toggle-input { display: none; }
        .toggle-input:checked ~ .group-body { max-height: 2000px; }
        .toggle-input:checked ~ .group-toggle .chevron { transform: rotate(90deg); }
        .group-body {
            max-height: 0; overflow: hidden;
            transition: max-height 0.3s ease;
        }
        .group-body-inner { padding: 0 1.25rem 0.75rem; }

        .endpoint {
            display: flex; align-items: center; gap: 0.75rem;
            padding: 0.5rem 0; border-top: 1px solid hsla(220, 15%, 15%, 0.5);
        }
        .endpoint:first-child { border-top: none; }
        .method {
            font-family: var(--font-mono); font-size: 0.62rem; font-weight: 700;
            padding: 0.18rem 0.5rem; border-radius: 4px;
            min-width: 52px; text-align: center; letter-spacing: 0.04em;
            flex-shrink: 0;
        }
        .method.get { background: var(--success-dim); color: var(--success); border: 1px solid var(--success-border); }
        .method.post { background: var(--info-dim); color: var(--info); border: 1px solid var(--info-border); }
        .method.put { background: var(--warning-dim); color: var(--warning); border: 1px solid var(--warning-border); }
        .method.delete { background: var(--destructive-dim); color: var(--destructive); border: 1px solid var(--destructive-border); }
        .endpoint-path {
            font-family: var(--font-mono); font-size: 0.8rem; color: var(--text);
            white-space: nowrap;
        }
        .endpoint-path .param { color: var(--primary); }
        .endpoint-desc {
            font-size: 0.75rem; color: var(--text-subtle);
            margin-left: auto; white-space: nowrap;
        }

        /* ── Tech footer ────────────────────────────────────── */
        .tech-section {
            max-width: 1200px; margin: 0 auto; padding: 0 2rem 1.5rem;
        }
        .tech-row {
            display: flex; flex-wrap: wrap; justify-content: center; gap: 0.5rem;
            margin-bottom: 1.5rem;
        }
        .tech-badge {
            background: var(--bg-card); border: 1px solid var(--border);
            border-radius: var(--radius); padding: 0.35rem 0.75rem;
            font-family: var(--font-mono); font-size: 0.7rem; font-weight: 500;
            color: var(--text-muted); transition: border-color 0.2s;
        }
        .tech-badge:hover { border-color: var(--border-hover); }
        .footer {
            text-align: center; padding: 1.5rem 2rem 2.5rem;
            border-top: 1px solid var(--border);
            max-width: 1200px; margin: 0 auto;
        }
        .footer p { font-size: 0.78rem; color: var(--text-subtle); }
        .footer a { color: var(--primary); text-decoration: none; font-weight: 500; }
        .footer a:hover { text-decoration: underline; }
        .footer .versions {
            font-family: var(--font-mono); font-size: 0.7rem;
            color: var(--text-subtle); margin-top: 0.5rem;
        }

        /* ── Responsive ─────────────────────────────────────── */
        @media (max-width: 640px) {
            .hero { padding: 2.5rem 1.25rem 2rem; }
            .hero h1 { font-size: 1.5rem; }
            .features, .endpoints-section, .tech-section { padding-left: 1.25rem; padding-right: 1.25rem; }
            .topbar-inner { padding: 0 1.25rem; }
            .topbar-links a span { display: none; }
            .endpoint-desc { display: none; }
            .features-grid { grid-template-columns: 1fr; }
            .status-row { flex-direction: column; gap: 0.5rem; }
        }
    </style>
</head>
<body>
<div class="page">

    <!-- Top bar -->
    <nav class="topbar">
        <div class="topbar-inner">
            <a href="/" class="logo">
                <div class="logo-icon">B</div>
                <div class="logo-text">By<span>Pass</span> API</div>
            </a>
            <div class="topbar-links">
                <a href="/api/health">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3.332.65-4.5 1.72A7.5 7.5 0 0 0 7.5 3 5.5 5.5 0 0 0 2 8.5c0 2.3 1.5 4.05 3 5.5l7 7Z"/></svg>
                    <span>Health</span>
                </a>
                <a href="/api/documentation">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z"/><path d="M14 2v4a2 2 0 0 0 2 2h4"/><path d="M10 9H8"/><path d="M16 13H8"/><path d="M16 17H8"/></svg>
                    <span>Swagger</span>
                </a>
            </div>
        </div>
    </nav>

    <!-- Hero -->
    <section class="hero">
        <div class="version-badge">
            <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2 2 7l10 5 10-5-10-5Z"/><path d="m2 17 10 5 10-5"/><path d="m2 12 10 5 10-5"/></svg>
            REST API v1
        </div>
        <h1>Industrial <span>Bypass</span> Management</h1>
        <p>
            Plateforme de gestion des demandes de bypass industriel.
            Creation, validation multi-niveaux et suivi en temps reel
            des contournements de capteurs sur equipements critiques.
        </p>
        <div class="status-row">
            <div class="status-pill">
                <span class="status-dot"></span>
                Operationnel
            </div>
            <span class="version-pill">
                Laravel v{{ Illuminate\Foundation\Application::VERSION }} / PHP v{{ PHP_VERSION }}
            </span>
        </div>
    </section>

    <!-- Features -->
    <section class="features">
        <div class="features-grid">
            <div class="feature">
                <div class="feature-icon primary">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12.22 2h-.44a2 2 0 0 0-2 2v.18a2 2 0 0 1-1 1.73l-.43.25a2 2 0 0 1-2 0l-.15-.08a2 2 0 0 0-2.73.73l-.22.38a2 2 0 0 0 .73 2.73l.15.1a2 2 0 0 1 1 1.72v.51a2 2 0 0 1-1 1.74l-.15.09a2 2 0 0 0-.73 2.73l.22.38a2 2 0 0 0 2.73.73l.15-.08a2 2 0 0 1 2 0l.43.25a2 2 0 0 1 1 1.73V20a2 2 0 0 0 2 2h.44a2 2 0 0 0 2-2v-.18a2 2 0 0 1 1-1.73l.43-.25a2 2 0 0 1 2 0l.15.08a2 2 0 0 0 2.73-.73l.22-.39a2 2 0 0 0-.73-2.73l-.15-.08a2 2 0 0 1-1-1.74v-.5a2 2 0 0 1 1-1.74l.15-.09a2 2 0 0 0 .73-2.73l-.22-.38a2 2 0 0 0-2.73-.73l-.15.08a2 2 0 0 1-2 0l-.43-.25a2 2 0 0 1-1-1.73V4a2 2 0 0 0-2-2z"/><circle cx="12" cy="12" r="3"/></svg>
                </div>
                <h3>Gestion des Bypass</h3>
                <p>Cycle complet : creation, validation niveau 1 et 2, approbation ou rejet. Double validation pour les urgences.</p>
            </div>
            <div class="feature">
                <div class="feature-icon info">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 10c0 4.993-5.539 10.193-7.399 11.799a1 1 0 0 1-1.202 0C9.539 20.193 4 14.993 4 10a8 8 0 0 1 16 0"/><circle cx="12" cy="10" r="3"/></svg>
                </div>
                <h3>Zones & Equipements</h3>
                <p>Organisation hierarchique des installations : sites, zones, equipements et capteurs. Import CSV en masse.</p>
            </div>
            <div class="feature">
                <div class="feature-icon success">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/><path d="M10.3 21a1.94 1.94 0 0 0 3.4 0"/></svg>
                </div>
                <h3>Notifications Temps Reel</h3>
                <p>WebSocket via Pusher et alertes WhatsApp (WHAPI) pour informer les equipes a chaque etape du workflow.</p>
            </div>
            <div class="feature">
                <div class="feature-icon purple">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10"/></svg>
                </div>
                <h3>Securite & RBAC</h3>
                <p>Authentification Sanctum, 4 roles metier, rate limiting, tokens a expiration et headers de securite.</p>
            </div>
            <div class="feature">
                <div class="feature-icon warning">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 3v16a2 2 0 0 0 2 2h16"/><path d="m19 9-5 5-4-4-3 3"/></svg>
                </div>
                <h3>Dashboard & Statistiques</h3>
                <p>Indicateurs cles, tendances et top capteurs. Donnees mises en cache avec invalidation automatique.</p>
            </div>
            <div class="feature">
                <div class="feature-icon destructive">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 12h.01"/><path d="M16 6V4a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v2"/><path d="M22 13a18.15 18.15 0 0 1-20 0"/><rect width="20" height="14" x="2" y="6" rx="2"/></svg>
                </div>
                <h3>Audit & Tracabilite</h3>
                <p>Journal d'audit complet. Soft delete sur les donnees metier. Purge automatisee des anciennes traces.</p>
            </div>
        </div>
    </section>

    <!-- API Endpoints -->
    <section class="endpoints-section">
        <div class="section-header">
            <h2>API Endpoints</h2>
            <span class="count">50+ routes</span>
        </div>

        {{-- Public --}}
        <div class="endpoint-group">
            <input type="checkbox" id="grp-public" class="toggle-input" checked>
            <label for="grp-public" class="group-toggle">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="var(--success)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="m9 12 2 2 4-4"/></svg>
                <span class="group-label">Public</span>
                <span class="group-badge public">No Auth</span>
                <svg class="chevron" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>
            </label>
            <div class="group-body"><div class="group-body-inner">
                <div class="endpoint">
                    <span class="method get">GET</span>
                    <span class="endpoint-path">/api/health</span>
                    <span class="endpoint-desc">Etat de sante (DB, Cache, Queue)</span>
                </div>
                <div class="endpoint">
                    <span class="method get">GET</span>
                    <span class="endpoint-path">/api/v1/settings/public</span>
                    <span class="endpoint-desc">Parametres publics d'affichage</span>
                </div>
                <div class="endpoint">
                    <span class="method post">POST</span>
                    <span class="endpoint-path">/api/v1/auth/login</span>
                    <span class="endpoint-desc">Authentification (throttle:5,1)</span>
                </div>
            </div></div>
        </div>

        {{-- Auth --}}
        <div class="endpoint-group">
            <input type="checkbox" id="grp-auth" class="toggle-input">
            <label for="grp-auth" class="group-toggle">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="var(--warning)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="7.5" cy="15.5" r="5.5"/><path d="m21 2-9.6 9.6"/><path d="m15.5 7.5 3 3L22 7l-3-3"/></svg>
                <span class="group-label">Authentification</span>
                <span class="group-badge auth">Sanctum</span>
                <svg class="chevron" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>
            </label>
            <div class="group-body"><div class="group-body-inner">
                <div class="endpoint">
                    <span class="method post">POST</span>
                    <span class="endpoint-path">/api/v1/auth/logout</span>
                    <span class="endpoint-desc">Deconnexion</span>
                </div>
                <div class="endpoint">
                    <span class="method get">GET</span>
                    <span class="endpoint-path">/api/v1/auth/me</span>
                    <span class="endpoint-desc">Profil utilisateur courant</span>
                </div>
            </div></div>
        </div>

        {{-- Dashboard --}}
        <div class="endpoint-group">
            <input type="checkbox" id="grp-dash" class="toggle-input">
            <label for="grp-dash" class="group-toggle">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="var(--primary)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 3v16a2 2 0 0 0 2 2h16"/><path d="m19 9-5 5-4-4-3 3"/></svg>
                <span class="group-label">Dashboard</span>
                <span class="group-badge auth">dashboard.view</span>
                <svg class="chevron" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>
            </label>
            <div class="group-body"><div class="group-body-inner">
                <div class="endpoint"><span class="method get">GET</span><span class="endpoint-path">/api/v1/dashboard/summary</span><span class="endpoint-desc">Statistiques globales</span></div>
                <div class="endpoint"><span class="method get">GET</span><span class="endpoint-path">/api/v1/dashboard/recent-requests</span><span class="endpoint-desc">Demandes recentes</span></div>
                <div class="endpoint"><span class="method get">GET</span><span class="endpoint-path">/api/v1/dashboard/system-status</span><span class="endpoint-desc">Etat du systeme</span></div>
                <div class="endpoint"><span class="method get">GET</span><span class="endpoint-path">/api/v1/dashboard/request-statistics</span><span class="endpoint-desc">Statistiques par periode</span></div>
                <div class="endpoint"><span class="method get">GET</span><span class="endpoint-path">/api/v1/dashboard/top-sensors</span><span class="endpoint-desc">Capteurs les plus bypasses</span></div>
            </div></div>
        </div>

        {{-- Requests --}}
        <div class="endpoint-group">
            <input type="checkbox" id="grp-requests" class="toggle-input">
            <label for="grp-requests" class="group-toggle">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="var(--primary)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z"/><path d="M14 2v4a2 2 0 0 0 2 2h4"/><path d="M9 15h6"/><path d="M12 18v-6"/></svg>
                <span class="group-label">Demandes de Bypass</span>
                <span class="group-badge auth">requests.*</span>
                <svg class="chevron" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>
            </label>
            <div class="group-body"><div class="group-body-inner">
                <div class="endpoint"><span class="method get">GET</span><span class="endpoint-path">/api/v1/requests</span><span class="endpoint-desc">Toutes les demandes</span></div>
                <div class="endpoint"><span class="method get">GET</span><span class="endpoint-path">/api/v1/requests/mine</span><span class="endpoint-desc">Mes demandes</span></div>
                <div class="endpoint"><span class="method get">GET</span><span class="endpoint-path">/api/v1/requests/pending</span><span class="endpoint-desc">En attente de validation</span></div>
                <div class="endpoint"><span class="method get">GET</span><span class="endpoint-path">/api/v1/requests/active</span><span class="endpoint-desc">Bypass actifs</span></div>
                <div class="endpoint"><span class="method post">POST</span><span class="endpoint-path">/api/v1/requests</span><span class="endpoint-desc">Creer une demande</span></div>
                <div class="endpoint"><span class="method get">GET</span><span class="endpoint-path">/api/v1/requests/<span class="param">{id}</span></span><span class="endpoint-desc">Detail d'une demande</span></div>
                <div class="endpoint"><span class="method put">PUT</span><span class="endpoint-path">/api/v1/requests/<span class="param">{id}</span></span><span class="endpoint-desc">Modifier une demande</span></div>
                <div class="endpoint"><span class="method put">PUT</span><span class="endpoint-path">/api/v1/requests/<span class="param">{id}</span>/submit</span><span class="endpoint-desc">Soumettre pour validation</span></div>
                <div class="endpoint"><span class="method put">PUT</span><span class="endpoint-path">/api/v1/requests/<span class="param">{id}</span>/validate</span><span class="endpoint-desc">Valider (niveau 1 ou 2)</span></div>
                <div class="endpoint"><span class="method put">PUT</span><span class="endpoint-path">/api/v1/requests/<span class="param">{id}</span>/activate</span><span class="endpoint-desc">Activer le bypass</span></div>
                <div class="endpoint"><span class="method put">PUT</span><span class="endpoint-path">/api/v1/requests/<span class="param">{id}</span>/close</span><span class="endpoint-desc">Cloturer le bypass</span></div>
                <div class="endpoint"><span class="method delete">DELETE</span><span class="endpoint-path">/api/v1/requests/<span class="param">{id}</span></span><span class="endpoint-desc">Supprimer</span></div>
            </div></div>
        </div>

        {{-- ORA --}}
        <div class="endpoint-group">
            <input type="checkbox" id="grp-ora" class="toggle-input">
            <label for="grp-ora" class="group-toggle">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="var(--purple)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
                <span class="group-label">ORA (Analyse de Risques)</span>
                <span class="group-badge auth">Sanctum</span>
                <svg class="chevron" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>
            </label>
            <div class="group-body"><div class="group-body-inner">
                <div class="endpoint"><span class="method post">POST</span><span class="endpoint-path">/api/v1/requests/<span class="param">{id}</span>/ora</span><span class="endpoint-desc">Creer une ORA</span></div>
                <div class="endpoint"><span class="method get">GET</span><span class="endpoint-path">/api/v1/requests/<span class="param">{id}</span>/ora</span><span class="endpoint-desc">Consulter l'ORA</span></div>
                <div class="endpoint"><span class="method put">PUT</span><span class="endpoint-path">/api/v1/oras/<span class="param">{id}</span>/validate</span><span class="endpoint-desc">Valider l'ORA</span></div>
            </div></div>
        </div>

        {{-- Sites --}}
        <div class="endpoint-group">
            <input type="checkbox" id="grp-sites" class="toggle-input">
            <label for="grp-sites" class="group-toggle">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="var(--info)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="16" height="20" x="4" y="2" rx="2"/><path d="M9 22v-4h6v4"/><path d="M8 6h.01"/><path d="M16 6h.01"/><path d="M12 6h.01"/><path d="M12 10h.01"/><path d="M12 14h.01"/><path d="M16 10h.01"/><path d="M16 14h.01"/><path d="M8 10h.01"/><path d="M8 14h.01"/></svg>
                <span class="group-label">Sites</span>
                <span class="group-badge auth">zones.*</span>
                <svg class="chevron" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>
            </label>
            <div class="group-body"><div class="group-body-inner">
                <div class="endpoint"><span class="method get">GET</span><span class="endpoint-path">/api/v1/sites</span><span class="endpoint-desc">Liste des sites</span></div>
                <div class="endpoint"><span class="method get">GET</span><span class="endpoint-path">/api/v1/sites/<span class="param">{id}</span></span><span class="endpoint-desc">Detail d'un site</span></div>
                <div class="endpoint"><span class="method post">POST</span><span class="endpoint-path">/api/v1/sites</span><span class="endpoint-desc">Creer un site</span></div>
                <div class="endpoint"><span class="method put">PUT</span><span class="endpoint-path">/api/v1/sites/<span class="param">{id}</span></span><span class="endpoint-desc">Modifier un site</span></div>
                <div class="endpoint"><span class="method delete">DELETE</span><span class="endpoint-path">/api/v1/sites/<span class="param">{id}</span></span><span class="endpoint-desc">Supprimer un site</span></div>
            </div></div>
        </div>

        {{-- Zones --}}
        <div class="endpoint-group">
            <input type="checkbox" id="grp-zones" class="toggle-input">
            <label for="grp-zones" class="group-toggle">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="var(--info)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 10c0 4.993-5.539 10.193-7.399 11.799a1 1 0 0 1-1.202 0C9.539 20.193 4 14.993 4 10a8 8 0 0 1 16 0"/><circle cx="12" cy="10" r="3"/></svg>
                <span class="group-label">Zones</span>
                <span class="group-badge auth">zones.*</span>
                <svg class="chevron" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>
            </label>
            <div class="group-body"><div class="group-body-inner">
                <div class="endpoint"><span class="method get">GET</span><span class="endpoint-path">/api/v1/zones</span><span class="endpoint-desc">Liste des zones</span></div>
                <div class="endpoint"><span class="method get">GET</span><span class="endpoint-path">/api/v1/zones/<span class="param">{id}</span></span><span class="endpoint-desc">Detail d'une zone</span></div>
                <div class="endpoint"><span class="method post">POST</span><span class="endpoint-path">/api/v1/zones</span><span class="endpoint-desc">Creer une zone</span></div>
                <div class="endpoint"><span class="method put">PUT</span><span class="endpoint-path">/api/v1/zones/<span class="param">{id}</span></span><span class="endpoint-desc">Modifier une zone</span></div>
                <div class="endpoint"><span class="method delete">DELETE</span><span class="endpoint-path">/api/v1/zones/<span class="param">{id}</span></span><span class="endpoint-desc">Supprimer une zone</span></div>
            </div></div>
        </div>

        {{-- Equipment --}}
        <div class="endpoint-group">
            <input type="checkbox" id="grp-equip" class="toggle-input">
            <label for="grp-equip" class="group-toggle">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="var(--warning)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="20" height="8" x="2" y="2" rx="2" ry="2"/><rect width="20" height="8" x="2" y="14" rx="2" ry="2"/><line x1="6" x2="6.01" y1="6" y2="6"/><line x1="6" x2="6.01" y1="18" y2="18"/></svg>
                <span class="group-label">Equipements</span>
                <span class="group-badge auth">equipment.*</span>
                <svg class="chevron" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>
            </label>
            <div class="group-body"><div class="group-body-inner">
                <div class="endpoint"><span class="method get">GET</span><span class="endpoint-path">/api/v1/equipment</span><span class="endpoint-desc">Liste des equipements</span></div>
                <div class="endpoint"><span class="method get">GET</span><span class="endpoint-path">/api/v1/equipment/<span class="param">{id}</span></span><span class="endpoint-desc">Detail d'un equipement</span></div>
                <div class="endpoint"><span class="method get">GET</span><span class="endpoint-path">/api/v1/zones/<span class="param">{id}</span>/equipements</span><span class="endpoint-desc">Equipements par zone</span></div>
                <div class="endpoint"><span class="method post">POST</span><span class="endpoint-path">/api/v1/equipment</span><span class="endpoint-desc">Creer un equipement</span></div>
                <div class="endpoint"><span class="method put">PUT</span><span class="endpoint-path">/api/v1/equipment/<span class="param">{id}</span></span><span class="endpoint-desc">Modifier un equipement</span></div>
                <div class="endpoint"><span class="method delete">DELETE</span><span class="endpoint-path">/api/v1/equipment/<span class="param">{id}</span></span><span class="endpoint-desc">Supprimer un equipement</span></div>
            </div></div>
        </div>

        {{-- Sensors --}}
        <div class="endpoint-group">
            <input type="checkbox" id="grp-sensors" class="toggle-input">
            <label for="grp-sensors" class="group-toggle">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="var(--success)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4.9 19.1C1 15.2 1 8.8 4.9 4.9"/><path d="M7.8 16.2c-2.3-2.3-2.3-6.1 0-8.4"/><circle cx="12" cy="12" r="2"/><path d="M16.2 7.8c2.3 2.3 2.3 6.1 0 8.4"/><path d="M19.1 4.9C23 8.8 23 15.1 19.1 19"/></svg>
                <span class="group-label">Capteurs</span>
                <span class="group-badge auth">sensors.*</span>
                <svg class="chevron" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>
            </label>
            <div class="group-body"><div class="group-body-inner">
                <div class="endpoint"><span class="method get">GET</span><span class="endpoint-path">/api/v1/sensors</span><span class="endpoint-desc">Tous les capteurs</span></div>
                <div class="endpoint"><span class="method get">GET</span><span class="endpoint-path">/api/v1/equipment/<span class="param">{id}</span>/sensors</span><span class="endpoint-desc">Capteurs par equipement</span></div>
                <div class="endpoint"><span class="method get">GET</span><span class="endpoint-path">/api/v1/sensors/<span class="param">{id}</span></span><span class="endpoint-desc">Detail d'un capteur</span></div>
                <div class="endpoint"><span class="method post">POST</span><span class="endpoint-path">/api/v1/equipment/<span class="param">{id}</span>/sensors</span><span class="endpoint-desc">Creer un capteur</span></div>
                <div class="endpoint"><span class="method put">PUT</span><span class="endpoint-path">/api/v1/sensors/<span class="param">{id}</span></span><span class="endpoint-desc">Modifier un capteur</span></div>
                <div class="endpoint"><span class="method delete">DELETE</span><span class="endpoint-path">/api/v1/sensors/<span class="param">{id}</span></span><span class="endpoint-desc">Supprimer un capteur</span></div>
            </div></div>
        </div>

        {{-- Users --}}
        <div class="endpoint-group">
            <input type="checkbox" id="grp-users" class="toggle-input">
            <label for="grp-users" class="group-toggle">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="var(--purple)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                <span class="group-label">Utilisateurs</span>
                <span class="group-badge auth">users.*</span>
                <svg class="chevron" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>
            </label>
            <div class="group-body"><div class="group-body-inner">
                <div class="endpoint"><span class="method get">GET</span><span class="endpoint-path">/api/v1/users</span><span class="endpoint-desc">Liste des utilisateurs</span></div>
                <div class="endpoint"><span class="method get">GET</span><span class="endpoint-path">/api/v1/users/<span class="param">{id}</span></span><span class="endpoint-desc">Detail d'un utilisateur</span></div>
                <div class="endpoint"><span class="method post">POST</span><span class="endpoint-path">/api/v1/users</span><span class="endpoint-desc">Creer un utilisateur</span></div>
                <div class="endpoint"><span class="method put">PUT</span><span class="endpoint-path">/api/v1/users/<span class="param">{id}</span></span><span class="endpoint-desc">Modifier un utilisateur</span></div>
                <div class="endpoint"><span class="method delete">DELETE</span><span class="endpoint-path">/api/v1/users/<span class="param">{id}</span></span><span class="endpoint-desc">Supprimer un utilisateur</span></div>
            </div></div>
        </div>

        {{-- Roles & Permissions --}}
        <div class="endpoint-group">
            <input type="checkbox" id="grp-roles" class="toggle-input">
            <label for="grp-roles" class="group-toggle">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="var(--destructive)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m21 2-2 2m-7.61 7.61a5.5 5.5 0 1 1-7.778 7.778 5.5 5.5 0 0 1 7.777-7.777zm0 0L15.5 7.5m0 0 3 3L22 7l-3-3m-3.5 3.5L19 4"/></svg>
                <span class="group-label">Roles & Permissions</span>
                <span class="group-badge admin">administrator</span>
                <svg class="chevron" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>
            </label>
            <div class="group-body"><div class="group-body-inner">
                <div class="endpoint"><span class="method get">GET</span><span class="endpoint-path">/api/v1/roles</span><span class="endpoint-desc">Liste des roles</span></div>
                <div class="endpoint"><span class="method get">GET</span><span class="endpoint-path">/api/v1/permissions</span><span class="endpoint-desc">Liste des permissions</span></div>
                <div class="endpoint"><span class="method put">PUT</span><span class="endpoint-path">/api/v1/roles/<span class="param">{role}</span>/permissions</span><span class="endpoint-desc">Modifier les permissions</span></div>
            </div></div>
        </div>

        {{-- System --}}
        <div class="endpoint-group">
            <input type="checkbox" id="grp-system" class="toggle-input">
            <label for="grp-system" class="group-toggle">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="var(--text-muted)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12.22 2h-.44a2 2 0 0 0-2 2v.18a2 2 0 0 1-1 1.73l-.43.25a2 2 0 0 1-2 0l-.15-.08a2 2 0 0 0-2.73.73l-.22.38a2 2 0 0 0 .73 2.73l.15.1a2 2 0 0 1 1 1.72v.51a2 2 0 0 1-1 1.74l-.15.09a2 2 0 0 0-.73 2.73l.22.38a2 2 0 0 0 2.73.73l.15-.08a2 2 0 0 1 2 0l.43.25a2 2 0 0 1 1 1.73V20a2 2 0 0 0 2 2h.44a2 2 0 0 0 2-2v-.18a2 2 0 0 1 1-1.73l.43-.25a2 2 0 0 1 2 0l.15.08a2 2 0 0 0 2.73-.73l.22-.39a2 2 0 0 0-.73-2.73l-.15-.08a2 2 0 0 1-1-1.74v-.5a2 2 0 0 1 1-1.74l.15-.09a2 2 0 0 0 .73-2.73l-.22-.38a2 2 0 0 0-2.73-.73l-.15.08a2 2 0 0 1-2 0l-.43-.25a2 2 0 0 1-1-1.73V4a2 2 0 0 0-2-2z"/><circle cx="12" cy="12" r="3"/></svg>
                <span class="group-label">Systeme & Administration</span>
                <span class="group-badge admin">admin</span>
                <svg class="chevron" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>
            </label>
            <div class="group-body"><div class="group-body-inner">
                <div class="endpoint"><span class="method get">GET</span><span class="endpoint-path">/api/v1/admin/settings</span><span class="endpoint-desc">Parametres systeme</span></div>
                <div class="endpoint"><span class="method put">PUT</span><span class="endpoint-path">/api/v1/admin/settings</span><span class="endpoint-desc">Modifier les parametres</span></div>
                <div class="endpoint"><span class="method get">GET</span><span class="endpoint-path">/api/v1/history</span><span class="endpoint-desc">Journal d'audit</span></div>
                <div class="endpoint"><span class="method get">GET</span><span class="endpoint-path">/api/v1/notifications</span><span class="endpoint-desc">Notifications utilisateur</span></div>
                <div class="endpoint"><span class="method get">GET</span><span class="endpoint-path">/api/v1/notification-preferences</span><span class="endpoint-desc">Preferences de notification</span></div>
                <div class="endpoint"><span class="method put">PUT</span><span class="endpoint-path">/api/v1/notification-preferences</span><span class="endpoint-desc">Modifier les preferences</span></div>
            </div></div>
        </div>

        {{-- CSV Import --}}
        <div class="endpoint-group">
            <input type="checkbox" id="grp-import" class="toggle-input">
            <label for="grp-import" class="group-toggle">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="var(--success)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" x2="12" y1="3" y2="15"/></svg>
                <span class="group-label">Import CSV</span>
                <span class="group-badge auth">create permissions</span>
                <svg class="chevron" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>
            </label>
            <div class="group-body"><div class="group-body-inner">
                <div class="endpoint"><span class="method get">GET</span><span class="endpoint-path">/api/v1/import/info/<span class="param">{type}</span></span><span class="endpoint-desc">Infos d'import (colonnes, regles)</span></div>
                <div class="endpoint"><span class="method get">GET</span><span class="endpoint-path">/api/v1/import/template/<span class="param">{type}</span></span><span class="endpoint-desc">Telecharger le template CSV</span></div>
                <div class="endpoint"><span class="method post">POST</span><span class="endpoint-path">/api/v1/import/zones</span><span class="endpoint-desc">Importer des zones</span></div>
                <div class="endpoint"><span class="method post">POST</span><span class="endpoint-path">/api/v1/import/equipment</span><span class="endpoint-desc">Importer des equipements</span></div>
                <div class="endpoint"><span class="method post">POST</span><span class="endpoint-path">/api/v1/import/sensors</span><span class="endpoint-desc">Importer des capteurs</span></div>
            </div></div>
        </div>
    </section>

    <!-- Tech Stack -->
    <section class="tech-section">
        <div class="tech-row">
            <span class="tech-badge">Laravel 11</span>
            <span class="tech-badge">PHP 8.2</span>
            <span class="tech-badge">MySQL 8</span>
            <span class="tech-badge">Redis</span>
            <span class="tech-badge">Sanctum</span>
            <span class="tech-badge">Spatie Permissions</span>
            <span class="tech-badge">Pusher WebSocket</span>
            <span class="tech-badge">WHAPI</span>
            <span class="tech-badge">OpenAPI 3.0</span>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <p>
            ByPass API &mdash; {{ date('Y') }} &mdash;
            <a href="/api/documentation">Documentation Swagger interactive</a>
        </p>
        <p class="versions">
            Laravel v{{ Illuminate\Foundation\Application::VERSION }} &middot; PHP v{{ PHP_VERSION }}
        </p>
    </footer>

</div>
</body>
</html>
