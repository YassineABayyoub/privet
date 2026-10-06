# Arshif — archive local des actes et contrats

Application web locale en PHP 8 et MySQL/MariaDB pour enregistrer, rechercher et consulter des contrats et leurs documents. L’interface peut être affichée en arabe (RTL) ou en français; le sélecteur de langue est disponible sur les pages de connexion et dans la barre supérieure.

Le choix de langue est mémorisé dans le navigateur. Les libellés de l’interface sont traduits; les noms, notes et documents saisis par les utilisateurs restent dans leur langue d’origine.

Les champs textuels d’un contrat, de ses parties et des biens doivent être renseignés en arabe et en français. Pour une base déjà installée, sauvegarde-la puis applique `database/migrations/002_bilingual_contract_text.sql` avant d’utiliser les formulaires de contrat. Les anciennes données restent consultables; les traductions manquantes s’affichent en arabe jusqu’à ce qu’un responsable complète le contrat.

> **Important :** les fichiers `.html` sont des redirections historiques. Les vraies pages et le traitement des formulaires sont en `.php`. Lance toujours le serveur PHP, et non un serveur de fichiers statiques.

> **Mise à jour sécurité :** applique aussi `database/migrations/003_login_attempts.sql` (limitation des tentatives de connexion). Définis `DOCUMENT_STORAGE_DIR` vers un dossier permanent hors du site, et `DB_USER`/`DB_PASS` pour MySQL. Le repli SQLite n'est plus automatique : il exige `DB_ALLOW_SQLITE=1` (développement uniquement). `SESSION_IDLE_SECONDS` (défaut 7200) règle l'expiration des sessions inactives.

## Sommaire

1. [Vue d’ensemble en ASCII](#vue-densemble-en-ascii)
2. [Comment une requête fonctionne](#comment-une-requête-fonctionne)
3. [Arborescence et responsabilité des fichiers](#arborescence-et-responsabilité-des-fichiers)
4. [Données et relations de la base](#données-et-relations-de-la-base)
5. [Installer et lancer en local](#installer-et-lancer-en-local)
6. [Ajouter ou modifier une fonctionnalité](#ajouter-ou-modifier-une-fonctionnalité)
7. [Exemples de modifications courantes](#exemples-de-modifications-courantes)
8. [Validation et dépannage](#validation-et-dépannage)
9. [Sécurité et limites connues](#sécurité-et-limites-connues)

## Vue d’ensemble en ASCII

```text
                       NAVIGATEUR (interface arabe RTL)
                 formulaires, liens, tableaux et documents
                                  |
                                  | HTTP : GET / POST
                                  v
+--------------------- Serveur PHP local ----------------------+
|                                                              |
|  index.php --------> connexion / installation initiale       |
|                                                              |
|  pages/*.php --------> authentification + traitement métier  |
|       |                    |                     |           |
|       | rend le HTML       | valide les données   | appelle  |
|       v                    v                     v           |
|  includes/*.php     app/bootstrap.php      config/database.php
|  gabarit partagé    session, CSRF, rôles,   PDO, variables
|                     validations, catalogues   d'environnement
|       |                                          |
|       +---- css/*.css et js/*.js                 |
|                                                  |
+--------------------------------------------------|-----------+
                                                   | PDO/MySQL
                                                   v
                                     +------------------------+
                                     | MySQL / MariaDB        |
                                     | users, contracts,      |
                                     | persons, parties,      |
                                     | contract_documents     |
                                     +------------------------+

  Fichiers PDF/JPG/PNG :
  navigateur -> pages/add-contract.php -> stockage privé hors du site
                                      -> métadonnées en base
  navigateur <- pages/document.php <- vérification de session et accès
```

Le navigateur ne se connecte jamais directement à MySQL. Le PHP reçoit la requête, vérifie la session et les données, exécute les requêtes PDO, puis produit le HTML ou le flux du document demandé.

## Comment une requête fonctionne

Exemple : ajouter un contrat.

```text
1. Le navigateur ouvre /pages/add-contract.php
2. La page charge app/bootstrap.php et exige une session valide
3. Le PHP affiche le formulaire avec includes/layout-start.php
4. Le navigateur envoie les valeurs avec POST et le jeton CSRF
5. Le PHP vérifie le jeton, les champs, les parties et les documents
6. Les données sont enregistrées dans une transaction PDO
       contracts -> contract_parties -> persons
       contrats et parties sont cohérents ou la transaction est annulée
7. Le PHP redirige vers la liste ou affiche les erreurs
8. La page de liste/détail relit les données depuis MySQL
```

Pour les documents, le contenu du fichier reste dans le dossier privé `archive-private-documents` par défaut, à côté du projet. Seules ses métadonnées (nom original, nom stocké, type MIME, taille et contrat associé) sont conservées dans `contract_documents`. La consultation passe par `pages/document.php`, qui contrôle l’authentification.

## Arborescence et responsabilité des fichiers

```text
privet/
|-- index.php                    Point d'entrée : installation, connexion ou accueil
|-- logout.php                   Déconnexion POST protégée par CSRF
|-- .htaccess                    Désactive l'indexation et bloque des dossiers/fichiers
|-- README.md                    Ce guide
|
|-- app/
|   `-- bootstrap.php            Session, sécurité, helpers, rôles et catalogues métier
|                                (types de contrats et types de biens)
|-- config/
|   `-- database.php             Connexion PDO à MySQL via variables d'environnement
|-- database/
|   |-- schema.sql               Schéma de création initial de la base
|   `-- migrations/               Modifications versionnées pour une base existante
|-- scripts/
|   `-- create-admin.php          Création CLI d'un compte responsable
|
|-- pages/                        Pages et actions serveur PHP
|   |-- setup.php                 Création web du premier compte, si aucun utilisateur
|   |-- login.php                 Connexion
|   |-- dashboard.php             Accueil et statistiques de la base
|   |-- contracts.php             Liste, recherche, filtres et pagination
|   |-- add-contract.php          Création et validation d'un contrat
|   |-- edit-contract.php         Modification d'un contrat
|   |-- delete-contract.php       Suppression protégée d'un contrat
|   |-- contract-details.php      Détails, parties, biens et documents
|   |-- document.php              Affichage/téléchargement authentifié d'un document
|   |-- persons.php               Liste et recherche des personnes
|   |-- person-details.php        Profil d'une personne et ses liens
|   |-- users.php                 Gestion des comptes
|   |-- settings.php              Paramètres du compte
|   |-- reports.php               Rapports et statistiques
|   `-- *.html                    Anciennes URL; redirigent vers les pages PHP
|
|-- includes/
|   |-- layout-start.php          Début du gabarit commun, navigation et CSS
|   `-- layout-end.php            Fin du gabarit commun et scripts partagés
|-- css/
|   |-- style.css                 Styles de base et composants visuels
|   |-- layout.css                Structure générale, barre latérale et en-tête
|   |-- dashboard.css             Tableau de bord
|   |-- forms.css                 Formulaires
|   |-- tables.css                Tableaux et listes
|   `-- responsive.css            Adaptation aux petits écrans
|-- js/
|   |-- i18n.js                   Sélecteur de langue arabe/français et libellés d'interface
|   |-- main.js                   Comportements communs et formulaires dynamiques
|   |-- navigation.js             Navigation
|   |-- contracts.js              Filtres interactifs historiques de la liste
|   |-- persons.js                Interactions de la liste de personnes
|   `-- users.js                  Interactions de la gestion des comptes
`-- assets/
    |-- icons/app-icon.svg         Icône de l'application
    `-- images/logo.svg            Logo
```

### Règle pour choisir le bon fichier

- **Une règle partagée, validation ou liste de choix métier** : `app/bootstrap.php`.
- **Connexion ou configuration de base** : `config/database.php` et variables d’environnement.
- **Lire/écrire des données pour une page** : la page PHP concernée, avec `database()` et des requêtes préparées.
- **Structure HTML réutilisable** : `includes/layout-start.php` ou `includes/layout-end.php`.
- **Comportement interactif dans le navigateur** : `js/`.
- **Présentation visuelle** : la feuille correspondante dans `css/`.
- **Tables, index ou contraintes de base** : migration SQL dans `database/migrations/`, puis appliquer cette migration à la base.
- **Une page `.html` existante** : ne pas y ajouter le traitement; elle n’est qu’une redirection. Modifier la page `.php` correspondante.

## Données et relations de la base

Le schéma initial est dans [`database/schema.sql`](./database/schema.sql).

```text
users (1) ----------------------< contracts
  |                                  |
  | crée/utilise                     | (1)
  |                                  +----< contract_documents
  |                                  |
  |                                  +----< contract_parties >---- (1) persons
  |                                               plusieurs-à-plusieurs
  +---- uploaded_by dans contract_documents
```

| Table | À quoi elle sert |
|---|---|
| `users` | Comptes, rôle (`judge` ou `clerk`), état actif et hash du mot de passe. |
| `contracts` | Informations générales, catégorie, type, dates, référence et créateur. `specific_data` est un champ JSON pour les informations détaillées propres au contrat. |
| `persons` | Personnes réutilisables; le numéro d’identité, quand il existe, permet de retrouver une personne déjà enregistrée. |
| `contract_parties` | Association entre un contrat et une personne, avec son rôle dans ce contrat. |
| `contract_documents` | Métadonnées des fichiers privés joints aux contrats. Le binaire n’est pas stocké dans cette table. |

Les suppressions et clés étrangères sont importantes : par exemple, la suppression d’un contrat supprime ses associations et ses métadonnées de document, mais les règles empêchent de supprimer un utilisateur ou une personne encore référencé. Ne modifie pas directement les données réelles pour faire un test.

## Installer et lancer en local

### Prérequis

- PHP 8.1 ou plus récent avec `PDO`, `pdo_mysql`, `fileinfo` et `session`.
- MySQL 8.0 ou une version compatible MariaDB prenant en charge le type JSON.
- XAMPP ou une installation équivalente.

Avec XAMPP, utilise son exécutable PHP : `C:\xampp\php\php.exe`.

### Démarrage

1. Démarre MySQL/MariaDB dans XAMPP.
2. Dans phpMyAdmin ou un client MySQL, exécute `database/schema.sql` pour une nouvelle base.
3. Pour une base déjà utilisée, vérifie quelles migrations de `database/migrations/` ont déjà été appliquées et n’exécute que celles qui manquent.
4. Dans PowerShell, configure les variables pour le terminal courant :

```powershell
$env:DB_HOST = "127.0.0.1"
$env:DB_PORT = "3306"
$env:DB_NAME = "archive_contrats"
$env:DB_USER = "root"
$env:DB_PASS = ""
```

Le mot de passe `root` vide n’est courant que pour une installation XAMPP locale neuve. Configure les vraies valeurs selon ton installation et ne mets jamais de secret dans le README ou dans le code.

5. Dans ce même terminal, démarre PHP à la racine du projet :

```powershell
C:\xampp\php\php.exe -S 127.0.0.1:8080 -t .
```

6. Ouvre <http://127.0.0.1:8080/>. Si la table `users` est vide, crée le premier compte responsable. Sinon, connecte-toi avec un compte existant.

La création du premier compte peut aussi se faire avec `php scripts/create-admin.php`. N’utilise pas `python -m http.server` : un serveur statique ne peut pas exécuter PHP.

## Ajouter ou modifier une fonctionnalité

Avant de modifier, identifie **toutes les couches concernées**. Une fonctionnalité n’est pas complète si elle apparaît seulement dans le formulaire : il faut également valider et enregistrer la donnée, la relire, l’afficher et la tester.

```text
                         +---------------------+
                         | Choix / besoin      |
                         +----------+----------+
                                    |
                 +------------------+------------------+
                 |                                     |
                 v                                     v
        Présentation HTML                         Règle métier
        pages/*.php                               app/bootstrap.php
        includes/*.php                            (si partagée)
                 |                                     |
                 +------------------+------------------+
                                    v
                         Traitement serveur
                         pages/<fonction>.php
                         GET/POST, validation, SQL
                                    |
                    +---------------+---------------+
                    |                               |
                    v                               v
             MySQL si nécessaire               Fichiers privés
             schema/migration SQL               si documents
                    |                               |
                    +---------------+---------------+
                                    v
                    Relecture et affichage des données
                    liste, détail, recherche, rapport
                                    |
                 +------------------+------------------+
                 |                                     |
                 v                                     v
        JS si interaction                          CSS si style
        js/<fonction>.js                           css/<fonction>.css
```

### Étapes recommandées

1. **Repérer l’existant** : chercher une page ou un champ similaire et réutiliser ses conventions.
2. **Établir le parcours de la donnée** : saisie → validation → stockage → lecture → affichage.
3. **Définir le modèle** : décider si la donnée appartient à une colonne SQL, à une table liée, ou au JSON `specific_data`. Une donnée à rechercher/filtrer ou à relier à d’autres enregistrements est souvent plus adaptée à une table/colonne dédiée qu’au JSON.
4. **Modifier le backend** : valider les données côté PHP même si le navigateur les valide aussi; utiliser des requêtes PDO préparées.
5. **Modifier l’interface** : mettre à jour le formulaire et les pages de détail/liste concernées.
6. **Ajouter une migration** si la structure de la base change. Ne réécris pas une migration déjà appliquée; ajoute-en une nouvelle, par exemple `002_nom_de_la_modification.sql`.
7. **Mettre à jour les styles/scripts** uniquement si nécessaire.
8. **Tester chaque étape** : cas normal, données absentes, valeurs invalides, permissions et affichage mobile si l’interface est concernée.
9. **Vérifier la syntaxe PHP/JS**, puis mettre à jour ce README si le fonctionnement ou l’installation évolue.

### Nouvelle page dans l’application

1. Crée une page `pages/nom-page.php`.
2. En haut, active le typage strict et charge le bootstrap :

```php
<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/bootstrap.php';
$user = require_auth();
$pdo = database();
$pageTitle = 'Titre';
$activePage = 'nom-page';
require __DIR__ . '/../includes/layout-start.php';
?>
<!-- contenu HTML échappé -->
<?php require __DIR__ . '/../includes/layout-end.php'; ?>
```

3. Si la page doit être réservée au responsable, remplace `require_auth()` par `require_judge()`.
4. Échappe les valeurs affichées venant de la base ou de l’utilisateur avec `e($value)`.
5. Pour un lien permanent, ajoute l’entrée de navigation dans `includes/layout-start.php` et configure `$activePage`.
6. Ajoute les styles dans le fichier CSS approprié. Ajoute un JS spécifique seulement si une interaction navigateur le nécessite.
7. Si une ancienne adresse `.html` doit continuer à fonctionner, crée ou garde un fichier `.html` qui redirige vers la route `.php`; ne duplique pas la logique dedans.

### Ajouter un champ enregistré

1. Ajoute le contrôle au formulaire PHP avec un nom stable, par exemple `name="reference"`.
2. Dans le traitement POST de la page, récupère la valeur, normalise-la et vérifie longueur/format.
3. Ajoute ou adapte une colonne avec une **nouvelle migration SQL** si c’est une donnée relationnelle ou recherchable. Si elle est vraiment spécifique et peu utilisée pour la recherche, vérifie d’abord si le JSON `specific_data` existant convient.
4. Ajoute la valeur à l’`INSERT` et à l’`UPDATE` avec un paramètre préparé.
5. Relis-la dans les requêtes et affiche-la dans les vues de liste/détail pertinentes avec `e(...)`.
6. Vérifie les anciens contrats (valeur absente ou NULL) et les formulaires de modification.

Ne concatène jamais directement une entrée utilisateur dans une requête SQL.

## Exemples de modifications courantes

### Ajouter un type d’acte

Le catalogue serveur et le menu dynamique du formulaire doivent rester alignés :

1. Dans `app/bootstrap.php`, ajoute le libellé dans `contract_types()` sous la bonne catégorie. Le backend se sert de ce catalogue pour valider les couples catégorie/type.
2. Dans `js/main.js`, ajoute le même libellé dans `contractTypes`. Le navigateur s’en sert pour mettre à jour le menu des types quand la catégorie change.
3. Si le nouveau type a des règles particulières (parties requises, champs spéciaux, etc.), ajoute les règles **côté serveur** dans `pages/add-contract.php` et `pages/edit-contract.php`; ne compte pas uniquement sur le JavaScript.
4. Vérifie la liste, les filtres et les rapports si leur présentation doit changer. Ils s’appuient généralement sur les données de `contracts`.
5. Teste la création, l’enregistrement, le détail, la modification et la recherche.

### Ajouter un type de bien ou ses caractéristiques

Les champs d’un bien sont construits dynamiquement. Pour ajouter, par exemple, un type de bien :

1. Ajoute le type et les identifiants de ses champs dans `property_types()` de `app/bootstrap.php`.
2. Ajoute le même type et les mêmes identifiants dans `propertyConfig` de `js/main.js`; sinon le formulaire ne générera pas les contrôles.
3. Garde les clés de champs identiques dans les deux fichiers. Le serveur transforme les valeurs en libellés et valide les longueurs dans `normalize_contract_properties()`.
4. Vérifie que `pages/add-contract.php` **et** `pages/edit-contract.php` appellent ce traitement et sérialisent les données dans `contracts.specific_data`.
5. Vérifie `contract_properties_for_form()` dans `app/bootstrap.php` et le rendu de `pages/contract-details.php`; ils doivent pouvoir relire et présenter ces champs.
6. Teste au moins un bien dans un nouveau contrat et un bien ajouté/modifié dans un contrat existant. Vérifie la page de détails et recharge à nouveau le formulaire de modification.

Les biens actuels sont enregistrés dans `specific_data` sous la clé JSON `الأملاك`. Les champs supplémentaires personnalisés sont également pris en charge.

### Ajouter une table ou colonne SQL

1. Sauvegarde la base de données avant une modification structurelle.
2. Ajoute un fichier de migration numéroté dans `database/migrations/`; écris un SQL clair qui ne détruit pas les données existantes.
3. Applique la migration à la base locale, puis adapte les requêtes PHP qui écrivent et lisent cette donnée.
4. Prends en compte les anciennes lignes, les valeurs NULL, les index et les clés étrangères.
5. N’édite pas seulement `schema.sql` pour une base déjà installée : ce fichier décrit l’installation neuve, il ne met pas à jour la base courante.

Ce projet ne possède pas encore de registre automatique des migrations. Note celles déjà appliquées et évite de rejouer une migration non idempotente.

### Ajouter un lien au menu

Modifie `includes/layout-start.php`. Les liens réservés au responsable se trouvent dans le bloc conditionnel du rôle `judge`. La visibilité dans le menu n’est **pas** un contrôle d’accès : la page doit tout de même appeler `require_auth()` ou `require_judge()`.

## Validation et dépannage

Il n’y a pas actuellement de suite de tests automatisés dédiée dans le dépôt. Après une modification, adapte ces vérifications au périmètre concerné.

```powershell
# Depuis la racine du projet (PHP XAMPP)
C:\xampp\php\php.exe -l app\bootstrap.php
C:\xampp\php\php.exe -l pages\add-contract.php
C:\xampp\php\php.exe -l pages\edit-contract.php
C:\xampp\php\php.exe -l pages\contract-details.php

# Si JavaScript a changé
node --check js\main.js
```

Pour valider un formulaire, teste les deux parcours :

- entrée valide → sauvegarde → fermeture/réouverture → donnée toujours présente;
- entrée invalide ou incomplète → message d’erreur utile, sans enregistrer un enregistrement partiel.

Pannes courantes :

| Symptôme | À vérifier |
|---|---|
| Erreur de connexion DB | MySQL démarré, variables `DB_*` définies dans le même terminal que le serveur, nom de base et identifiants corrects. |
| `could not find driver` | Utiliser le PHP XAMPP et vérifier que `pdo_mysql` est activé. |
| Page PHP affichée comme texte/téléchargée | Démarrer le serveur PHP; ne pas ouvrir le fichier directement ni utiliser un serveur statique. |
| Le nouveau choix n’apparaît pas | Catalogue dans `app/bootstrap.php` **et** configuration cliente concernée dans `js/main.js`; recharger sans cache. |
| Erreur après modification SQL | Confirmer que la migration a été appliquée à la bonne base et examiner le journal d’erreurs PHP. |
| Document introuvable | Vérifier le chemin privé `DOCUMENT_STORAGE_DIR` ou le dossier privé par défaut et ne pas déplacer les fichiers sans mettre à jour leur stockage. |

## Sécurité et limites connues

- Les nouveaux écrans exigent une session. Utilise `require_judge()` pour les actions responsables.
- Les formulaires modifiant des données utilisent POST et un jeton CSRF via `csrf_token()` / `verify_csrf()`.
- Les requêtes SQL sont préparées avec PDO. Continue à utiliser des paramètres liés.
- Échappe le contenu affiché avec `e()` pour éviter qu’une donnée saisie soit interprétée comme du HTML.
- Les mots de passe sont enregistrés sous forme de hash; ne mets aucun vrai mot de passe, secret ou donnée d’accès dans ce fichier.
- Les documents acceptés sont PDF/JPG/PNG, vérifiés par type MIME et stockés hors du document root. Configure `DOCUMENT_STORAGE_DIR` pour choisir un autre emplacement privé.
- Les données réelles de l’archive peuvent être sensibles. Fais les essais avec de fausses données et une copie de sauvegarde; ne supprime pas de données réelles pour tester.
- L’application vise un usage local de bureau. Avant toute mise en production, prévoir au minimum HTTPS, sauvegardes restaurables, journalisation, limitation des tentatives de connexion et revue de sécurité.
- La réinitialisation de mot de passe, le journal d’audit, la sauvegarde/restauration automatisée et des permissions granulaires ne sont pas encore implémentés.
