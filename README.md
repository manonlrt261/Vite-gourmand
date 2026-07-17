# Vite & Gourmand

![PHP](https://img.shields.io/badge/PHP-%3E%3D%208.4-777BB4?logo=php&logoColor=white)
![Symfony](https://img.shields.io/badge/Symfony-8.1-000000?logo=symfony&logoColor=white)

Vite & Gourmand est une application web de gestion pour un traiteur. Elle présente le catalogue de menus, permet la constitution d'un panier et regroupe des espaces dédiés aux clients, aux employés et aux administrateurs.

Le projet a été réalisé dans un cadre pédagogique et reste en cours de développement. Plusieurs parcours sont codés, mais leur validation complète est actuellement limitée par une incohérence de configuration entre PostgreSQL et le schéma MySQL fourni.

## Sommaire

- [Présentation du projet](#présentation-du-projet)
- [État du projet](#état-du-projet)
- [Fonctionnalités](#fonctionnalités)
- [Règles métier principales](#règles-métier-principales)
- [Technologies utilisées](#technologies-utilisées)
- [Architecture générale](#architecture-générale)
- [Prérequis](#prérequis)
- [Installation du projet](#installation-du-projet)
- [Configuration de la base de données relationnelle](#configuration-de-la-base-de-données-relationnelle)
- [Configuration de MongoDB](#configuration-de-mongodb)
- [Compilation des assets](#compilation-des-assets)
- [Lancement du projet](#lancement-du-projet)
- [Comptes de démonstration](#comptes-de-démonstration)
- [Utilisation de l'application](#utilisation-de-lapplication)
- [Structure du projet](#structure-du-projet)
- [Organisation du code](#organisation-du-code)
- [Sécurité](#sécurité)
- [Gestion des rôles et autorisations](#gestion-des-rôles-et-autorisations)
- [Commandes utiles](#commandes-utiles)
- [Tests](#tests)
- [Qualité du code](#qualité-du-code)
- [Déploiement](#déploiement)
- [Variables sensibles et fichiers ignorés](#variables-sensibles-et-fichiers-ignorés)
- [Documentation complémentaire](#documentation-complémentaire)
- [Liens du projet](#liens-du-projet)
- [Améliorations futures](#améliorations-futures)
- [Auteur](#auteur)
- [Licence](#licence)

## Présentation du projet

Vite & Gourmand répond au besoin d'un traiteur souhaitant centraliser la présentation de ses menus, la prise de commandes et l'administration de son activité. L'application distingue quatre publics : les visiteurs, les clients inscrits, les employés et les administrateurs.

Le dépôt constitue un projet d'examen réalisé en 2026. Il met notamment en pratique Symfony, Twig, l'accès aux données relationnelles, un stockage documentaire MongoDB, la gestion de sessions, les formulaires HTML et les interfaces d'administration.

## État du projet

> Le projet est actuellement en cours de développement.

Les contrôleurs, vues et scripts front-end couvrent une grande partie des parcours prévus. Cependant, l'application ne peut pas être qualifiée de terminée :

- `.env` et les fichiers Docker configurent PostgreSQL 16 ;
- les scripts de création et de démonstration sont des exports MySQL 8 ;
- plusieurs requêtes SQL des contrôleurs utilisent une syntaxe propre à MySQL ;
- aucune migration Doctrine ne crée le schéma ;
- aucun test automatisé métier ou fonctionnel n'est présent.

La procédure fonctionnelle décrite ci-dessous retient donc MySQL 8, qui correspond au schéma livré et aux requêtes du code. La variable `DATABASE_URL` doit être corrigée localement avant le lancement.

## Fonctionnalités

La classification suivante s'appuie sur les flux effectivement présents dans les contrôleurs, les templates, les scripts et le schéma SQL. Les fonctionnalités nécessitant une base opérationnelle restent à valider de bout en bout après harmonisation de la configuration.

### Visiteur

Fonctionnalités implémentées dans le code :

- consulter la page d'accueil ;
- consulter et filtrer les menus actifs ;
- afficher le détail d'un menu et ses composants ;
- constituer un panier conservé en session ;
- envoyer une demande de contact, enregistrée en base et transmise par e-mail si le transport est configuré ;
- créer un compte client ;
- se connecter et se déconnecter ;
- demander et utiliser un lien de réinitialisation de mot de passe.

### Client (`utilisateur`)

Fonctionnalités implémentées dans le code :

- valider un panier et créer une commande ;
- consulter ses commandes, leur détail et leur historique de statuts ;
- modifier ou annuler une commande encore en attente ;
- ajouter un menu à une commande encore en attente ;
- modifier ses coordonnées et son mot de passe ;
- déposer un avis sur une commande terminée qui n'a pas encore reçu d'avis.

Fonctionnalités à valider après correction de la base :

- calcul de livraison reposant sur des services publics externes, avec estimation locale de secours ;
- synchronisation MongoDB après une commande ou une annulation ;
- envoi des e-mails de confirmation et d'invitation à déposer un avis.

### Employé (`employe`)

Fonctionnalités implémentées dans le code :

- consulter un tableau de bord et la liste des commandes ;
- modifier les informations et le statut d'une commande ;
- annuler une commande après saisie d'une trace de contact client ;
- créer, modifier, activer, désactiver et supprimer des menus, entrées, plats et desserts ;
- synchroniser l'état des composants avec celui de leur menu ;
- gérer les horaires et les fermetures exceptionnelles ;
- accepter, refuser ou remettre en attente des avis et sélectionner ceux de l'accueil ;
- consulter, répondre, archiver, restaurer et supprimer les messages de contact ;
- envoyer des rappels de retour de matériel.

### Administrateur (`administrateur`)

Fonctionnalités implémentées dans le code :

- accéder au tableau de bord administrateur ;
- consulter le volume et le chiffre d'affaires par menu ;
- créer, modifier, activer, désactiver et supprimer des employés ;
- accéder également à l'espace employé.

Fonctionnalités partiellement sécurisées :

- les autorisations sont contrôlées manuellement à partir de la session, et non par le composant Security de Symfony ;
- le mot de passe initial d'un employé est enregistré en clair dans `mot_de_passe_initial`, ce qui doit être supprimé avant toute utilisation réelle.

## Règles métier principales

Les règles suivantes sont codées :

- un compte actif est obligatoire pour finaliser une commande ;
- chaque menu impose un nombre minimal de personnes ;
- le prix d'une ligne correspond au prix par personne multiplié par le nombre de personnes ;
- une remise de 10 % est appliquée à une ligne lorsque le nombre de personnes atteint le minimum du menu augmenté de cinq ;
- la livraison est gratuite pour le code postal `33000` ; hors de ce code postal, elle comprend 5 € de base et 0,59 € par kilomètre ;
- la date de prestation doit respecter le délai extrait du texte des conditions du menu ;
- les livraisons sont acceptées entre 8 h et 22 h ;
- le prêt de matériel n'est proposé que si au moins un menu du panier l'autorise ;
- un client ne peut modifier, compléter ou annuler que ses commandes au statut `en_attente` ;
- un avis ne peut être créé que pour une commande `terminee`, par son client, et une seule fois par commande ;
- un avis passe d'abord au statut `en_attente`, puis peut être `accepte` ou `refuse` par un employé ;
- au maximum trois avis acceptés peuvent être sélectionnés pour la page d'accueil ;
- les statuts de commande prévus sont `en_attente`, `acceptee`, `en_preparation`, `en_livraison`, `livree`, `en_attente_retour_materiel`, `terminee` et `annulee`.

Limites connues : le catalogue public filtre sur le champ `actif`, mais ne filtre pas directement les dates de disponibilité ni le stock. La disponibilité réelle doit donc être confirmée avant une mise en production.

## Technologies utilisées

### Back-end

- **PHP 8.4 ou supérieur** : langage d'exécution, déclaré dans `composer.json` ;
- **Symfony 8.1** : framework HTTP, routage, sessions, validation, mail et console ;
- **Doctrine DBAL** : accès aux tables et exécution de requêtes SQL brutes ;
- **Doctrine ORM** : installé et configuré, mais aucune entité applicative n'est actuellement définie ;
- **Twig 3** : rendu des pages HTML ;
- **Symfony Mailer** : notifications de contact, de compte, de commande et de suivi.

### Front-end

- **HTML, CSS et JavaScript natif** : structure, présentation et interactions ;
- **AssetMapper et Importmap** : exposition et chargement des assets sans Node.js ;
- **Stimulus 3 et Turbo 8** : installés via l'importmap et initialisés dans les assets.

Le dépôt ne contient pas de `package.json`. Node.js et npm ne sont donc pas nécessaires.

### Bases de données

- **MySQL 8** : format du schéma et des données fournis dans `database/`, et dialecte utilisé par plusieurs requêtes métier ;
- **MongoDB** : copie documentaire des commandes utilisée pour les statistiques par menu ; si MongoDB est indisponible, le service retourne les données calculées depuis la base relationnelle ;
- **PostgreSQL 16** : présent dans la configuration générée (`.env`, `compose.yaml`), mais incompatible en l'état avec les scripts et une partie du SQL applicatif.

### Outils de développement

- **Composer** : gestion des dépendances PHP ;
- **PHPUnit 13.2** : infrastructure de tests ;
- **Git et GitHub** : versionnement du dépôt ;
- **Laragon** : environnement local actuellement disponible sur le poste de développement ; il reste facultatif si PHP, Composer et MySQL sont installés autrement.

## Architecture générale

Le projet suit partiellement l'organisation MVC de Symfony :

- les contrôleurs reçoivent les requêtes, appliquent la logique métier et interrogent la base ;
- les templates Twig produisent les vues ;
- Doctrine DBAL assure la persistance avec du SQL écrit dans les contrôleurs ;
- `MongoStatsService` isole la synchronisation des statistiques MongoDB ;
- `InputValidator` centralise plusieurs validations réutilisées ;
- AssetMapper publie les fichiers CSS, JavaScript et images du dossier `assets/`.

Il n'existe actuellement ni entité Doctrine, ni repository applicatif, ni classe de formulaire Symfony. Une part importante de la logique métier et SQL demeure donc concentrée dans les contrôleurs.

## Prérequis

- Git ;
- PHP 8.4 ou supérieur ;
- Composer 2 ;
- MySQL 8, recommandé tant que l'incohérence PostgreSQL n'est pas corrigée ;
- MongoDB et son extension PHP, uniquement pour persister les statistiques documentaires ;
- un serveur SMTP ou Mailpit, uniquement pour tester les e-mails réels.

Extensions PHP explicitement déclarées ou requises par les composants utilisés :

- `ext-ctype` ;
- `ext-iconv` ;
- PDO avec le pilote de la base choisie (`pdo_mysql` pour la procédure ci-dessous) ;
- `ext-mongodb` pour utiliser MongoDB.

La présence exacte de toutes les extensions sur une autre machine peut être vérifiée avec :

```bash
composer check-platform-reqs
```

## Installation du projet

### 1. Cloner le dépôt

```bash
git clone https://github.com/manonlrt261/Vite-gourmand.git
cd Vite-gourmand
```

### 2. Installer les dépendances PHP

```bash
composer install
```

Si Composer n'est pas disponible dans le `PATH` sous Laragon :

```powershell
& "C:\laragon\bin\php\php-8.4.12-nts-Win32-vs17-x64\php.exe" "C:\laragon\bin\composer\composer.phar" install
```

### 3. Créer la configuration locale

Sous Linux ou macOS :

```bash
cp .env .env.local
```

Sous PowerShell :

```powershell
Copy-Item .env .env.local
```

Ne versionnez pas `.env.local`. Remplacez-y les valeurs de développement par des valeurs propres à votre poste, sans publier de secret.

Exemple cohérent avec les scripts SQL fournis :

```dotenv
APP_ENV=dev
APP_SECRET=change_me_with_a_long_random_value
APP_URL="http://127.0.0.1:8005"
DEFAULT_URI="http://127.0.0.1:8005"
DATABASE_URL="mysql://vite_gourmand:mot_de_passe@127.0.0.1:3306/vite_et_gourmand?serverVersion=8.0&charset=utf8mb4"
MONGODB_URI="mongodb://127.0.0.1:27017"
MONGODB_DATABASE="vite_gourmand_nosql"
MAILER_DSN="null://null"
MAILER_FROM="contact@example.test"
ADMIN_EMAIL="administration@example.test"
```

| Variable | Obligatoire | Rôle |
| --- | --- | --- |
| `APP_ENV` | Oui | Environnement Symfony, généralement `dev` en local. |
| `APP_SECRET` | Oui | Secret interne de Symfony ; utiliser une valeur aléatoire privée. |
| `APP_URL` | Oui pour les liens applicatifs | URL de base de l'application. |
| `DEFAULT_URI` | Recommandée | URI utilisée pour générer des URL absolues hors requête. |
| `DATABASE_URL` | Oui | Connexion à la base relationnelle. Elle doit être MySQL tant que le code n'est pas porté vers PostgreSQL. |
| `MONGODB_URI` | Non | Connexion MongoDB ; le code utilise `mongodb://127.0.0.1:27017` par défaut. |
| `MONGODB_DATABASE` | Non | Nom de la base documentaire ; défaut : `vite_gourmand_nosql`. |
| `MAILER_DSN` | Oui | Transport d'e-mails ; `null://null` désactive l'envoi réel. |
| `MAILER_FROM` | Recommandée | Adresse expéditrice des e-mails. |
| `ADMIN_EMAIL` | Recommandée | Destinataire des demandes de contact. |
| `MESSENGER_TRANSPORT_DSN` | Présente dans `.env` | Transport Symfony Messenger ; aucune file métier personnalisée n'a été trouvée. |

### 4. Initialiser les données

Suivez la section suivante. Le projet n'utilise ni migration Doctrine ni fixture PHP pour créer et remplir son schéma.

## Configuration de la base de données relationnelle

### Situation actuelle

Les fichiers `database/01_creation_base.sql` et `database/02_insertion_donnees.sql` sont des exports MySQL. Ils ne sont pas compatibles avec le conteneur PostgreSQL défini dans `compose.yaml`. N'utilisez pas `doctrine:migrations:migrate` ni `doctrine:fixtures:load` pour l'installation actuelle : aucune migration et aucune fixture ne sont présentes.

### Initialisation avec MySQL 8

1. Démarrez MySQL 8.
2. Créez un utilisateur et une base vides avec l'outil de votre choix.
3. Configurez `DATABASE_URL` dans `.env.local`.
4. Importez le schéma, puis les données de démonstration :

```bash
mysql -u vite_gourmand -p vite_et_gourmand < database/01_creation_base.sql
mysql -u vite_gourmand -p vite_et_gourmand < database/02_insertion_donnees.sql
```

Équivalent PowerShell, qui évite l'opérateur de redirection :

```powershell
Get-Content -Raw database/01_creation_base.sql | mysql -u vite_gourmand -p vite_et_gourmand
Get-Content -Raw database/02_insertion_donnees.sql | mysql -u vite_gourmand -p vite_et_gourmand
```

Les scripts contiennent des suppressions de tables. Leur réimportation peut effacer les données existantes : utilisez-les uniquement sur une base de développement sauvegardée ou vide.

> `[À COMPLÉTER : décider si la configuration officielle doit rester sur MySQL 8 ou si le code et le schéma doivent être migrés vers PostgreSQL 16]`

## Configuration de MongoDB

MongoDB stocke la collection `commandes_par_menu` dans la base `vite_gourmand_nosql` par défaut. Lors de l'affichage des statistiques administrateur ou de certaines opérations sur les commandes, `MongoStatsService` reconstruit les documents depuis la base relationnelle, remplace le contenu de la collection, puis le relit.

1. Démarrez un serveur MongoDB accessible localement.
2. Activez l'extension PHP MongoDB.
3. Ajoutez au besoin `MONGODB_URI` et `MONGODB_DATABASE` dans `.env.local`.
4. Ouvrez une page de statistiques administrateur après connexion : la base et la collection sont créées automatiquement lors de la première écriture.

Aucune commande d'import MongoDB ni donnée documentaire indépendante n'est fournie. Si MongoDB est indisponible, le service intercepte l'erreur et utilise directement les documents calculés depuis SQL ; les graphiques peuvent donc s'afficher sans persistance MongoDB.

## Compilation des assets

Le projet utilise AssetMapper ; aucun `npm install` n'est nécessaire.

En développement, Symfony sert les assets depuis `assets/`. Pour préparer les fichiers optimisés de production :

```bash
php bin/console asset-map:compile
```

Cette commande écrit les fichiers compilés dans `public/assets/`. Il n'existe pas de commande `watch` dans la configuration actuelle.

## Lancement du projet

Après configuration des bases :

```bash
php -S 127.0.0.1:8005 -t public public/router.php
```

Sous PowerShell avec l'installation Laragon détectée :

```powershell
& "C:\laragon\bin\php\php-8.4.12-nts-Win32-vs17-x64\php.exe" -S 127.0.0.1:8005 -t public public/router.php
```

Ouvrez ensuite `http://127.0.0.1:8005`. Arrêtez le serveur avec `Ctrl+C`.

Le dépôt contient aussi `demarrer-site.bat`, qui lance ce serveur avec le chemin PHP Laragon ci-dessus.

## Comptes de démonstration

Les scripts de données ne créent aucun utilisateur de démonstration. Aucun identifiant ne peut donc être documenté de manière fiable.

| Rôle | Adresse e-mail | Mot de passe | Accès principal |
| --- | --- | --- | --- |
| Administrateur | `[À COMPLÉTER : compte de démonstration]` | `[À COMPLÉTER : mot de passe de démonstration]` | Administration et espace employé |
| Employé | `[À COMPLÉTER : compte de démonstration]` | `[À COMPLÉTER : mot de passe de démonstration]` | Gestion opérationnelle |
| Client | `[À COMPLÉTER : compte de démonstration]` | `[À COMPLÉTER : mot de passe de démonstration]` | Commandes et espace personnel |

Ces comptes devront être réservés aux environnements de développement ou de démonstration. Un client peut être créé depuis `/inscription`. La création sécurisée du premier administrateur reste à définir.

## Utilisation de l'application

### Parcours visiteur

1. Ouvrir l'accueil, puis le catalogue `/menus`.
2. Filtrer les menus ou afficher une fiche détaillée.
3. Ajouter un menu au panier.
4. Créer un compte ou se connecter pour finaliser la commande.

### Parcours client

1. Ajuster les quantités du panier en respectant le minimum du menu.
2. Renseigner la date, l'heure et l'adresse de livraison.
3. Valider la commande puis consulter son suivi dans `/mon-compte/commandes`.
4. Modifier ou annuler une commande encore en attente.
5. Déposer un avis lorsqu'une commande est terminée.

### Parcours employé

1. Se connecter avec un compte `employe` ou `administrateur`.
2. Ouvrir `/espace-employe`.
3. Gérer les commandes, le catalogue, les horaires, les avis et les messages.

### Parcours administrateur

1. Se connecter avec un compte `administrateur`.
2. Ouvrir `/espace-administrateur`.
3. Gérer les employés et consulter les statistiques par menu.

## Structure du projet

```text
Vite-gourmand/
├── assets/                  # CSS, JavaScript, images et contrôleurs Stimulus
├── bin/                     # Console Symfony et lanceur PHPUnit
├── config/                  # Services, routes et configuration des composants
├── database/                # Schéma et données de démonstration MySQL
├── migrations/              # Dossier présent, sans migration applicative
├── public/                  # Point d'entrée HTTP et routeur du serveur PHP
├── src/
│   ├── Controller/          # Contrôleurs et logique métier
│   ├── Service/             # Synchronisation des statistiques MongoDB
│   ├── Twig/                # Extension Twig des horaires
│   └── Validator/           # Validations partagées
├── templates/               # Vues Twig publiques et espaces de gestion
├── tests/                   # Amorçage PHPUnit, sans classe de test
├── translations/            # Fichiers de traduction du framework
├── .env                     # Valeurs de configuration par défaut
├── composer.json            # Dépendances et scripts Composer
├── compose.yaml             # Service PostgreSQL actuellement incohérent avec le SQL
├── importmap.php            # Dépendances JavaScript de l'importmap
└── phpunit.dist.xml         # Configuration PHPUnit
```

`vendor/`, `var/` et `public/assets/` sont générés localement et ne doivent pas être versionnés.

## Organisation du code

- `src/Controller/` contient les huit contrôleurs publics, client, employé et administrateur.
- `src/Service/MongoStatsService.php` transforme les commandes SQL en documents MongoDB.
- `src/Validator/InputValidator.php` valide notamment dates, horaires, téléphone, code postal, longueur et thèmes.
- `src/Twig/ScheduleExtension.php` expose les horaires et fermetures aux templates.
- `templates/` regroupe 38 fichiers Twig.
- `assets/app.js` charge les styles et scripts applicatifs ; AssetMapper les publie.
- `config/routes.yaml` déclare les routes applicatives ; aucune route par attribut n'a été trouvée dans les contrôleurs.
- `database/` remplace actuellement les migrations pour créer et remplir la base MySQL.

## Sécurité

### Protections configurées dans le code

- mots de passe clients hachés avec `password_hash(..., PASSWORD_DEFAULT)` et vérifiés avec `password_verify` ;
- politique de mot de passe : au moins 10 caractères, une majuscule, une minuscule, un chiffre et un caractère spécial ;
- jetons de réinitialisation aléatoires, stockés sous forme de hash SHA-256, expirant après une heure et utilisables une fois ;
- jetons CSRF vérifiés sur les formulaires et actions sensibles ;
- validation serveur des données utilisateur ;
- requêtes paramétrées par Doctrine DBAL pour les valeurs saisies ;
- contrôle de propriété des commandes côté client ;
- échappement explicite des contenus injectés dans les e-mails HTML.

### Protections natives utilisées

- auto-échappement Twig contre la plupart des injections HTML ;
- gestion des sessions par Symfony ;
- abstraction DBAL et paramètres liés pour réduire le risque d'injection SQL.

### Limites et améliorations nécessaires

- l'authentification est entièrement personnalisée dans `AuthController` ; le firewall Symfony utilise encore un fournisseur en mémoire vide ;
- aucune règle `access_control`, hiérarchie `role_hierarchy`, classe User, voter ou authenticator Symfony n'est configuré ;
- les droits sont contrôlés manuellement dans les contrôleurs à partir de données de session ;
- la déconnexion est accessible en GET et ne vérifie pas de jeton CSRF ;
- le mot de passe initial d'un employé est conservé en clair dans la colonne `mot_de_passe_initial` ;
- aucune limitation de tentatives de connexion ou de réinitialisation n'a été trouvée ;
- aucune politique explicite de cookies sécurisés ou d'en-têtes HTTP de sécurité n'est configurée ;
- aucune validation de téléversement n'est nécessaire actuellement, car les formulaires enregistrent des chemins d'image et ne téléversent pas de fichier.

## Gestion des rôles et autorisations

Les noms réellement stockés sont :

| Rôle en base | Identifiant fourni | Accès principal |
| --- | ---: | --- |
| `utilisateur` | 1 | Espace client et commandes personnelles |
| `employe` | 2 | Espace employé |
| `administrateur` | 3 | Espace administrateur et espace employé |

Il ne s'agit pas de rôles Symfony de type `ROLE_USER`, `ROLE_EMPLOYEE` ou `ROLE_ADMIN`. L'héritage administrateur vers employé est codé dans `EmployeeController`, qui accepte le libellé `administrateur` ou l'identifiant 3.

## Commandes utiles

| Action | Commande |
| --- | --- |
| Installer les dépendances | `composer install` |
| Vérifier les prérequis PHP | `composer check-platform-reqs` |
| Afficher l'environnement | `php bin/console about` |
| Lister les routes | `php bin/console debug:router` |
| Vérifier les fichiers YAML | `php bin/console lint:yaml config` |
| Vérifier les templates Twig | `php bin/console lint:twig templates` |
| Vider le cache | `php bin/console cache:clear` |
| Compiler les assets | `php bin/console asset-map:compile` |
| Lancer le serveur local | `php -S 127.0.0.1:8005 -t public public/router.php` |
| Lancer PHPUnit | `php bin/phpunit` |

Les commandes `doctrine:migrations:migrate` et `doctrine:fixtures:load` sont volontairement absentes : le dépôt ne contient ni migration ni fixture Symfony.

## Tests

PHPUnit 13.2 est installé et configuré dans `phpunit.dist.xml`. Le lanceur est disponible dans `bin/phpunit` et le dossier prévu est `tests/`.

```bash
php bin/phpunit
```

> Aucun test automatisé n'est actuellement présent dans le projet. La commande aboutit avec le message `No tests executed!`.

Tests recommandés :

- inscription, connexion et réinitialisation de mot de passe ;
- calcul du panier, remise et livraison ;
- création, modification et annulation d'une commande ;
- cloisonnement des commandes entre clients ;
- autorisations employé et administrateur ;
- modération des avis ;
- repli lorsque MongoDB ou le service de distance est indisponible.

## Qualité du code

Aucun PHPStan, Psalm, PHP CS Fixer, ESLint ou Prettier n'est configuré. Les vérifications actuellement disponibles sont :

```bash
composer validate --no-check-publish
php bin/console lint:yaml config
php bin/console lint:twig templates
```

## Déploiement

L'application n'est pas confirmée comme déployée. Avant toute mise en production, il faut d'abord harmoniser le moteur relationnel et remplacer le mécanisme de création de tables à l'exécution par des migrations.

Étapes compatibles avec la structure actuelle après cette correction :

1. configurer un serveur web dont le document root pointe vers `public/` ;
2. installer PHP 8.4, les extensions nécessaires, MySQL 8 et éventuellement MongoDB ;
3. définir les variables de production sans les commiter ;
4. installer les dépendances :

   ```bash
   composer install --no-dev --optimize-autoloader
   ```

5. initialiser la base avec une procédure de migration validée ;
6. compiler les assets et le fichier d'environnement :

   ```bash
   php bin/console asset-map:compile --env=prod
   composer dump-env prod
   ```

7. vider et préchauffer le cache :

   ```bash
   php bin/console cache:clear --env=prod
   ```

8. donner au processus web les droits d'écriture nécessaires sur `var/` ;
9. configurer HTTPS, les cookies sécurisés, le SMTP et les sauvegardes ;
10. créer le premier administrateur par une procédure sécurisée à définir ;
11. exécuter des tests fonctionnels sur l'environnement déployé.

Application déployée : `[À COMPLÉTER : URL de l'application déployée]`

## Variables sensibles et fichiers ignorés

Les secrets, mots de passe, jetons et identifiants SMTP ne doivent jamais être publiés sur GitHub. La configuration locale doit rester dans `.env.local`.

Le `.gitignore` exclut notamment :

- `.env.local`, `.env.local.php` et `.env.*.local` ;
- la clé privée de déchiffrement des secrets de production ;
- `vendor/` ;
- `var/` ;
- `public/assets/` et `public/bundles/` ;
- `assets/vendor/` ;
- `.phpunit.cache/` et `phpunit.xml` ;
- `tmp/`.

Le projet n'utilise pas `node_modules/`, puisqu'il n'a pas de `package.json`.

## Documentation complémentaire

- Manuel utilisateur : `[À COMPLÉTER : lien ou chemin]`
- Charte graphique : `[À COMPLÉTER : lien ou chemin]`
- Documentation technique : ce README et `[À COMPLÉTER : documentation complémentaire]`
- Documentation de gestion de projet : `[À COMPLÉTER : lien ou chemin]`
- Schémas UML et diagramme de classes : `[À COMPLÉTER : lien ou chemin]`
- MCD : `[À COMPLÉTER : lien ou chemin]`
- Diagrammes de séquence : `[À COMPLÉTER : lien ou chemin]`
- Maquettes Figma : `[À COMPLÉTER : lien]`

## Liens du projet

- Dépôt GitHub : [manonlrt261/Vite-gourmand](https://github.com/manonlrt261/Vite-gourmand)
- Application en ligne : `[À COMPLÉTER : URL]`
- Gestion de projet : `[À COMPLÉTER : URL]`
- Maquettes Figma : `[À COMPLÉTER : URL]`
- Documentation : `[À COMPLÉTER : URL ou chemin]`

## Améliorations futures

- choisir officiellement MySQL ou PostgreSQL et harmoniser `.env`, Docker, le schéma et toutes les requêtes ;
- créer des migrations Doctrine reproductibles et supprimer les créations ou altérations de tables dans les contrôleurs ;
- migrer l'authentification et les autorisations vers Symfony Security ;
- supprimer tout stockage de mot de passe en clair et utiliser un lien d'activation à usage unique pour les employés ;
- ajouter des tests unitaires, d'intégration et fonctionnels, puis une intégration continue ;
- extraire la logique métier et SQL volumineuse des contrôleurs vers des services et repositories ;
- contrôler les dates de disponibilité et le stock dans le catalogue et au moment de commander ;
- renforcer l'accessibilité, le responsive et les contrôles de sécurité avant déploiement.

## Auteur

Projet réalisé par Manon Fayolle en 2026.

## Licence

Le champ `license` de `composer.json` vaut `proprietary`, mais aucun fichier de licence n'est présent.

> Ce projet a été réalisé dans un cadre pédagogique. Aucune licence de réutilisation n'est actuellement définie.
