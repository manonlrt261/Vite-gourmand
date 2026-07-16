# Vite & Gourmand

## Environnement technique

### Présentation

Vite & Gourmand est un projet web développé avec Symfony dans le cadre d'un projet d'examen.

L'application permet de présenter les menus d'un traiteur, de gérer un panier, de passer commande, de gérer un espace client, un espace employé et un espace administrateur.

Ce document décrit l'environnement technique nécessaire pour installer, configurer et lancer le projet en local.

---

## Technologies utilisées

Le projet utilise les technologies suivantes :

- PHP 8.4
- Symfony 8.1
- Twig
- MySQL
- MongoDB
- HTML
- CSS
- JavaScript
- Laragon
- Composer
- Visual Studio Code
- MongoDB Compass

---

## Prérequis

Avant de lancer le projet, il faut avoir installé :

- Laragon
- PHP 8.4
- MySQL
- MongoDB
- MongoDB Compass
- Composer
- Visual Studio Code

Le projet a été développé et testé en local avec Laragon.

---

## Bases de données utilisées

Le projet utilise deux types de bases de données : une base relationnelle MySQL et une base non relationnelle MongoDB.

### Base relationnelle : MySQL

MySQL est la base principale du projet.

Elle contient les données métier du site :

- utilisateurs ;
- rôles ;
- menus ;
- entrées ;
- plats ;
- desserts ;
- commandes ;
- statuts de commande ;
- historique des statuts ;
- avis clients ;
- messages de contact.

### Base non relationnelle : MongoDB

MongoDB est utilisé pour les statistiques administrateur.

Il contient notamment :

- le nombre de commandes par menu ;
- le chiffre d'affaires par menu.

MongoDB permet de stocker ces données sous forme de documents, ce qui est adapté aux statistiques et aux données de synthèse.

---

## Variables d'environnement

Les variables d'environnement permettent de configurer le projet sans écrire directement les informations sensibles dans le code.

Elles sont définies dans les fichiers suivants :

```text
.env
.env.local
```

Le fichier `.env` contient les valeurs générales du projet.

Le fichier `.env.local` contient les valeurs propres à l'ordinateur local.

### Variables principales

```env
APP_ENV=dev
APP_SECRET=...
DATABASE_URL="mysql://root:mot_de_passe@127.0.0.1:3306/vite_et_gourmand?serverVersion=8.0.32&charset=utf8mb4"
MONGODB_URI="mongodb://127.0.0.1:27017"
MONGODB_DATABASE="vite_gourmand_nosql"
APP_URL="http://127.0.0.1:8005"
DEFAULT_URI="http://127.0.0.1:8005"
MAILER_DSN=null://null
MAILER_FROM="contact@vite-gourmand.fr"
ADMIN_EMAIL="contact@vite-gourmand.fr"
```

### Rôle des variables

- `APP_ENV` : définit l'environnement utilisé, ici `dev` pour le développement.
- `APP_SECRET` : clé interne utilisée par Symfony pour sécuriser certaines données.
- `DATABASE_URL` : permet de connecter Symfony à la base MySQL.
- `MONGODB_URI` : permet de connecter Symfony à MongoDB.
- `MONGODB_DATABASE` : définit le nom de la base MongoDB utilisée.
- `APP_URL` : définit l'URL locale du projet.
- `DEFAULT_URI` : permet de générer des liens complets, par exemple pour la réinitialisation de mot de passe.
- `MAILER_DSN` : configure l'envoi des emails.
- `MAILER_FROM` : définit l'adresse email utilisée comme expéditeur.
- `ADMIN_EMAIL` : définit l'adresse email de réception principale pour l'entreprise.

---

## Installation du projet

### 1. Ouvrir Laragon

Lancer Laragon, puis démarrer les services nécessaires :

- Apache ;
- MySQL ;
- MongoDB si configuré dans Laragon, sinon lancer MongoDB séparément.

### 2. Ouvrir le projet dans Visual Studio Code

Ouvrir le dossier du projet :

```text
vite_gourmandtest
```

Le dossier doit contenir notamment :

```text
src
templates
public
config
composer.json
.env
.env.local
```

### 3. Installer les dépendances

Après avoir récupéré le projet, installer les dépendances PHP avec Composer :

```powershell
& "C:\laragon\bin\php\php-8.4.12-nts-Win32-vs17-x64\php.exe" "C:\laragon\bin\composer\composer.phar" install
```

Cette commande installe les bibliothèques nécessaires au fonctionnement du projet Symfony.

### 4. Vérifier la base MySQL

Dans phpMyAdmin, vérifier que la base suivante existe :

```text
vite_et_gourmand
```

Elle doit contenir les tables principales du projet.

### 5. Vérifier MongoDB

Dans MongoDB Compass, vérifier que MongoDB est lancé sur :

```text
mongodb://127.0.0.1:27017
```

La base utilisée est :

```text
vite_gourmand_nosql
```

---

## Lancement du projet en local

Depuis le terminal de Visual Studio Code, se placer à la racine du projet.

### Commande simple

```bash
php -S 127.0.0.1:8005 -t public public/router.php
```

### Commande avec le chemin complet de PHP

Si la commande `php` n'est pas reconnue, utiliser le chemin complet de PHP avec Laragon :

```powershell
& "C:\laragon\bin\php\php-8.4.12-nts-Win32-vs17-x64\php.exe" -S 127.0.0.1:8005 -t public public/router.php
```

Ensuite, ouvrir le site dans le navigateur :

```text
http://127.0.0.1:8005
```

---

## Vérification du projet

Pour vérifier que Symfony fonctionne correctement, utiliser :

```powershell
& "C:\laragon\bin\php\php-8.4.12-nts-Win32-vs17-x64\php.exe" bin/console about
```

Cette commande permet de vérifier :

- la version de Symfony ;
- la version de PHP ;
- l'environnement utilisé ;
- le mode debug ;
- les dossiers de cache et de logs.

---

## Organisation des fichiers

### Contrôleurs

Les contrôleurs sont situés dans :

```text
src/Controller
```

Ils gèrent la logique des pages :

- `HomeController.php`
- `MenuController.php`
- `CartController.php`
- `AuthController.php`
- `CustomerController.php`
- `EmployeeController.php`
- `AdminController.php`
- `ContactController.php`

### Templates Twig

Les templates sont situés dans :

```text
templates
```

Ils gèrent l'affichage HTML des pages.

### Fichiers CSS

Les fichiers CSS sont situés dans :

```text
public/assets/styles
```

Ils contiennent :

- les variables graphiques ;
- la typographie ;
- les boutons ;
- les formulaires ;
- le header ;
- le footer ;
- les cartes ;
- les styles spécifiques des pages.

### Fichiers JavaScript

Les fichiers JavaScript sont situés dans :

```text
public/assets/scripts
```

Ils permettent notamment :

- les filtres sans rechargement ;
- l'ajout au panier sans rechargement ;
- les actions dynamiques dans les espaces client, employé et administrateur ;
- l'affichage ou le masquage des mots de passe.

---

## Comptes de test

Des comptes de test peuvent être utilisés pour vérifier les différents espaces du site.

### Client

```text
Email : cliente.test@vite-gourmand.local
Mot de passe : à compléter
```

### Employé

```text
Email : employe.test@vite-gourmand.local
Mot de passe : à compléter
```

### Administrateur

```text
Email : admin.test@vite-gourmand.local
Mot de passe : à compléter
```

---

## Fonctionnalités principales

Le projet permet :

- de consulter les menus ;
- de filtrer les menus ;
- d'ajouter un menu au panier ;
- de passer commande ;
- de recevoir une confirmation de commande ;
- de gérer son espace client ;
- de modifier ses informations personnelles ;
- de consulter ses commandes ;
- de laisser un avis ;
- de gérer les commandes côté employé ;
- de gérer les menus, plats, entrées et desserts ;
- de gérer les horaires ;
- de gérer les avis clients ;
- de gérer la messagerie de contact ;
- de gérer les employés côté administrateur ;
- de consulter les statistiques administrateur avec MongoDB.

---

## Conclusion

L'environnement technique du projet repose sur Symfony, PHP, MySQL et MongoDB.

MySQL est utilisé comme base principale pour les données métier.

MongoDB est utilisé comme base non relationnelle pour les statistiques administrateur.

Les fichiers CSS, JavaScript, Twig et PHP sont organisés selon leur rôle afin de faciliter la maintenance du projet.

```md
---

## Configuration des emails

Le projet utilise le composant **Symfony Mailer** pour envoyer les emails automatiquement depuis l'application.

Les emails sont envoyés avec un service SMTP externe. Pour ce projet, le service utilisé est **Brevo**.

Brevo permet à Symfony d'envoyer les emails depuis l'application, y compris en environnement local.

### Emails envoyés par l'application

L'application peut envoyer plusieurs types d'emails :

- email de bienvenue après l'inscription d'un utilisateur ;
- email de réinitialisation du mot de passe ;
- email de confirmation de commande ;
- email invitant le client à laisser un avis lorsque la commande passe au statut terminée ;
- email de rappel de retour du matériel lorsque la commande contient du matériel prêté ;
- email envoyé à l'entreprise lorsqu'un visiteur utilise le formulaire de contact.

### Service SMTP utilisé

Le service SMTP utilisé est :

```text
Brevo
```

Le serveur SMTP utilisé est :

```text
smtp-relay.brevo.com
```

Le port utilisé est :

```text
587
```

### Variables d'environnement utilisées

La configuration des emails se fait dans le fichier :

```text
.env.local
```

Les variables utilisées sont :

```env
MAILER_DSN="smtp://IDENTIFIANT_SMTP:MOT_DE_PASSE_SMTP@smtp-relay.brevo.com:587"
MAILER_FROM="adresse-expeditrice@example.com"
ADMIN_EMAIL="adresse-reception@example.com"
```

### Rôle des variables

#### MAILER_DSN

`MAILER_DSN` contient les informations de connexion au serveur SMTP.

Elle permet à Symfony de savoir quel service utiliser pour envoyer les emails.

Dans ce projet, elle pointe vers le serveur SMTP de Brevo :

```text
smtp-relay.brevo.com
```

#### MAILER_FROM

`MAILER_FROM` définit l'adresse email utilisée comme expéditeur des emails envoyés par le site.

Cette adresse doit être validée dans Brevo avant de pouvoir être utilisée.

#### ADMIN_EMAIL

`ADMIN_EMAIL` définit l'adresse email qui reçoit les demandes envoyées depuis le formulaire de contact.

Dans ce projet, cette adresse correspond à l'adresse de l'entreprise.

### Sécurité

Le fichier `.env.local` contient des informations sensibles, notamment la clé SMTP Brevo.

Il ne doit jamais être envoyé sur GitHub.

Le fichier `.env.local` doit rester uniquement sur l'ordinateur local.

La clé SMTP doit être régénérée si elle a été partagée publiquement ou copiée dans un endroit non sécurisé.

### Fonctionnement général

Lorsqu'une action déclenche un email, Symfony utilise automatiquement la configuration définie dans `MAILER_DSN`.

Exemples :

- lorsqu'un utilisateur crée un compte, un email de bienvenue est envoyé ;
- lorsqu'un utilisateur demande une réinitialisation de mot de passe, un email contenant un lien sécurisé est envoyé ;
- lorsqu'une commande est validée, un email de confirmation est envoyé au client ;
- lorsqu'une commande passe au statut terminée, un email invite le client à laisser un avis ;
- lorsqu'une commande terminée contient du matériel prêté, un email rappelle les règles de retour du matériel ;
- lorsqu'un visiteur envoie un message de contact, un email est envoyé à l'entreprise.

### Test des emails

Pour tester l'envoi des emails, il faut d'abord vérifier que le site est lancé en local.

Commande de lancement du projet :

```powershell
& "C:\laragon\bin\php\php-8.4.12-nts-Win32-vs17-x64\php.exe" -S 127.0.0.1:8005 -t public public/router.php
```

Ensuite, il est possible de tester les emails en utilisant les fonctionnalités du site :

- créer un compte pour tester l'email de bienvenue ;
- utiliser la page mot de passe oublié pour tester l'email de réinitialisation ;
- passer une commande pour tester l'email de confirmation ;
- passer une commande au statut terminée pour tester l'email d'avis ;
- passer une commande avec matériel au statut terminée pour tester l'email de retour matériel ;
- envoyer un message depuis la page contact pour tester l'email envoyé à l'entreprise.

Il est aussi possible de tester rapidement la configuration mailer avec la commande Symfony suivante :

```powershell
& "C:\laragon\bin\php\php-8.4.12-nts-Win32-vs17-x64\php.exe" bin/console mailer:test adresse@example.com
```

Cette commande permet de vérifier que Symfony arrive bien à envoyer un email avec la configuration SMTP.
```