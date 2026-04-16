# Security Role Matrix

## Objectif

Ce document définit la matrice simple des rôles du backend API.

Buts :

- rendre les règles d’accès explicites
- éviter les incohérences entre `security.yaml`, les contrôleurs et la documentation
- distinguer clairement les accès publics, les accès tablette, les accès manager et les accès admin

Ce document sert de référence pour toute nouvelle route API.

---

## Rôles

### `PUBLIC_ACCESS`
Accès sans authentification.

Utilisé uniquement pour :
- la santé de l’API
- le login humain
- la récupération du token tablette

### `ROLE_TABLET`
Rôle réservé aux terminaux de pointage.

Ce rôle ne doit servir qu’au scan de pointage.  
Il ne doit jamais donner accès aux routes d’administration RH.

### `ROLE_MANAGER`
Rôle métier RH standard.

Un manager peut consulter et utiliser les fonctionnalités RH courantes :
- employés
- pointages
- retards
- alertes
- horaires
- calendriers

### `ROLE_ADMIN`
Rôle d’administration sensible.

Un admin peut faire tout ce qu’un manager peut faire, plus :
- gérer les comptes utilisateurs
- gérer les terminaux/tablettes
- accéder aux routes de configuration sensible

> Convention actuelle : `ROLE_ADMIN` hérite de `ROLE_MANAGER`.

---

## Principes de sécurité

1. Toute route sensible doit être protégée explicitement.
2. Une route admin ne doit pas dépendre uniquement de la règle globale `^/api`.
3. Une route tablette ne doit jamais être accessible à un utilisateur RH classique.
4. Toute nouvelle route doit être classée dans cette matrice avant implémentation.
5. La documentation OpenAPI doit refléter les rôles réellement appliqués.

---

## Matrice des accès

| Zone / endpoint            | Public | ROLE_TABLET | ROLE_MANAGER | ROLE_ADMIN |
|---|---:|---:|---:|---:|
| `/api/health`              | Oui    | Non         | Non          | Non        |
| `/api/login_check`         | Oui    | Non         | Non          | Non        |
| `/api/tablet/token`        | Oui    | Non         | Non          | Non        |
| `/api/pointages/scan`      | Non    | Oui         | Non          | Non        |
| `/api/me`                  | Non    | Non         | Oui          | Oui        |
| `/api/utilisateurs`        | Non    | Non         | Non          | Oui        |
| `/api/departements`        | Non    | Non         | Oui          | Oui        |
| `/api/employes`            | Non    | Non         | Oui          | Oui        |
| `/api/horaires-travail`    | Non    | Non         | Oui          | Oui        |
| `/api/plages-horaires`     | Non    | Non         | Oui          | Oui        |
| `/api/calendriers-travail` | Non    | Non         | Oui          | Oui        |
| `/api/pointages` (lecture) | Non    | Non         | Oui          | Oui        |
| `/api/retards`             | Non    | Non         | Oui          | Oui        |
| `/api/alertes`             | Non    | Non         | Oui          | Oui        |
| `/api/terminals` ou routes terminales d’admin | Non | Non | Non | Oui |

---

## Mapping cible par contrôleur

### Contrôleurs publics
- `SecuritySmokeController::health` → `PUBLIC_ACCESS`
- route JWT `/api/login_check` → `PUBLIC_ACCESS`
- `TabletAuthController::getToken` → `PUBLIC_ACCESS`

### Contrôleurs tablette
- `PointageController::scan` → `ROLE_TABLET`

### Contrôleurs manager+
- `SecuritySmokeController::me` → `ROLE_MANAGER`
- `EmployeController` → `ROLE_MANAGER`
- `DepartementController` → `ROLE_MANAGER`
- `HoraireTravailController` → `ROLE_MANAGER`
- `PlageHoraireController` → `ROLE_MANAGER`
- `CalendrierTravailController` → `ROLE_MANAGER`
- `PointageController` (hors `scan`) → `ROLE_MANAGER`
- `RetardController` → `ROLE_MANAGER`
- `AlerteController` → `ROLE_MANAGER`

### Contrôleurs admin only
- `UtilisateurController` → `ROLE_ADMIN`
- futur `TerminalPointageController` → `ROLE_ADMIN`
- toute future route de configuration sensible → `ROLE_ADMIN`

---

## Règles d’implémentation

### 1. Règle globale dans `security.yaml`
La règle globale peut rester :

- `^/api` → `ROLE_MANAGER`

Mais elle ne suffit pas pour les routes admin ni pour la route tablette.

### 2. Règles spécifiques obligatoires
Les routes suivantes doivent être traitées explicitement :

- `/api/pointages/scan` → `ROLE_TABLET`
- `/api/utilisateurs/**` → `ROLE_ADMIN`
- futures routes `/api/terminals/**` → `ROLE_ADMIN`

### 3. Attributs de sécurité dans les contrôleurs
Même si `security.yaml` protège déjà l’API, les contrôleurs sensibles doivent aussi être annotés explicitement.

Exemples :

```php
#[IsGranted('ROLE_ADMIN')]
#[Route('/api/utilisateurs')]
class UtilisateurController extends AbstractController
{
}