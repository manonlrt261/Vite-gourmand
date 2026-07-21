# Documentation de gestion de projet

## Projet Vite & Gourmand

**Autrice :** Manon Fayolle  
**Année :** 2026  
**Version :** 2.0  
**Dernière mise à jour :** 20 juillet 2026  
**État du projet :** application développée et mise en ligne ; documentation finale en cours  
**Formation :** [À COMPLÉTER]  
**Titre professionnel préparé :** [À COMPLÉTER]  
**Établissement :** [À COMPLÉTER]  
**Session d’examen :** [À COMPLÉTER]

---

## Historique du document

| Version | Date | Auteur | Modification |
|---|---|---|---|
| 1.0 | 17/07/2026 | Manon Fayolle | Première rédaction fondée principalement sur le dépôt local |
| 2.0 | 20/07/2026 | Manon Fayolle | Réécriture complète à partir du tableau Notion réel, croisé avec Git et le code |

## Sommaire

1. [Introduction](#1-introduction)
2. [Contexte et objectifs](#2-contexte-et-objectifs)
3. [Sources et méthode de vérification](#3-sources-et-méthode-de-vérification)
4. [Organisation générale du projet](#4-organisation-générale-du-projet)
5. [Méthode de gestion choisie](#5-méthode-de-gestion-choisie)
6. [Outil de suivi Notion](#6-outil-de-suivi-notion)
7. [Découpage du projet](#7-découpage-du-projet)
8. [Planification](#8-planification)
9. [Estimation de la charge](#9-estimation-de-la-charge)
10. [Gestion des priorités](#10-gestion-des-priorités)
11. [Suivi de l’avancement](#11-suivi-de-lavancement)
12. [Chronologie du projet](#12-chronologie-du-projet)
13. [Gestion des besoins](#13-gestion-des-besoins)
14. [Périmètre fonctionnel](#14-périmètre-fonctionnel)
15. [Gestion des versions avec Git et GitHub](#15-gestion-des-versions-avec-git-et-github)
16. [Gestion des changements](#16-gestion-des-changements)
17. [Gestion de la qualité](#17-gestion-de-la-qualité)
18. [Organisation des tests](#18-organisation-des-tests)
19. [Gestion de la sécurité](#19-gestion-de-la-sécurité)
20. [Gestion de l’accessibilité](#20-gestion-de-laccessibilité)
21. [Gestion du déploiement](#21-gestion-du-déploiement)
22. [Gestion des risques](#22-gestion-des-risques)
23. [Difficultés et problèmes](#23-difficultés-et-problèmes)
24. [Prise de décision](#24-prise-de-décision)
25. [Gestion de la documentation](#25-gestion-de-la-documentation)
26. [Communication et traçabilité](#26-communication-et-traçabilité)
27. [Bilan de la gestion de projet](#27-bilan-de-la-gestion-de-projet)
28. [Améliorations possibles](#28-améliorations-possibles)
29. [Préparation de la présentation au jury](#29-préparation-de-la-présentation-au-jury)
30. [Conclusion](#30-conclusion)
31. [Annexes](#31-annexes)

---

# 1. Introduction

J’ai réalisé Vite & Gourmand dans le cadre de mon projet d’examen en développement web. Ce projet ne consistait pas uniquement à produire une application fonctionnelle. Je devais également organiser mon travail, définir des priorités, suivre l’avancement, conserver un historique des versions, tester les fonctionnalités et préparer les livrables attendus.

Cette documentation explique ma gestion du projet depuis l’analyse du sujet jusqu’à la mise en ligne. Elle présente la méthode que j’ai réellement suivie, les outils que j’ai utilisés, les phases de travail, les choix effectués et les améliorations que je pourrais apporter à mon organisation.

Le projet étant individuel, je n’ai pas appliqué une méthode Scrum complète avec une équipe, un Scrum Master et des cérémonies. J’ai adopté une organisation personnelle inspirée de l’agilité et du Kanban : j’ai découpé le travail en tâches dans Notion, attribué un statut, une priorité, une date et une durée estimée à chaque tâche, puis j’ai utilisé Git et GitHub pour conserver la progression du code.

# 2. Contexte et objectifs

## 2.1 Contexte

Vite & Gourmand est une application web destinée à un traiteur. Elle permet de présenter l’entreprise et ses menus, de recevoir les commandes des clients, de suivre leur traitement et de fournir des espaces de gestion adaptés aux employés et à l’administrateur.

Le projet s’adresse à quatre profils :

- le visiteur, qui découvre l’entreprise et consulte les menus ;
- le client, qui possède un compte et passe une commande ;
- l’employé, qui gère les commandes, les menus, les horaires, les avis et les messages ;
- l’administrateur, qui gère les employés et consulte les statistiques.

## 2.2 Problématique

La problématique retenue est la suivante :

> Comment concevoir et organiser le développement d’une application web accessible, sécurisée et administrable permettant à un traiteur de présenter ses prestations et de centraliser la gestion de ses commandes ?

## 2.3 Objectifs de gestion de projet

Pour mener le projet à son terme, je me suis fixé plusieurs objectifs d’organisation :

- analyser le sujet avant de commencer le développement ;
- transformer les besoins en tâches réalisables ;
- classer ces tâches par domaine et par priorité ;
- estimer le temps nécessaire ;
- suivre l’avancement dans un outil central ;
- versionner régulièrement le code ;
- traiter les sujets transversaux comme la sécurité et l’accessibilité ;
- organiser une phase de tests ;
- préparer et vérifier le déploiement ;
- produire les documents nécessaires pour l’examen.

# 3. Sources et méthode de vérification

J’ai construit cette documentation à partir de trois sources complémentaires.

La première source est ma base Notion [Vite&Gourmand](https://app.notion.com/p/6482621c5f4d42b1ad54ca0e19fc8a46). Elle constitue la source principale pour expliquer ma planification, mon découpage des tâches, mes priorités, mes estimations et mes statuts.

La deuxième source est le dépôt Git local et son dépôt distant GitHub. Git fournit une chronologie vérifiable des changements apportés au code. Au 20 juillet 2026, l’historique contient 30 commits entre le 9 et le 19 juillet.

La troisième source est le projet lui-même : contrôleurs Symfony, templates Twig, scripts JavaScript, styles, configuration, scripts SQL et documents existants. Elle permet de vérifier qu’une tâche marquée comme terminée dans Notion correspond bien à une réalisation visible.

J’ai appliqué les règles suivantes :

- une information présente dans Notion est présentée comme une information de suivi ;
- une information confirmée par Git ou le code est présentée comme vérifiée techniquement ;
- lorsqu’un statut Notion et le dépôt ne correspondent pas, je signale l’écart ;
- je n’invente aucune réunion, difficulté, date ou décision absente de mes traces ;
- les informations personnelles, mots de passe et secrets de configuration ne sont jamais reproduits.

# 4. Organisation générale du projet

J’ai organisé le travail en grandes phases successives, tout en gardant la possibilité de revenir sur une fonctionnalité pour la corriger.

```text
Analyse du sujet
        ↓
Planification dans Notion
        ↓
Wireframes et maquettes
        ↓
Environnement et bases de données
        ↓
Développement par profil utilisateur
        ↓
Sécurité, accessibilité et e-mails
        ↓
Tests et corrections
        ↓
Déploiement
        ↓
Documentation et préparation du jury
```

Ce fonctionnement est à la fois séquentiel et itératif. Les grandes phases ont été planifiées dans un ordre logique, mais les commits montrent que j’ai continué à améliorer les espaces client et employé après la mise en ligne. Je suis donc revenue sur certaines parties lorsqu’un test ou l’utilisation en ligne faisait apparaître un besoin de correction.

[SCHÉMA À INSÉRER : cycle réel de gestion du projet]

# 5. Méthode de gestion choisie

## 5.1 Une approche hybride

J’ai utilisé une approche hybride. La préparation du projet suit un découpage par grandes phases, proche d’une méthode en cascade : analyse, conception, environnement, développement, tests, déploiement et documentation.

Le suivi quotidien est toutefois plus proche d’un Kanban personnel. Chaque tâche possède un statut parmi « Pas commencé », « En cours » et « Terminé ». Je pouvais ainsi visualiser le travail restant et faire évoluer l’avancement au fur et à mesure.

Cette méthode était adaptée à un projet individuel. Une organisation Scrum complète aurait ajouté des rôles et des cérémonies peu pertinentes dans mon contexte. En revanche, le suivi visuel et la possibilité de réorganiser les tâches m’ont permis de conserver une démarche souple.

## 5.2 Principes appliqués

Ma méthode reposait sur les principes suivants :

- une tâche devait correspondre à une action identifiable ;
- les fonctionnalités importantes étaient classées en priorité haute ;
- chaque tâche possédait une estimation ;
- les tâches étaient regroupées par section ;
- les statuts devaient refléter l’avancement ;
- les modifications du code étaient sauvegardées dans Git ;
- les tests et le déploiement étaient considérés comme des phases du projet, et non comme des actions extérieures au développement.

# 6. Outil de suivi Notion

## 6.1 Structure du tableau

J’ai créé une base Notion spécialement consacrée à Vite & Gourmand. Elle contient actuellement **215 tâches**. Chaque ligne correspond à une tâche et comporte les propriétés suivantes :

| Propriété | Utilisation |
|---|---|
| Tâche | Décrit l’action à réaliser |
| Statut | Pas commencé, En cours ou Terminé |
| Date | Jour ou période planifiée |
| Durée en minutes | Temps estimé pour la tâche |
| Priorité | Haute, Moyenne ou Basse |
| Section | Domaine fonctionnel ou phase du projet |

Le tableau couvre la période du **22 juin au 23 juillet 2026**. Le total des estimations enregistrées est de **7 862 minutes**, soit environ **131 heures**. Cette valeur représente une charge prévisionnelle et non un relevé exact du temps réellement passé.

## 6.2 État du tableau au 20 juillet 2026

| Statut | Nombre de tâches | Part du total |
|---|---:|---:|
| Terminé | 203 | 94,4 % |
| En cours | 1 | 0,5 % |
| Pas commencé | 11 | 5,1 % |
| **Total** | **215** | **100 %** |

La seule tâche encore marquée « En cours » concerne l’interdiction de créer un nouvel administrateur depuis l’application. Les onze tâches non commencées concernent principalement les livrables documentaires et une sauvegarde Git propre.

[CAPTURE À INSÉRER : vue générale de la base Notion Vite&Gourmand]

[CAPTURE À INSÉRER : tableau Notion groupé par statut]

## 6.3 Utilité de Notion

Notion m’a permis de centraliser les tâches dans un seul endroit. Sans cet outil, j’aurais dû conserver plusieurs listes indépendantes et j’aurais eu plus de difficulté à visualiser le travail restant.

La tâche [Créer un outil de suivi](https://app.notion.com/p/f4a72c713f3e4f6db4b671cfad862655) était planifiée le 22 juin, en priorité haute, avec une estimation de 150 minutes. La tâche [Découper le projet en tâches](https://app.notion.com/p/885db728fb8d4eba9fa8969717751025) a été réalisée le même jour. Ces deux actions montrent que l’organisation a été mise en place avant le développement principal.

# 7. Découpage du projet

J’ai découpé le projet en sections correspondant aux grandes phases et aux principaux profils.

| Section Notion | Nombre de tâches |
|---|---:|
| Analyse du sujet | 4 |
| Gestion de projet | 4 |
| Maquettes | 6 |
| Charte graphique | 6 |
| Environnement | 8 |
| Git / GitHub | 6 tâches propres, plus 3 tâches partagées |
| PhpMyAdmin / MySQL | 11 |
| NoSQL / MongoDB | 4 |
| Visiteur | 27 |
| Authentification | 19 |
| Client | 26 |
| Employé | 25 |
| Administrateur | 10 |
| Sécurité | 8 |
| Accessibilité | 8 |
| E-mails | 7 |
| Tests | 16 |
| Déploiement | 6 tâches propres, plus 3 tâches partagées |
| Documentation | 10 tâches propres, plus 2 tâches partagées |

Certaines tâches appartiennent à plusieurs sections. Par exemple, la fusion de `develop` vers `main` relève à la fois de Git et du déploiement. Pour cette raison, la somme des sections est supérieure au nombre total de lignes.

Le découpage par profil m’a aidée à traiter les besoins de manière cohérente. J’ai d’abord préparé les éléments publics, puis l’authentification, le parcours client, l’espace employé et enfin l’administration.

# 8. Planification

## 8.1 Planning prévisionnel

Le planning commence le 22 juin par l’analyse du sujet et la mise en place de l’outil de suivi. Les maquettes et la charte graphique occupent la fin du mois de juin. L’environnement, Git et les bases sont planifiés entre le 30 juin et le 2 juillet. Le développement fonctionnel s’étend ensuite du 2 au 13 juillet. Les sujets transversaux, les tests et le déploiement sont concentrés du 15 au 17 juillet. La documentation finale commence le 20 juillet.

| Période | Phase planifiée |
|---|---|
| 22 juin | Analyse et organisation du projet |
| 23 au 29 juin | Wireframes, maquettes et charte graphique |
| 30 juin au 1er juillet | Environnement Symfony et organisation du projet |
| 1er juillet | Git, GitHub et README initial |
| 2 juillet | MySQL, MongoDB et données |
| 2 au 6 juillet | Pages publiques et menus |
| 7 juillet | Authentification |
| 8 au 9 juillet | Panier, commande et espace client |
| 9 au 10 juillet | Espace employé |
| 13 juillet | Espace administrateur |
| 15 juillet | Sécurité et accessibilité |
| 16 juillet | E-mails transactionnels |
| 17 juillet | Tests et déploiement |
| 20 au 23 juillet | Documentation et livrables |

[CAPTURE À INSÉRER : calendrier ou chronologie Notion]

## 8.2 Différence entre prévision et réalisation

Le premier commit Git date du 9 juillet, alors que Notion indique que l’analyse, les maquettes, l’environnement et une partie du développement avaient commencé avant cette date. Git ne représente donc pas le début complet du travail ; il représente le début de la chronologie versionnée accessible.

Après le déploiement planifié le 17 juillet, onze commits supplémentaires ont été réalisés le 19 juillet. Cette situation montre que le projet a continué à évoluer après la première mise en ligne. Les corrections en production et les améliorations fonctionnelles ont donc dépassé le planning initial.

# 9. Estimation de la charge

J’ai renseigné une durée estimée pour chaque tâche. Les tâches courtes, comme ajouter un lien ou modifier un statut, étaient évaluées entre 5 et 20 minutes. Les tâches plus importantes, comme la création de l’espace employé, les maquettes ou les tests responsive, recevaient une estimation plus élevée.

La création des maquettes est la tâche la plus importante du planning, avec 2 160 minutes réparties du 24 au 29 juin. La création de l’outil de suivi, les templates Twig, l’organisation des fichiers CSS/JavaScript et plusieurs tâches de conception ont également reçu des estimations importantes.

Cette estimation m’a aidée à visualiser la charge et à placer les tâches dans le calendrier. Cependant, je n’ai pas enregistré le temps réellement passé. Je ne peux donc pas calculer précisément les écarts entre estimation et réalisation.

Pour un futur projet, j’ajouterais une propriété « Durée réelle » et une propriété « Écart ». Je pourrais ainsi repérer les tâches sous-estimées et améliorer progressivement ma capacité d’estimation.

# 10. Gestion des priorités

La base Notion contient **195 tâches de priorité haute** et **20 tâches de priorité moyenne**. Aucune tâche n’est actuellement classée en priorité basse.

J’ai placé en priorité haute les éléments nécessaires au fonctionnement du projet ou à la conformité au sujet : environnement, bases, parcours de commande, espaces utilisateur, sécurité, tests essentiels, déploiement et livrables.

Les priorités moyennes concernent principalement l’accessibilité, les e-mails et quelques fonctionnalités complémentaires comme les fermetures exceptionnelles. Cela ne signifie pas que ces sujets étaient inutiles ; cela indique qu’ils intervenaient après la mise en place des fonctions métier principales.

Cette organisation m’a aidée à protéger le périmètre essentiel. Cependant, le nombre très élevé de tâches classées « Haute » réduit l’efficacité de la priorisation. Dans un prochain projet, j’utiliserais une méthode plus discriminante, par exemple MoSCoW :

- **Must have** : indispensable pour l’examen ou le fonctionnement ;
- **Should have** : important, mais contournable temporairement ;
- **Could have** : amélioration si le temps le permet ;
- **Won’t have for now** : explicitement hors périmètre.

# 11. Suivi de l’avancement

J’ai suivi l’avancement à l’aide des trois statuts de Notion. La tâche [Suivre l’avancement](https://app.notion.com/p/54ce400755dc4eb7b4591ec6b5da78a2) couvre la période du 22 juin au 23 juillet et est marquée terminée.

La progression actuelle de 203 tâches terminées sur 215 montre que l’essentiel du développement et du déploiement a été traité. Le travail restant est principalement documentaire.

Le tableau révèle toutefois deux points à surveiller :

- la création d’un nouvel administrateur est encore marquée « En cours » ;
- certaines tâches documentaires sont « Pas commencé » alors que des fichiers existent déjà dans le dépôt. Le tableau doit donc être mis à jour après validation de chaque livrable.

Notion donne une vision déclarative du travail. Git et le code permettent de vérifier cette déclaration. Cette double lecture est importante : une tâche peut être terminée dans Notion sans que la branche ou le document correspondant soit à jour dans le dépôt.

# 12. Chronologie du projet

## 12.1 Chronologie issue de Notion

La chronologie Notion commence le 22 juin et s’achève le 23 juillet. Elle montre une préparation en amont du développement : analyse, maquettes, charte graphique et configuration.

## 12.2 Chronologie Git

Git contient 30 commits entre le 9 et le 19 juillet 2026.

| Date | Nombre de commits | Éléments principaux |
|---|---:|---|
| 9 juillet | 1 | Initialisation du projet |
| 13 juillet | 1 | MongoDB et finalisation du panier |
| 15 juillet | 2 | Pages et e-mails transactionnels |
| 16 juillet | 2 | RGAA, sécurité et première phase de tests |
| 17 juillet | 8 | Matériel, base, README et préparation du déploiement |
| 18 juillet | 5 | Production Alwaysdata et corrections après mise en ligne |
| 19 juillet | 11 | Améliorations clients, employés, commandes, graphiques et horaires |

Les commits les plus significatifs pour la gestion du projet sont notamment :

- `ddca5cd` — initialisation du projet ;
- `d65805f` — ajout de MongoDB et finalisation du panier ;
- `7fac0cf` — ajout des e-mails transactionnels ;
- `3cb6164` — modifications RGAA et sécurité ;
- `e44dec2` — première phase de tests ;
- `4e7086f` — sauvegarde avant déploiement ;
- `64a5987` et `86e379d` — configuration de production et MySQL Alwaysdata ;
- `279154d` — corrections après mise en ligne ;
- `783a114` — fermetures exceptionnelles.

# 13. Gestion des besoins

J’ai commencé par analyser le sujet, identifier les profils et recenser les grandes fonctionnalités. Ces actions apparaissent dans les quatre premières tâches Notion et sont toutes terminées.

J’ai ensuite transformé les besoins en tâches concrètes. Par exemple, le besoin « permettre au client de commander » a été divisé en plusieurs étapes : ajouter un menu au panier, modifier le nombre de personnes, contrôler le minimum, calculer le prix, appliquer la réduction, saisir les informations de prestation, calculer la livraison, enregistrer la commande, créer son statut et envoyer une confirmation.

Cette décomposition m’a évité de traiter une fonctionnalité importante comme une seule tâche trop vague. Elle m’a également permis de vérifier séparément les règles métier.

| Profil | Besoins principaux pris en charge |
|---|---|
| Visiteur | Accueil, menus, filtres, détails, avis et contact |
| Client | Inscription, connexion, panier, commande, profil, suivi et avis |
| Employé | Commandes, catalogue, horaires, fermetures, avis et messages |
| Administrateur | Employés, statistiques et fonctions employé |

# 14. Périmètre fonctionnel

Le périmètre réalisé comprend :

- un site vitrine ;
- un catalogue de menus filtrable ;
- un formulaire de contact ;
- l’inscription, la connexion et la réinitialisation du mot de passe ;
- un panier et un parcours de commande ;
- un calcul de prix, de remise et de livraison ;
- un espace client ;
- un espace employé ;
- un espace administrateur ;
- la gestion des menus, plats, horaires, avis, messages et employés ;
- des statistiques MongoDB ;
- des e-mails transactionnels ;
- une mise en ligne sur Alwaysdata, confirmée par les commits de production.

Le paiement en ligne, les SMS, l’application mobile native et la facturation complète ne sont pas présents. Ils ne faisaient pas partie du périmètre final réalisé.

# 15. Gestion des versions avec Git et GitHub

## 15.1 Organisation prévue

Dans Notion, j’ai prévu l’initialisation de Git, la création d’un dépôt GitHub public, une branche principale, une branche de développement, une branche par fonctionnalité et des commits réguliers.

Le dépôt contient effectivement :

- `main` ;
- `develop` ;
- 17 branches locales `feature-*` ;
- leurs équivalents distants ;
- un dépôt distant GitHub public.

[CAPTURE À INSÉRER : branches Git et dépôt GitHub]

## 15.2 Fonctionnement réellement observé

L’historique actuel est principalement linéaire sur `develop`. Les branches fonctionnelles et `main` pointent encore vers le commit `22f9eac` du 15 juillet. Aucun commit de fusion n’apparaît dans l’historique.

Il existe donc un écart entre le suivi Notion et Git : la tâche [Fusionner développement vers main quand le projet est stable](https://app.notion.com/p/05b3059ff9494f338550461cfbc0c2aa) est marquée terminée, mais la branche `main` locale et distante n’intègre pas les 21 commits plus récents présents dans `develop`.

Je dois corriger le statut Notion ou effectuer la fusion après une dernière validation. Je ne dois pas présenter cette fusion comme techniquement réalisée tant que Git ne la confirme pas.

## 15.3 Qualité des commits

Les 30 commits conservent les principales étapes du développement. Leur fréquence augmente autour de la mise en ligne, ce qui reflète les corrections et ajustements réalisés en production.

Certains messages sont précis, comme « Correction de la configuration MySQL Alwaysdata ». D’autres sont plus génériques, comme « database », « changement readme » ou « Carte commandes clients ». Pour améliorer la traçabilité, je pourrais utiliser une convention du type :

```text
feat: ajouter les fermetures exceptionnelles
fix: corriger la connexion MySQL en production
docs: mettre à jour le guide d’installation
test: valider le parcours de commande
```

# 16. Gestion des changements

Le projet a continué à évoluer après la première mise en ligne. Les changements visibles dans Git montrent que le déploiement a servi de nouvelle étape de validation.

| Changement | Trace | Effet sur le projet |
|---|---|---|
| Ajout de MongoDB | Commit du 13 juillet | Statistiques non relationnelles |
| Ajout des e-mails | Commit du 15 juillet | Information des clients et de l’entreprise |
| Renforcement RGAA et sécurité | Commit du 16 juillet | Amélioration transverse |
| Préparation du déploiement | Commits du 17 juillet | Assets publics et redirection Apache |
| Correction MySQL Alwaysdata | Commit du 18 juillet | Fonctionnement de la base en production |
| Corrections après mise en ligne | Commit du 18 juillet | Stabilisation de l’application en ligne |
| Amélioration des espaces métier | Commits du 19 juillet | Détails des commandes, graphiques et horaires |

Je n’ai pas utilisé de registre formel de demandes de changement. Les commits jouent donc le rôle principal de journal technique. Pour un prochain projet, j’ajouterais dans Notion une propriété « Origine du changement » et une propriété « Décision associée ».

# 17. Gestion de la qualité

J’ai prévu des phases distinctes pour la sécurité, l’accessibilité et les tests. Cette organisation m’a permis de ne pas limiter la qualité à la seule vérification visuelle des pages.

Les tâches Notion couvrent notamment :

- la validation des données côté serveur ;
- la protection des formulaires ;
- les rôles et les espaces privés ;
- la prévention des injections SQL ;
- les textes alternatifs ;
- la hiérarchie des titres ;
- les contrastes ;
- la navigation au clavier ;
- le responsive ;
- les principaux parcours utilisateurs.

La tâche « Corriger les bugs » est marquée terminée avec une estimation de 60 minutes. Git montre également des commits de correction après la mise en ligne.

La qualité reste cependant perfectible : aucun test automatisé métier n’est présent dans le dossier `tests`, aucun rapport d’audit complet n’est disponible et aucune intégration continue ne vérifie automatiquement les nouvelles versions.

# 18. Organisation des tests

## 18.1 Tests planifiés dans Notion

La section Tests contient 16 tâches. Elles sont toutes terminées sauf « Faire une sauvegarde Git propre ».

| Test | Statut Notion |
|---|---|
| Responsive desktop/mobile | Terminé |
| Accessibilité | Terminé |
| Parcours visiteur | Terminé |
| Inscription et connexion | Terminé |
| Commande complète | Terminé |
| Espace client | Terminé |
| Espace employé | Terminé |
| Espace administrateur | Terminé |
| Filtres dynamiques | Terminé |
| E-mails | Terminé |
| Sécurité des accès | Terminé |
| Correction des bugs | Terminé |
| Sauvegarde Git propre | Pas commencé |

La tâche [Tester la sécurité des accès](https://app.notion.com/p/811abc6ca999451fa58b19b03eccba32) est planifiée le 17 juillet, en priorité haute, avec une estimation de 20 minutes.

## 18.2 Limites de la preuve

Notion confirme que j’ai déclaré ces tests comme terminés, et les commits de correction renforcent cette trace. En revanche, aucun cahier de recette détaillant les données d’entrée, le résultat attendu, le résultat obtenu et les anomalies n’est présent dans le dépôt.

PHPUnit est configuré, mais aucun fichier de test automatisé métier n’est disponible. Les tests réalisés sont donc principalement des tests manuels.

Avant le jury, je dois créer un tableau de recette récapitulatif et conserver quelques captures des résultats.

[CAPTURE À INSÉRER : plan de tests ou cahier de recette]

# 19. Gestion de la sécurité

La sécurité a fait l’objet d’une section spécifique de huit tâches, toutes marquées terminées le 15 juillet. J’ai planifié le hachage des mots de passe, la protection des routes, l’interdiction d’accès aux espaces privés, la validation serveur, la protection des formulaires, la prévention des injections SQL, les règles liées aux données personnelles et la protection des actions sensibles.

La tâche [Hacher les mots de passe](https://app.notion.com/p/f82ccdb390424bd49d7cef537626475e) est classée en priorité haute et terminée. Le code confirme l’utilisation de `password_hash` pour les nouveaux mots de passe et de requêtes SQL paramétrées pour de nombreux accès aux données.

La sécurité est néanmoins mise en œuvre principalement par des contrôles personnalisés dans les contrôleurs. Le système complet de Symfony Security est peu exploité. Cette organisation fonctionne dans la version actuelle, mais elle augmente le risque de répétition ou d’oubli. Une évolution recommandée serait de centraliser l’authentification et les autorisations avec les mécanismes du framework.

# 20. Gestion de l’accessibilité

J’ai créé huit tâches d’accessibilité, toutes terminées le 15 juillet : textes alternatifs, structure des titres, contrastes, boutons compréhensibles, navigation clavier, labels de formulaires, taille des textes et test des pages principales.

Le commit `3cb6164`, intitulé « Modifications RGAA et sécurité », confirme qu’une phase de modification du code a été consacrée à ces sujets.

Cette démarche montre que l’accessibilité a été intégrée au projet avant le déploiement. Je ne peux cependant pas affirmer une conformité RGAA complète sans audit formalisé. Je présente donc cette phase comme une amélioration et une vérification manuelle, et non comme une certification.

## 20.1 Tableau de contrôle des pages principales

Le contrôle ci-dessous a été renouvelé le 21 juillet 2026 sur les principales pages publiques du site déployé. Il combine l’inspection du HTML et des feuilles de style avec un affichage à 1 280 px et à 640 px. Il s’agit d’un contrôle de premier niveau et non d’un audit exhaustif des 106 critères du RGAA.

Légende : **OK** = aucun écart détecté sur le contrôle réalisé ; **Partiel** = disposition présente dans le code, mais parcours manuel complet restant à effectuer ; **NC** = écart constaté ; **N/A** = contrôle non applicable à l’état observé ; **À vérifier** = mesure spécialisée ou vérification visuelle complémentaire nécessaire.

| Page contrôlée | Navigation au clavier | Focus visible | Textes alternatifs | Structure des titres | Labels des champs | Messages d’erreur accessibles | Contrastes | Zoom à 200 % | Affichage mobile |
|---|---|---|---|---|---|---|---|---|---|
| Accueil (`/`) | Partiel | OK | OK | OK | N/A | N/A | À vérifier | OK | OK |
| Liste des menus (`/menus`) | Partiel | OK | OK | OK | OK | N/A | À vérifier | OK | OK |
| Contact (`/contact`) | Partiel | OK | OK | **NC** | OK | **NC** | À vérifier | OK | OK |
| Connexion (`/connexion`) | Partiel | OK | OK | **NC** | OK | **NC** | À vérifier | OK | OK |
| Inscription (`/inscription`) | Partiel | OK | OK | **NC** | OK | **NC** | À vérifier | OK | OK |
| Panier vide (`/panier`) | Partiel | OK | OK | OK | N/A | N/A | À vérifier | OK | OK |

## 20.2 Résultats et limites du contrôle

- **Navigation au clavier :** les éléments interactifs utilisent des éléments HTML natifs (`a`, `button`, `input`, `select` et `textarea`). Le contrôle reste classé « Partiel », car l’ordre complet de tabulation, les menus dynamiques, les modales et tous les parcours authentifiés n’ont pas été testés manuellement jusqu’à leur terme.
- **Visibilité du focus :** une règle globale `:focus-visible` ajoute un contour de 3 px et une ombre sur les liens, boutons et champs. Aucun style global ne supprime ce repère.
- **Textes alternatifs :** aucune image sans attribut `alt` n’a été relevée sur les six pages contrôlées. Les images de contenu possèdent un texte alternatif et les éléments décoratifs identifiés sont masqués aux technologies d’assistance.
- **Structure des titres :** l’accueil, la liste des menus et le panier vide possèdent un seul `h1` et une hiérarchie cohérente. Contact, Connexion et Inscription possèdent chacune deux `h1` : celui du bandeau commun et celui du contenu. Le second devrait devenir un `h2`, ou le bandeau devrait être rendu sans nouveau `h1`.
- **Labels des champs :** tous les champs visibles de l’échantillon possèdent un `label` associé ou un nom accessible. Les champs cachés de protection CSRF ne nécessitent pas de label.
- **Messages d’erreur :** le panier prévoit une zone `role="alert"`, mais les alertes de Contact, Connexion et Inscription sont de simples paragraphes ou `div`. Elles doivent recevoir `role="alert"` ou être placées dans une zone `aria-live`, et être reliées aux champs concernés lorsque l’erreur porte sur un champ précis.
- **Contrastes :** les couleurs et les voiles de fond montrent une prise en compte de la lisibilité, mais les rapports de contraste n’ont pas été mesurés pour chaque combinaison texte/fond et chaque état de composant. Ce point ne peut donc pas être déclaré conforme.
- **Zoom à 200 % et affichage mobile :** aucun débordement horizontal n’a été détecté sur les pages contrôlées à 640 px de large, largeur utilisée comme approximation d’un affichage à 200 % depuis 1 280 px. Une vérification visuelle à 200 % dans le navigateur et sur plusieurs appareils reste recommandée, notamment pour détecter les contenus masqués ou superposés.

Les résultats « OK » portent uniquement sur les pages, états et dimensions indiqués. Ils ne constituent ni une déclaration d’accessibilité, ni une preuve de conformité RGAA complète. Un audit formalisé devra aussi couvrir les espaces client, employé et administrateur, les contenus dynamiques, les erreurs de formulaire déclenchées, les modales, les tableaux, ainsi que des tests avec lecteur d’écran.

# 21. Gestion du déploiement

## 21.1 Préparation

Dans Notion, j’ai découpé le déploiement en plusieurs tâches : choisir la plateforme, configurer la production, déployer l’application, configurer les bases relationnelle et non relationnelle, fusionner les versions, vérifier le fonctionnement en ligne et ajouter le lien aux livrables.

Ces tâches sont toutes marquées terminées le 17 juillet. La tâche [Vérifier que l’application fonctionne en ligne](https://app.notion.com/p/bfce4fc9a72743b8b9d9598930eaba06) est classée en priorité haute.

## 21.2 Preuves Git

Le déploiement est confirmé par plusieurs commits :

- ajout des assets publics ;
- ajout de la redirection Apache vers Symfony ;
- ajout de la configuration Doctrine de production ;
- correction de la configuration MySQL Alwaysdata ;
- corrections finales après mise en ligne.

La plateforme utilisée est donc **Alwaysdata** pour l’application et MySQL. La configuration MongoDB de production est indiquée comme terminée dans Notion ; sa solution d’hébergement et son URL ne sont pas reproduites pour ne pas exposer d’informations sensibles.

L’adresse publique de l’application doit être ajoutée ici après vérification : **[À COMPLÉTER : URL publique de Vite & Gourmand]**.

## 21.3 Enseignement du déploiement

Le déploiement n’a pas été une simple dernière étape. Les commits des 18 et 19 juillet montrent qu’il a révélé des problèmes de configuration et des besoins d’amélioration. Il a donc servi de phase de test dans un environnement différent du poste local.

# 22. Gestion des risques

Je n’avais pas créé de registre formel des risques dans Notion. Le tableau suivant constitue une formalisation a posteriori à partir des situations réellement visibles.

| Risque | Impact | Mesure appliquée ou recommandée | État |
|---|---|---|---|
| Retard sur les livrables | Jury incomplet | Réserver la période du 20 au 23 juillet à la documentation | En cours |
| Écart entre Notion et Git | Traçabilité incorrecte | Croiser les statuts avec les branches et commits | Constaté |
| Erreur de configuration en production | Application indisponible | Commits dédiés à Alwaysdata et tests en ligne | Survenu et corrigé |
| Régression après correction | Fonction existante cassée | Rejouer les parcours essentiels | Risque actuel |
| Absence de tests automatisés | Contrôle manuel coûteux | Ajouter des tests Symfony/PHPUnit | Non traité |
| Mauvaise protection des secrets | Compromission de services | Exclure `.env.local` et ne pas publier les clés | Mesure présente |
| Dépendance aux services externes | Statistiques ou e-mails indisponibles | Gérer les erreurs et documenter les services | Partiellement traité |
| Trop de tâches prioritaires | Arbitrage difficile | Utiliser MoSCoW ou limiter les priorités hautes | Constaté |
| Développement concentré sur quelques jours | Fatigue et défauts | Prévoir des marges et tester plus tôt | Constaté |
| Branche `main` non actualisée | Livraison d’une ancienne version | Valider puis fusionner `develop` | À traiter |

# 23. Difficultés et problèmes

Les difficultés suivantes sont visibles dans les traces, sans inventer mon ressenti personnel :

| Difficulté | Preuve | Réponse apportée |
|---|---|---|
| Configuration MySQL de production | Commit du 18 juillet | Correction spécifique Alwaysdata |
| Envoi des e-mails | Commit « Correction de l’envoi des emails » | Ajustement après mise en ligne |
| Corrections post-déploiement | Commit dédié | Stabilisation de la version en ligne |
| Ajustements des espaces client et employé | Plusieurs commits du 19 juillet | Amélioration des parcours métier |
| Suivi Notion/Git non aligné | Fusion marquée terminée mais absente de Git | À corriger avant livraison |
| Tests uniquement manuels | Dossier de tests sans scénarios métier | Cahier de recette et automatisation recommandés |

Je dois compléter cette partie avec mon propre retour : **[À COMPLÉTER : difficultés personnellement rencontrées, solutions essayées et apprentissages]**.

# 24. Prise de décision

Les décisions techniques peuvent être prouvées par le projet. Leurs alternatives exactes n’ont pas toutes été consignées au moment du choix.

| Décision | Justification | Impact |
|---|---|---|
| Utiliser Symfony et PHP | Structurer une application web complète | Organisation en contrôleurs, services et templates |
| Utiliser MySQL | Gérer les relations métier | Scripts SQL et données relationnelles |
| Utiliser MongoDB | Répondre au besoin de base non relationnelle et de statistiques | Service de synchronisation des statistiques |
| Utiliser Notion | Centraliser et planifier 215 tâches | Suivi daté, priorisé et estimé |
| Utiliser GitHub | Sauvegarder et rendre le dépôt accessible | Dépôt distant public |
| Utiliser une approche hybride | S’adapter à un projet individuel | Phases planifiées et suivi de type Kanban |
| Déployer sur Alwaysdata | Héberger Symfony et MySQL | Configuration de production spécifique |
| Utiliser un SMTP externe | Envoyer les e-mails transactionnels | Configuration sensible hors du dépôt |

Pour améliorer la traçabilité, je pourrais ajouter dans Notion une base « Journal des décisions » avec la date, le contexte, les options, le choix, la justification et les conséquences.

# 25. Gestion de la documentation

La documentation constitue la dernière grande phase du planning. Plusieurs tâches sont encore marquées « Pas commencé » au 20 juillet.

| Livrable | Statut Notion | État observé dans le dépôt |
|---|---|---|
| README avec installation locale | Pas commencé | Fichier présent, mais à finaliser |
| Documentation technique | Pas commencé | Fichier présent et modifié localement |
| Documentation de gestion de projet | Pas commencé | Présent document en cours de rédaction |
| Manuel utilisateur PDF | Pas commencé | Non détecté dans le dépôt |
| Charte graphique PDF | Pas commencé | Non détectée dans le dépôt |
| Fichiers SQL | Pas commencé | Deux fichiers présents dans `database/` |
| Lien GitHub public | Pas commencé | Dépôt confirmé |
| Lien de l’application | Pas commencé | Déploiement confirmé, URL à renseigner |
| Lien de l’outil de gestion | Pas commencé | Base Notion confirmée |

Ce tableau montre l’importance de mettre Notion à jour lorsque le livrable existe réellement. Un statut obsolète peut donner une image fausse de l’avancement.

# 26. Communication et traçabilité

Le projet étant individuel, je n’avais pas besoin d’organiser des réunions quotidiennes ou de répartir les tâches entre plusieurs développeurs. J’ai remplacé cette communication d’équipe par une traçabilité écrite.

Notion conserve ce que je prévoyais de faire. Git conserve les changements apportés au code. Les documents expliquent le fonctionnement et les choix. Ces trois niveaux sont complémentaires :

```text
Notion → travail prévu et statut déclaré
Git → progression technique datée
Documentation → explication et prise de recul
```

Les éventuels échanges avec les formateurs ne sont pas présents dans les sources analysées. Je dois les ajouter uniquement s’ils ont réellement influencé une décision : **[À COMPLÉTER : retour reçu, date et correction effectuée]**.

# 27. Bilan de la gestion de projet

## 27.1 Points positifs

J’ai commencé par analyser le sujet et créer un outil de suivi. Le projet a été découpé en 215 tâches, ce qui m’a permis de couvrir l’ensemble du cycle : conception, développement, qualité, déploiement et documentation.

La progression de 203 tâches terminées montre que le suivi a accompagné un projet largement réalisé. Les sections par profil sont cohérentes avec l’organisation du code. Les commits apportent une trace concrète des principales étapes et des corrections en production.

J’ai également réservé des sections distinctes à la sécurité, à l’accessibilité, aux e-mails et aux tests. Ces sujets n’ont donc pas été complètement oubliés au profit des seules pages visibles.

## 27.2 Limites

Mon suivi comporte plusieurs limites :

- presque toutes les tâches sont en priorité haute ;
- aucune durée réelle n’est enregistrée ;
- certaines tâches terminées dans les faits restent « Pas commencé » ;
- la fusion vers `main` est déclarée terminée mais n’est pas visible dans Git ;
- aucun registre formel des risques, décisions ou anomalies n’a été utilisé ;
- les tests ne disposent pas d’un compte rendu détaillé ;
- le développement Git est très concentré entre le 17 et le 19 juillet ;
- les branches fonctionnelles existent mais ne montrent pas un véritable cycle de fusion.

## 27.3 Compétences mobilisées

La gestion du projet m’a permis de développer des compétences en analyse du besoin, planification, estimation, priorisation, organisation personnelle, suivi d’avancement, versionnement, test, déploiement, documentation et résolution de problèmes.

# 28. Améliorations possibles

Pour un futur projet, je mettrais en place les améliorations suivantes :

1. ajouter une définition de terminé commune à toutes les tâches ;
2. enregistrer la durée réelle et l’écart avec l’estimation ;
3. limiter les tâches de priorité haute ;
4. créer des jalons visibles dans Notion ;
5. ajouter les statuts « À vérifier » et « Bloqué » ;
6. tenir un registre des décisions, risques et anomalies ;
7. utiliser réellement une branche par fonctionnalité ;
8. ouvrir des pull requests avant les fusions ;
9. adopter une convention de commits ;
10. ajouter des tests automatisés dès le début ;
11. mettre en place une intégration continue ;
12. déployer une première version plus tôt ;
13. mettre à jour les livrables au fur et à mesure ;
14. vérifier chaque soir la cohérence entre Notion, Git et l’état réel du code.

Ces pratiques n’ont pas toutes été appliquées pendant Vite & Gourmand. Elles constituent mon retour d’expérience et les pistes d’amélioration que je retiens.

# 29. Préparation de la présentation au jury

Pour présenter ma gestion de projet au jury, je suivrai le scénario suivant :

1. rappeler brièvement le besoin de Vite & Gourmand ;
2. expliquer pourquoi j’ai choisi une approche hybride ;
3. montrer la base Notion et ses propriétés ;
4. présenter le découpage en 215 tâches ;
5. montrer la chronologie du 22 juin au 23 juillet ;
6. expliquer les priorités et les estimations ;
7. comparer l’avancement Notion avec les 30 commits Git ;
8. présenter une évolution importante, par exemple le déploiement ;
9. expliquer les tests et les corrections après mise en ligne ;
10. présenter honnêtement les limites et les améliorations.

Les captures à préparer sont :

- la vue générale de Notion ;
- le tableau groupé par statut ;
- le calendrier Notion ;
- le détail d’une tâche ;
- le graphe Git ;
- la liste des branches ;
- la page du dépôt GitHub ;
- une preuve de l’application en ligne ;
- le cahier de recette final.

# 30. Conclusion

La gestion de Vite & Gourmand s’est appuyée sur une démarche structurée. J’ai commencé par analyser le sujet, puis j’ai créé une base Notion afin de transformer les besoins en tâches planifiées, estimées et priorisées. Cette organisation m’a donné une vision globale du projet et m’a aidée à suivre les différentes phases.

Git et GitHub ont complété ce suivi en conservant l’évolution du code. Les 30 commits montrent la progression du développement, la préparation du déploiement et les corrections effectuées après la mise en ligne.

Le bilan est positif : 203 tâches sur 215 sont terminées et l’application a été développée puis déployée. L’analyse met aussi en évidence des améliorations nécessaires, notamment l’alignement entre Notion et Git, la mise à jour de `main`, la formalisation des tests et la finalisation des livrables.

Ce projet m’a montré qu’une bonne gestion ne se limite pas à créer une liste au début. Elle nécessite de maintenir les statuts, conserver des preuves, comparer le prévu au réel et ajuster l’organisation jusqu’à la livraison finale.

# 31. Annexes

## Annexe 1 – Indicateurs clés

| Indicateur | Valeur au 20/07/2026 |
|---|---:|
| Tâches Notion | 215 |
| Tâches terminées | 203 |
| Tâches en cours | 1 |
| Tâches non commencées | 11 |
| Charge totale estimée | 7 862 minutes, soit environ 131 heures |
| Tâches de priorité haute | 195 |
| Tâches de priorité moyenne | 20 |
| Période planifiée | 22/06/2026 au 23/07/2026 |
| Commits Git | 30 |
| Période Git | 09/07/2026 au 19/07/2026 |
| Branches locales | `main`, `develop` et 17 branches `feature-*` |
| Tags Git | 0 |
| Fusions visibles | 0 |

## Annexe 2 – Liens

| Ressource | Lien |
|---|---|
| Tableau Notion | [Vite&Gourmand](https://app.notion.com/p/6482621c5f4d42b1ad54ca0e19fc8a46) |
| Dépôt GitHub | [manonlrt261/Vite-gourmand](https://github.com/manonlrt261/Vite-gourmand) |
| Application déployée | [À COMPLÉTER] |
| Maquettes | [À COMPLÉTER : lien Figma ou emplacement du PDF] |
| Documentation technique | `docs/documentation-technique.md` |
| README | `README.md` |

## Annexe 3 – Tâches restant à traiter

| Tâche | Statut Notion |
|---|---|
| Empêcher la création d’un nouvel administrateur depuis l’application | En cours |
| Faire une sauvegarde Git propre | Pas commencé |
| README avec installation locale | Pas commencé |
| Documentation technique | Pas commencé |
| Documentation de gestion de projet | Pas commencé |
| Manuel utilisateur PDF | Pas commencé |
| Charte graphique PDF | Pas commencé |
| Fichiers SQL | Pas commencé |
| Lien GitHub public | Pas commencé |
| Lien application déployée | Pas commencé |
| Lien outil de gestion de projet | Pas commencé |
| Ajouter le lien GitHub dans les livrables | Pas commencé |

Plusieurs de ces livrables existent déjà partiellement ou totalement. Après leur relecture, je dois mettre à jour leur statut dans Notion.

## Annexe 4 – Définition de terminé proposée

Une tâche est considérée comme terminée lorsque :

- le besoin est satisfait ;
- le code est intégré dans la branche prévue ;
- les données invalides sont gérées ;
- les autorisations sont contrôlées ;
- le fonctionnement desktop et mobile est vérifié ;
- le scénario principal et les erreurs sont testés ;
- aucune régression critique n’est observée ;
- le changement est sauvegardé dans Git ;
- la documentation est mise à jour si nécessaire ;
- le statut Notion correspond à l’état réel.

Cette définition est proposée a posteriori afin d’améliorer la fin du projet et mes futurs travaux.

---

**Fin de la documentation de gestion de projet**
