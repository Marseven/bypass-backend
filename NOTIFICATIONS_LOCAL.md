# Configuration des Notifications en Local

## Problème
Les notifications en temps réel nécessitent Pusher, qui n'est pas toujours disponible ou configuré en développement local.

## Solution Implémentée

### Frontend (bypass-guard)
Le système détecte automatiquement l'environnement local et adapte le comportement :

1. **En local (localhost/127.0.0.1) sans clé Pusher** :
   - Utilise le **polling** pour récupérer les notifications toutes les 5 secondes
   - Les notifications sont récupérées via l'endpoint `/api/notifications`
   - Pas besoin de configuration Pusher

2. **En production ou avec clé Pusher** :
   - Utilise **Laravel Echo + Pusher** pour les notifications temps réel
   - Les notifications arrivent instantanément via WebSocket

### Backend (ByPass_API)
Pour le développement local, vous pouvez configurer le driver de broadcasting :

#### Option 1 : Utiliser le driver `log` (recommandé pour le développement)
Dans votre fichier `.env` :
```env
BROADCAST_DRIVER=log
```

Les notifications seront loggées dans `storage/logs/laravel.log` au lieu d'être envoyées via Pusher.

#### Option 2 : Utiliser le driver `null` (désactiver complètement)
```env
BROADCAST_DRIVER=null
```

Les notifications ne seront pas broadcastées, mais seront toujours enregistrées en base de données.

#### Option 3 : Configurer Pusher pour le développement local
Si vous avez des clés Pusher, vous pouvez les configurer :
```env
BROADCAST_DRIVER=pusher
PUSHER_APP_ID=your_app_id
PUSHER_APP_KEY=your_app_key
PUSHER_APP_SECRET=your_app_secret
PUSHER_APP_CLUSTER=mt1
```

Et dans le frontend (`.env` ou `.env.local`) :
```env
VITE_PUSHER_APP_KEY=your_app_key
VITE_PUSHER_APP_CLUSTER=mt1
VITE_API_URL=http://localhost:8000
```

## Fonctionnement

### Notifications en Base de Données
Les notifications sont **toujours** enregistrées en base de données, peu importe le driver de broadcasting utilisé. Elles sont accessibles via :
- L'endpoint `/api/notifications` (GET)
- La table `notifications` dans la base de données

### Notifications Broadcast (Temps Réel)
Les notifications broadcast ne fonctionnent que si :
1. `BROADCAST_DRIVER=pusher` est configuré
2. Les clés Pusher sont valides
3. Le frontend a les clés Pusher configurées

Sinon, le frontend utilise automatiquement le polling pour récupérer les nouvelles notifications.

## Test

Pour tester les notifications en local :

1. **Sans Pusher** (mode polling) :
   - Assurez-vous que `BROADCAST_DRIVER=log` ou `null` dans `.env`
   - Les notifications apparaîtront toutes les 5 secondes via polling
   - Vérifiez les logs dans `storage/logs/laravel.log` si vous utilisez `log`

2. **Avec Pusher** :
   - Configurez les clés Pusher dans `.env` et `.env.local` du frontend
   - Les notifications apparaîtront instantanément via WebSocket

## Dépannage

### Les notifications n'apparaissent pas en local
1. Vérifiez que l'endpoint `/api/notifications` fonctionne
2. Vérifiez la console du navigateur pour les erreurs
3. Vérifiez que l'utilisateur est bien authentifié
4. Vérifiez les logs Laravel pour les erreurs de broadcasting

### Les notifications apparaissent mais avec du retard
- C'est normal en mode polling (rafraîchissement toutes les 5 secondes)
- Pour un rafraîchissement plus rapide, configurez Pusher ou réduisez l'intervalle de polling

