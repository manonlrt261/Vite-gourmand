# 1. Objectif de la mission

La présente documentation technique a pour objectif de décrire de manière précise l’application web **Vite & Gourmand**, réalisée par **Manon Fayolle** dans le cadre d’un projet d’examen en développement web. L’application répond aux besoins d’un traiteur en proposant un site public de présentation ainsi que plusieurs espaces de gestion adaptés aux différents profils d’utilisateurs.

L’étude du code confirme notamment la présence d’un catalogue de menus, d’un panier conservé en session, d’un processus de commande, d’un espace client, d’un espace employé, d’un espace administrateur et d’un formulaire de contact. Le projet comporte également des traitements liés aux frais de livraison, au suivi des commandes, aux avis clients, à la gestion des employés, aux statistiques administratives et à l’envoi d’e-mails transactionnels.

Cette documentation est destinée à deux publics principaux :

- le jury d’examen, auquel elle présente les choix techniques, l’architecture et les compétences mobilisées pendant la réalisation du projet ;
- tout développeur amené à reprendre, installer, comprendre, tester, maintenir ou déployer l’application.

Elle doit permettre à un développeur qui ne connaît pas encore le projet de :

- comprendre le contexte du projet et les besoins métier auxquels répond l’application ;
- identifier les technologies, bibliothèques et outils réellement utilisés ;
- comprendre l’architecture générale de l’application et les responsabilités de ses principaux composants ;
- installer les dépendances et préparer un environnement de développement compatible ;
- configurer l’application sans exposer les données sensibles ;
- initialiser et exploiter les bases de données relationnelle et non relationnelle ;
- comprendre l’organisation des dossiers, des contrôleurs, des templates, des services, des scripts et des ressources graphiques ;
- comprendre la structure des données, leurs relations et les principaux traitements réalisés par l’application ;
- identifier les profils utilisateur, leurs rôles et leurs autorisations ;
- comprendre les règles métier appliquées au catalogue, au panier, aux commandes, aux livraisons, aux avis et à l’administration ;
- connaître les mécanismes de validation et de sécurité effectivement présents dans le code ;
- lancer l’application et exécuter les tests disponibles ;
- diagnostiquer les erreurs courantes ;
- préparer un déploiement et assurer les opérations courantes de maintenance ;
- reprendre le projet de manière autonome, sans dépendre de son autrice.

Le document distinguera systématiquement les fonctionnalités confirmées par le code, les éléments uniquement annoncés dans le cahier des charges, les informations restant à confirmer et les recommandations d’évolution. Aucune fonctionnalité, technologie, règle métier, mesure de sécurité ou procédure ne sera présentée comme existante sans avoir été vérifiée dans les fichiers du projet.

L’objectif final est de fournir une référence technique fiable et maintenable, suffisamment détaillée pour faciliter la reprise du projet tout en démontrant les compétences techniques mises en œuvre dans **Vite & Gourmand**.

# 2. Analyse préalable obligatoire

La rédaction de cette documentation repose sur une analyse en lecture seule de l’état actuel du dépôt. Les résultats présentés dans cette section proviennent des fichiers réellement présents dans le projet au moment de l’analyse, réalisée le **17 juillet 2026**. Les déclarations du `README.md` ont été confrontées au code, aux configurations, aux scripts SQL et aux commandes de diagnostic disponibles.

Aucun fichier fonctionnel, paramètre d’environnement, schéma de base de données ou historique Git n’a été modifié pendant cette analyse. Les commandes exécutées ont été limitées à l’inventaire, à la lecture, au contrôle de syntaxe PHP et à l’inspection du routeur Symfony.

## 2.0 Analyse exhaustive obligatoire

### Périmètre parcouru

L’analyse a couvert récursivement la racine du projet ainsi que les dossiers suivants :

- `assets/` ;
- `bin/` ;
- `config/` et `config/packages/` ;
- `database/` ;
- `docs/` ;
- `migrations/` ;
- `public/` ;
- `src/` ;
- `templates/` ;
- `tests/` ;
- `translations/`.

Les fichiers situés à la racine ont également été examinés, notamment `README.md`, `composer.json`, `composer.lock`, `symfony.lock`, `importmap.php`, `phpunit.dist.xml`, `compose.yaml`, `compose.override.yaml`, `demarrer-site.bat`, `.editorconfig`, `.gitignore` et les différents fichiers `.env`.

Les répertoires de dépendances ou de données générées (`vendor/`, `node_modules/`, `var/` et `.git/`) ont été exclus de la lecture détaillée. Leur présence et leurs fichiers de verrouillage ont néanmoins été pris en compte pour identifier les dépendances, les versions et l’état du dépôt.

L’inventaire physique recense **595 fichiers** hors dépendances, cache et métadonnées internes de Git. Ce total comprend notamment **296 images PNG**, **92 fichiers `.DS_Store`**, **38 fichiers JavaScript**, **38 templates Twig**, **26 fichiers YAML**, **24 fichiers CSS**, **19 fichiers PHP**, **2 scripts SQL**, **2 fichiers Markdown** et plusieurs fichiers de configuration sans extension.

La différence avec les **202 fichiers** visibles par l’inventaire Git standard s’explique principalement par la présence de fichiers ignorés dans `public/assets/`, par les copies d’assets et par les métadonnées macOS. Les images binaires ont été contrôlées par leur présence, leur emplacement, leur taille et leur empreinte lorsque la comparaison entre les deux arborescences était nécessaire. Leur contenu textuel ne peut, par nature, pas être analysé comme du code.

### Résultat général

Le projet est une application Symfony fonctionnant majoritairement avec des contrôleurs et des requêtes SQL écrites directement avec **Doctrine DBAL**. Malgré la configuration de Doctrine ORM, aucun modèle objet Doctrine n’est présent : les dossiers `src/Entity/` et `src/Repository/` ne contiennent que leur fichier `.gitignore`. Le dossier `migrations/` ne contient également aucune migration PHP.

Le modèle relationnel est fourni par deux scripts SQL MySQL :

- `database/01_creation_base.sql`, qui définit le schéma ;
- `database/02_insertion_donnees.sql`, qui fournit les rôles, les statuts, les menus et les données de démonstration.

L’application utilise en parallèle MongoDB pour produire et lire des statistiques par menu. Cette intégration est réalisée directement avec le pilote `mongodb/mongodb` dans `src/Service/MongoStatsService.php`, sans Doctrine MongoDB ODM et sans classe Document.

### Contrôles exécutés

Les contrôles de syntaxe réalisés avec PHP 8.4.12 sur les **12 fichiers PHP applicatifs de `src/`** n’ont signalé aucune erreur de syntaxe. La commande Symfony de diagnostic du routeur a chargé correctement l’application et confirmé **55 routes métier**, auxquelles s’ajoutent les routes techniques de développement du profiler.

Ce contrôle ne constitue pas un test fonctionnel complet. L’application n’a pas fait l’objet, dans cette phase, d’un parcours navigateur exhaustif ni d’une exécution contre les services MySQL, MongoDB, SMTP ou les API externes.

## 2.0.1 Inventaire technique obligatoire

### Technologies et versions détectées

| Élément | Version ou état confirmé | Source de vérification |
|---|---:|---|
| PHP | contrainte `>= 8.4` ; environnement local détecté : **8.4.12** | `composer.json`, exécutable Laragon |
| Symfony FrameworkBundle | **8.1.0** | `composer.lock` |
| Doctrine DBAL | **4.4.3** | `composer.lock` |
| Doctrine ORM | **3.6.7**, installé mais sans entité applicative | `composer.lock`, `src/Entity/` |
| DoctrineBundle | **3.2.3** | `composer.lock` |
| Doctrine Migrations Bundle | **4.0.0**, sans migration applicative | `composer.lock`, `migrations/` |
| Twig | **3.27.1** | `composer.lock` |
| MongoDB PHP Library | **2.3.0** | `composer.lock` |
| Symfony AssetMapper | **8.1.0** | `composer.lock`, `importmap.php` |
| Symfony StimulusBundle | **3.1.0** | `composer.lock` |
| Symfony UX Turbo | **3.1.0** | `composer.lock` |
| Hotwired Stimulus | **3.2.2** | `importmap.php` |
| Hotwired Turbo | **8.0.23** | `importmap.php` |
| PHPUnit | **13.2.0** | `composer.lock` |
| MySQL | utilisé par les scripts SQL et l’URL locale ; version serveur non imposée dans la configuration Doctrine | `.env.local`, `database/*.sql`, `config/packages/doctrine.yaml` |
| MongoDB | utilisé ; version serveur non précisée | `.env.local`, `MongoStatsService.php` |

Aucun `package.json`, verrou npm, Yarn ou pnpm n’est présent. Le projet n’emploie donc pas de chaîne de compilation Node.js détectable. Le chargement front-end repose sur AssetMapper et Importmap.

### Bundles Symfony enregistrés

Le fichier `config/bundles.php` enregistre les bundles suivants : FrameworkBundle, DoctrineBundle, DoctrineMigrationsBundle, DebugBundle, TwigBundle, WebProfilerBundle, StimulusBundle, TurboBundle, TwigExtraBundle, SecurityBundle, MonologBundle et MakerBundle. Les bundles de débogage, de profiler et de génération de code sont limités aux environnements appropriés.

### Dépendances Composer principales

Les dépendances fonctionnelles principales sont les composants Symfony 8.1 liés au framework, au routage, aux formulaires, à la validation, à la sécurité, au mailer, au cache, au processus, à Messenger, au Serializer et à AssetMapper. S’y ajoutent Doctrine DBAL/ORM, Twig, le pilote MongoDB, Monolog, Stimulus et Turbo.

Les dépendances de développement comprennent PHPUnit, BrowserKit, CssSelector, DebugBundle, MakerBundle, Stopwatch et WebProfilerBundle. PHPStan lui-même n’est pas installé ; seuls `phpstan/phpdoc-parser` et des bibliothèques de réflexion sont présents comme dépendances techniques.

### Variables d’environnement détectées

Les noms suivants ont été relevés sans recopier leurs valeurs sensibles :

- `APP_ENV` ;
- `APP_SECRET` ;
- `APP_SHARE_DIR` ;
- `APP_URL` ;
- `DEFAULT_URI` ;
- `DATABASE_URL` ;
- `MONGODB_URI` ;
- `MONGODB_DATABASE` ;
- `MESSENGER_TRANSPORT_DSN` ;
- `MAILER_DSN` ;
- `MAILER_FROM` ;
- `ADMIN_EMAIL` ;
- `KERNEL_CLASS`, pour l’environnement de test.

Le fichier `.env.local` est bien exclu par `.gitignore`. Aucune valeur secrète n’est reproduite dans cette documentation.

### Composants applicatifs détectés

| Type | Éléments confirmés |
|---|---|
| Contrôleurs | `HomeController`, `MenuController`, `CartController`, `AuthController`, `CustomerController`, `EmployeeController`, `AdminController`, `ContactController` |
| Services | `MongoStatsService` |
| Extension Twig | `ScheduleExtension`, qui expose les horaires d’ouverture aux vues |
| Validateur utilitaire | `InputValidator` |
| Entités Doctrine | aucune |
| Documents MongoDB | aucune classe Document ; documents manipulés sous forme de tableaux par `MongoStatsService` |
| Repositories | aucun |
| Form types Symfony | aucun ; formulaires HTML/Twig traités manuellement dans les contrôleurs |
| Validators Symfony personnalisés | aucun validateur basé sur une contrainte Symfony ; `InputValidator` est un utilitaire statique |
| Voters | aucun |
| DTO | aucun |
| Enums | aucune |
| Interfaces applicatives | aucune |
| Traits applicatifs | aucun |
| Listeners et subscribers | aucun |
| Commandes Symfony applicatives | aucune |
| Migrations | aucune migration PHP |
| Fixtures Symfony | aucune |
| Scripts de données | deux scripts SQL MySQL |
| Templates Twig | 38 fichiers, dont un layout principal, trois composants et trois partials |
| Routes métier | 55 routes confirmées par `debug:router` |

### Domaines fonctionnels détectés dans le code

Le code confirme les fonctionnalités suivantes : pages publiques, catalogue et détail des menus, filtrage côté navigateur, panier en session, création et modification de commandes, calcul de livraison, authentification et inscription manuelles, réinitialisation de mot de passe, espace client, gestion du profil, avis, espaces employé et administrateur, gestion des menus et plats, horaires, fermetures exceptionnelles, messagerie de contact, employés, statistiques par menu et envoi d’e-mails.

Les rôles stockés en base sont `utilisateur`, `employe` et `administrateur`. Le visiteur correspond à une personne sans session authentifiée ; il ne s’agit pas d’un rôle enregistré.

### Mécanismes de sécurité détectés

Les mots de passe nouvellement créés sont hachés avec `password_hash(..., PASSWORD_DEFAULT)` et vérifiés avec `password_verify`. Des jetons CSRF distincts protègent les actions d’authentification, de panier, de client, d’employé, d’administrateur et de contact. Les requêtes DBAL utilisent majoritairement des paramètres liés. Les contrôleurs vérifient manuellement la session, le rôle et l’état actif de l’utilisateur avant l’accès aux espaces protégés.

Une compatibilité avec d’anciens mots de passe non hachés subsiste toutefois dans `AuthController` et `CustomerController` au moyen d’un repli sur `hash_equals`. Cette tolérance constitue une dette de sécurité à supprimer après migration de tous les mots de passe.

### Tests et outils de qualité

PHPUnit et les composants Symfony de test sont installés et `phpunit.dist.xml` est configuré. Cependant, `tests/` ne contient que `tests/bootstrap.php` : aucun test unitaire, fonctionnel, d’intégration ou de sécurité n’est présent.

Le dépôt ne contient aucune configuration PHPStan, PHP CS Fixer, Psalm, ESLint, Prettier, outil de couverture ou pipeline d’intégration continue. `.editorconfig` impose néanmoins UTF-8, les fins de ligne LF, une indentation de quatre espaces et la suppression des espaces de fin de ligne, sauf en Markdown.

### Éléments de déploiement détectés

Le projet contient un script local Windows `demarrer-site.bat` qui lance le serveur PHP intégré sur `127.0.0.1:8005` avec un chemin Laragon fixe. Les fichiers `compose.yaml` et `compose.override.yaml`, générés par les recettes Symfony, décrivent PostgreSQL et Mailpit. Ils ne correspondent pas entièrement à l’environnement réellement documenté et utilisé, qui repose sur MySQL, MongoDB et un SMTP externe.

Aucun Dockerfile applicatif, script de production, configuration Apache/Nginx, pipeline CI/CD ou procédure automatisée de déploiement n’a été détecté.

### Éléments inutilisés, incomplets ou incohérents

- Doctrine ORM et Doctrine Migrations sont installés et configurés, mais aucune entité, aucun repository et aucune migration ne sont présents.
- Symfony Form et Validator sont installés, mais les formulaires et validations métier sont principalement réalisés manuellement.
- Symfony SecurityBundle est installé, mais son provider en mémoire est vide et aucun mécanisme `form_login` ni `access_control` actif ne protège les espaces métier.
- Messenger est configuré pour Doctrine, mais aucun message applicatif ni worker métier n’a été détecté.
- Turbo et Stimulus sont installés, mais le JavaScript métier repose surtout sur des scripts DOM classiques et `fetch`.
- `compose.yaml` configure PostgreSQL alors que l’application et les scripts utilisent MySQL.
- Les assets existent à la fois dans `assets/` et `public/assets/`. Parmi les fichiers ayant un correspondant, 72 sont identiques et 29 diffèrent ; le répertoire public peut donc contenir des versions obsolètes par rapport aux sources.
- `public/assets/scripts/customer-account.js` n’a pas de source correspondante dans `assets/scripts/`.
- De nombreux fichiers macOS `.DS_Store` et `._*` sont physiquement présents dans les dossiers d’images, malgré leur exclusion actuelle dans `.gitignore`.
- Les contrôleurs `CartController` et surtout `EmployeeController` concentrent un grand nombre de responsabilités, de requêtes SQL et de règles métier.
- Plusieurs méthodes `ensure*` exécutent des créations ou altérations de tables à l’exécution. Le schéma peut donc évoluer pendant une requête HTTP au lieu d’être géré uniquement par une migration ou un script d’installation versionné.
- Le fichier `README.md` est modifié localement avant l’intervention de documentation ; cette modification préexistante n’a pas été altérée.

## 2.0.2 Règles de vérification

Chaque affirmation de cette documentation est classée selon son niveau de preuve :

- une information observée dans le code, la configuration, les scripts SQL ou la sortie d’une commande de diagnostic est considérée comme **confirmée** ;
- une information uniquement présente dans le sujet ou le README, sans implémentation correspondante, sera accompagnée de la mention : « Prévue dans le cahier des charges, mais non implémentée dans la version actuelle. » ;
- une information plausible, mais non vérifiable dans le dépôt, sera indiquée sous la forme `[À CONFIRMER : …]` ;
- une information indispensable et absente sera indiquée sous la forme `[À COMPLÉTER : …]` ;
- une procédure qui nécessite un navigateur, une infrastructure ou un service externe sera indiquée sous la forme `[À VÉRIFIER MANUELLEMENT : …]`.

Le README est utilisé comme source d’orientation, mais non comme preuve suffisante. Par exemple, il déclare l’utilisation de MySQL, MongoDB et Brevo ; MySQL et MongoDB sont confirmés par les scripts et le code, tandis que le choix effectif du fournisseur SMTP dépend de la valeur locale de `MAILER_DSN` et devra rester `[À CONFIRMER]` dans la documentation publique.

## 2.1 Fichiers généraux

La racine correspond à un projet Symfony géré avec Composer. `composer.json` impose PHP 8.4 ou supérieur et Symfony 8.1. `composer.lock` verrouille les versions installées. `symfony.lock` consigne les recettes Symfony appliquées.

Le projet ne contient pas de `package.json` : aucun gestionnaire de dépendances JavaScript Node.js n’est requis par l’état actuel du dépôt. `importmap.php` référence l’entrée `assets/app.js`, Stimulus 3.2.2 et Turbo 8.0.23.

Les fichiers `.env`, `.env.dev`, `.env.local` et `.env.test` couvrent les environnements générique, développement local et test. Leurs noms de variables ont été inventoriés sans divulguer les valeurs. `.gitignore` protège notamment `.env.local`, `vendor/`, `var/`, `public/assets/`, les caches PHPUnit et les métadonnées macOS.

Le README décrit l’installation locale sous Laragon, le lancement avec le serveur PHP intégré, MySQL, MongoDB et l’envoi d’e-mails. Certaines indications ne sont cependant plus parfaitement alignées avec l’arborescence : il situe les CSS et JavaScript dans `public/assets/`, alors que la source gérée par AssetMapper se trouve dans `assets/`.

## 2.2 Configuration Symfony

Les services applicatifs sont autowirés et autoconfigurés par `config/services.yaml`. Toutes les classes sous `src/` sont enregistrées comme services, sauf comportement contraire du conteneur.

Doctrine DBAL lit `DATABASE_URL`. Doctrine ORM est activé avec un mapping par attributs vers `src/Entity`, mais ce dossier ne contient aucune entité. Les caches de requêtes et de résultats Doctrine sont configurés en production.

Les sessions Symfony sont activées. En test, elles utilisent un stockage de fichiers simulé. Le mailer lit `MAILER_DSN`. Messenger définit des transports `async` et `failed` fondés sur Doctrine, sans message applicatif détecté.

Monolog écrit les journaux de développement dans `var/log`, utilise un gestionnaire `fingers_crossed` en test et envoie des journaux JSON vers `stderr` en production. Le profiler et la barre d’outils sont actifs en développement.

AssetMapper expose `assets/` et utilise un mode strict pour les imports manquants hors production. La protection CSRF sans état est configurée pour `submit`, `authenticate` et `logout`, avec vérification possible par en-tête pour Turbo.

La locale par défaut Symfony est actuellement `en`, alors que l’application est rédigée en français. Aucun catalogue de traduction n’est présent dans `translations/`.

## 2.3 Code source

Le dossier `src/` contient huit contrôleurs, un service MongoDB, une extension Twig, un validateur utilitaire et le noyau de l’application.

`HomeController` affiche la page d’accueil. `MenuController` charge les menus actifs et leurs plats associés. `CartController` gère le panier, les quantités, la validation des commandes, les frais de livraison, la transaction d’enregistrement et la confirmation par e-mail. `AuthController` prend en charge la connexion, l’inscription, la déconnexion et la réinitialisation du mot de passe.

`CustomerController` gère le tableau de bord client, les commandes, les modifications de commande, l’annulation, le profil et les avis. `EmployeeController` centralise les commandes, menus, plats, horaires, avis, messages et notifications. `AdminController` gère le tableau de bord, les employés et les statistiques. `ContactController` enregistre les demandes publiques et tente d’envoyer leur copie par e-mail.

`MongoStatsService` agrège les commandes depuis MySQL, écrit les documents statistiques dans MongoDB et normalise leur lecture. `ScheduleExtension` fournit à Twig les horaires d’ouverture issus de la base. `InputValidator` centralise plusieurs contrôles de format et de longueur.

Aucune architecture Entity/Repository classique n’est réellement mise en œuvre. Les requêtes SQL et une part importante des règles métier résident directement dans les contrôleurs. Les méthodes d’envoi d’e-mails sont également répétées dans plusieurs contrôleurs au lieu d’être regroupées dans un service dédié.

## 2.4 Interface utilisateur

Le dossier `templates/` contient 38 templates Twig organisés par domaine : administration, authentification, panier, contact, client, employé, accueil et menus. `templates/base.html.twig` constitue le layout principal. Les composants réutilisables sont `_big_title.html.twig`, `_menu_card.html.twig` et `_workspace_back_link.html.twig`. Les partials couvrent les en-têtes et pieds de page publics ou employés.

Les styles sources sont répartis dans 12 fichiers CSS sous `assets/styles/`. Les 20 fichiers JavaScript sources comprennent l’entrée AssetMapper, l’amorçage Stimulus, un contrôleur CSRF et 17 scripts métier ou d’interface. Ils gèrent notamment les filtres, le panier asynchrone, les formulaires, les tableaux de bord, les avis, la messagerie et le remplissage automatique des villes.

Le script `postal-city-autofill.js` appelle l’API publique `geo.api.gouv.fr`. Le calcul de distance routière du panier appelle également un service cartographique externe depuis le code PHP, avec une solution de repli locale lorsque le service ne répond pas.

Les ressources graphiques couvrent les visuels de l’entreprise et 17 ensembles de menus. Leur duplication entre `assets/` et `public/assets/`, ainsi que les divergences de contenu détectées pour 29 fichiers CSS/JS correspondants, rendent ambiguë la source réellement à maintenir.

## 2.5 Base de données

La base relationnelle réelle est décrite par `database/01_creation_base.sql`. Elle contient 18 tables :

- `avis` ;
- `avis_email_log` ;
- `avis_email_queue` ;
- `commande_menus` ;
- `commandes` ;
- `contact` ;
- `dessert` ;
- `entree` ;
- `fermetures_exceptionnelles` ;
- `historique_statuts_commande` ;
- `horaires_ouverture` ;
- `materiel_email_log` ;
- `menus` ;
- `password_reset_tokens` ;
- `plat` ;
- `roles` ;
- `statuts_commande` ;
- `utilisateurs`.

Les relations principales relient les utilisateurs aux rôles et aux commandes, les commandes aux statuts et à leur historique, les commandes aux menus via `commande_menus`, les avis aux utilisateurs et commandes, et les entrées, plats et desserts à un menu. Les suppressions en cascade ou restrictions sont définies dans le script selon la nature de la relation.

Le script `database/02_insertion_donnees.sql` insère trois rôles et huit statuts de commande : `en_attente`, `acceptee`, `en_preparation`, `en_livraison`, `livree`, `en_attente_retour_materiel`, `terminee` et `annulee`. Il fournit également les menus et leurs éléments de repas.

Une incohérence de donnée est visible dans le menu « Saveurs de printemps » : sa date de début est enregistrée en 2027, mais sa date de fin en 2021. Cette donnée rend sa période de disponibilité invalide et devra être corrigée dans la source de données, sans correction automatique dans le cadre de cette documentation.

Le code contient par ailleurs plusieurs instructions `CREATE TABLE` et `ALTER TABLE` de compatibilité exécutées à la volée. Le schéma final d’une base déjà utilisée peut donc différer du seul script initial si certaines de ces méthodes ont été déclenchées.

## 2.6 Sécurité

`config/packages/security.yaml` contient la configuration Symfony générée par défaut : un provider en mémoire vide, un firewall principal paresseux et aucune règle `access_control` active. Les espaces client, employé et administrateur ne reposent donc pas sur l’utilisateur Symfony standard.

L’authentification métier est réalisée manuellement par `AuthController`, qui interroge la table `utilisateurs`, vérifie le mot de passe, contrôle le champ `actif` puis écrit `utilisateur_id` et les informations utilisateur dans la session. Les contrôleurs protégés vérifient ensuite la session et le rôle. Les ressources client sont filtrées par `utilisateur_id`, ce qui met en œuvre un contrôle de propriété au niveau SQL.

Les opérations de modification utilisent des jetons CSRF et les routes sensibles déclarent généralement la méthode POST. Les données sont validées côté serveur par des contrôles de présence, format, longueur, date, heure, téléphone, code postal et force du mot de passe. Les valeurs SQL sont majoritairement transmises comme paramètres DBAL, ce qui réduit le risque d’injection SQL.

Twig applique son échappement HTML par défaut. Les e-mails HTML construits manuellement utilisent `htmlspecialchars` pour les données utilisateur dans les cas observés. Aucune fonctionnalité générale de téléversement de fichiers n’a été détectée : les images de menus sont sélectionnées par chemin parmi les ressources existantes.

Points de vigilance confirmés : compatibilité avec des mots de passe potentiellement stockés en clair, autorisation entièrement manuelle, absence de voters, absence de limitation de tentatives de connexion détectée, absence de politique active `access_control` et présence de secrets locaux dont la protection dépend du respect de `.gitignore`.

## 2.7 Tests

La configuration PHPUnit existe et définit une suite couvrant `tests/`, avec `tests/bootstrap.php` comme fichier d’amorçage. L’environnement de test utilise `APP_ENV=test`, une base suffixée `_test` et des coûts de hachage réduits.

Cependant, aucun fichier de test n’est présent. Il n’existe donc actuellement aucun test unitaire, fonctionnel, d’intégration, de contrôleur, de formulaire, de sécurité ou de règle métier vérifiable dans le dépôt.

Le contrôle de syntaxe des fichiers PHP a réussi et le routeur Symfony a pu être chargé. Ces diagnostics ne doivent pas être présentés comme une suite de tests applicatifs.

## 2.8 Déploiement

Le dépôt ne fournit pas de procédure de déploiement de production automatisée. Aucun Dockerfile applicatif, fichier Apache, configuration Nginx, workflow GitHub Actions, script CI/CD, configuration de worker ou tâche planifiée versionnée n’a été détecté.

Les fichiers Docker Compose présents proviennent des recettes Symfony. Ils configurent PostgreSQL 16 et Mailpit, alors que le fonctionnement documenté repose sur MySQL, MongoDB et un SMTP configurable. Ils ne constituent donc pas, en l’état, une infrastructure complète et cohérente pour l’application.

Le script `demarrer-site.bat` est spécifique à Windows et à un chemin Laragon local. Il convient au développement sur la machine prévue, mais il n’est pas portable et ne doit pas être utilisé comme procédure de production.

Les exigences de production suivantes restent absentes ou à formaliser : hébergement cible, serveur web, version serveur MySQL, version MongoDB, HTTPS, nom de domaine, permissions, sauvegardes, supervision, rotation des journaux, gestion des secrets, exécution éventuelle des workers Messenger et stratégie de retour arrière.

## 2.9 Historique Git

Le dépôt Git est disponible et se trouve actuellement sur la branche `develop`. La branche locale est en avance sur `origin/develop` d’au moins un commit visible. Les branches fonctionnelles locales et distantes couvrent l’authentification, la base MySQL, le contact, les espaces utilisateur, les avis, les employés, les horaires, les menus, MongoDB, le panier et les statistiques.

L’historique principal observé est composé des commits suivants, du plus récent au plus ancien :

- `4e7086f` — « Sauvegarde avant déploiement » ;
- `66176ce` — « database » ;
- `f709dcb` — « Matériel et avis client » ;
- `e44dec2` — « Sauvergarde de la première phase de test » ;
- `3cb6164` — « Modifications RGAA et sécurité » ;
- `7fac0cf` — « Ajout et configuration des emails transactionnels » ;
- `22f9eac` — « Sauvegarde des pages » ;
- `d65805f` — « Ajout MongoDB et finalisation du panier » ;
- `ddca5cd` — « Initialisation du projet Vite et Gourmand ».

Aucun tag Git n’est présent. Le dépôt distant `origin` pointe vers le dépôt GitHub du projet. Le nom de l’URL distante pourra être documenté ultérieurement, mais aucune information d’authentification n’a été exposée.

Au moment de l’analyse, `README.md` comportait déjà des modifications locales non validées. Le dossier `docs/`, créé pour la présente documentation, est également non suivi. Aucune branche, aucun commit, aucun tag et aucun fichier historique n’ont été modifiés pendant l’analyse.

# 3. Règles générales de sincérité

La documentation décrit l’état observé au commit local `4e7086f`, complété par les modifications non validées signalées par Git. Une fonctionnalité est considérée comme présente uniquement lorsqu’un traitement correspondant existe dans le code et peut être relié à une route, un template, une configuration ou une structure de données.

Les recommandations sont présentées comme des évolutions et non comme des protections existantes. Les résultats d’exécution sont limités aux contrôles réellement effectués : syntaxe PHP et chargement des routes. Aucune conformité RGAA, WCAG, sécurité ou couverture de tests n’est revendiquée.

# 4. Contexte du projet

**Vite & Gourmand** est une application web de traiteur développée dans le cadre d’un projet d’examen. Elle associe un site vitrine à des fonctions de vente et de gestion interne. Le public peut découvrir l’entreprise et ses menus ; un client inscrit peut préparer et suivre une commande ; un employé gère les contenus et l’activité ; un administrateur gère les employés et consulte les statistiques.

Les profils fonctionnels sont le visiteur, le client enregistré sous le rôle `utilisateur`, l’employé et l’administrateur. Les domaines confirmés sont les menus, les repas, le panier, les commandes, les livraisons, les comptes, les avis, les horaires, les messages de contact, les employés et les statistiques.

# 5. Format du document final

Le présent fichier est rédigé en Markdown UTF-8. Il peut être versionné avec le projet, consulté directement, copié dans un traitement de texte ou converti ultérieurement en PDF. Il ne contient aucun secret ni mot de passe réel.

# 6. Page de garde

## Documentation technique

| Information | Valeur |
|---|---|
| Projet | Vite & Gourmand |
| Autrice | Manon Fayolle |
| Année | 2026 |
| Version du document | 1.0 |
| Dernière mise à jour | 17 juillet 2026 |
| Version de l’application | commit local `4e7086f` sur `develop` |
| État du projet | application développée en local ; déploiement de production non vérifié |
| Formation | [À COMPLÉTER : nom de la formation] |
| Titre professionnel | [À COMPLÉTER : titre professionnel préparé] |
| Établissement | STARTUM CFA |
| Session d’examen | [À COMPLÉTER : session d’examen] |
| Dépôt GitHub | `https://github.com/manonlrt261/Vite-gourmand` |
| Application publiée | [À COMPLÉTER : URL de production] |

# 7. Historique des versions

| Version | Date | Autrice | Modifications |
|---|---|---|---|
| 1.0 | 17 juillet 2026 | Manon Fayolle | Création initiale de la documentation technique |

# 8. Sommaire

- [1. Objectif de la mission](#1-objectif-de-la-mission)
- [2. Analyse préalable obligatoire](#2-analyse-préalable-obligatoire)
- [3. Règles générales de sincérité](#3-règles-générales-de-sincérité)
- [4. Contexte du projet](#4-contexte-du-projet)
- [5. Format du document final](#5-format-du-document-final)
- [6. Page de garde](#6-page-de-garde)
- [7. Historique des versions](#7-historique-des-versions)
- [8. Sommaire](#8-sommaire)
- [9. Introduction](#9-introduction)
- [10. Présentation technique du projet](#10-présentation-technique-du-projet)
- [11. Choix technologiques](#11-choix-technologiques)
- [12. Architecture générale](#12-architecture-générale)
- [13. Arborescence du projet](#13-arborescence-du-projet)
- [14. Configuration de l’environnement](#14-configuration-de-lenvironnement)
- [15. Installation et lancement](#15-installation-et-lancement)
- [16. Routage](#16-routage)
- [17. Contrôleurs](#17-contrôleurs)
- [18. Modèle de données relationnel](#18-modèle-de-données-relationnel)
- [19. Migrations](#19-migrations)
- [20. Fixtures et données de démonstration](#20-fixtures-et-données-de-démonstration)
- [21. Base non relationnelle](#21-base-non-relationnelle)
- [22. Repositories et accès aux données](#22-repositories-et-accès-aux-données)
- [23. Services métiers](#23-services-métiers)
- [24. Formulaires](#24-formulaires)
- [25. Validation des données](#25-validation-des-données)
- [26. Authentification](#26-authentification)
- [27. Rôles et autorisations](#27-rôles-et-autorisations)
- [28. Sécurité](#28-sécurité)
- [29. Règles métier](#29-règles-métier)
- [30. Gestion des commandes](#30-gestion-des-commandes)
- [31. Gestion des menus et plats](#31-gestion-des-menus-et-plats)
- [32. Gestion des utilisateurs](#32-gestion-des-utilisateurs)
- [33. Gestion des avis](#33-gestion-des-avis)
- [34. Statistiques](#34-statistiques)
- [35. Templates Twig](#35-templates-twig)
- [36. CSS et charte graphique](#36-css-et-charte-graphique)
- [37. JavaScript](#37-javascript)
- [38. Gestion des assets](#38-gestion-des-assets)
- [39. Gestion des images et fichiers](#39-gestion-des-images-et-fichiers)
- [40. Gestion des erreurs](#40-gestion-des-erreurs)
- [41. Logs et débogage](#41-logs-et-débogage)
- [42. Cache](#42-cache)
- [43. Tests](#43-tests)
- [44. Qualité du code](#44-qualité-du-code)
- [45. Performances](#45-performances)
- [46. Accessibilité technique](#46-accessibilité-technique)
- [47. Responsive et compatibilité](#47-responsive-et-compatibilité)
- [48. API et échanges asynchrones](#48-api-et-échanges-asynchrones)
- [49. Services externes](#49-services-externes)
- [50. Déploiement](#50-déploiement)
- [51. Sauvegarde et restauration](#51-sauvegarde-et-restauration)
- [52. Maintenance](#52-maintenance)
- [53. Diagnostic des problèmes courants](#53-diagnostic-des-problèmes-courants)
- [54. Commandes utiles](#54-commandes-utiles)
- [55. Diagrammes obligatoires](#55-diagrammes-obligatoires)
- [56. Captures à intégrer](#56-captures-à-intégrer)
- [57. Limitations techniques connues](#57-limitations-techniques-connues)
- [58. Améliorations techniques](#58-améliorations-techniques)
- [59. Conclusion technique](#59-conclusion-technique)
- [60. Annexes](#60-annexes)
- [61. Dictionnaire de données](#61-dictionnaire-de-données)
- [62. Conventions de développement](#62-conventions-de-développement)
- [63. Règles de rédaction appliquées](#63-règles-de-rédaction-appliquées)
- [64. Extraits de code représentatifs](#64-extraits-de-code-représentatifs)
- [65. Synthèse de l’analyse initiale](#65-synthèse-de-lanalyse-initiale)
- [66. Contrôle final](#66-contrôle-final)
- [67. Compte rendu final](#67-compte-rendu-final)
- [68. Restrictions de modification](#68-restrictions-de-modification)

# 9. Introduction

Cette documentation décrit la conception et l’implémentation de Vite & Gourmand. Elle s’adresse au jury, à l’autrice et à un futur développeur chargé de reprendre le projet. Elle couvre l’environnement, l’architecture, les données, la sécurité, les fonctionnalités, l’interface, les tests, la maintenance et le déploiement.

Le `README.md` fournit surtout une installation rapide et une présentation générale. Le manuel utilisateur, lorsqu’il sera disponible, devra expliquer les parcours fonctionnels. La documentation de gestion de projet décrit l’organisation du travail. Le présent document se concentre sur les décisions techniques et le fonctionnement interne.

Ses limites sont celles de l’état du dépôt : aucun environnement de production n’a été fourni, aucun test automatisé applicatif n’existe et les services externes n’ont pas été sollicités pendant la rédaction. Les procédures dépendant de ces éléments sont donc signalées comme manuelles ou à confirmer.

# 10. Présentation technique du projet

## 10.1 Finalité de l’application

L’application permet à un traiteur de présenter son offre, convertir un visiteur en client, enregistrer des commandes et donner aux équipes des outils de gestion. Elle centralise les menus, composants de repas, commandes, coordonnées de livraison, avis, messages et horaires.

## 10.2 Utilisateurs

| Profil | Description technique |
|---|---|
| Visiteur | aucune session ; accès à l’accueil, aux menus, au contact, à l’inscription et à la connexion |
| Client | rôle SQL `utilisateur` ; accès au panier validé, au profil, aux commandes et aux avis |
| Employé | rôle SQL `employe` ; gestion opérationnelle des commandes, contenus, horaires, avis et messages |
| Administrateur | rôle SQL `administrateur` ; gestion des employés et statistiques, avec accès aux espaces internes |

## 10.3 Principaux domaines fonctionnels

- contenu public et présentation de l’entreprise ;
- catalogue de menus et repas ;
- authentification et comptes ;
- panier et commandes ;
- livraison et prêt de matériel ;
- espace client ;
- espace employé ;
- administration et employés ;
- avis ;
- horaires et fermetures ;
- contact et messagerie ;
- statistiques MySQL/MongoDB ;
- e-mails transactionnels.

## 10.4 État fonctionnel

| Domaine | Prévu | Présent dans le code | État technique |
|---|---|---|---|
| Authentification | inscription, connexion, réinitialisation | traitements complets manuels, session et e-mails | présent, mais hors SecurityBundle standard |
| Menus | catalogue, détail, filtres, gestion | routes publiques et gestion employé | présent |
| Panier | ajout, quantité, suppression | session, CSRF, réponses HTML/JSON | présent |
| Commandes | création et suivi | création transactionnelle, historique, modification, annulation | présent |
| Livraisons | calcul des frais | géocodage, OSRM et repli estimatif | présent, dépend de services externes |
| Client | profil, commandes, avis | espace dédié et contrôle par propriétaire | présent |
| Employé | contenus et exploitation | contrôleur et vues dédiés | présent, contrôleur très volumineux |
| Administration | employés et statistiques | gestion CRUD et agrégations | présent |
| MongoDB | statistiques | service et collection par menu | présent, sans ODM |
| Tests | validation automatisée | configuration uniquement | absent |
| Déploiement | mise en production | script local et Compose incohérent | partiel |

# 11. Choix technologiques

## 11.1 PHP

Le projet exige PHP `>= 8.4`. L’environnement local détecté utilise PHP 8.4.12. PHP exécute les contrôleurs, règles métier, accès DBAL, sessions, e-mails et intégrations externes. Les extensions indispensables explicitement déclarées sont `ctype` et `iconv`; les extensions requises indirectement par Symfony, MySQL et MongoDB doivent être validées sur le serveur cible.

## 11.2 Symfony

Symfony 8.1.0 fournit le noyau HTTP, le conteneur de services, le routage, Twig, les sessions, le mailer, les journaux et AssetMapper. Le cycle est classique : `public/index.php` initialise le runtime, le routeur choisit un contrôleur, les dépendances sont injectées, puis une réponse HTML, JSON ou redirection est retournée.

Le projet utilise l’injection de `Doctrine\DBAL\Connection`, `MailerInterface` et `MongoStatsService`. Les formulaires et l’authentification ne suivent cependant pas les abstractions Symfony Form et Security habituelles.

## 11.3 Twig

Twig 3.27.1 génère l’interface. `base.html.twig` définit les blocs communs ; les vues étendent ce layout et incluent des composants. L’échappement automatique protège les sorties ordinaires. Les conditions basées sur la session adaptent la navigation aux rôles.

## 11.4 Doctrine ORM et DBAL

Doctrine ORM 3.6.7 est installé, mais non utilisé par des entités. L’accès réel repose sur DBAL 4.4.3 : requêtes SQL, `fetchAssociative`, `fetchAllAssociative`, `insert`, `update` et transactions. Ce choix donne un contrôle direct, mais concentre le SQL dans les contrôleurs et prive l’application du modèle objet, des repositories et des migrations ORM.

## 11.5 Base relationnelle

Le dump est issu de MySQL 8.0.46 et utilise InnoDB/utf8mb4. MySQL stocke toutes les données métier et garantit les relations principales avec des clés étrangères. Les montants utilisent `DECIMAL(10,2)`.

## 11.6 Base non relationnelle

MongoDB stocke des documents de statistiques agrégées par menu. La bibliothèque PHP officielle est utilisée directement. MongoDB n’est pas la source de vérité : les commandes restent dans MySQL et les statistiques sont recalculées à partir de celles-ci.

## 11.7 Front-end

HTML5, CSS et JavaScript natif composent l’interface. AssetMapper et Importmap remplacent une compilation Node. Stimulus et Turbo sont installés, mais les comportements métier reposent essentiellement sur des scripts classiques, des attributs `data-*` et `fetch`.

## 11.8 Outils de développement

Composer gère PHP. Laragon et le serveur PHP intégré sont utilisés localement. Git/GitHub assurent le versionnement. PHPUnit est installé mais sans tests. MySQL et MongoDB Compass sont mentionnés dans le README ; seul leur rôle dans le projet est confirmé, pas l’obligation d’utiliser Compass.

## 11.9 Tableau récapitulatif

| Technologie | Version | Usage |
|---|---:|---|
| PHP | 8.4.12 local | exécution serveur |
| Symfony | 8.1.0 | framework web |
| Twig | 3.27.1 | vues |
| Doctrine DBAL | 4.4.3 | accès MySQL |
| MySQL | dump 8.0.46 | données métier |
| MongoDB Library | 2.3.0 | statistiques NoSQL |
| AssetMapper | 8.1.0 | assets sans Node |
| Stimulus/Turbo | 3.2.2 / 8.0.23 | infrastructure front-end |
| PHPUnit | 13.2.0 | tests, actuellement absents |

# 12. Architecture générale

## 12.1 Style d’architecture

Le projet suit une architecture MVC Symfony simplifiée : contrôleurs, templates Twig et base de données. La couche modèle n’est pas matérialisée par des entités ; les contrôleurs utilisent directement DBAL. Un service spécialisé existe pour MongoDB et une extension Twig pour les horaires.

## 12.2 Schéma global

```text
Navigateur
   │ HTTP / Fetch
   ▼
Routeur Symfony ──► Contrôleurs ──► Templates Twig / JSON
                         │
                         ├── Doctrine DBAL ──► MySQL
                         ├── MongoStatsService ──► MongoDB
                         ├── Symfony Mailer ──► SMTP
                         └── API Adresse / OSRM / API Découpage administratif
```

[DIAGRAMME À INSÉRER : architecture physique montrant navigateur, Symfony, MySQL, MongoDB, SMTP et API cartographiques]

## 12.3 Flux d’une requête

Une URL est associée à une méthode de contrôleur dans `config/routes.yaml`. Symfony injecte les dépendances. Le contrôleur vérifie la session, le rôle, le CSRF et les données selon le cas, interroge DBAL ou un service, puis rend Twig ou JSON. Les écritures complexes de commande utilisent une transaction.

## 12.4 Séparation des responsabilités

La séparation est correcte pour les vues et le service MongoDB, mais insuffisante pour les traitements métier. `EmployeeController` dépasse 2 000 lignes et `CartController` dépasse 1 200 lignes. L’accès SQL, la validation, les e-mails et les règles de domaine devraient être répartis entre repositories et services.

# 13. Arborescence du projet

```text
vite_gourmandtest/
├── assets/                 sources CSS, JavaScript et images
├── bin/                    console Symfony et lanceur PHPUnit
├── config/                 routes, services et packages Symfony
├── database/               création et insertion MySQL
├── docs/                   présente documentation
├── migrations/             vide hors .gitignore
├── public/                 point d’entrée et copies publiques d’assets
├── src/
│   ├── Controller/         huit contrôleurs
│   ├── Entity/             aucune entité
│   ├── Repository/         aucun repository
│   ├── Service/            MongoStatsService
│   ├── Twig/               ScheduleExtension
│   └── Validator/          InputValidator
├── templates/              38 templates Twig
├── tests/                  bootstrap uniquement
├── translations/           aucun catalogue
├── composer.json
├── importmap.php
└── README.md
```

[CAPTURE À INSÉRER : arborescence du projet en masquant le chemin personnel Windows]

# 14. Configuration de l’environnement

## 14.1 Prérequis

- PHP 8.4 avec les extensions exigées par Symfony, PDO MySQL et MongoDB ;
- Composer ;
- MySQL 8 compatible avec le dump ;
- MongoDB ;
- accès à un SMTP pour les e-mails réels ;
- Git ;
- navigateur moderne.

Node.js n’est pas requis dans l’état actuel.

## 14.2 Installation sous Windows

Le projet a été développé avec Laragon. Le script `demarrer-site.bat` utilise le chemin fixe `C:\laragon\bin\php\php-8.4.12-nts-Win32-vs17-x64\php.exe`. Si PHP est installé ailleurs, il faut adapter la commande de lancement, pas nécessairement le script versionné.

## 14.3 Installation des dépendances

```bash
composer install
```

Cette commande installe exactement les versions de `composer.lock` et exécute les auto-scripts Symfony.

## 14.4 Configuration des variables d’environnement

Les variables principales sont `DATABASE_URL`, `MONGODB_URI`, `MONGODB_DATABASE`, `MAILER_DSN`, `MAILER_FROM`, `ADMIN_EMAIL`, `APP_URL`, `DEFAULT_URI` et `APP_SECRET`. Les secrets ne doivent pas être placés dans un fichier suivi par Git.

## 14.5 Création du fichier local

Créer `.env.local` à partir des noms documentés, avec des valeurs propres à la machine. Le fichier est ignoré par Git. Vérifier cette exclusion avant toute publication.

# 15. Installation et lancement

## 15.1 Cloner le dépôt

```bash
git clone https://github.com/manonlrt261/Vite-gourmand.git
cd Vite-gourmand
```

## 15.2 Installer les dépendances

```bash
composer install
```

## 15.3 Configurer l’environnement

Renseigner `.env.local` sans recopier de secret dans la documentation. Démarrer MySQL et MongoDB.

## 15.4 Créer la base

La base est fournie sous forme SQL, pas sous forme d’entités Doctrine. Créer une base `vite_et_gourmand`, puis importer `database/01_creation_base.sql`.

```bash
mysql -u utilisateur -p vite_et_gourmand < database/01_creation_base.sql
```

## 15.5 Exécuter les migrations

Il n’existe aucune migration applicative. La commande `doctrine:migrations:migrate` n’initialise donc pas le schéma actuel. **Ne pas la présenter comme méthode principale d’installation tant que des migrations n’ont pas été créées.**

## 15.6 Charger les données de démonstration

```bash
mysql -u utilisateur -p vite_et_gourmand < database/02_insertion_donnees.sql
```

Cette commande modifie la base et doit être réservée à une base vide ou de démonstration. Elle n’a pas été exécutée pendant la rédaction.

## 15.7 Préparer les assets

```bash
php bin/console asset-map:compile
```

En développement, AssetMapper peut servir les sources sans compilation. En production, la compilation doit créer les fichiers publics versionnés.

## 15.8 Lancer l’application

```bash
php -S 127.0.0.1:8005 -t public public/router.php
```

Sous l’environnement Laragon détecté :

```powershell
& "C:\laragon\bin\php\php-8.4.12-nts-Win32-vs17-x64\php.exe" -S 127.0.0.1:8005 -t public public/router.php
```

## 15.9 Vérifier l’installation

```bash
php bin/console about
php bin/console debug:router
```

[À VÉRIFIER MANUELLEMENT : ouvrir l’accueil, consulter un menu, créer un compte, se connecter et tester une commande sur une base de démonstration.]

# 16. Routage

## 16.1 Organisation des routes

Les 55 routes métier sont définies en YAML dans `config/routes.yaml`. Les routes techniques de profiler ne sont actives qu’en développement. Les identifiants sont contraints par `\d+` et la plupart des mutations imposent POST.

## 16.2 Principales routes

| Méthode | URL | Nom | Contrôleur |
|---|---|---|---|
| ANY | `/` | `home_show` | `HomeController::show` |
| ANY | `/menus` | `menu_index` | `MenuController::index` |
| ANY | `/menus/{id}` | `menu_show` | `MenuController::show` |
| ANY | `/connexion` | `login` | `AuthController::login` |
| ANY | `/inscription` | `register` | `AuthController::register` |
| ANY | `/mon-compte` | `customer_account` | `CustomerController::account` |
| ANY | `/panier` | `cart_index` | `CartController::index` |
| POST | `/panier/commander` | `cart_checkout` | `CartController::checkout` |
| ANY | `/espace-employe` | `employee_dashboard` | `EmployeeController::dashboard` |
| ANY | `/espace-administrateur` | `admin_dashboard` | `AdminController::dashboard` |

La liste exhaustive figure en annexe.

## 16.3 Convention de nommage

Les noms sont en anglais, en `snake_case`, préfixés par domaine (`customer_`, `employee_`, `admin_`, `cart_`). Les URL sont en français et en minuscules avec tirets. Cette convention est cohérente.

# 17. Contrôleurs

## 17.1 Organisation

Chaque contrôleur correspond à un espace fonctionnel. Les dépendances sont injectées dans les méthodes. Il n’existe pas de contrôleur API séparé ; les mêmes méthodes peuvent retourner HTML ou JSON selon la requête.

## 17.2 Analyse des contrôleurs

| Contrôleur | Responsabilités principales |
|---|---|
| `HomeController` | accueil |
| `MenuController` | catalogue, regroupement par thème, détail |
| `AuthController` | session, inscription, connexion, réinitialisation, e-mails |
| `CartController` | panier, livraison, commande, transaction, confirmation |
| `CustomerController` | profil, commandes, annulation, avis |
| `EmployeeController` | exploitation complète : commandes, contenus, horaires, avis, messages, e-mails |
| `AdminController` | employés, tableaux de bord, statistiques |
| `ContactController` | formulaire public, stockage et notification |

## 17.3 Bonnes pratiques et limites

Points positifs : typage des signatures, injection de dépendances, requêtes paramétrées, méthodes privées nommées, contrôles CSRF et transactions. Limites : contrôleurs très chargés, SQL dispersé, exceptions SMTP silencieuses et modifications de schéma à l’exécution.

# 18. Modèle de données relationnel

## 18.1 Vue d’ensemble

MySQL est la source de vérité. Le schéma contient 18 tables. Les tables cœur sont `utilisateurs`, `roles`, `menus`, `commandes`, `commande_menus`, `statuts_commande` et `historique_statuts_commande`.

## 18.2 Description des tables

| Table | Fonction |
|---|---|
| `utilisateurs` | comptes clients, employés et administrateurs |
| `roles` | profils applicatifs |
| `menus` | offre commerciale |
| `entree`, `plat`, `dessert` | composants des menus |
| `commandes` | en-tête, livraison, total et statut |
| `commande_menus` | lignes multi-menus d’une commande |
| `statuts_commande` | référentiel des statuts |
| `historique_statuts_commande` | traçabilité des changements |
| `avis` | notes et commentaires clients |
| `contact` | messagerie publique et interne |
| `horaires_ouverture` | horaires hebdomadaires |
| `fermetures_exceptionnelles` | indisponibilités datées |
| `password_reset_tokens` | réinitialisation sécurisée |
| tables `*_email_*` | files et journaux anti-doublon des notifications |

## 18.3 Relations

- un rôle possède plusieurs utilisateurs ;
- un utilisateur possède plusieurs commandes ;
- une commande possède plusieurs lignes dans `commande_menus` ;
- un menu peut apparaître dans plusieurs lignes de commande ;
- un menu possède zéro ou plusieurs entrées, plats et desserts ;
- une commande possède plusieurs événements d’historique ;
- une commande et un utilisateur peuvent être liés à un avis ;
- un utilisateur peut être lié à plusieurs messages de contact.

## 18.4 MCD

[DIAGRAMME À INSÉRER : MCD MySQL complet avec cardinalités issues des clés étrangères]

## 18.5 MLD

Le MLD correspond aux 18 tables du script `01_creation_base.sql`. Les clés primaires sont généralement des entiers auto-incrémentés ; `horaires_ouverture` utilise `jour_code` comme clé textuelle.

## 18.6 Diagramme de classes

Le projet ne possède aucune classe Entity. Un diagramme de classes Doctrine serait donc trompeur. Le diagramme à produire doit représenter le modèle relationnel ou, séparément, les dépendances entre contrôleurs et services.

## 18.7 Contraintes et intégrité

Les e-mails utilisateurs et codes de statut sont uniques. Les clés étrangères utilisent `CASCADE`, `RESTRICT` ou `SET NULL` selon le cas. Les scripts désactivent temporairement les contrôles de clés pendant l’import du dump.

## 18.8 Index

Les clés étrangères et colonnes de file d’e-mails disposent d’index. Aucun index composite spécialisé n’est visible pour les recherches fréquentes sur dates, statuts et utilisateurs des commandes ; une analyse de requêtes est recommandée avant ajout.

# 19. Migrations

Doctrine Migrations Bundle est installé, mais `migrations/` ne contient aucune classe de migration. Le schéma est initialisé par le dump SQL et parfois complété par des méthodes `ensure*` dans les contrôleurs. Cette pratique rend la version du schéma difficile à garantir.

Recommandation prioritaire : créer une migration de référence correspondant exactement au schéma validé, supprimer progressivement les `CREATE TABLE`/`ALTER TABLE` des requêtes HTTP et tester la migration sur une copie de la base.

# 20. Fixtures et données de démonstration

Il n’existe pas de fixtures Symfony. `database/02_insertion_donnees.sql` fournit les rôles, huit statuts, des menus, leurs composants, des horaires et des comptes ou données de démonstration selon le dump. Il ne doit être importé qu’après examen de son contenu et sur une base prévue à cet effet.

Les mots de passe et données personnelles de démonstration ne sont pas reproduits ici. [À VÉRIFIER MANUELLEMENT : renouveler les identifiants de tout compte importé avant une présentation publique.]

# 21. Base non relationnelle

## 21.1 Justification

MongoDB est utilisé pour stocker des données de synthèse destinées aux graphiques administratifs. Ce choix sépare les statistiques de lecture des données transactionnelles MySQL.

## 21.2 Données stockées

`MongoStatsService` utilise la base configurée par `MONGODB_DATABASE`, avec un repli `vite_gourmand_nosql`, et une collection dédiée aux commandes par menu. Les documents contiennent des informations normalisées sur le menu, son thème, le nombre de commandes et le chiffre d’affaires.

## 21.3 Structure des documents

```json
{
  "menu_id": 1,
  "nom_menu": "Menu Découverte",
  "theme": "Classique",
  "nombre_commandes": 3,
  "chiffre_affaires": 1080.00
}
```

Cet exemple illustre les clés construites par le service ; les valeurs sont fictives et ne constituent pas un résultat réel.

## 21.4 Configuration

La connexion lit `MONGODB_URI` et `MONGODB_DATABASE`. Aucun bundle ODM ni mapping n’est utilisé. Le client est instancié directement par le service.

## 21.5 Synchronisation

Les agrégats sont reconstruits depuis MySQL, puis remplacés ou réécrits dans MongoDB. Les annulations déclenchent une actualisation afin que les commandes annulées ne restent pas comptabilisées. MySQL reste la référence.

## 21.6 Limites

L’absence de mécanisme asynchrone, de transaction distribuée et de journal de synchronisation peut créer un décalage si MongoDB est indisponible. Le contrôleur administrateur contient en plus un repli sur fichier JSON, ce qui introduit une troisième représentation possible des statistiques.

# 22. Repositories et accès aux données

Aucun repository n’est présent. Les contrôleurs et `MongoStatsService` exécutent directement les requêtes DBAL. Les requêtes utilisent généralement des paramètres positionnels et `ArrayParameterType` pour les listes.

Cette approche est fonctionnelle, mais réduit la réutilisabilité et la testabilité. Des repositories par agrégat (`MenuRepository`, `OrderRepository`, `UserRepository`) permettraient d’isoler le SQL et de centraliser les filtres de propriété et de statut.

# 23. Services métiers

Le seul service métier explicite est `MongoStatsService`. Il dépend du pilote MongoDB et reçoit une connexion DBAL pour agréger les commandes. Les autres services sont implicites dans les contrôleurs : calcul de livraison, panier, commande, e-mails, authentification et gestion des contenus.

Services à extraire en priorité :

- `DeliveryPriceCalculator` ;
- `OrderService` et `OrderStatusService` ;
- `AuthenticationService` ;
- `TransactionalMailer` ;
- `MenuManagementService` ;
- repositories DBAL.

# 24. Formulaires

## 24.1 Organisation

Aucune classe sous `src/Form/` n’existe. Les formulaires sont écrits en HTML/Twig et leurs champs sont récupérés avec `$request->request`.

## 24.2 Description

Les principaux formulaires couvrent inscription, connexion, mot de passe oublié, profil, panier, commande, contact, employés, menus, repas, horaires, avis et messages. Chaque action sensible doit fournir un `_csrf_token` adapté au domaine.

L’absence de Form Types évite une couche supplémentaire, mais duplique la lecture, la normalisation et l’affichage des erreurs. Elle limite aussi l’usage automatique du composant Validator.

# 25. Validation des données

`InputValidator` contrôle notamment les codes postaux, téléphones, dates, heures, plages horaires, longueurs et thèmes de menu. Les contrôleurs ajoutent des règles contextuelles : e-mail valide, mot de passe fort, quantité minimale, prix et stock bornés, dates cohérentes et propriété des commandes.

Le mot de passe fort exige au moins 10 caractères, une majuscule, une minuscule, un chiffre et un caractère spécial. Les menus imposent au moins une personne et plafonnent les valeurs saisies à 500 personnes, 10 000 € par personne et 10 000 unités de stock dans la validation employé.

L’application n’utilise pas de contraintes Symfony personnalisées. Les messages et règles sont dispersés dans les contrôleurs.

# 26. Authentification

La connexion est gérée par `AuthController::login`. Elle recherche l’utilisateur par e-mail, vérifie qu’il est actif, contrôle le mot de passe, puis écrit l’identifiant, le profil et le rôle dans la session. La redirection dépend de `utilisateur`, `employe` ou `administrateur`.

L’inscription crée un client, hache son mot de passe et tente d’envoyer un e-mail de bienvenue. La réinitialisation crée un jeton aléatoire, n’enregistre que son empreinte SHA-256, fixe une expiration et marque le jeton utilisé après changement du mot de passe.

La déconnexion supprime les informations applicatives de session. Elle ne repose pas sur le mécanisme `form_login` de Symfony.

# 27. Rôles et autorisations

## 27.1 Rôles

| Identifiant SQL | Libellé | Usage |
|---:|---|---|
| 1 | `utilisateur` | client |
| 2 | `employe` | personnel opérationnel |
| 3 | `administrateur` | administration |

## 27.2 Hiérarchie

Il n’existe aucune `role_hierarchy` Symfony. L’administrateur est accepté dans certaines vérifications manuelles d’espace interne, mais cette relation n’est pas centralisée.

## 27.3 Matrice des autorisations

| Fonction | Visiteur | Client | Employé | Administrateur |
|---|:---:|:---:|:---:|:---:|
| Voir accueil et menus | Oui | Oui | Oui | Oui |
| Envoyer un contact | Oui | Oui | Oui | Oui |
| Gérer son panier | Oui | Oui | Oui | Oui |
| Valider une commande | Non | Oui | Non prévu | Non prévu |
| Voir ses commandes | Non | Oui | Non | Non |
| Gérer commandes et contenus | Non | Non | Oui | selon contrôle interne |
| Gérer les employés | Non | Non | Non | Oui |
| Voir les statistiques | Non | Non | Non | Oui |

## 27.4 Mécanismes utilisés

Les autorisations reposent sur la session, `role_id`, `role_libelle`, le champ `actif`, des redirections et des clauses SQL par propriétaire. Aucun voter ni attribut `IsGranted` n’est présent.

# 28. Sécurité

## 28.1 Hashage des mots de passe

Les nouveaux mots de passe utilisent `PASSWORD_DEFAULT`. Le repli compatible avec un mot de passe en clair doit être supprimé après migration.

## 28.2 Protection CSRF

Les formulaires sensibles utilisent `isCsrfTokenValid` avec des identifiants de domaine. Les jetons sont transmis dans les formulaires classiques et requêtes asynchrones.

## 28.3 Injections SQL

DBAL et les paramètres liés protègent les valeurs. Quelques noms de table ou fragments dynamiques sont construits depuis des listes fermées internes, pas directement depuis une entrée libre. Un audit systématique reste nécessaire.

## 28.4 XSS

Twig échappe les variables par défaut. Les chaînes intégrées aux e-mails HTML sont généralement passées dans `htmlspecialchars`. Toute utilisation future de `|raw` doit être auditée.

## 28.5 Validation serveur

Les contrôles JavaScript améliorent l’expérience, mais les contrôleurs répètent les validations critiques côté serveur.

## 28.6 Contrôle des accès

Les contrôles manuels fonctionnent au niveau des contrôleurs. L’absence d’`access_control` actif rend leur exhaustivité essentielle et augmente le risque d’oubli sur une nouvelle route.

## 28.7 Sessions

Le panier et l’utilisateur sont stockés en session Symfony. L’identifiant de session doit être renouvelé après connexion pour réduire le risque de fixation ; ce renouvellement explicite n’a pas été détecté.

## 28.8 Fichiers téléversés

Aucun téléversement utilisateur n’est implémenté. Les formulaires de contenu sélectionnent des chemins d’images existants.

## 28.9 Secrets

`.env.local` est ignoré par Git. `APP_SECRET`, les identifiants SQL, MongoDB et SMTP ne doivent jamais être commités ni affichés dans une capture.

## 28.10 Données personnelles

La base contient identité, coordonnées, adresse, date et lieu de naissance des employés, historique de commandes et messages. Aucune politique de durée de conservation, export, anonymisation ou suppression RGPD n’est implémentée de manière identifiable.

## 28.11 Utilisateurs désactivés

La connexion refuse les utilisateurs inactifs. Les espaces employé et administrateur revérifient l’utilisateur actif en base, ce qui limite la persistance d’un accès après désactivation.

## 28.12 Sécurité restant à améliorer

- migrer tous les mots de passe et supprimer le repli en clair ;
- adopter SecurityBundle, un provider DBAL ou une entité User et `access_control` ;
- régénérer la session à la connexion ;
- limiter les tentatives de connexion et de réinitialisation ;
- journaliser les événements sensibles ;
- ajouter des tests d’autorisation et de propriété ;
- formaliser HTTPS, cookies sécurisés et en-têtes HTTP en production.

# 29. Règles métier

| Règle | Implémentation observée | Fichier principal |
|---|---|---|
| seuls les menus actifs sont publics | filtre `actif = 1` | `MenuController.php` |
| quantité minimale | quantité ramenée au `personnes_minimum` | `CartController.php` |
| prix recalculé serveur | prix et stock relus en base | `CartController.php` |
| frais de livraison | base 5 € + 0,59 €/km | `CartController.php` |
| origine de livraison | coordonnées de Bordeaux | `CartController.php` |
| délai de commande | extrait du texte `conditions` et comparé à la date | `CartController.php` |
| prêt de matériel | accepté seulement si au moins un menu le propose | `CartController.php` |
| commande transactionnelle | commande, lignes et historique atomiques | `CartController.php` |
| annulation traçable | statut annulé et motif conservé | contrôleurs client/employé |
| propriété client | `commande_id` et `utilisateur_id` exigés | `CustomerController.php` |
| activation d’un repas | impossible si le menu parent est inactif | `EmployeeController.php` |
| désactivation menu | synchronise entrée, plat et dessert | `EmployeeController.php` |
| invitation avis | évitée si déjà envoyée ou avis déjà créé | `EmployeeController.php` |
| rappel matériel | un seul envoi journalisé par commande | `EmployeeController.php` |
| mot de passe fort | 10 caractères et quatre catégories | Auth/Customer/Admin |

# 30. Gestion des commandes

## 30.1 Création

Le client connecté soumet le panier avec ses coordonnées et la date de prestation. Le serveur valide les champs, relit les menus, applique les minima, calcule les montants, crée la commande et ses lignes dans une transaction, ajoute l’historique initial, synchronise les statistiques et tente l’e-mail de confirmation.

## 30.2 Calcul du montant

Pour chaque ligne : `prix_menu = nombre_personnes × prix_par_personne`, auquel une réduction éventuelle peut être associée dans `commande_menus`. Le total ajoute les frais de livraison. Les montants de session ne sont pas considérés comme fiables.

## 30.3 Statuts

`en_attente` → `acceptee` → `en_preparation` → `en_livraison` → `livree` → éventuellement `en_attente_retour_materiel` → `terminee`. `annulee` est une sortie terminale distincte.

## 30.4 Cycle de vie

Chaque changement est ajouté à `historique_statuts_commande`. Les commandes livrées dont l’heure est passée peuvent être achevées automatiquement lors de l’accès employé, avec traitement du matériel et de l’avis.

## 30.5 Annulation

Le client ne peut agir que sur sa propre commande et dans les états autorisés par le contrôleur. L’employé peut également annuler avec un motif. La ligne reste en base pour la traçabilité et sort des statistiques actives.

## 30.6 Consultation et filtres

Les listes client et employé sont triées par date. Les filtres employé (recherche, statut, période, personnes, date, code postal) sont appliqués côté navigateur sur les cartes chargées.

## 30.7 Diagramme de séquence

[DIAGRAMME À INSÉRER : client → CartController → MySQL transaction → MongoDB → Mailer → confirmation]

# 31. Gestion des menus et plats

Le catalogue public affiche uniquement les menus actifs et les regroupe en classique, événementiel, saisonnier ou régime particulier. Le détail charge le premier élément trouvé pour chaque étape du repas.

L’employé peut créer, modifier, activer, désactiver et supprimer menus, entrées, plats et desserts. Les valeurs sont bornées et les types autorisés sont issus d’une liste fermée. Un composant ne peut pas rester actif si son menu parent est inactif.

Les allergènes sont stockés sous forme de texte dans chaque table de repas. Il n’existe pas de référentiel normalisé d’allergènes ni de relation plusieurs-à-plusieurs.

# 32. Gestion des utilisateurs

Les clients s’inscrivent eux-mêmes avec le rôle `utilisateur`. Ils peuvent modifier leurs coordonnées et leur mot de passe. Les employés sont créés par l’administrateur avec identité, poste et adresse professionnelle ; un e-mail est envoyé à leur adresse personnelle.

L’administrateur peut activer, désactiver, modifier ou supprimer un employé. Les postes autorisés sont définis dans une liste PHP. Le stockage de `mot_de_passe_initial` dans `utilisateurs` est problématique : un mot de passe initial ne devrait pas être conservé de manière récupérable.

# 33. Gestion des avis

Un client peut laisser un avis pour une commande lui appartenant et éligible. Les avis ont une note, un commentaire, un statut de modération et un indicateur d’affichage sur l’accueil. L’employé peut modérer et choisir les avis visibles.

Des tables de file et de journal empêchent l’envoi répété d’invitations. Aucun worker planifié n’est fourni pour vider automatiquement `avis_email_queue` ; le déclenchement effectif dépend des traitements du contrôleur employé.

# 34. Statistiques

L’administration présente le nombre de commandes et le chiffre d’affaires par menu. MySQL produit l’agrégation source ; `MongoStatsService` la copie dans MongoDB. Les commandes annulées sont exclues. Un repli JSON local existe dans `AdminController`.

Les graphiques sont générés côté navigateur par les scripts `admin-orders-by-menu.js` et `admin-revenue-by-menu.js`, à partir des données intégrées au HTML. Aucun outil externe d’analytics n’est détecté.

# 35. Templates Twig

## 35.1 Organisation

Les vues sont rangées par domaine. Les noms suivent majoritairement le `snake_case`.

## 35.2 Layout principal

`base.html.twig` charge l’importmap, les styles et la structure commune. Il adapte certaines classes et navigations à la session.

## 35.3 Composants réutilisables

Les cartes de menu, grands titres, liens de retour et en-têtes/pieds de page sont mutualisés.

## 35.4 Affichage conditionnel

La session et le rôle déterminent les liens vers l’espace client, employé ou administrateur. Ce masquage visuel ne remplace pas le contrôle serveur.

## 35.5 Échappement

L’échappement automatique Twig est la protection principale. Aucun audit exhaustif de toutes les utilisations de HTML brut n’a été certifié.

# 36. CSS et charte graphique

Les variables, typographies, boutons, formulaires, cartes, filtres, en-têtes, pieds de page, badges et widgets sont séparés en 12 fichiers. La charte utilise des composants dédiés et de nombreuses règles responsive.

Les copies sous `public/assets/styles/` divergent des sources. La source de vérité doit être `assets/styles/`, puis être publiée par AssetMapper. [À COMPLÉTER : référence officielle de la charte graphique et valeurs de contraste validées.]

# 37. JavaScript

Les 17 scripts métier gèrent filtres, panier, tableaux de bord, comptes, employés, horaires, messages, commandes, avis et formulaires. Ils utilisent le DOM natif, `fetch`, `FormData`, les attributs `data-*`, les modales et les mises à jour partielles.

Les erreurs réseau sont généralement affichées ou entraînent un repli. Une partie des filtres traite toutes les cartes déjà chargées, ce qui ne remplace pas une pagination serveur.

# 38. Gestion des assets

AssetMapper expose `assets/`; `importmap.php` déclare `app` comme point d’entrée. Les imports manquants sont stricts en développement et seulement avertis en production.

Le dossier `public/assets/` est ignoré par Git, mais présent localement avec des copies parfois différentes. Il doit être régénéré et non édité manuellement.

# 39. Gestion des images et fichiers

`assets/` contient 76 PNG réels hors métadonnées Apple, dont 74 images de 1536 × 1024, pour environ 185 Mo au total. Ce volume est important pour un site web. Les tables stockent `image_url` et `image_alt`.

Aucun upload n’est présent. Recommandations : convertir en WebP/AVIF, produire plusieurs tailles, compresser, définir largeur/hauteur HTML et chargement différé. Supprimer les `.DS_Store` et `._*` des copies physiques.

# 40. Gestion des erreurs

Les ressources absentes déclenchent des exceptions 404. Les formulaires utilisent des messages flash. Les requêtes asynchrones renvoient des statuts 400, 403 ou 404 et du JSON.

Plusieurs blocs `catch (\Throwable)` ignorent volontairement les erreurs SMTP, MongoDB ou de modification de schéma. L’action principale continue, mais le diagnostic est difficile faute de journalisation systématique.

# 41. Logs et débogage

Monolog écrit en développement dans `var/log/dev.log`. En production, les erreurs sont regroupées par `fingers_crossed` et envoyées en JSON sur `stderr`. Le profiler est disponible uniquement en développement.

Commandes utiles :

```bash
php bin/console about
php bin/console debug:router
php bin/console debug:container
```

# 42. Cache

Symfony utilise le cache fichier par défaut. En production, Doctrine dispose de pools de cache système et résultat. Aucun cache métier personnalisé n’est détecté.

```bash
php bin/console cache:clear
php bin/console cache:warmup --env=prod
```

# 43. Tests

## 43.1 Stratégie

La stratégie recommandée doit combiner tests unitaires de règles métier, intégration DBAL/MongoDB et tests fonctionnels HTTP.

## 43.2 Environnement de test

`phpunit.dist.xml` force `APP_ENV=test`. Doctrine suffixe la base avec `_test`; les sessions utilisent un stockage simulé et les coûts de hash sont réduits.

## 43.3 Tests unitaires

Absents. Priorités : `InputValidator`, calcul de livraison, délais, transitions de statut et calcul des montants.

## 43.4 Tests fonctionnels

Absents. Priorités : inscription, connexion, panier, commande, propriété client, espace employé et administration.

## 43.5 Tests de sécurité

Absents. Tester CSRF, utilisateurs inactifs, accès inter-rôles, accès à la commande d’un autre client et réutilisation d’un jeton de mot de passe.

## 43.6 Tests manuels

[À VÉRIFIER MANUELLEMENT : responsive, e-mails SMTP, indisponibilité MongoDB, géocodage, calcul OSRM, navigation clavier et lecteurs d’écran.]

## 43.7 Exécution

```bash
php bin/phpunit
```

La commande ne trouve actuellement aucun test applicatif.

## 43.8 Couverture

Aucune mesure de couverture n’est disponible.

## 43.9 Limites

Le contrôle de syntaxe réussi ne valide ni les règles métier ni l’intégration aux bases.

# 44. Qualité du code

| Critère | Observation |
|---|---|
| Typage | signatures typées, tableaux souvent documentés |
| Style | `.editorconfig` présent |
| Analyse statique | aucune configuration |
| Formatage | aucun outil automatique |
| Tests | absents |
| Complexité | très élevée dans deux contrôleurs |
| Duplication | e-mails, validation, normalisation et SQL répétés |
| Erreurs | exceptions parfois silencieuses |

Le code ne peut pas être déclaré conforme à PSR-12 sans exécution d’un outil dédié.

# 45. Performances

| Point | Observation | Impact | Recommandation |
|---|---|---|---|
| Images | environ 185 Mo de sources PNG | chargement et stockage | formats modernes et variantes |
| Pagination | collections complètes dans plusieurs espaces | mémoire/DOM | pagination SQL |
| Filtres | traitement navigateur de toutes les cartes | ralentissement progressif | filtres serveur |
| Requêtes | nombreuses requêtes dans contrôleurs | risque N+1 | profiler puis repositories |
| Statistiques | reconstruction complète MySQL → MongoDB | coût à chaque synchronisation | traitement asynchrone/incrémental |
| Index | peu d’index composites métier | scans possibles | analyser `EXPLAIN` |
| Assets | doublons et divergences | incohérence/cache | compilation unique |

# 46. Accessibilité technique

Les templates contiennent des labels, textes alternatifs, titres, attributs ARIA, zones de retour et gestion de focus dans certaines modales. Les scripts ajoutent des interactions clavier, notamment Escape et Entrée.

Ces observations ne suffisent pas à certifier le RGAA ou WCAG. Les contrastes, l’ordre de tabulation, les messages dynamiques, les tableaux, les modales et le zoom doivent être audités avec des outils et utilisateurs.

> Une vérification complète de conformité WCAG reste nécessaire.

# 47. Responsive et compatibilité

Les feuilles CSS contiennent des media queries et des variantes mobiles pour navigation, cartes, formulaires et espaces internes. Aucun rapport de test navigateur n’est fourni.

| Appareil ou largeur | Navigateur | Pages vérifiées | État |
|---|---|---|---|
| 393 px | [À COMPLÉTER] | [À COMPLÉTER] | non testé pendant l’analyse |
| tablette | [À COMPLÉTER] | [À COMPLÉTER] | non testé |
| 1512 px | [À COMPLÉTER] | [À COMPLÉTER] | non testé |

# 48. API et échanges asynchrones

L’application ne fournit pas une API REST publique. Plusieurs actions internes retournent du JSON aux scripts `fetch`.

| Méthode | Exemple d’URL | Entrée | Sortie | Authentification |
|---|---|---|---|---|
| POST | `/panier/ajouter/{id}` | CSRF | JSON panier | non requise |
| POST | `/panier/modifier` | quantités, CSRF | JSON récapitulatif | session panier |
| POST | `/espace-administrateur/employes/{id}/actif` | CSRF | JSON statut | session administrateur |
| POST | `/espace-employe/avis-clients/{id}/statut` | action, CSRF | JSON avis | session employé |
| POST | `/espace-employe/messagerie/{id}/archiver` | CSRF | HTML/JSON selon appel | session employé |

Les erreurs utilisent notamment 400 pour entrée invalide, 403 pour CSRF/accès et 404 pour ressource absente.

# 49. Services externes

| Service | Rôle | Configuration | Repli/risque |
|---|---|---|---|
| SMTP Symfony Mailer | e-mails transactionnels | `MAILER_DSN`, `MAILER_FROM` | erreurs souvent ignorées ; action métier conservée |
| API Adresse nationale | géocodage | URL codée dans PHP/JS | estimation par code postal |
| OSRM public | distance routière | URL codée | distance à vol d’oiseau/estimation |
| API Découpage administratif | ville par code postal | URL codée en JS | saisie manuelle |
| MongoDB | statistiques | URI et base via environnement | repli JSON dans administration |

Le README cite Brevo, mais le fournisseur réellement utilisé dépend de `MAILER_DSN` : `[À CONFIRMER : fournisseur SMTP de production]`.

# 50. Déploiement

## 50.1 Prérequis serveur

Serveur Linux ou Windows avec PHP 8.4, serveur web pointant sur `public/`, Composer, MySQL 8, MongoDB, extensions PHP nécessaires, HTTPS et accès sécurisé aux variables d’environnement. Un accès SSH est recommandé. Node.js n’est pas nécessaire.

## 50.2 Préparation

Sauvegarder MySQL, MongoDB et les éventuels fichiers persistants. Identifier le commit à livrer, vérifier l’état Git, valider les secrets de production et exécuter les contrôles disponibles. L’absence de tests automatisés impose une recette manuelle renforcée.

## 50.3 Récupération du code

```bash
git clone https://github.com/manonlrt261/Vite-gourmand.git
cd Vite-gourmand
git checkout <commit-validé>
```

## 50.4 Installation de production

```bash
composer install --no-dev --optimize-autoloader
```

## 50.5 Variables d’environnement

Configurer `APP_ENV=prod`, `APP_DEBUG=0` si cette variable est utilisée par l’hébergement, `APP_SECRET`, `DATABASE_URL`, `MONGODB_URI`, `MONGODB_DATABASE`, `MAILER_DSN`, `MAILER_FROM`, `ADMIN_EMAIL`, `APP_URL` et `DEFAULT_URI`. Stocker les secrets hors dépôt.

## 50.6 Base de données

Pour une installation neuve, importer les scripts SQL dans l’ordre. Pour une mise à jour, aucune procédure fiable de migration incrémentale n’existe actuellement :

```bash
mysql -u utilisateur -p vite_et_gourmand < database/01_creation_base.sql
mysql -u utilisateur -p vite_et_gourmand < database/02_insertion_donnees.sql
```

Attention : le premier script contient des `DROP TABLE`; il est destructif sur une base existante. Il ne doit jamais être exécuté en mise à jour de production. La commande `doctrine:migrations:migrate` n’apporte rien tant que le dossier de migrations est vide.

## 50.7 Assets

```bash
php bin/console asset-map:compile --env=prod
```

## 50.8 Cache

```bash
php bin/console cache:clear --env=prod
php bin/console cache:warmup --env=prod
```

## 50.9 Permissions

L’utilisateur du serveur web doit pouvoir écrire dans `var/cache/` et `var/log/`. Aucun répertoire d’upload n’est requis dans l’état actuel.

## 50.10 Configuration du serveur web

Le document root doit être `public/`. Les autres dossiers, en particulier `.env*`, `config/`, `src/` et `database/`, ne doivent pas être servis directement.

## 50.11 HTTPS

HTTPS est indispensable pour protéger sessions, mots de passe et données personnelles. Forcer la redirection HTTP → HTTPS et activer des cookies `Secure`, `HttpOnly` et une politique `SameSite` adaptée.

## 50.12 MongoDB

Créer la base et autoriser uniquement le compte applicatif. La collection peut être initialisée lors du premier recalcul. [À VÉRIFIER MANUELLEMENT : comportement exact au premier accès administrateur sur une base MongoDB vide.]

## 50.13 Compte administrateur

Le script de données peut contenir un compte de démonstration. En production, créer un administrateur avec un mot de passe fort haché et supprimer ou désactiver les comptes de test. Aucune commande Symfony dédiée n’existe.

## 50.14 Vérifications post-déploiement

- accueil et assets accessibles ;
- connexion MySQL et MongoDB ;
- inscription et connexion ;
- accès refusé aux mauvais rôles ;
- panier et commande de test ;
- calcul de livraison ;
- e-mails ;
- espaces client, employé et administrateur ;
- journaux sans erreur critique ;
- profiler inaccessible en production ;
- sauvegardes opérationnelles.

## 50.15 Retour arrière

Conserver le commit précédent, une sauvegarde SQL, un export MongoDB et les variables de configuration. Restaurer d’abord le code précédent, puis uniquement la base compatible. Ne jamais annuler une évolution de schéma sans plan testé. L’absence de migrations versionnées rend le retour arrière de base particulièrement risqué.

# 51. Sauvegarde et restauration

```bash
mysqldump -u utilisateur -p vite_et_gourmand > sauvegarde.sql
mysql -u utilisateur -p vite_et_gourmand < sauvegarde.sql
mongodump --uri="<URI_MONGODB>" --db=vite_gourmand_nosql --out=sauvegarde-mongo
mongorestore --uri="<URI_MONGODB>" --db=vite_gourmand_nosql sauvegarde-mongo/vite_gourmand_nosql
```

Ne jamais placer le mot de passe dans la commande. Sauvegarder aussi les secrets dans un coffre dédié, pas dans la même archive publique. Recommandation : sauvegarde quotidienne, conservation glissante et test de restauration trimestriel. `[À CONFIRMER : exigences de conservation de l’entreprise]`.

# 52. Maintenance

`composer install` reproduit les versions verrouillées ; `composer update` recherche de nouvelles versions et modifie `composer.lock`. Toute mise à jour doit être réalisée sur une branche, suivie de tests et d’une sauvegarde.

Opérations courantes : surveiller `var/log`, renouveler les secrets, vérifier les certificats, contrôler les sauvegardes, nettoyer les métadonnées d’assets, compiler les assets et auditer les dépendances.

```bash
composer audit
composer outdated
php bin/console cache:clear
```

Il n’existe aucune dépendance npm à maintenir.

# 53. Diagnostic des problèmes courants

## Erreur 500

**Symptômes :** page générique ou réponse 500. **Causes :** variable absente, base inaccessible, erreur PHP. **Vérifications :** `var/log`, `APP_ENV`, `php bin/console about`. **Solution :** corriger la configuration puis vider le cache.

## Base MySQL inaccessible

**Symptômes :** exception DBAL. **Causes :** service arrêté, URL erronée, droits insuffisants. **Vérifications :** `DATABASE_URL` et connexion MySQL. **Solution :** démarrer le service et corriger le compte sans exposer son secret.

## MongoDB inaccessible

**Symptômes :** statistiques absentes ou repli JSON. **Causes :** service arrêté, URI ou extension incorrecte. **Vérifications :** `MONGODB_URI`, pilote PHP, journaux. **Solution :** rétablir MongoDB et recalculer les statistiques.

## Migration en erreur

**Symptômes :** aucune migration exécutée ou schéma inchangé. **Cause :** dossier de migrations vide. **Solution :** utiliser les scripts SQL pour une base neuve ; créer de vraies migrations avant les mises à jour.

## CSS ou JavaScript non chargé

**Symptômes :** page sans style ou interaction. **Causes :** assets non compilés, copie publique obsolète, cache. **Vérifications :** `debug:asset-map`, console navigateur. **Solution :** compiler depuis `assets/` et vider le cache.

## Accès refusé ou redirection connexion

**Symptômes :** retour à `/connexion`. **Causes :** session absente, rôle incorrect, utilisateur inactif. **Vérifications :** compte en base et nouvelle connexion. **Solution :** réactiver seulement si autorisé, puis recréer la session.

## Connexion impossible

**Symptômes :** identifiants refusés. **Causes :** e-mail, mot de passe, compte inactif ou ancien hash. **Vérifications :** état du compte sans afficher le mot de passe. **Solution :** réinitialisation sécurisée.

## Route introuvable

**Symptômes :** 404. **Vérification :** `php bin/console debug:router`. **Solution :** utiliser le nom et la méthode HTTP exacts.

## Image absente

**Symptômes :** visuel cassé. **Causes :** chemin SQL incorrect, asset non publié. **Vérifications :** `image_url`, fichier sous `assets/`. **Solution :** corriger la donnée ou republier les assets.

## Formulaire rejeté ou erreur CSRF

**Symptômes :** 403 ou message d’expiration. **Causes :** session expirée, jeton absent, page ancienne. **Solution :** recharger la page et soumettre le formulaire courant.

## E-mail non envoyé

**Symptômes :** action réussie sans message reçu. **Causes :** SMTP ou expéditeur. **Vérifications :** `MAILER_DSN`, journaux et dossier indésirable. **Solution :** corriger le SMTP ; les exceptions étant parfois absorbées, ajouter une journalisation.

## Serveur local inaccessible

**Symptômes :** refus sur le port 8005. **Causes :** PHP absent du PATH, port occupé. **Solution :** utiliser le chemin Laragon ou choisir un port libre.

# 54. Commandes utiles

| Action | Commande |
|---|---|
| Informations Symfony | `php bin/console about` |
| Routes | `php bin/console debug:router` |
| Services | `php bin/console debug:container` |
| Installer | `composer install` |
| Audit dépendances | `composer audit` |
| Importer le schéma neuf | `mysql -u utilisateur -p vite_et_gourmand < database/01_creation_base.sql` |
| Charger la démonstration | `mysql -u utilisateur -p vite_et_gourmand < database/02_insertion_donnees.sql` |
| Cache | `php bin/console cache:clear` |
| Tests | `php bin/phpunit` |
| Serveur local | `php -S 127.0.0.1:8005 -t public public/router.php` |
| Compiler les assets | `php bin/console asset-map:compile` |

# 55. Diagrammes obligatoires

## 55.1 Cas d’utilisation

[DIAGRAMME À INSÉRER : visiteur, client, employé et administrateur reliés uniquement aux cas confirmés]

Objectif : visualiser le périmètre de chaque profil et les frontières d’autorisation.

## 55.2 Diagramme de classes ou MCD

[DIAGRAMME À INSÉRER : MCD des 18 tables, clés et cardinalités]

Objectif : représenter le modèle réel, puisque les entités Doctrine sont absentes.

## 55.3 Séquence de connexion

[DIAGRAMME À INSÉRER : navigateur → AuthController → MySQL → vérification mot de passe → session → redirection]

## 55.4 Séquence de commande

[DIAGRAMME À INSÉRER : panier → validation → transaction MySQL → statistiques MongoDB → e-mail]

## 55.5 Séquence de changement de statut

[DIAGRAMME À INSÉRER : employé → EmployeeController → commande → historique → notifications]

## 55.6 État d’une commande

[DIAGRAMME À INSÉRER : en attente, acceptée, préparation, livraison, livrée, retour matériel, terminée et annulée]

## 55.7 Architecture

[DIAGRAMME À INSÉRER : navigateur, Symfony, DBAL/MySQL, MongoDB, SMTP et API publiques]

Chaque diagramme doit reprendre les noms de routes, méthodes et statuts documentés, sans introduire de couche inexistante.

# 56. Captures à intégrer

| N° | Partie | Capture | Objectif | À masquer |
|---:|---|---|---|---|
| 1 | Architecture | arborescence | organisation | chemin personnel |
| 2 | Symfony | `debug:router` | routage | chemins locaux |
| 3 | Base | schéma MySQL | relations | données personnelles |
| 4 | MongoDB | collection statistique | documents | URI/identifiants |
| 5 | Sécurité | formulaire et CSRF | protections | secrets |
| 6 | Tests | sortie PHPUnit après création des tests | preuve | chemins locaux |
| 7 | Responsive | accueil mobile/desktop | adaptation | données personnelles |
| 8 | Déploiement | application en ligne | version livrée | comptes et secrets |

# 57. Limitations techniques connues

- aucun test applicatif ;
- aucune entité, repository ou migration Doctrine ;
- authentification et autorisations manuelles ;
- repli acceptant potentiellement des mots de passe en clair ;
- contrôleurs très volumineux ;
- changements de schéma à l’exécution ;
- absence de pagination serveur ;
- assets dupliqués et divergents ;
- images très lourdes ;
- Docker Compose incohérent avec MySQL/MongoDB ;
- absence de CI/CD et de procédure de production validée ;
- synchronisation MongoDB non transactionnelle ;
- exceptions externes parfois silencieuses ;
- donnée saisonnière incohérente pour « Saveurs de printemps » ;
- conformité accessibilité non auditée.

# 58. Améliorations techniques

| Priorité | Problème | Solution | Bénéfice |
|---|---|---|---|
| Critique | ancien mot de passe accepté en clair | migration des hashes puis suppression du repli | sécurité |
| Critique | pas de tests | tests auth, permissions, commande et montants | fiabilité |
| Haute | schéma non versionné | migrations Doctrine | déploiement sûr |
| Haute | autorisation manuelle | SecurityBundle, provider et voters | contrôle centralisé |
| Haute | contrôleurs massifs | services et repositories | maintenance |
| Haute | mot de passe initial stocké | jeton d’activation à usage unique | protection des employés |
| Moyenne | assets dupliqués | source unique AssetMapper | cohérence |
| Moyenne | images lourdes | WebP/AVIF et responsive images | performances |
| Moyenne | listes complètes | pagination et filtres SQL | scalabilité |
| Moyenne | synchronisation MongoDB | message asynchrone idempotent | résilience |
| Moyenne | aucune CI | lint, analyse statique et PHPUnit | qualité continue |
| Basse | locale `en` | définir `fr` et catalogues | cohérence linguistique |

# 59. Conclusion technique

Vite & Gourmand est une application Symfony 8.1 riche couvrant les parcours publics, clients, employés et administrateurs. PHP, Twig, Doctrine DBAL, MySQL, MongoDB, AssetMapper et JavaScript natif forment son socle.

Ses points forts sont l’étendue fonctionnelle, les contrôles serveur, le CSRF, les requêtes paramétrées, l’historique des commandes et la séparation MySQL/MongoDB. Ses principales limites sont l’absence de tests et migrations, les contrôleurs très chargés, l’authentification manuelle et le déploiement non industrialisé.

La reprise doit commencer par sécuriser les mots de passe, figer le schéma, introduire des tests et extraire les services métier. Ces travaux rendront l’application plus maintenable sans remettre en cause son périmètre fonctionnel.

# 60. Annexes

## Annexe 1 — Arborescence simplifiée

Voir le chapitre 13.

## Annexe 2 — Liste des routes métier

| Nom | Méthode | Chemin |
|---|---|---|
| `home_show` | ANY | `/` |
| `menu_index` | ANY | `/menus` |
| `menu_show` | ANY | `/menus/{id}` |
| `contact_index` | ANY | `/contact` |
| `login` | ANY | `/connexion` |
| `register` | ANY | `/inscription` |
| `forgot_password` | ANY | `/mot-de-passe-oublie` |
| `reset_password` | ANY | `/reinitialiser-mot-de-passe/{token}` |
| `logout` | ANY | `/deconnexion` |
| `customer_account` | ANY | `/mon-compte` |
| `customer_orders` | ANY | `/mon-compte/commandes` |
| `customer_order_detail` | ANY | `/mon-compte/commandes/{id}` |
| `customer_order_cancel` | POST | `/mon-compte/commandes/{id}/annuler` |
| `customer_order_update` | POST | `/mon-compte/commandes/{id}/modifier` |
| `customer_order_add_menu` | POST | `/mon-compte/commandes/{id}/ajouter-menu` |
| `customer_profile` | ANY | `/mon-compte/mes-informations` |
| `cart_index` | ANY | `/panier` |
| `cart_add` | POST | `/panier/ajouter/{id}` |
| `cart_remove` | POST | `/panier/supprimer/{id}` |
| `cart_update` | POST | `/panier/modifier` |
| `cart_checkout` | POST | `/panier/commander` |
| `order_confirmation` | ANY | `/commande/confirmation` |
| `employee_dashboard` | ANY | `/espace-employe` |
| `admin_dashboard` | ANY | `/espace-administrateur` |
| `admin_employees` | ANY | `/espace-administrateur/employes` |
| `admin_employee_create` | ANY | `/espace-administrateur/employes/ajouter` |
| `admin_employee_toggle` | POST | `/espace-administrateur/employes/{id}/actif` |
| `admin_employee_update` | POST | `/espace-administrateur/employes/{id}/modifier` |
| `admin_employee_delete` | POST | `/espace-administrateur/employes/{id}/supprimer` |
| `admin_orders_by_menu` | ANY | `/espace-administrateur/commandes-par-menu` |
| `admin_revenue_by_menu` | ANY | `/espace-administrateur/chiffre-affaires-par-menu` |
| `employee_orders` | ANY | `/espace-employe/commandes` |
| `employee_order_advance_status` | POST | `/espace-employe/commandes/{id}/statut-suivant` |
| `employee_order_update` | POST | `/espace-employe/commandes/{id}/modifier` |
| `employee_order_cancel` | POST | `/espace-employe/commandes/{id}/annuler` |
| `employee_menus` | ANY | `/espace-employe/menus-et-plats` |
| `employee_items_all` | ANY | `/espace-employe/menus-et-plats/tous` |
| `employee_menu_create` | GET/POST | `/espace-employe/menus-et-plats/ajouter-menu` |
| `employee_meal_create` | GET/POST | `/espace-employe/menus-et-plats/ajouter-{type}` |
| `employee_item_edit` | GET/POST | `/espace-employe/menus-et-plats/{type}/{id}/modifier` |
| `employee_item_toggle` | POST | `/espace-employe/menus-et-plats/{type}/{id}/actif` |
| `employee_item_delete` | POST | `/espace-employe/menus-et-plats/{type}/{id}/supprimer` |
| `employee_hours` | GET/POST | `/espace-employe/horaires` |
| `employee_closure_delete` | POST | `/espace-employe/horaires/fermetures/{id}/supprimer` |
| `employee_reviews` | ANY | `/espace-employe/avis-clients` |
| `employee_messages` | ANY | `/espace-employe/messagerie` |
| `employee_material_return_email` | POST | `/espace-employe/messagerie/retour-materiel` |
| `employee_message_reply` | POST | `/espace-employe/messagerie/{id}/repondre` |
| `employee_message_archive` | POST | `/espace-employe/messagerie/{id}/archiver` |
| `employee_message_unarchive` | POST | `/espace-employe/messagerie/{id}/desarchiver` |
| `employee_message_restore` | POST | `/espace-employe/messagerie/{id}/restaurer` |
| `employee_message_delete` | POST | `/espace-employe/messagerie/{id}/supprimer` |
| `employee_reviews_all` | ANY | `/espace-employe/avis-clients/tous` |
| `employee_review_status` | POST | `/espace-employe/avis-clients/{id}/statut` |
| `cart_show` | ANY | `/panier/{id}` |

## Annexe 3 — Entités

Aucune entité Doctrine. Les 18 tables sont décrites au chapitre 61.

## Annexe 4 — Dictionnaire de données

Voir le chapitre 61.

## Annexe 5 — Relations

Voir le chapitre 18.3 et le script SQL de référence.

## Annexe 6 — Variables d’environnement

Voir les chapitres 2.0.1 et 14.4. Les valeurs restent secrètes.

## Annexe 7 — Rôles et autorisations

Voir le chapitre 27.

## Annexe 8 — Statuts de commande

| Ordre | Code | Libellé |
|---:|---|---|
| 1 | `en_attente` | En attente |
| 2 | `acceptee` | Validée |
| 3 | `en_preparation` | En cours de préparation |
| 4 | `en_livraison` | En cours de livraison |
| 5 | `livree` | Livrée |
| 6 | `en_attente_retour_materiel` | En attente retour matériel |
| 7 | `terminee` | Terminée |
| 8 | `annulee` | Annulée |

## Annexe 9 — Commandes Symfony

Voir le chapitre 54.

## Annexe 10 — Plan de tests

1. validateurs et calculs ; 2. authentification ; 3. autorisations ; 4. panier ; 5. commande ; 6. statuts ; 7. MongoDB ; 8. e-mails simulés ; 9. interface manuelle.

## Annexe 11 — Déploiement

Voir le chapitre 50.

## Annexe 12 — Illustrations

Voir les chapitres 55 et 56.

## Annexe 13 — Liens utiles

- Dépôt GitHub : `https://github.com/manonlrt261/Vite-gourmand`
- Application : [À COMPLÉTER]
- README : `README.md`
- Manuel utilisateur : [À COMPLÉTER]
- Gestion de projet : [À COMPLÉTER]
- Charte graphique : [À COMPLÉTER]
- Maquettes Figma : [À COMPLÉTER]

# 61. Dictionnaire de données

Les types et tailles proviennent de `database/01_creation_base.sql`.

| Table | Champs (type ; nullabilité ; clé/défaut) | Description |
|---|---|---|
| `roles` | `role_id INT` PK AI ; `libelle VARCHAR(50)` NN ; `created_at DATETIME` NN défaut courant ; `updated_at DATETIME` NULL | rôles |
| `utilisateurs` | `id INT` PK AI ; `nom VARCHAR(100)` NN ; `prenom VARCHAR(100)` NN ; `email VARCHAR(255)` NN UNIQUE ; `telephone VARCHAR(20)` NN ; `mot_de_passe VARCHAR(255)` NN ; `adresse_postale VARCHAR(255)` NN ; `ville VARCHAR(250)` NN ; `code_postal VARCHAR(10)` NN ; `role_id INT` NN FK ; `actif TINYINT(1)` NN ; `created_at DATETIME` NN ; `updated_at DATETIME` NULL ; `poste VARCHAR(100)` NN ; `date_naissance DATE` NULL ; `lieu_naissance VARCHAR(150)` NULL ; `email_personnel VARCHAR(255)` NULL ; `mot_de_passe_initial VARCHAR(255)` NULL | comptes |
| `menus` | `menu_id INT` PK AI ; `nom_menu VARCHAR(250)` NN ; `theme VARCHAR(250)` NN ; `description VARCHAR(800)` NN ; dates de disponibilité `DATE` NULL ; `conditions VARCHAR(800)` NN ; `personnes_minimum INT` NN ; `prix_par_personne DECIMAL(10,2)` NN ; `stock_disponible INT` NN ; `materiel_disponible TINYINT(1)` NN défaut 0 ; `actif TINYINT(1)` NN défaut 1 ; `image_url/image_alt VARCHAR(255)` NN | menus |
| `entree` | `entree_id INT` PK AI ; nom `VARCHAR(250)` ; description `VARCHAR(500)` ; `menu_id INT` NULL FK ; thème/allergènes `VARCHAR(250)` ; image/alt `VARCHAR(255)` ; `actif TINYINT(1)` | entrées |
| `plat` | `plat_id INT` PK AI ; nom `VARCHAR(250)` ; description `VARCHAR(500)` ; `menu_id INT` NULL FK ; thème/allergènes `VARCHAR(250)` ; image/alt `VARCHAR(255)` ; `actif TINYINT(1)` | plats |
| `dessert` | `dessert_id INT` PK AI ; nom/description `VARCHAR(250)` ; `menu_id INT` NULL FK ; thème/allergènes `VARCHAR(250)` ; image/alt `VARCHAR(255)` ; `actif TINYINT(1)` | desserts |
| `statuts_commande` | `statut_id INT` PK AI ; `code VARCHAR(50)` NN UNIQUE ; `libelle VARCHAR(100)` NN ; `ordre INT` NN | statuts |
| `commandes` | `commande_id INT` PK AI ; `utilisateur_id/menu_id/statut_id INT` NN FK ; `date_commande DATETIME` ; `date_prestation DATE` ; heure `TIME` ; adresse `VARCHAR(255)` ; ville `VARCHAR(100)` ; code postal `VARCHAR(10)` ; personnes `INT` ; trois prix `DECIMAL(10,2)` ; prêt `TINYINT(1)` ; motif `TEXT` ; dates techniques `DATETIME` ; contact méthode `VARCHAR(100)` NULL ; contact date `DATETIME` NULL ; contact message `TEXT` NULL | commandes |
| `commande_menus` | `commande_menu_id INT` PK AI ; `commande_id/menu_id INT` NN FK ; personnes `INT` ; prix unitaire/menu/réduction `DECIMAL(10,2)` ; `created_at DATETIME` | lignes de commande |
| `historique_statuts_commande` | `historique_id INT` PK AI ; `commande_id/statut_id INT` FK ; `date_changement DATETIME` ; `commentaire TEXT` NULL | historique |
| `avis` | `avis_id INT` PK AI ; commande/utilisateur FK ; `note TINYINT` ; `commentaire TEXT` ; `statut VARCHAR(50)` ; dates `DATETIME` ; `afficher_accueil TINYINT(1)` défaut 0 | avis |
| `contact` | `contact_id INT` PK AI ; `email VARCHAR(255)` ; `titre VARCHAR(150)` ; `description TEXT` ; `statut VARCHAR(50)` ; dates création/mise à jour/archive/suppression/réponse ; `utilisateur_id INT` NULL FK ; `reponse TEXT` NULL ; répondants `INT` NULL | messages |
| `horaires_ouverture` | `jour_code VARCHAR(20)` PK ; `jour_label VARCHAR(30)` ; `est_ouvert TINYINT(1)` ; heures `TIME` NULL ; `ordre INT` ; `updated_at DATETIME` | horaires |
| `fermetures_exceptionnelles` | `fermeture_id INT` PK AI ; dates début/fin `DATE` ; `motif VARCHAR(255)` ; dates techniques `DATETIME` | fermetures |
| `password_reset_tokens` | `id INT` PK AI ; `utilisateur_id INT` FK ; `token_hash VARCHAR(64)` UNIQUE ; expiration/utilisation/création `DATETIME` | jetons |
| `avis_email_log` | `log_id INT` PK AI ; `commande_id INT` UNIQUE FK ; `email VARCHAR(255)` ; `sent_at DATETIME` | envois avis |
| `avis_email_queue` | `queue_id INT` PK AI ; commande UNIQUE FK ; utilisateur FK ; email ; `send_after/sent_at/created_at DATETIME` | file avis |
| `materiel_email_log` | `log_id INT` PK AI ; `commande_id INT` UNIQUE FK ; `email VARCHAR(255)` ; `sent_at DATETIME` | rappels matériel |

`NN` signifie non nul, `PK` clé primaire, `FK` clé étrangère et `AI` auto-incrémenté.

# 62. Conventions de développement

Classes en PascalCase, méthodes et variables en camelCase, routes en snake_case, URL en français avec tirets et templates en snake_case. Le code PHP utilise quatre espaces et des types de retour. Les commentaires sont nombreux et en français.

Incohérences : accents parfois normalisés par listes redondantes, SQL et noms métier mélangés français/anglais, règles répétées et anciens chemins `templates/Home` visibles dans Git. Convention future recommandée : PSR-12 contrôlée automatiquement, services par domaine, DTO immuables, enums de statut, migrations et commits au format `type(scope): message`.

# 63. Règles de rédaction appliquées

Le document est en français, distingue le confirmé du recommandé, ne contient aucun secret, cite les vrais chemins et utilise les marqueurs demandés. Les procédures non exécutées sont signalées et aucun résultat de test fonctionnel n’est inventé.

# 64. Extraits de code représentatifs

`config/routes.yaml` — route de validation :

```yaml
cart_checkout:
    path: /panier/commander
    controller: App\Controller\CartController::checkout
    methods: [POST]
```

`src/Controller/CartController.php` — constantes de livraison :

```php
private const DELIVERY_BASE_PRICE = 5.00;
private const DELIVERY_PRICE_PER_KM = 0.59;
```

`src/Controller/AuthController.php` — hachage :

```php
'mot_de_passe' => password_hash($data['password'], PASSWORD_DEFAULT),
```

Ces extraits montrent respectivement la restriction HTTP, la règle tarifaire et la protection des nouveaux mots de passe.

# 65. Synthèse de l’analyse initiale

1. PHP 8.4.12 local, contrainte ≥ 8.4.
2. Symfony 8.1.0.
3. Doctrine DBAL/ORM, Twig, MongoDB, Mailer, AssetMapper, Stimulus/Turbo.
4. AssetMapper/Importmap, sans Node.
5. MySQL 8.0.46 dans le dump.
6. MongoDB direct, sans ODM.
7. Aucune entité Doctrine ; 18 tables SQL.
8. Huit contrôleurs.
9. Un service métier explicite.
10. Aucun Form Type.
11. `utilisateur`, `employe`, `administrateur`.
12. 55 routes métier.
13. Panier, minima, livraison, délais, statuts, avis et matériel.
14. CSRF, hash, paramètres DBAL, session et contrôles manuels.
15. Aucun test applicatif.
16. PHPUnit et EditorConfig ; pas d’analyse statique configurée.
17. Script local ; pas de déploiement automatisé.
18. Tests, migrations, CI/CD et industrialisation incomplets.
19. PostgreSQL Docker/MySQL réel, assets dupliqués, date saisonnière invalide.
20. Hébergement, formation, production, politique RGPD et recette manuelle à compléter.

# 66. Contrôle final

La numérotation, les technologies, versions, chemins, classes, routes, tables, rôles, statuts et commandes ont été confrontés aux fichiers réels. Les secrets ne sont pas copiés. Les tests absents et procédures non vérifiées sont explicitement signalés.

Points restant à vérifier manuellement : ancres selon le moteur Markdown cible, import sur base vierge, SMTP, MongoDB, API cartographiques, responsive, accessibilité, déploiement et restauration.

# 67. Compte rendu final

| Élément | Résultat |
|---|---|
| Fichier | `docs/documentation-technique.md` |
| Chapitres numérotés | 68 |
| Technologies | PHP, Symfony, Twig, DBAL/ORM, MySQL, MongoDB, AssetMapper, JavaScript |
| Entités | aucune ; 18 tables documentées |
| Contrôleurs | 8 |
| Services | `MongoStatsService`, `ScheduleExtension`, `InputValidator` documentés selon leur nature |
| Formulaires | formulaires Twig manuels ; aucun Form Type |
| Routes | 55 routes métier |
| Rôles | 3 rôles SQL et visiteur |
| Sécurité | CSRF, hash, DBAL, session, propriété et limites |
| Tests | configuration présente, aucun test applicatif |
| Diagrammes | 7 emplacements |
| Captures | 8 suggestions |
| Informations à compléter | formation, examen, URL, hébergement, liens documentaires |
| Risques majeurs | mots de passe hérités, absence de tests/migrations, contrôleurs massifs |
| Priorités | sécurité, tests, migrations, autorisations, services |

# 68. Restrictions de modification

La rédaction n’a modifié que `docs/documentation-technique.md`. Aucun code source, configuration, base, fixture, dépendance, branche ou commit n’a été modifié. Les corrections recommandées sont documentées sans être appliquées.
