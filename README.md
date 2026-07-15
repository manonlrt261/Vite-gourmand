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

