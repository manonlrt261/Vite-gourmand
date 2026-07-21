# 1. Réflexion initiale technologique sur le projet

Vite & Gourmand est une application web de traiteur réalisée dans le cadre d’un projet d’examen. Elle permet de présenter l’entreprise et ses menus, de gérer un panier et des commandes, puis de proposer des espaces adaptés aux clients, employés et administrateurs.

L’analyse repose sur le code et les fichiers réellement présents dans le dépôt. Les versions indiquées proviennent de `composer.json`, `composer.lock`, du dump MySQL et de l’environnement PHP local.

## 1.1 Besoins fonctionnels identifiés

L’application couvre quatre profils :

- le visiteur consulte l’accueil, les menus et le formulaire de contact ;
- le client crée un compte, se connecte, gère son panier, commande, consulte ses commandes, modifie son profil et dépose un avis ;
- l’employé gère les commandes, menus, repas, horaires, avis et messages ;
- l’administrateur gère les employés et consulte les statistiques.

Les domaines métier confirmés dans le code sont le catalogue de menus, les entrées, plats et desserts, le panier, les commandes, la livraison, le prêt de matériel, les avis, la messagerie, les horaires, les comptes et les statistiques.

## 1.2 Technologies retenues

| Technologie | Version | Rôle dans le projet |
|---|---:|---|
| PHP | contrainte `>= 8.4`, version locale 8.4.12 | exécution de la logique serveur |
| Symfony | 8.1.0 | routage, contrôleurs, injection de dépendances, sessions, cache et configuration |
| Twig | 3.27.1 | génération des pages HTML |
| Doctrine DBAL | 4.4.3 | exécution des requêtes SQL vers MySQL |
| MySQL | dump produit par MySQL 8.0.46 | stockage principal des données métier |
| MongoDB | bibliothèque PHP 2.3.0 | stockage des statistiques agrégées par menu |
| AssetMapper | 8.1.0 | publication des fichiers CSS et JavaScript sans compilation Node.js |
| Stimulus | 3.2.2 | infrastructure JavaScript Symfony |
| Turbo | 8.0.23 | infrastructure de navigation dynamique |
| Symfony Mailer | 8.1.0 | envoi des e-mails transactionnels |
| PHPUnit | 13.2.0 | infrastructure de test, sans test applicatif présent |

## 1.3 Justification des choix

### PHP et Symfony

PHP est adapté à une application web métier reposant sur des formulaires, des sessions et une base relationnelle. Symfony apporte une organisation structurée, un routeur, un conteneur de services, Twig, le mailer, la gestion des environnements et des outils de diagnostic.

La version actuelle utilise huit contrôleurs : `HomeController`, `MenuController`, `AuthController`, `CartController`, `CustomerController`, `EmployeeController`, `AdminController` et `ContactController`.

### Twig

Twig sépare l’affichage de la logique serveur. Le layout `templates/base.html.twig` fournit la structure commune. Les templates sont regroupés par domaine et utilisent des composants réutilisables pour les cartes, titres, en-têtes et pieds de page. L’échappement automatique de Twig limite les risques d’injection HTML lors de l’affichage de données utilisateur.

### Doctrine DBAL et MySQL

MySQL stocke les utilisateurs, menus, commandes, statuts, avis, horaires et messages. InnoDB apporte les transactions et les clés étrangères.

L’accès réel aux données repose sur Doctrine DBAL avec des requêtes SQL dans les contrôleurs et `MongoStatsService`.

Ce choix permet de contrôler précisément les requêtes, mais il augmente le volume des contrôleurs et disperse la logique d’accès aux données. Une évolution future pourrait introduire des repositories DBAL ou des entités Doctrine.

### MongoDB

MongoDB stocke des documents statistiques contenant notamment le nombre de commandes et le chiffre d’affaires par menu. MySQL reste la base principale ; les statistiques sont recalculées depuis MySQL puis écrites dans MongoDB par `src/Service/MongoStatsService.php`.

Ce découpage facilite la lecture de données agrégées, mais crée un risque de désynchronisation si MongoDB est indisponible.

### Front-end et assets

L’interface utilise HTML, CSS et JavaScript natif. Les scripts utilisent `fetch`, `FormData` et des attributs `data-*` pour mettre à jour le panier, les commandes, les avis et la messagerie sans rechargement complet.

AssetMapper et Importmap évitent l’installation de Node.js. Aucun `package.json` ni verrou npm, Yarn ou pnpm n’est présent.

## 1.4 Architecture retenue

Le projet suit une architecture MVC Symfony simplifiée :

```text
Navigateur
    │ Requête HTTP ou Fetch
    ▼
Routeur Symfony
    ▼
Contrôleur
    ├── Doctrine DBAL ──► MySQL
    ├── MongoStatsService ──► MongoDB
    ├── Symfony Mailer ──► Serveur SMTP
    └── API cartographiques publiques
    ▼
Template Twig ou réponse JSON
    ▼
Navigateur
```

Les points forts de cette architecture sont la séparation des vues, l’injection de dépendances, les requêtes paramétrées, l’utilisation de transactions pour les commandes et l’organisation des routes par domaine.


## 1.5 Principales règles métier

- seuls les menus actifs sont visibles publiquement ;
- le nombre de personnes ne peut pas être inférieur au minimum du menu ;
- les prix et stocks sont relus depuis MySQL avant la commande ;
- les frais de livraison comprennent une base de 5 € et 0,59 € par kilomètre ;
- le trajet est calculé depuis Bordeaux ;
- la date de prestation doit respecter le délai inscrit dans les conditions du menu ;
- le prêt de matériel est accepté uniquement si un menu le propose ;
- la création d’une commande, de ses lignes et de son historique utilise une transaction ;
- un client ne peut consulter ou modifier que ses propres commandes ;
- un composant de repas ne peut pas être actif si son menu parent est inactif ;
- les invitations à déposer un avis et les rappels de matériel sont journalisés pour éviter les doublons ;
- un mot de passe nouvellement créé doit contenir au moins dix caractères, une majuscule, une minuscule, un chiffre et un caractère spécial.

# 2. Configuration de l’environnement de travail

## 2.1 Prérequis

L’environnement de développement doit disposer des éléments suivants :

- PHP 8.4 avec `ctype`, `iconv`, PDO MySQL et l’extension MongoDB ;
- Composer ;
- MySQL 8 ;
- MongoDB ;
- Git ;
- un navigateur récent ;
- un serveur SMTP pour tester les e-mails réels.

Le projet a été développé sous Windows avec Laragon. Node.js n’est pas nécessaire, car les assets utilisent AssetMapper.

## 2.2 Récupération du projet

```bash
git clone https://github.com/manonlrt261/Vite-gourmand-production.git
cd Vite-gourmand
```

## 2.3 Installation des dépendances

```bash
composer install
```

Cette commande utilise `composer.lock` afin d’installer les versions validées pour le projet. Elle exécute également les auto-scripts Symfony, notamment le nettoyage du cache et l’installation des assets.

## 2.4 Variables d’environnement

Les valeurs propres à la machine doivent être définies dans `.env.local`, fichier exclu de Git.

| Variable | Utilisation |
|---|---|
| `APP_ENV` | environnement Symfony, par exemple `dev` ou `prod` |
| `APP_SECRET` | secret interne de Symfony |
| `DATABASE_URL` | connexion MySQL |
| `MONGODB_URI` | connexion au serveur MongoDB |
| `MONGODB_DATABASE` | nom de la base MongoDB |
| `MAILER_DSN` | connexion au serveur SMTP |
| `MAILER_FROM` | adresse d’expédition des e-mails |
| `ADMIN_EMAIL` | adresse recevant les messages de contact |
| `APP_URL` | URL de l’application |
| `DEFAULT_URI` | génération des URL absolues |
| `MESSENGER_TRANSPORT_DSN` | transport Symfony Messenger configuré |

Exemple sans identifiants réels :

```env
APP_ENV=dev
APP_SECRET=
DATABASE_URL="mysql://utilisateur:mot_de_passe@127.0.0.1:3306/vite_et_gourmand?charset=utf8mb4"
MONGODB_URI="mongodb://127.0.0.1:27017"
MONGODB_DATABASE="vite_gourmand_nosql"
MAILER_DSN=smtp://b22015001%40smtp-brevo.com:CLESMTP@smtp-relay.brevo.com:587
MAILER_FROM="viteetgourmand33@gmail.com"
ADMIN_EMAIL="viteetgourmand33@gmail.com"
APP_URL="https://viteetgourmand33.alwaysdata.net"
DEFAULT_URI="https://viteetgourmand33.alwaysdata.net"
```

## 2.5 Initialisation de MySQL

L’initialisation d’une base neuve repose sur deux scripts :

1. `database/01_creation_base.sql` crée les 18 tables ;
2. `database/02_insertion_donnees.sql` insère les rôles, statuts, menus et données de démonstration.

```bash
mysql -u utilisateur -p -e "CREATE DATABASE vite_et_gourmand CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -u utilisateur -p vite_et_gourmand < database/01_creation_base.sql
mysql -u utilisateur -p vite_et_gourmand < database/02_insertion_donnees.sql
```

Le premier script contient des instructions `DROP TABLE`. Il ne doit être utilisé que pour initialiser ou réinitialiser volontairement une base. Il ne constitue pas une procédure de mise à jour d’une base de production.

## 2.6 Initialisation de MongoDB

MongoDB doit être accessible par l’URI définie dans `.env.local`. La base par défaut utilisée par le service est `vite_gourmand_nosql`. La collection statistique est alimentée depuis les données MySQL lors du recalcul effectué par l’application.

Aucune commande d’initialisation MongoDB dédiée n’est présente. Le comportement sur une base entièrement vide doit être vérifié manuellement depuis l’espace administrateur.

## 2.7 Préparation des assets

En développement, AssetMapper peut servir directement les fichiers de `assets/`. La configuration se trouve dans `config/packages/asset_mapper.yaml` et `importmap.php`.

Pour produire les assets publics :

```bash
php bin/console asset-map:compile
```

`assets/` doit rester la source de vérité. Les copies présentes dans `public/assets/` ne doivent pas être modifiées manuellement, car certains fichiers publics divergent des sources.

## 2.8 Lancement en local

```bash
php -S 127.0.0.1:8005 -t public public/router.php
```

Dans l’environnement Laragon observé :

```powershell
& "C:\laragon\bin\php\php-8.4.12-nts-Win32-vs17-x64\php.exe" -S 127.0.0.1:8005 -t public public/router.php
```

Le script `demarrer-site.bat` automatise ce lancement, mais contient un chemin PHP propre à la machine de développement.

## 2.9 Vérifications de l’environnement

```bash
php bin/console about
php bin/console debug:router
php bin/console debug:container
```

La commande `debug:router` confirme 55 routes métier, auxquelles s’ajoutent les routes techniques disponibles en développement.


# 3. Modèle conceptuel de données avec cardinalités

Le modèle ci-dessous est construit à partir de `database/01_creation_base.sql`. Il représente les 18 tables, leurs principales clés et les relations réellement garanties par des clés étrangères.

```mermaid
erDiagram
    ROLES ||--o{ UTILISATEURS : attribue
    UTILISATEURS ||--o{ COMMANDES : passe
    MENUS ||--o{ COMMANDES : reference
    STATUTS_COMMANDE ||--o{ COMMANDES : definit
    COMMANDES ||--o{ COMMANDE_MENUS : contient
    MENUS ||--o{ COMMANDE_MENUS : compose
    MENUS o|--o{ ENTREE : regroupe
    MENUS o|--o{ PLAT : regroupe
    MENUS o|--o{ DESSERT : regroupe
    COMMANDES ||--o{ HISTORIQUE_STATUTS_COMMANDE : possede
    STATUTS_COMMANDE ||--o{ HISTORIQUE_STATUTS_COMMANDE : consigne
    COMMANDES ||--o{ AVIS : recoit
    UTILISATEURS ||--o{ AVIS : redige
    UTILISATEURS o|--o{ CONTACT : associe
    UTILISATEURS ||--o{ PASSWORD_RESET_TOKENS : demande
    COMMANDES ||--o| AVIS_EMAIL_LOG : journalise
    COMMANDES ||--o| AVIS_EMAIL_QUEUE : planifie
    UTILISATEURS ||--o{ AVIS_EMAIL_QUEUE : recoit
    COMMANDES ||--o| MATERIEL_EMAIL_LOG : journalise

    ROLES {
        int role_id PK
        varchar libelle
        datetime created_at
        datetime updated_at "nullable"
    }
    UTILISATEURS {
        int id PK
        int role_id FK
        varchar nom
        varchar prenom
        varchar email UK
        varchar mot_de_passe
        bool actif
        datetime created_at
    }
    MENUS {
        int menu_id PK
        varchar nom_menu
        varchar theme
        int personnes_minimum
        decimal prix_par_personne
        int stock_disponible
        bool materiel_disponible
        bool actif
    }
    ENTREE {
        int entree_id PK
        int menu_id FK "nullable"
        varchar nom_entree
        varchar allergenes
        bool actif
    }
    PLAT {
        int plat_id PK
        int menu_id FK "nullable"
        varchar nom_plat
        varchar allergenes
        bool actif
    }
    DESSERT {
        int dessert_id PK
        int menu_id FK "nullable"
        varchar nom_dessert
        varchar allergenes
        bool actif
    }
    STATUTS_COMMANDE {
        int statut_id PK
        varchar code UK
        varchar libelle
        int ordre
    }
    COMMANDES {
        int commande_id PK
        int utilisateur_id FK
        int menu_id FK
        int statut_id FK
        date date_prestation
        int nombre_personnes
        decimal prix_total
        bool pret_materiel
    }
    COMMANDE_MENUS {
        int commande_menu_id PK
        int commande_id FK
        int menu_id FK
        int nombre_personnes
        decimal prix_par_personne
        decimal prix_menu
        decimal reduction
    }
    HISTORIQUE_STATUTS_COMMANDE {
        int historique_id PK
        int commande_id FK
        int statut_id FK
        datetime date_changement
        text commentaire "nullable"
    }
    AVIS {
        int avis_id PK
        int commande_id FK
        int utilisateur_id FK
        int note
        text commentaire
        varchar statut
        bool afficher_accueil
    }
    CONTACT {
        int contact_id PK
        int utilisateur_id FK "nullable"
        varchar email
        varchar titre
        text description
        varchar statut
        datetime archived_at "nullable"
        datetime deleted_at "nullable"
    }
    HORAIRES_OUVERTURE {
        varchar jour_code PK
        varchar jour_label
        bool est_ouvert
        time heure_ouverture "nullable"
        time heure_fermeture "nullable"
        int ordre
    }
    FERMETURES_EXCEPTIONNELLES {
        int fermeture_id PK
        date date_fermeture
        date date_fin_fermeture "nullable"
        varchar motif
    }
    PASSWORD_RESET_TOKENS {
        int id PK
        int utilisateur_id FK
        varchar token_hash UK
        datetime expires_at
        datetime used_at "nullable"
    }
    AVIS_EMAIL_LOG {
        int log_id PK
        int commande_id FK, UK
        varchar email
        datetime sent_at
    }
    AVIS_EMAIL_QUEUE {
        int queue_id PK
        int commande_id FK, UK
        int utilisateur_id FK
        varchar email
        datetime send_after
        datetime sent_at "nullable"
    }
    MATERIEL_EMAIL_LOG {
        int log_id PK
        int commande_id FK, UK
        varchar email
        datetime sent_at
    }
```

## 3.1 Lecture des cardinalités

- `||` signifie exactement une occurrence obligatoire ;
- `o|` signifie zéro ou une occurrence ;
- `o{` signifie zéro à plusieurs occurrences.

Un utilisateur appartient obligatoirement à un rôle, alors qu’un rôle peut être associé à zéro ou plusieurs utilisateurs. Une commande appartient obligatoirement à un utilisateur, un menu principal et un statut. Une commande peut contenir plusieurs lignes `commande_menus`.

Les colonnes `menu_id` des tables `entree`, `plat` et `dessert` acceptent `NULL`. Un composant peut donc être associé à zéro ou un menu, tandis qu’un menu peut regrouper zéro à plusieurs composants.

`commande_id` est unique dans `avis_email_log`, `avis_email_queue` et `materiel_email_log`. Une commande possède donc zéro ou un enregistrement dans chacune de ces tables.

`horaires_ouverture` et `fermetures_exceptionnelles` n’ont aucune clé étrangère. Les champs `responded_by` et `respondent_id` de `contact` ne sont pas représentés comme relations, car aucune contrainte SQL ne les relie à `utilisateurs`.

# 4. Diagramme d’utilisation et diagrammes de séquence

## 4.1 Diagramme d’utilisation

Mermaid ne possède pas de notation UML native pour les cas d’utilisation. Le diagramme suivant utilise un organigramme afin de représenter les quatre acteurs et leurs fonctionnalités confirmées par le code.

```mermaid
flowchart LR
    visitor([Visiteur])
    customer([Client])
    employee([Employe])
    administrator([Administrateur])

    viewCompany[Consulter accueil]
    viewMenus[Consulter menus]
    sendContact[Envoyer message]
    register[Creer compte]
    login[Se connecter]
    manageCart[Gerer panier]
    placeOrder[Passer commande]
    customerOrders[Consulter ses commandes]
    customerProfile[Modifier son profil]
    customerReview[Deposer un avis]
    manageOrders[Gerer commandes]
    manageContent[Gerer menus et repas]
    manageHours[Gerer horaires]
    moderateReviews[Moderer avis]
    manageMessages[Gerer messagerie]
    manageEmployees[Gerer employes]
    viewStats[Consulter statistiques]

    visitor --> viewCompany
    visitor --> viewMenus
    visitor --> sendContact
    visitor --> register
    visitor --> login

    customer --> viewCompany
    customer --> viewMenus
    customer --> manageCart
    customer --> placeOrder
    customer --> customerOrders
    customer --> customerProfile
    customer --> customerReview

    employee --> manageOrders
    employee --> manageContent
    employee --> manageHours
    employee --> moderateReviews
    employee --> manageMessages

    administrator --> manageEmployees
    administrator --> viewStats
```

Le visiteur n’est pas un rôle enregistré en base : il correspond à une personne sans session authentifiée. Les rôles SQL sont `utilisateur`, `employe` et `administrateur`.

## 4.2 Diagramme de séquence de connexion

Ce diagramme représente le fonctionnement réel de `AuthController::login`.

```mermaid
sequenceDiagram
    title Connexion a Vite et Gourmand
    participant Utilisateur
    participant Navigateur
    participant AuthController
    participant MySQL
    participant Session

    Utilisateur->>Navigateur: Saisir email et mot de passe
    Navigateur->>AuthController: POST /connexion avec CSRF
    AuthController->>AuthController: Verifier jeton CSRF
    AuthController->>MySQL: Rechercher utilisateur et role
    MySQL-->>AuthController: Compte et mot de passe stocke
    AuthController->>AuthController: Verifier actif et mot de passe
    AuthController->>Session: Enregistrer utilisateur et role
    Session-->>AuthController: Session initialisee
    AuthController-->>Navigateur: Redirection selon le role
    Navigateur-->>Utilisateur: Afficher espace autorise
```

Le contrôleur vérifie le jeton CSRF, recherche le compte par e-mail, refuse un utilisateur inactif et redirige vers l’espace correspondant au rôle. L’authentification utilise une session applicative manuelle et non le mécanisme `form_login` de Symfony Security.

## 4.3 Diagramme de séquence de commande

Ce diagramme représente le chemin principal de `CartController::checkout`.

```mermaid
sequenceDiagram
    title Creation d une commande
    participant Client
    participant Navigateur
    participant CartController
    participant MySQL
    participant ServicesCarte
    participant MongoDB
    participant Mailer

    Client->>Navigateur: Valider panier et livraison
    Navigateur->>CartController: POST /panier/commander avec CSRF
    CartController->>CartController: Verifier session et donnees
    CartController->>MySQL: Relire menus prix stocks et minimums
    MySQL-->>CartController: Donnees fiables du panier
    CartController->>ServicesCarte: Geocoder et calculer distance
    ServicesCarte-->>CartController: Coordonnees et kilometres
    CartController->>CartController: Calculer menus livraison et total
    CartController->>MySQL: Demarrer transaction
    CartController->>MySQL: Inserer commande lignes et historique
    MySQL-->>CartController: Valider transaction
    CartController->>MongoDB: Actualiser statistiques
    MongoDB-->>CartController: Statistiques synchronisees
    CartController->>Mailer: Envoyer confirmation
    Mailer-->>CartController: Envoi tente
    CartController-->>Navigateur: Redirection confirmation
    Navigateur-->>Client: Afficher recapitulatif
```

Le serveur ne fait pas confiance aux prix du navigateur. Les menus, prix, stocks et quantités minimales sont relus depuis MySQL. La commande, ses lignes et son premier historique sont enregistrés dans une transaction. Une indisponibilité du SMTP ne doit pas annuler une commande déjà créée.

# 5. Documentation du déploiement de l’application

L’application Vite & Gourmand est déployée sur la plateforme **alwaysdata** et accessible à l’adresse suivante :

**URL de production : <https://gourmandetvite.alwaysdata.net/>**

Le déploiement est donc effectif. Aucun pipeline CI/CD ni script de déploiement automatisé n’est toutefois présent dans le dépôt : la mise en ligne repose sur une procédure manuelle. La documentation ci-dessous présente la démarche correspondant au projet et à l’environnement alwaysdata.

## 5.1 Préparation avant déploiement

Avant toute mise en production :

1. identifier le commit Git à livrer ;
2. sauvegarder les bases existantes ;
3. vérifier les variables de production ;
4. contrôler la syntaxe PHP et les routes ;
5. prévoir une recette manuelle, car aucun test applicatif n’est présent ;
6. préparer une stratégie de retour arrière.

Le compte alwaysdata utilisé pour publier l’application est associé au sous-domaine `viteetgourmand33.alwaysdata.net`. La configuration précise des utilisateurs, mots de passe, noms de bases et chemins internes n’est pas reproduite afin de ne pas exposer d’informations sensibles.

Commandes de contrôle :

```bash
git status
git log -1 --oneline
composer validate
composer audit
php bin/console about
php bin/console debug:router
```

## 5.2 Prérequis du serveur

Alwaysdata fournit l’hébergement web et permet de sélectionner une version de PHP compatible avec le projet. PHP y fonctionne en FastCGI derrière Apache. Pour Vite & Gourmand, l’environnement doit fournir :

- PHP 8.4 et les extensions nécessaires ;
- Composer ;
- PHP 8.4 ;
- MariaDB/MySQL ;
- Apache, fourni par alwaysdata ;
- une solution MongoDB externe ou un service MongoDB lancé séparément ;
- HTTPS ;
- un accès sécurisé au SMTP ;
- un mécanisme de sauvegarde ;
- un accès aux journaux.

Dans l’interface alwaysdata, le site doit être déclaré dans **Web > Sites** avec l’adresse `viteetgourmand33.alwaysdata.net`. Le répertoire racine du site doit pointer vers le dossier `public/` du projet Symfony. Les dossiers `src/`, `config/`, `database/`, `vendor/` et les fichiers `.env*` ne doivent pas être accessibles directement depuis le Web.


## 5.3 Récupération du code

```bash
git clone https://github.com/manonlrt261/Vite-gourmand.git
cd Vite-gourmand
git checkout <commit-valide>
```

En cas de déploiement depuis une copie existante, éviter un `git pull` non contrôlé. Identifier explicitement le commit déployé permet de revenir à la version précédente.

Sur alwaysdata, les fichiers peuvent être transférés par SSH/SFTP ou récupérés avec Git depuis une session SSH. Le projet doit être placé dans un répertoire du compte, puis le site déclaré dans l’interface doit cibler son sous-dossier `public/`.

## 5.4 Installation des dépendances de production

```bash
composer install --no-dev --optimize-autoloader
```

`composer install` doit être utilisé et non `composer update`, afin de conserver les versions de `composer.lock`.

## 5.5 Configuration de production

Configurer les variables dans l’environnement du serveur ou dans un fichier local non versionné :

```env
APP_ENV=prod
APP_SECRET=secret_de_production
DATABASE_URL=mysql://...
MONGODB_URI=mongodb://...
MONGODB_DATABASE=vite_gourmand_nosql
MAILER_DSN=smtp://...
MAILER_FROM=...
ADMIN_EMAIL=...
APP_URL=https://viteetgourmand33.alwaysdata.net
DEFAULT_URI=https://viteetgourmand33.alwaysdata.net
```

Les valeurs réelles doivent être stockées dans un gestionnaire de secrets ou dans la configuration sécurisée de l’hébergeur. Elles ne doivent jamais être ajoutées au dépôt.

## 5.6 Mise en place de MySQL

### Installation neuve

Sur alwaysdata, la base et son utilisateur doivent être créés depuis l’interface d’administration, dans la rubrique **Bases de données > MySQL**, ou avec l’API alwaysdata. La création n’est pas réalisée par l’application.

L’hôte de connexion utilise la forme `mysql-[compte].alwaysdata.net` et le port 3306. Les valeurs exactes de la base et de l’utilisateur sont disponibles dans l’interface alwaysdata et doivent être reportées dans `DATABASE_URL`.

Une fois la base vide créée, les scripts peuvent être importés avec phpMyAdmin ou depuis la session SSH :

```bash
mysql -h mysql-[compte].alwaysdata.net -u [utilisateur] -p [base] < database/01_creation_base.sql
mysql -h mysql-[compte].alwaysdata.net -u [utilisateur] -p [base] < database/02_insertion_donnees.sql
```

Le second script contient des données de démonstration. Avant une mise en production, vérifier les comptes, données personnelles et mots de passe importés. Les comptes de test doivent être supprimés ou désactivés.

### Mise à jour d’une base existante

Le projet ne contient aucune migration Doctrine. `database/01_creation_base.sql` contient des suppressions de tables et ne doit jamais être exécuté sur une base de production existante.

Tant que des migrations versionnées n’ont pas été créées, chaque évolution de schéma doit faire l’objet d’un script SQL incrémental relu, sauvegardé et testé sur une copie de la base avant production.


## 5.7 Mise en place de MongoDB

MongoDB n’est plus proposé comme base managée par alwaysdata. Pour conserver les statistiques NoSQL du projet, il faut utiliser l’une des solutions suivantes :

- exécuter MongoDB comme service personnalisé sur alwaysdata, si l’offre le permet ;
- utiliser un service externe tel que MongoDB Atlas ;
- utiliser l’instance MongoDB effectivement configurée lors du déploiement.

Configurer ensuite `MONGODB_URI` et `MONGODB_DATABASE`, puis vérifier que l’espace administrateur peut alimenter et relire la collection.


## 5.8 Compilation des assets

```bash
php bin/console asset-map:compile --env=prod
```

Cette commande publie les fichiers provenant de `assets/`. Le dossier `public/assets/` doit être généré à partir des sources et non copié depuis une version locale potentiellement obsolète.

## 5.9 Cache Symfony

```bash
php bin/console cache:clear --env=prod
php bin/console cache:warmup --env=prod
```

L’utilisateur du serveur web doit pouvoir écrire dans `var/cache/` et `var/log/`.

## 5.10 Configuration du serveur web et HTTPS

La configuration du site alwaysdata doit :

- servir `public/index.php` comme point d’entrée ;
- rediriger les URL applicatives vers Symfony ;
- bloquer l’accès aux fichiers cachés et sensibles ;
- forcer HTTPS ;
- transmettre correctement les en-têtes du proxy, si nécessaire ;
- désactiver l’affichage des erreurs PHP en production.

L’URL publique utilise déjà HTTPS : `https://gourmandetvite.alwaysdata.net/`. Le certificat TLS doit rester valide. Les cookies de session doivent être protégés avec `Secure`, `HttpOnly` et une politique `SameSite` adaptée.

## 5.11 Vérifications après déploiement

Effectuer au minimum les contrôles suivants :

- l’accueil et les menus sont accessibles ;
- les CSS, JavaScript et images sont chargés ;
- l’environnement est `prod` et le profiler est inaccessible ;
- la connexion MySQL fonctionne ;
- la connexion MongoDB fonctionne ;
- l’inscription, la connexion et la déconnexion fonctionnent ;
- un client ne peut pas accéder à la commande d’un autre client ;
- les espaces employé et administrateur refusent les mauvais rôles ;
- le panier recalcule les prix depuis MySQL ;
- une commande de test est créée avec son historique ;
- les frais de livraison sont cohérents ;
- les e-mails sont reçus ;
- les statistiques sont mises à jour ;
- aucun secret ou chemin local n’apparaît dans les pages ou journaux publics.

Les journaux du site peuvent être consultés depuis l’interface alwaysdata. Ils doivent être vérifiés après chaque nouvelle version afin de repérer les erreurs PHP, les problèmes de connexion aux bases ou les échecs d’envoi d’e-mails.

## 5.12 Sauvegarde

Sauvegarde MySQL :

```bash
mysqldump -u utilisateur -p vite_et_gourmand > sauvegarde.sql
```

Sauvegarde MongoDB :

```bash
mongodump --uri="<URI_MONGODB>" --db=vite_gourmand_nosql --out=sauvegarde-mongo
```

Les sauvegardes doivent être chiffrées, stockées hors du serveur principal et testées régulièrement par une restauration sur un environnement isolé.

## 5.13 Retour arrière

La stratégie de retour arrière doit conserver :

- le commit précédemment déployé ;
- une sauvegarde MySQL antérieure au déploiement ;
- une sauvegarde MongoDB ;
- les variables et configurations compatibles ;
- les assets de la version précédente.

En cas d’échec :

1. mettre l’application en maintenance si nécessaire ;
2. restaurer le commit précédent ;
3. réinstaller ses dépendances avec son `composer.lock` ;
4. restaurer les assets et le cache ;
5. restaurer la base uniquement si l’évolution de schéma l’exige ;
6. effectuer une recette rapide avant de rouvrir le service.

Une restauration de base est une opération sensible. Elle doit être testée et ne doit jamais être improvisée directement sur la production.

## 5.14 Reprise du déploiement et création d'un nouvel environnement de production

Lors de la première tentative de déploiement sur alwaysdata, l'hébergement a rencontré une saturation de l'espace disque disponible. Une partie importante de cet espace était occupée par le dépôt Git cloné sur le serveur, qui contenait l'ensemble de son historique de versions. Cette situation compliquait également les différentes tentatives de correction déjà effectuées sur l'environnement de production.

Au fil des essais, plusieurs modifications de configuration avaient également été réalisées (variables d'environnement, installation des dépendances Composer, configuration de MongoDB, extension PHP MongoDB, cache Symfony et configuration du serveur). L'accumulation de ces changements rendait l'environnement difficile à maintenir et à diagnostiquer.

Afin de repartir sur une base propre et de garantir un déploiement fiable, il a été décidé de recréer entièrement l'environnement de production plutôt que de poursuivre les corrections sur celui existant.

Pour cela, un nouveau projet nommé vite-gourmand-production a été créé à partir du projet d'origine. Un nouveau dépôt GitHub a été mis en place afin de disposer d'une version dédiée au déploiement. Un nouveau compte alwaysdata a également été créé afin de bénéficier d'un environnement vierge, sans les fichiers ni les configurations issus des premiers essais.

Les principales opérations réalisées ont été les suivantes :

- duplication du projet dans un nouveau dossier vite-gourmand-production ;
- création d'un nouveau dépôt GitHub destiné au déploiement ;
- création d'un nouveau compte alwaysdata ;
- création d'une nouvelle base de données MySQL ;
- import de la structure et des données de démonstration ;
- configuration des variables d'environnement ;
- installation des dépendances Composer ;
- compilation et installation de l'extension MongoDB compatible avec PHP 8.4 ;
- configuration du site alwaysdata afin d'utiliser le dossier public/ comme racine du site ;
- reconstruction du cache Symfony et vérification complète du fonctionnement de l'application.

Le dépôt GitHub d'origine a été conservé afin de préserver l'intégralité de l'historique de développement (branches, commits et évolution du projet). Le nouveau dépôt GitHub a uniquement été utilisé comme support de déploiement de la version finale sur alwaysdata.

Cette démarche a permis de repartir d'un environnement entièrement propre, de supprimer les configurations devenues obsolètes et d'obtenir un déploiement plus stable et plus facilement reproductible.

- Dépôt GitHub historique : [manonlrt261/Vite-gourmand](https://github.com/manonlrt261/Vite-gourmand)
- Dépôt Github de déploiement : [manonlrt261/Vite-gourmand-production](https://github.com/manonlrt261/Vite-gourmand-production)

## 6 Conclusion

Le déploiement de Vite & Gourmand sur alwaysdata rend l’application accessible en ligne à l’adresse <https://gourmandetvite.alwaysdata.net/>. La mise en production repose sur la configuration du site Symfony, l’installation des dépendances Composer, la connexion à la base MariaDB/MySQL, la préparation des assets et la sécurisation des variables d’environnement.

Cette mise en ligne démontre que l’application peut fonctionner en dehors de l’environnement local de développement. La procédure reste actuellement manuelle : les prochaines améliorations prioritaires consisteraient à créer des migrations versionnées, ajouter des tests automatisés et mettre en place un processus de déploiement reproductible. Une attention particulière doit également être portée à la disponibilité de MongoDB, aux sauvegardes et au contrôle des journaux après chaque nouvelle version.
