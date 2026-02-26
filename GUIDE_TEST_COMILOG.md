# Guide de Test Complet — ByPass / COMILOG

> **Version** : 1.0
> **Date** : Fevrier 2026
> **Contexte** : Complexe Minier de Moanda + Terminal Mineralier d'Owendo

---

## 1. Setup initial

### Reinitialisation complete de la base

```bash
cd ByPass_API
php artisan migrate:fresh --seed
```

Cette commande execute dans l'ordre :
1. `RolesAndPermissionsSeeder` — 8 roles CDC + 4 roles legacy + permissions Spatie
2. `SiteSeeder` — 2 sites (Moanda + Owendo)
3. `EquipmentSeeder` — 10 zones + 24 equipements + ~50 capteurs
4. `UserSeeder` — 9 utilisateurs avec roles Spatie
5. `SystemSettingSeeder` — parametres application

### Verification rapide

```bash
php artisan tinker
```

```php
User::count();       // 9
Zone::count();       // 10
Equipment::count();  // 24
Sensor::count();     // 50
Site::count();       // 2
```

---

## 2. Comptes utilisateurs

**Mot de passe commun** : `Comilog@2026!`

| Username | Nom complet | Role CDC | Email |
|----------|------------|----------|-------|
| `admin.comilog` | Administrateur Systeme COMILOG | administrateur | admin@comilog.com |
| `j.moussavou` | Jean-Baptiste Moussavou | operateur | jean.moussavou@comilog.com |
| `p.ndong` | Patrick Ndong Essono | technicien | patrick.ndong@comilog.com |
| `a.obame` | Alain Obame Nguema | instrumentiste | alain.obame@comilog.com |
| `m.mbadinga` | Marcel Mbadinga Ondo | chef_de_quart | marcel.mbadinga@comilog.com |
| `s.nzoghe` | Sylvie Nzoghe Mba | responsable_hse | sylvie.nzoghe@comilog.com |
| `r.edzang` | Roger Edzang Essono | resp_exploitation | roger.edzang@comilog.com |
| `f.mba` | Francois Mba Abessolo | directeur | francois.mba@comilog.com |
| `t.engonga` | Thierry Engonga Ondo | administrateur | thierry.engonga@comilog.com |

---

## 3. Workflows de test

### Workflow 1 : Connexion et navigation par role

**Objectif** : Verifier que chaque profil accede uniquement aux fonctionnalites autorisees.

| Etape | Action | Resultat attendu |
|-------|--------|-----------------|
| 1 | Se connecter avec `j.moussavou` / `Comilog@2026!` | Dashboard operateur (lecture seule) |
| 2 | Verifier que le menu ne montre PAS la gestion des zones/equipements | Acces restreint |
| 3 | Se deconnecter, se connecter avec `admin.comilog` | Dashboard complet, tous les menus |
| 4 | Repeter pour chaque profil du tableau ci-dessus | Menus adaptes au role |

### Workflow 2 : Creation d'un bypass process (technicien)

**Acteur** : Patrick Ndong (`p.ndong`, technicien)

| Etape | Action | Resultat attendu |
|-------|--------|-----------------|
| 1 | Connexion avec `p.ndong` / `Comilog@2026!` | Acces OK |
| 2 | Creer une nouvelle demande de bypass | Formulaire accessible |
| 3 | Selectionner zone "Station de Concassage" | Equipements de la zone affiches |
| 4 | Selectionner equipement "CON-001 — Concasseur Primaire" | Capteurs affiches |
| 5 | Selectionner capteur "CAP-CON-001-01 — Vibration chassis" | OK |
| 6 | Type : process, Priorite : medium | OK |
| 7 | Raison : "Maintenance preventive palier excentrique" | OK |
| 8 | Soumettre la demande | Demande creee, statut "pending" |

**Suite validation** : Se connecter avec `m.mbadinga` (chef de quart) pour valider au niveau 1.

### Workflow 3 : Bypass securite (instrumentiste + double validation)

**Acteur initial** : Alain Obame (`a.obame`, instrumentiste)

| Etape | Action | Resultat attendu |
|-------|--------|-----------------|
| 1 | Connexion avec `a.obame` / `Comilog@2026!` | Acces OK |
| 2 | Creer un bypass sur "UTM-003 — Four de Frittage Rotatif" | Formulaire accessible |
| 3 | Selectionner capteur "CAP-UTM-003-02 — Pression gaz" | OK |
| 4 | Type : securite, Priorite : critical | Bypass securite |
| 5 | Soumettre | Demande creee, necessite double validation |

**Validation niveau 1** : `s.nzoghe` (responsable HSE)

| Etape | Action | Resultat attendu |
|-------|--------|-----------------|
| 6 | Connexion avec `s.nzoghe` | Demande visible dans les validations |
| 7 | Valider niveau 1 | Statut passe a "validated_level1" |

**Validation niveau 2** : `f.mba` (directeur)

| Etape | Action | Resultat attendu |
|-------|--------|-----------------|
| 8 | Connexion avec `f.mba` | Demande visible pour validation niveau 2 |
| 9 | Valider niveau 2 | Statut passe a "approved" |

### Workflow 4 : Cycle complet (creation → activation → fermeture)

| Etape | Acteur | Action | Statut resultant |
|-------|--------|--------|-----------------|
| 1 | `p.ndong` (technicien) | Creer bypass process sur PMP-001 | `pending` |
| 2 | `m.mbadinga` (chef de quart) | Valider niveau 1 | `validated_level1` |
| 3 | Si urgence : `r.edzang` (resp exploitation) | Valider niveau 2 | `approved` |
| 4 | `a.obame` (instrumentiste) | Activer le bypass | `active` |
| 5 | `a.obame` (instrumentiste) | Fermer le bypass | `closed` |

### Workflow 5 : Gestion des zones/equipements/capteurs (admin)

**Acteur** : `admin.comilog` (administrateur)

| Etape | Action | Resultat attendu |
|-------|--------|-----------------|
| 1 | Aller dans Gestion des zones | 10 zones affichees (7 Moanda + 3 Owendo) |
| 2 | Ouvrir "Usine de Traitement du Minerai (UTM)" | 4 equipements affiches |
| 3 | Ouvrir "UTM-001 — Broyeur a Boulets SAG Mill" | 3 capteurs affiches |
| 4 | Creer un nouvel equipement dans la zone | Equipement cree |
| 5 | Modifier un capteur existant | Mise a jour OK |
| 6 | Supprimer un equipement de test | Soft delete OK |

### Workflow 6 : Import CSV (admin)

**Acteur** : `admin.comilog` (administrateur)

| Etape | Action | Resultat attendu |
|-------|--------|-----------------|
| 1 | Preparer un fichier CSV avec 5 zones | Format conforme |
| 2 | Importer via l'interface | Zones creees |
| 3 | Preparer CSV equipements (avec zone_id) | Format conforme |
| 4 | Importer | Equipements lies aux zones |
| 5 | Importer capteurs CSV | Capteurs lies aux equipements |

### Workflow 7 : Dashboard et statistiques

**Acteur** : N'importe quel profil avec `dashboard.view`

| Etape | Action | Resultat attendu |
|-------|--------|-----------------|
| 1 | Acceder au dashboard | Statistiques affichees |
| 2 | Verifier le nombre d'equipements | 24 equipements |
| 3 | Verifier le nombre de capteurs | ~50 capteurs |
| 4 | Verifier graphiques (si demandes existent) | Graphiques fonctionnels |

### Workflow 8 : Notifications

| Etape | Action | Resultat attendu |
|-------|--------|-----------------|
| 1 | Creer un bypass (technicien) | Notification envoyee aux validateurs |
| 2 | Valider (chef de quart) | Notification au demandeur |
| 3 | Verifier l'icone de notification | Badge avec compteur |
| 4 | Cliquer pour voir les notifications | Liste des notifications |

---

## 4. Matrice des permissions par role

| Permission | operateur | technicien | instrumentiste | chef_quart | resp_hse | resp_exploit | directeur | admin |
|-----------|:---------:|:----------:|:--------------:|:----------:|:--------:|:------------:|:---------:|:-----:|
| dashboard.view | x | x | x | x | x | x | x | x |
| requests.view.own | x | x | x | x | x | x | x | x |
| requests.view.all | | | | x | x | x | x | x |
| requests.create | | x | x | x | | x | x | x |
| requests.update.own | | x | x | x | | x | x | x |
| requests.delete.own | | x | x | x | | x | x | x |
| requests.validate.level1 | | | | x | x | x | x | x |
| requests.validate.level2 | | | | | | x | x | x |
| bypass.create.process | | x | x | | | | | x |
| bypass.create.securite | | | x | | | | | x |
| bypass.activate | | | x | | | | | x |
| bypass.close | | | x | | | | | x |
| bypass.approve.short_term | | | | x | | | | x |
| bypass.approve.security | | | | | x | | x | x |
| bypass.approve.long_term | | | | | | x | | x |
| ora.validate | | | | | x | | | x |
| moc.trigger | | | | | | | x | x |
| equipment.* | | | | view | view | CRUD | CRUD | CRUD |
| zones.* | | | | view | view | CRUD | CRUD | CRUD |
| sensors.* | | | | view | view | view | CRUD | CRUD |
| users.* | | | | | | | | CRUD |
| system.settings | | | | | | | | x |

---

## 5. Donnees de reference

### Sites (2)

| Code | Nom | Localisation |
|------|-----|-------------|
| CML-MOANDA | Complexe Minier de Moanda | Moanda, Haut-Ogooue, Gabon |
| CML-OWENDO | Terminal Mineralier d'Owendo | Owendo, Estuaire, Gabon |

### Zones (10)

| # | Zone | Site | Nb Equipements |
|---|------|------|:-------------:|
| 1 | Plateau Bangombe - Extraction | Moanda | 3 |
| 2 | Usine de Traitement du Minerai (UTM) | Moanda | 4 |
| 3 | Station de Concassage | Moanda | 3 |
| 4 | Centrale Electrique | Moanda | 2 |
| 5 | Station de Pompage | Moanda | 2 |
| 6 | Ateliers de Maintenance | Moanda | 2 |
| 7 | Parc a Residus Miniers | Moanda | 2 |
| 8 | Quai de Chargement Maritime | Owendo | 2 |
| 9 | Parc de Stockage Minerai | Owendo | 2 |
| 10 | Station de Criblage Owendo | Owendo | 2 |

### Equipements (24) — par zone

**Extraction (3)**
| Code | Nom | Criticite | SIL |
|------|-----|-----------|-----|
| EXT-001 | Excavatrice Hydraulique CAT 6040 | haute | na |
| EXT-002 | Dumper Komatsu HD785-7 | haute | na |
| EXT-003 | Foreuse Atlas Copco DM45 | moyenne | na |

**UTM (4)**
| Code | Nom | Criticite | SIL |
|------|-----|-----------|-----|
| UTM-001 | Broyeur a Boulets SAG Mill | critique | SIL 1 |
| UTM-002 | Separateur Magnetique Haute Intensite | haute | SIL 1 |
| UTM-003 | Four de Frittage Rotatif | critique | SIL 2 |
| UTM-004 | Filtre-Presse Larox PF30 | moyenne | na |

**Concassage (3)**
| Code | Nom | Criticite | SIL |
|------|-----|-----------|-----|
| CON-001 | Concasseur Primaire a Machoires | critique | SIL 1 |
| CON-002 | Convoyeur a Bande Principal | haute | SIL 1 |
| CON-003 | Crible Vibrant Primaire | haute | na |

**Centrale Electrique (2)**
| Code | Nom | Criticite | SIL |
|------|-----|-----------|-----|
| NRG-001 | Groupe Electrogene Diesel 5MW | critique | SIL 2 |
| NRG-002 | Transformateur HT/BT 20kV | critique | SIL 2 |

**Station de Pompage (2)**
| Code | Nom | Criticite | SIL |
|------|-----|-----------|-----|
| PMP-001 | Pompe Centrifuge DN300 | haute | SIL 1 |
| PMP-002 | Station de Traitement des Eaux | critique | SIL 2 |

**Ateliers de Maintenance (2)**
| Code | Nom | Criticite | SIL |
|------|-----|-----------|-----|
| MNT-001 | Pont Roulant 50T | haute | SIL 2 |
| MNT-002 | Compresseur Air Atlas Copco GA90 | moyenne | na |

**Parc a Residus (2)**
| Code | Nom | Criticite | SIL |
|------|-----|-----------|-----|
| RES-001 | Systeme Detection Fuites Digue | critique | SIL 2 |
| RES-002 | Pompe de Recirculation Boues | haute | SIL 1 |

**Quai de Chargement (2)**
| Code | Nom | Criticite | SIL |
|------|-----|-----------|-----|
| QCH-001 | Portique de Chargement Maritime | critique | SIL 1 |
| QCH-002 | Convoyeur Maritime Telescopique | haute | SIL 1 |

**Parc de Stockage (2)**
| Code | Nom | Criticite | SIL |
|------|-----|-----------|-----|
| STK-001 | Stacker-Reclaimer | haute | SIL 1 |
| STK-002 | Bascule Ferroviaire 120T | moyenne | na |

**Criblage Owendo (2)**
| Code | Nom | Criticite | SIL |
|------|-----|-----------|-----|
| CRB-001 | Crible Vibrant Double Pont | haute | na |
| CRB-002 | Alimentateur a Tablier | moyenne | na |

### Capteurs (~50)

Chaque equipement possede 2 a 3 capteurs. Types :
- **temperature** : °C (paliers, huile, moteurs, gaz)
- **pressure** : bar, kPa (hydraulique, air, gaz)
- **vibration** : mm/s (chassis, paliers, structures)
- **level** : %, m, pH, NTU (niveaux, charges, qualite eau)
- **flow** : m3/h, m/s, l/min, km/h (debits, vitesses)

Convention de nommage : `CAP-{CODE_EQUIP}-{NN}` (ex: `CAP-UTM-003-01`)

---

## 6. Scenarios de test avances

### Test de rejet

| Etape | Acteur | Action |
|-------|--------|--------|
| 1 | `p.ndong` | Creer bypass process |
| 2 | `m.mbadinga` | Rejeter la demande avec motif |
| 3 | Verifier | Statut "rejected", notification au demandeur |

### Test d'acces non autorise

| Test | Action | Resultat attendu |
|------|--------|-----------------|
| Operateur tente de creer un bypass | `j.moussavou` → creation | Erreur 403 |
| Technicien tente bypass securite | `p.ndong` → type securite | Erreur 403 |
| Chef de quart tente validation niveau 2 | `m.mbadinga` → niveau 2 | Erreur 403 |

### Test SIL critique

Les equipements avec SIL 2 (Four de Frittage, Groupe Electrogene, Transformateur, Station Traitement Eaux, Pont Roulant, Detection Fuites Digue) devraient necessiter une validation renforcee pour tout bypass de leurs capteurs.
