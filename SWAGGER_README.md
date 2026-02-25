# Documentation Swagger de l'API ByPass

## Accès à la documentation

Une fois le serveur Laravel démarré, la documentation Swagger est accessible à l'adresse :

```
http://votre-domaine/api/documentation
```

Ou si vous utilisez le serveur de développement local :

```
http://localhost:8000/api/documentation
```

## Authentification

L'API utilise Laravel Sanctum pour l'authentification. Pour utiliser les endpoints protégés :

1. **Se connecter** via `POST /api/auth/login` avec les credentials :
   ```json
   {
     "username": "votre_username",
     "password": "votre_password"
   }
   ```

2. **Copier le token** retourné dans la réponse

3. **Utiliser le token** dans l'interface Swagger :
   - Cliquer sur le bouton "Authorize" en haut de la page
   - Entrer : `Bearer votre_token` (remplacer `votre_token` par le token obtenu)
   - Cliquer sur "Authorize" puis "Close"

4. Toutes les requêtes protégées incluront automatiquement le token d'authentification

## Régénération de la documentation

Si vous modifiez les annotations Swagger dans les contrôleurs, régénérez la documentation avec :

```bash
php artisan l5-swagger:generate
```

## Structure de l'API

L'API est organisée en plusieurs sections :

- **Authentification** : Connexion, déconnexion, informations utilisateur
- **Dashboard** : Statistiques et résumés du système
- **Demandes** : Gestion des demandes de bypass
- **Zones** : Gestion des zones
- **Équipements** : Gestion des équipements
- **Capteurs** : Gestion des capteurs
- **Utilisateurs** : Gestion des utilisateurs (admin uniquement)
- **Système** : Paramètres système et historique (admin uniquement)

## Rôles et permissions

- **operator/user** : Peut créer et voir ses propres demandes
- **supervisor** : Peut valider les demandes de priorité faible/normale/haute
- **administrator** : Accès complet à toutes les fonctionnalités

## Exemples de réponses

Les réponses sont au format JSON. Les listes sont paginées par défaut (15 éléments par page).

Les erreurs suivent ce format :
```json
{
  "message": "Message d'erreur descriptif"
}
```

Les succès suivent ce format :
```json
{
  "status": 200,
  "data": { ... },
  "message": ["Message de succès"]
}
```

## Support

Pour toute question ou problème, consultez la documentation Swagger interactive ou contactez l'équipe de développement.
