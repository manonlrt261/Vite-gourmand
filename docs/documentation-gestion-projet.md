# Documentation de gestion de projet

## Projet : Vite & Gourmand

**Autrice :** Manon Fayolle  
**Année :** 2026  
**Version du document :** 1.0  
**Dernière mise à jour :** 17 juillet 2026  
**État du projet :** développement avancé ; déploiement et recette finale à confirmer  
**Formation :** [À COMPLÉTER]  
**Titre professionnel préparé :** [À COMPLÉTER]  
**Établissement :** [À COMPLÉTER]  
**Session d’examen :** [À COMPLÉTER]

---

## Historique des versions du document

| Version | Date | Auteur | Modifications |
|---|---|---|---|
| 1.0 | 17/07/2026 | Manon Fayolle | Création initiale à partir du projet et de son historique Git |

## Sommaire

1. [Objectif de la mission](#1-objectif-de-la-mission)
2. [Analyse initiale du projet](#2-analyse-initiale-du-projet)
3. [Contexte du projet](#3-contexte-du-projet)
4. [Format et portée du document](#4-format-et-portée-du-document)
5. [Présentation du projet](#5-présentation-du-projet)
6. [Analyse des besoins](#6-analyse-des-besoins)
7. [Définition du périmètre](#7-définition-du-périmètre)
8. [Méthode de gestion de projet](#8-méthode-de-gestion-de-projet)
9. [Outils utilisés](#9-outils-utilisés)
10. [Organisation du travail](#10-organisation-du-travail)
11. [Planification](#11-planification)
12. [Backlog et découpage des tâches](#12-backlog-et-découpage-des-tâches)
13. [User stories](#13-user-stories)
14. [Priorisation](#14-priorisation)
15. [Suivi de l’avancement](#15-suivi-de-lavancement)
16. [Chronologie réelle](#16-chronologie-réelle)
17. [Jalons](#17-jalons)
18. [Gestion du temps](#18-gestion-du-temps)
19. [Organisation Git](#19-organisation-git)
20. [Historique et progression Git](#20-historique-et-progression-git)
21. [Gestion des changements](#21-gestion-des-changements)
22. [Gestion des risques](#22-gestion-des-risques)
23. [Problèmes et blocages](#23-problèmes-et-blocages)
24. [Prise de décision](#24-prise-de-décision)
25. [Gestion de la qualité](#25-gestion-de-la-qualité)
26. [Définition de terminé](#26-définition-de-terminé)
27. [Stratégie de tests](#27-stratégie-de-tests)
28. [Gestion des anomalies](#28-gestion-des-anomalies)
29. [Gestion de la sécurité](#29-gestion-de-la-sécurité)
30. [Gestion de la documentation](#30-gestion-de-la-documentation)
31. [Communication et traçabilité](#31-communication-et-traçabilité)
32. [Recette et validation finale](#32-recette-et-validation-finale)
33. [Préparation de la démonstration](#33-préparation-de-la-démonstration)
34. [Bilan du projet](#34-bilan-du-projet)
35. [Améliorations de la gestion de projet](#35-améliorations-de-la-gestion-de-projet)
36. [Évolutions futures](#36-évolutions-futures)
37. [Conclusion](#37-conclusion)
38. [Annexes](#38-annexes)
39. [Liste des captures à préparer](#39-liste-des-captures-à-préparer)
40. [Règles de rédaction et de sincérité](#40-règles-de-rédaction-et-de-sincérité)
41. [Contrôle final](#41-contrôle-final)
42. [Compte rendu de réalisation](#42-compte-rendu-de-réalisation)

---

# 1. Objectif de la mission

Dans le cadre de mon projet d’examen, j’ai conçu et développé l’application web **Vite & Gourmand**. Cette documentation présente la manière dont j’ai préparé, organisé, planifié, suivi, versionné, contrôlé et fait évoluer ce projet.

Mon objectif est de permettre au jury de comprendre non seulement le résultat obtenu, mais également la démarche suivie. Je distingue systématiquement ce que je peux prouver grâce au code, aux fichiers de configuration, aux scripts de base de données, aux documents et à l’historique Git, de ce qui reste à confirmer ou à finaliser.

Ce document concerne principalement la gestion de projet. Les détails d’architecture et d’implémentation sont développés séparément dans `docs/documentation-technique.md`.

# 2. Analyse initiale du projet

Avant de rédiger ce document, j’ai réalisé un état des lieux du dépôt local. Le README n’étant pas encore finalisé, je l’ai utilisé comme source d’orientation et non comme preuve unique. En cas de contradiction, le code et l’historique Git ont été considérés comme prioritaires.

## 2.1 Technologies détectées

| Domaine | Technologies confirmées | Observation |
|---|---|---|
| Back-end | PHP 8.4 ou supérieur, Symfony 8.1 | Versions définies dans `composer.json` |
| Affichage | Twig, HTML, CSS personnalisé | Templates et feuilles de style présents |
| Interactions | JavaScript, Stimulus 3.2.2, Turbo 8.0.23 | ImportMap et scripts métier présents |
| Données relationnelles | MySQL, Doctrine DBAL | Scripts SQL MySQL et requêtes DBAL |
| Données non relationnelles | MongoDB | Dépendance MongoDB et `MongoStatsService` |
| Ressources | AssetMapper, ImportMap | Aucun `package.json` détecté |
| E-mails | Symfony Mailer | Fournisseur Brevo déclaré mais [À CONFIRMER] |
| Tests | PHPUnit 13, BrowserKit, CSS Selector | Outils installés, aucun cas de test présent |
| Environnement | Composer, serveur PHP intégré | Laragon déclaré et chemin local détecté |

Bootstrap, Node.js et npm ne sont pas confirmés dans la version analysée. Les fichiers Docker Compose décrivent PostgreSQL et Mailpit, mais cette configuration ne correspond pas à la base MySQL et au SMTP externe utilisés par l’application ; son utilisation réelle reste donc à confirmer.

## 2.2 Outils de gestion détectés

Git et GitHub sont confirmés par le dépôt local et le dépôt distant `origin`. Notion est confirmé par des exports historiques présents dans le commit `22f9eac`, puis supprimés dans le commit `e44dec2`. Ces exports comprennent une planification de 236 tâches, des lots d’import Notion et un fichier de suivi au format tableur.

Figma est mentionné dans le README, mais aucun export, lien ni fichier de maquette n’est présent dans le dépôt. Son utilisation doit donc rester **[À CONFIRMER : maquettes réalisées dans Figma et lien du fichier]**. Visual Studio Code, Laragon, phpMyAdmin et MongoDB Compass sont décrits dans les documents, mais leur utilisation ne peut pas être entièrement prouvée par le dépôt seul.

## 2.3 Documents existants

| Document ou trace | État observé |
|---|---|
| `README.md` | Présent, mais en cours de révision et modifié localement |
| `docs/documentation-technique.md` | Présent |
| Scripts de base de données | Présents dans `database/` |
| Export de planning Notion | Accessible dans l’historique Git, absent de la version courante |
| Liste détaillée de tâches | Accessible dans l’historique Git |
| Manuel utilisateur | Non détecté |
| Charte graphique | Non détectée |
| Cahier de tests | Non détecté |
| Diagrammes UML ou MCD | Non détectés |
| Exports Figma | Non détectés |

## 2.4 État du dépôt Git

Le dépôt est actuellement positionné sur la branche `develop`. Au moment de l’analyse, cette branche locale possède un commit d’avance sur `origin/develop`. Le fichier README comporte une modification locale antérieure à cette documentation et le dossier `docs/` n’est pas encore suivi. Aucun commit, changement de branche, rebase ou fusion n’a été réalisé pendant la rédaction.

Le dépôt distant est hébergé sur GitHub à l’adresse `https://github.com/manonlrt261/Vite-gourmand.git`.

## 2.5 Branches détectées

Les branches locales et distantes suivantes sont visibles :

- `main` ;
- `develop` ;
- `feature-analyse-sujet` ;
- `feature-environnement-technique` ;
- `feature-pages-vitrine` ;
- `feature-menus-et-details` ;
- `feature-authentification` ;
- `feature-panier-commandes` ;
- `feature-espace-client` ;
- `feature-espace-employe` ;
- `feature-espace-administrateur` ;
- `feature-base-donnees-mysql` ;
- `feature-nosql-mongodb` ;
- `feature-gestion-avis` ;
- `feature-gestion-horaires` ;
- `feature-contact-messagerie` ;
- `feature-gestion-employes` ;
- `feature-statistiques-admin` ;
- `feature-documentation-readme`.

Cependant, toutes les branches fonctionnelles et `main` pointent vers le même commit `22f9eac`. L’historique ne montre aucune fusion. Les noms traduisent un découpage fonctionnel prévu, mais ils ne prouvent pas un développement réellement isolé et fusionné branche par branche.

## 2.6 Période couverte

L’historique Git couvre la période du **9 juillet 2026 au 17 juillet 2026**. Un export de planification retrouvé dans l’historique couvre une période plus large, du **21 juin au 21 juillet 2026**. Les dates du planning constituent une prévision ou une saisie de suivi ; elles ne doivent pas être confondues avec les dates de commits.

## 2.7 Phases identifiables

Les phases traçables sont l’initialisation, la réalisation des pages publiques, le panier, l’intégration MongoDB, les espaces client, employé et administrateur, les e-mails transactionnels, les améliorations d’accessibilité et de sécurité, une première phase de tests manuels déclarée, la gestion du matériel et des avis, la consolidation de la base et une sauvegarde avant déploiement.

La conception des maquettes et l’analyse du sujet figurent dans le planning historique, mais aucune pièce de conception n’est disponible dans la version courante du dépôt.

## 2.8 Fonctionnalités terminées ou fortement avancées

Le code contient les pages publiques, la consultation et le filtrage des menus, le panier, la création de commande, l’authentification, la réinitialisation du mot de passe, l’espace client, la gestion des commandes, les avis, les espaces employé et administrateur, les menus et plats, les horaires, la messagerie, les employés, les statistiques MongoDB et plusieurs e-mails transactionnels.

Ces fonctionnalités sont considérées comme **implémentées dans le code**, mais pas toutes comme définitivement validées, car aucun cahier de recette ni test automatisé n’est présent.

## 2.9 Fonctionnalités en cours ou à confirmer

Le déploiement, la recette finale, la couverture de tests, la validation multi-navigateurs, les maquettes Figma, la charte graphique, le manuel utilisateur et l’automatisation de la livraison restent à confirmer ou à terminer. Le commit « Sauvegarde avant déploiement » indique une intention, mais ne prouve pas que l’application est effectivement accessible en production.

## 2.10 Fonctionnalités absentes

Aucun paiement en ligne, notification SMS, application mobile native, facturation complète, pipeline CI/CD ou système de géolocalisation avancée n’a été détecté. Ces éléments ne sont pas présentés comme des fonctionnalités réalisées.

## 2.11 Difficultés visibles

Les difficultés techniques visibles sont la coexistence d’assets sources et publics parfois différents, une configuration Docker PostgreSQL incohérente avec MySQL, l’absence de tests automatisés, une sécurité Symfony standard peu exploitée au profit de contrôles manuels, des contrôleurs volumineux et la gestion de certaines évolutions SQL à l’exécution. Une date de fin antérieure à la date de début a également été détectée pour le menu « Saveurs de printemps » dans les données SQL.

## 2.12 Informations manquantes

Les informations manquantes concernent la formation, la session, le lien Figma, le tableau Notion actuel, les captures, l’hébergement, l’URL de production, la date de rendu, les résultats détaillés des tests, les retours de formateur, les temps réellement passés et la validation finale.

## 2.13 Éléments à confirmer manuellement

- [À CONFIRMER : méthode de gestion appliquée au quotidien et fréquence réelle de mise à jour de Notion]
- [À CONFIRMER : utilisation de Figma et disponibilité des maquettes]
- [À CONFIRMER : utilisation effective de Brevo]
- [À CONFIRMER : tests manuels réellement exécutés et résultats]
- [À CONFIRMER : environnement Laragon, phpMyAdmin, MongoDB Compass et Visual Studio Code]
- [À CONFIRMER : application déployée et URL]
- [À CONFIRMER : difficultés personnellement rencontrées au-delà des incohérences techniques observées]

# 3. Contexte du projet

Vite & Gourmand est un projet individuel réalisé dans le cadre de mon examen en développement web. L’application répond au besoin d’un traiteur souhaitant présenter ses prestations et centraliser ses activités numériques.

Le projet réunit un site vitrine, un catalogue de menus, un parcours de commande et des espaces adaptés aux clients, aux employés et aux administrateurs. La difficulté principale du projet tient à la coexistence de plusieurs profils, de nombreuses règles métier et de deux types de bases de données.

# 4. Format et portée du document

J’ai choisi le format Markdown afin que cette documentation puisse être versionnée avec le projet, relue facilement et convertie ultérieurement en PDF ou intégrée dans Word ou Google Docs. Les captures ne sont pas intégrées automatiquement ; leur emplacement est signalé par des marqueurs explicites.

Ce document décrit l’organisation et le pilotage. Il ne remplace ni le README d’installation, ni le manuel utilisateur, ni la documentation technique.

# 5. Présentation du projet

## 5.1 Contexte et problématique

L’activité d’un traiteur implique de présenter des offres, recevoir des commandes, suivre leur préparation et leur livraison, gérer les retours de matériel et conserver une vision administrative. Une gestion dispersée rend le suivi plus difficile.

La problématique retenue est la suivante :

> Comment concevoir une application web accessible, sécurisée et administrable permettant à un traiteur de présenter ses prestations, de gérer ses menus et de centraliser les commandes de ses clients ?

## 5.2 Objectifs

Mon objectif principal est de produire une application web cohérente avec l’activité de Vite & Gourmand.

Les objectifs fonctionnels sont de permettre la consultation des menus, la création d’un compte, la commande, le suivi client, le traitement par les employés, la gestion administrative et la consultation de statistiques.

Les objectifs techniques sont de structurer l’application avec Symfony, stocker les données métier dans MySQL, produire des statistiques dans MongoDB, sécuriser les actions sensibles, proposer une interface responsive et versionner le travail avec Git.

Les objectifs pédagogiques précis restent **[À COMPLÉTER : référentiel de compétences évalué]**.

## 5.3 Public cible

| Profil | Besoins principaux |
|---|---|
| Visiteur | Découvrir l’entreprise, consulter les menus et contacter le traiteur |
| Client | Créer un compte, commander, suivre ou modifier une commande éligible et laisser un avis |
| Employé | Traiter les commandes, gérer le catalogue, les horaires, les avis et les messages |
| Administrateur | Gérer les employés et consulter les statistiques |

## 5.4 Livrables

| Livrable | État au 17/07/2026 |
|---|---|
| Application web | Implémentation avancée ; recette finale à réaliser |
| Dépôt GitHub | Confirmé |
| Application déployée | [À CONFIRMER] |
| README | Présent, mais à finaliser |
| Manuel utilisateur | [À COMPLÉTER] |
| Charte graphique | [À COMPLÉTER] |
| Documentation technique | Présente |
| Documentation de gestion de projet | Présente dans ce fichier |
| Diagrammes | [À COMPLÉTER] |

# 6. Analyse des besoins

## 6.1 Besoins fonctionnels

| Profil | Besoin | Fonction associée | Priorité reconstituée |
|---|---|---|---|
| Visiteur | Consulter l’offre | Catalogue et fiches menus | Haute |
| Visiteur | Contacter l’entreprise | Formulaire de contact | Moyenne |
| Client | S’identifier | Inscription, connexion, mot de passe oublié | Haute |
| Client | Commander | Panier et validation de commande | Haute |
| Client | Suivre ses achats | Espace client et historique | Haute |
| Employé | Traiter les commandes | Statuts, modifications et annulations | Haute |
| Employé | Administrer l’offre | Gestion des menus et plats | Haute |
| Employé | Gérer la relation client | Avis et messagerie | Moyenne |
| Administrateur | Gérer les comptes employés | Création, modification et désactivation | Haute |
| Administrateur | Piloter l’activité | Statistiques par menu | Moyenne |

## 6.2 Besoins non fonctionnels

J’ai identifié des besoins de sécurité, d’ergonomie, d’accessibilité, d’adaptation mobile, de fiabilité des calculs, de maintenabilité et de protection des données. Le commit `3cb6164` atteste d’une phase consacrée au RGAA et à la sécurité. Cela ne suffit toutefois pas à affirmer une conformité complète au Référentiel général d’amélioration de l’accessibilité.

## 6.3 Contraintes

Les contraintes confirmées sont l’utilisation de PHP/Symfony, d’une base relationnelle MySQL, d’une base MongoDB, la séparation des profils, la sécurité des formulaires et la livraison d’un projet d’examen. La contrainte de délai est visible dans la planification allant jusqu’au 21 juillet 2026, mais la date officielle de rendu reste **[À CONFIRMER]**.

## 6.4 Règles métier

| Règle observée | État |
|---|---|
| Connexion nécessaire pour finaliser une commande | Implémentée |
| Respect du minimum de personnes propre au menu | Implémenté |
| Prix calculé à partir du prix par personne | Implémenté |
| Remise de 10 % à partir de cinq personnes au-dessus du minimum | Implémentée |
| Frais de livraison calculés selon la destination | Implémentation présente, validation métier à confirmer |
| Modification client limitée aux commandes en attente | Implémentée |
| Annulation encadrée par le statut | Implémentée |
| Historique des statuts | Implémenté |
| Avis lié à une commande et modéré | Implémenté |
| Gestion du prêt et du retour de matériel | Implémentée |
| Désactivation des employés | Implémentée |

# 7. Définition du périmètre

## 7.1 Périmètre inclus

Le périmètre comprend le site public, les menus, le contact, l’inscription, l’authentification, le panier, les commandes, l’espace client, l’espace employé, l’espace administrateur, les avis, les horaires, les messages, les employés, les statistiques et les e-mails transactionnels.

## 7.2 Périmètre exclu ou absent

Le paiement en ligne, les SMS, l’application mobile native, la facturation complète, la comptabilité et la réservation en temps réel ne sont pas présents. Je les considère comme hors de la version actuelle et non comme des travaux terminés.

## 7.3 Priorités MoSCoW reconstituées

La méthode MoSCoW n’est pas prouvée dans les traces. Le tableau suivant formalise donc a posteriori les priorités visibles dans le code et le sujet.

| Fonctionnalité | Priorité | Justification | État |
|---|---|---|---|
| Consultation des menus | Must have | Point d’entrée commercial | Implémentée |
| Authentification | Must have | Nécessaire pour commander | Implémentée |
| Panier et commande | Must have | Fonction métier centrale | Implémentée |
| Traitement employé | Must have | Nécessaire au cycle de commande | Implémenté |
| Gestion des employés | Must have | Besoin administrateur | Implémentée |
| Statistiques | Should have | Pilotage de l’activité | Implémentées |
| E-mails | Should have | Information des utilisateurs | Implémentés, fournisseur à confirmer |
| Paiement en ligne | Won’t have for now | Non détecté dans le périmètre actuel | Absent |
| Notifications SMS | Could have | Amélioration future | Absentes |

# 8. Méthode de gestion de projet

## 8.1 Méthode retenue

Les traces disponibles correspondent à une **approche hybride et individuelle**. J’ai découpé le projet en domaines fonctionnels, créé une liste détaillée de tâches dans Notion et utilisé des statuts de suivi. Parallèlement, j’ai développé par grandes phases et sauvegardé les avancées dans Git.

Je ne présente pas cette organisation comme un Scrum complet : aucun sprint, rôle Scrum, cérémonial ou compte rendu d’équipe n’est prouvé. L’approche se rapproche davantage d’un Kanban personnel associé à un déroulement par étapes.

## 8.2 Justification

Cette méthode est adaptée à un projet individuel et à une durée limitée. Elle permet de visualiser un grand nombre de tâches, de les regrouper par domaine, d’identifier les priorités et d’ajuster le travail en fonction des difficultés rencontrées.

## 8.3 Cycle de travail

```text
Analyse du sujet
      ↓
Découpage et planification
      ↓
Conception fonctionnelle et visuelle
      ↓
Modélisation des données
      ↓
Développement par espace
      ↓
Sécurité et accessibilité
      ↓
Tests et corrections
      ↓
Documentation
      ↓
Déploiement à confirmer
```

[SCHÉMA À INSÉRER : cycle de gestion du projet Vite & Gourmand]

## 8.4 Adaptations visibles

L’historique montre plusieurs phases d’ajustement : ajout de MongoDB et finalisation du panier, ajout des e-mails transactionnels, modifications RGAA et sécurité, première phase de tests, ajout de la gestion du matériel et des avis, puis consolidation de la base avant déploiement.

Les raisons exactes de chaque adaptation ne sont pas toutes consignées. **[À COMPLÉTER : décisions personnelles, problèmes déclencheurs et arbitrages réalisés]**.

# 9. Outils utilisés

## 9.1 Notion

J’ai utilisé Notion pour préparer un suivi détaillé du projet. L’export historique comporte 236 tâches réparties dans 14 domaines : gestion de projet, base MySQL, MongoDB, authentification, pages visiteur, panier, espaces client, employé et administrateur, avis, contact, design, tests et documentation.

L’export contient les champs tâche, section, étiquette, priorité, statut, durée estimée, date de début et date de fin. Au moment de cet export, 53 tâches étaient marquées « Done » et 183 « Not started ». Ces données constituent une photographie historique et non l’état actuel.

[CAPTURE À INSÉRER : vue générale du tableau Notion ou Kanban de Vite & Gourmand]

[CAPTURE À INSÉRER : détail d’une tâche avec priorité, statut, estimation et dates]

## 9.2 GitHub

GitHub héberge le dépôt distant et assure la sauvegarde externe du code. Les branches distantes reprennent le découpage fonctionnel. Aucune issue ni pull request n’est vérifiable depuis le dépôt local ; ces pratiques ne sont donc pas présentées comme utilisées.

[CAPTURE À INSÉRER : page du dépôt GitHub]

## 9.3 Git

J’ai utilisé Git pour conserver neuf étapes principales du projet entre le 9 et le 17 juillet 2026. Les commits ont été réalisés sous le nom de Manon Fayolle. Aucun tag, merge ou retour en arrière explicite n’apparaît dans l’historique.

## 9.4 Figma

Figma est mentionné comme outil de maquette, mais aucune preuve n’est accessible dans le dépôt. Je dois compléter cette partie avec le lien et les captures si l’outil a effectivement été utilisé.

**[À CONFIRMER : utilisation de Figma, wireframes, maquettes desktop/mobile et composants créés]**.

## 9.5 Environnement de développement

Le projet est compatible avec PHP 8.4, Composer et MySQL. Le README en cours de rédaction indique un développement sous Laragon, avec Visual Studio Code, phpMyAdmin et MongoDB Compass. Un script Windows contient un chemin Laragon local. Je dois néanmoins confirmer personnellement l’usage exact de chaque interface.

## 9.6 Codex

J’ai utilisé Codex comme outil d’assistance pour analyser la structure du projet, contrôler la cohérence des informations, repérer des éléments à documenter et m’aider à rédiger cette documentation. Les résultats ont été confrontés aux fichiers réels et doivent être relus, corrigés et validés par mes soins. Codex n’a pas pris seul les décisions fonctionnelles du projet.

# 10. Organisation du travail

J’ai organisé le projet par domaines fonctionnels correspondant aux profils et aux composants majeurs. Cette structure se retrouve à la fois dans le planning historique, dans les noms des branches et dans les dossiers de templates.

| Lot | Contenu principal |
|---|---|
| Cadrage | Analyse du sujet et gestion de projet |
| Données | MySQL, scripts SQL et MongoDB |
| Public | Accueil, menus et contact |
| Client | Authentification, panier, commandes, profil et avis |
| Employé | Commandes, catalogue, horaires, avis et messages |
| Administrateur | Employés et statistiques |
| Transverse | Sécurité, accessibilité, e-mails, tests et documentation |

Le code suit une organisation Symfony par contrôleurs, templates, ressources et services. Toutefois, plusieurs contrôleurs regroupent de nombreuses responsabilités. Cette organisation a permis d’avancer rapidement, mais elle devra être améliorée pour faciliter la maintenance.

# 11. Planification

## 11.1 Planning prévisionnel retrouvé

L’export Notion historique couvre la période du 21 juin au 21 juillet 2026. Il contient 236 micro-tâches estimées en minutes. Il s’agit du planning le plus précis accessible, même s’il n’est plus présent dans la version courante du dépôt.

| Domaine | Nombre de tâches dans l’export |
|---|---:|
| Gestion de projet | 12 |
| Base MySQL | 27 |
| Base NoSQL / MongoDB | 13 |
| Authentification | 16 |
| Pages visiteur | 15 |
| Panier / commande | 17 |
| Espace client | 19 |
| Espace employé | 24 |
| Espace administrateur | 20 |
| Avis clients | 14 |
| Contact / messagerie | 13 |
| Front-end / design | 12 |
| Tests | 18 |
| Documentation | 16 |

## 11.2 Limites du planning

Le planning ne reflète pas l’état final du code : 183 tâches y sont encore marquées non commencées alors que de nombreuses fonctionnalités correspondantes sont aujourd’hui implémentées. Je dois donc mettre à jour Notion ou créer un export final avant la présentation.

[CAPTURE À INSÉRER : planning prévisionnel avec les principales phases]

# 12. Backlog et découpage des tâches

Le backlog historique découpe les grands besoins en actions courtes : analyser le sujet, créer les tables, ajouter les données, développer chaque écran, mettre en place la sécurité, tester les profils et préparer les documents.

Cette granularité est adaptée au suivi quotidien. En revanche, l’export ne contient que deux états, « Done » et « Not started », ce qui limite la visibilité sur les tâches en cours, bloquées ou à vérifier.

| Exemple de besoin | Découpage en tâches |
|---|---|
| Authentification | Créer l’inscription, la connexion, la déconnexion et le mot de passe oublié |
| Commande | Créer le panier, calculer le prix, valider les données et enregistrer la commande |
| Espace employé | Lister, modifier, annuler et faire progresser les commandes |
| Administration | Gérer les employés et produire les statistiques |
| Qualité | Contrôler la sécurité, l’accessibilité et les principaux parcours |

# 13. User stories

Les user stories suivantes sont reformulées à partir des fonctionnalités présentes. Elles ne constituent pas un export original d’un outil de suivi.

| ID | User story | Critères d’acceptation principaux | État code |
|---|---|---|---|
| US-01 | En tant que visiteur, je veux consulter les menus afin de choisir une prestation. | Liste accessible, filtres et détail d’un menu | Implémenté |
| US-02 | En tant que visiteur, je veux créer un compte afin de commander. | Données validées, mot de passe haché, compte créé | Implémenté |
| US-03 | En tant que client, je veux ajouter un menu au panier afin de préparer ma commande. | Minimum respecté, quantité modifiable, prix recalculé | Implémenté |
| US-04 | En tant que client, je veux passer une commande afin de réserver une prestation. | Adresse et date valides, commande enregistrée, confirmation | Implémenté |
| US-05 | En tant que client, je veux suivre mes commandes. | Liste, détail et statut visibles | Implémenté |
| US-06 | En tant qu’employé, je veux mettre à jour les statuts afin de suivre le traitement. | Autorisation, CSRF, historique du statut | Implémenté |
| US-07 | En tant qu’employé, je veux gérer les menus et plats. | Création, modification, activation et suppression contrôlées | Implémenté |
| US-08 | En tant qu’employé, je veux modérer les avis. | Acceptation, refus et sélection pour l’accueil | Implémenté |
| US-09 | En tant qu’administrateur, je veux gérer les employés. | Création, modification, désactivation et suppression contrôlées | Implémenté |
| US-10 | En tant qu’administrateur, je veux consulter les statistiques par menu. | Commandes et chiffre d’affaires agrégés | Implémenté |

# 14. Priorisation

L’export Notion utilise trois niveaux possibles de priorité, mais les lignes consultées sont principalement classées en priorité haute ou moyenne. Le code indique que j’ai donné la priorité aux fondations, à la base, aux parcours de commande et aux espaces métier avant la documentation et le déploiement.

Pour améliorer ce suivi, je dois distinguer la priorité métier de l’urgence liée à l’examen et limiter le nombre de tâches simultanément considérées comme hautes.

# 15. Suivi de l’avancement

Le suivi repose sur deux sources : le tableau Notion et les commits Git. Notion apporte la vue détaillée des tâches ; Git donne une preuve datée des changements réellement enregistrés.

La comparaison met en évidence un décalage : le tableau historique est moins avancé que le code actuel. Avant le jury, je dois actualiser les statuts et conserver une capture datée du tableau final.

| Indicateur | Valeur vérifiée |
|---|---:|
| Tâches dans l’export historique | 236 |
| Tâches « Done » dans l’export | 53 |
| Tâches « Not started » dans l’export | 183 |
| Commits Git | 9 |
| Auteurs Git | 1 |
| Tags | 0 |
| Fusions visibles | 0 |

# 16. Chronologie réelle

| Date | Commit | Phase identifiable |
|---|---|---|
| 09/07/2026 | `ddca5cd` | Initialisation du projet et premières pages |
| 13/07/2026 | `d65805f` | MongoDB, panier et espaces métier |
| 15/07/2026 | `22f9eac` | Sauvegarde des pages, scripts SQL et traces Notion |
| 15/07/2026 | `7fac0cf` | E-mails transactionnels |
| 16/07/2026 | `3cb6164` | Accessibilité RGAA et sécurité |
| 16/07/2026 | `e44dec2` | Première phase de tests déclarée et corrections |
| 17/07/2026 | `f709dcb` | Matériel et avis client |
| 17/07/2026 | `66176ce` | Consolidation de la base et de l’interface |
| 17/07/2026 | `4e7086f` | Sauvegarde avant déploiement |

L’analyse, la conception et la planification ont probablement commencé avant le premier commit, puisque le planning débute le 21 juin. Leur déroulement exact doit cependant être confirmé par mes propres archives.

# 17. Jalons

| Jalon | Preuve | État |
|---|---|---|
| Initialisation technique | Premier commit | Atteint |
| Parcours public et panier | Code initial et commit du 13 juillet | Atteint dans le code |
| Espaces client, employé et administrateur | Contrôleurs et templates | Atteint dans le code |
| Double stockage MySQL/MongoDB | Scripts SQL et service MongoDB | Atteint dans le code |
| E-mails transactionnels | Commit dédié | Atteint dans le code |
| Sécurité et accessibilité | Commit dédié | Première passe réalisée |
| Première phase de tests | Commit dédié | Réalisée selon le message ; détails absents |
| Déploiement | Commit de préparation | [À CONFIRMER] |
| Recette finale | Aucun procès-verbal | À réaliser |

# 18. Gestion du temps

Le planning contient des estimations en minutes et des dates, ce qui montre une volonté d’anticiper la charge. En revanche, aucun relevé du temps réellement passé n’est disponible. Je ne peux donc pas calculer l’écart entre prévision et réalisation.

Pour la suite, je dois ajouter une durée réelle, une date de fin réelle et un commentaire lorsqu’une tâche dépasse son estimation. Cette mesure permettra d’améliorer mes estimations sur de futurs projets.

# 19. Organisation Git

J’ai structuré les noms de branches autour de `main`, `develop` et de branches `feature-*`. Cette nomenclature rend le périmètre de chaque lot compréhensible. Toutefois, l’historique montre que les branches fonctionnelles pointent toutes vers le même commit et qu’aucune fusion n’est enregistrée.

Le workflow réellement vérifiable est donc principalement linéaire sur `develop`. Je ne dois pas présenter les branches `feature-*` comme des développements isolés ou fusionnés sans preuve supplémentaire.

Une stratégie améliorée serait : créer une branche depuis `develop`, réaliser des commits ciblés, ouvrir une pull request, effectuer une revue, fusionner, puis supprimer la branche devenue inutile.

[CAPTURE À INSÉRER : liste des branches locales et distantes]

# 20. Historique et progression Git

L’historique comprend neuf commits, tous attribués à Manon Fayolle. La fréquence augmente à l’approche du 17 juillet : un commit le 9, un le 13, deux le 15, deux le 16 et trois le 17.

Les messages décrivent les grandes sauvegardes, mais certains restent trop génériques, par exemple « database » ou « Sauvegarde des pages ». Des messages plus précis faciliteraient la compréhension et le retour en arrière.

Le dépôt ne contient aucun tag de version. La création d’un tag `v1.0.0` ne devra intervenir qu’après recette et validation de la version livrée.

[CAPTURE À INSÉRER : historique Git sous forme de graphe]

# 21. Gestion des changements

Les changements sont visibles dans les commits, mais aucun registre formel de demandes de changement n’est présent. J’ai donc reconstitué les principales évolutions à partir de l’historique.

| Changement | Origine vérifiable | Impact |
|---|---|---|
| Ajout de MongoDB | Commit `d65805f` | Statistiques administratives non relationnelles |
| Ajout des e-mails | Commit `7fac0cf` | Notifications et relation client |
| Renforcement RGAA/sécurité | Commit `3cb6164` | Validation, CSRF et améliorations d’interface |
| Corrections après première phase de test | Commit `e44dec2` | Nombreux contrôleurs, scripts et templates modifiés |
| Gestion du matériel et des avis | Commit `f709dcb` | Extension du cycle de commande |

Les demandes à l’origine de ces changements restent **[À COMPLÉTER : demande du sujet, retour de formateur, anomalie ou décision personnelle]**.

# 22. Gestion des risques

Le registre suivant distingue les risques observés des risques encore préventifs.

| Risque | Probabilité | Impact | Mesure | Réalisation |
|---|---|---|---|---|
| Planning non actualisé | Élevée | Élevé | Mettre à jour Notion avant le jury | Réalisé : export ancien décalé du code |
| Régression faute de tests automatisés | Élevée | Élevé | Ajouter des tests prioritaires | Risque actuel |
| Incohérence MySQL/PostgreSQL | Moyenne | Moyen | Nettoyer ou documenter Docker Compose | Réalisé dans la configuration |
| Divergence des assets | Élevée | Moyen | Définir `assets/` comme source unique | Réalisé : fichiers divergents |
| Erreur de sécurité dans les contrôles manuels | Moyenne | Élevé | Centraliser l’authentification avec Symfony Security | Risque actuel |
| Secret local exposé | Faible à moyenne | Critique | Conserver `.env.local` hors Git et régénérer si besoin | Non constaté dans l’analyse |
| Indisponibilité MongoDB ou service cartographique | Moyenne | Moyen | Gérer les erreurs et prévoir un repli | Partiellement traité dans le code |
| Déploiement tardif | Élevée | Élevé | Tester tôt sur l’hébergement cible | À confirmer |
| Données SQL incohérentes | Moyenne | Moyen | Valider les dates et jeux de données | Réalisé sur un menu |
| Dépendance excessive à l’IA | Moyenne | Moyen | Relire, comprendre et tester chaque proposition | Risque maîtrisé par validation humaine |

# 23. Problèmes et blocages

Je ne peux pas attribuer automatiquement une difficulté personnelle à une incohérence technique. Le tableau ci-dessous constitue donc un registre des problèmes objectivement visibles ; mon ressenti et la solution réellement appliquée doivent être complétés si nécessaire.

| Problème observé | Conséquence | Cause probable | Traitement recommandé | État |
|---|---|---|---|---|
| Aucun test automatisé | Régressions difficiles à détecter | Priorité donnée aux fonctionnalités | Ajouter des tests critiques | Ouvert |
| Docker configure PostgreSQL alors que l’application utilise MySQL | Installation ambiguë | Recette Symfony conservée | Aligner ou retirer la configuration après validation | Ouvert |
| Assets dupliqués et parfois différents | Risque d’afficher une version obsolète | Copies historiques dans `public/` | Définir une source unique | Ouvert |
| Contrôleurs très volumineux | Maintenance difficile | Développement rapide et centralisé | Extraire des services métier | Ouvert |
| Sécurité Symfony standard peu utilisée | Autorisations répétées manuellement | Authentification personnalisée | Migrer vers provider, firewall et voters | Ouvert |
| Date incohérente d’un menu | Menu saisonnier potentiellement indisponible | Erreur de donnée | Corriger après validation métier | Ouvert |
| Planning ancien | Avancement non représentatif | Export non mis à jour | Actualiser le tableau | Ouvert |

# 24. Prise de décision

| Décision | Preuve | Justification reconstituée | Impact |
|---|---|---|---|
| Utiliser Symfony 8.1 et PHP 8.4 | `composer.json` | Structurer l’application avec un framework moderne | Architecture Symfony |
| Utiliser MySQL | Scripts SQL et code DBAL | Stocker des données métier relationnelles | Schéma de 18 tables |
| Utiliser MongoDB | Dépendance et service dédié | Stocker des statistiques sous forme de documents | Double système de données |
| Utiliser Twig et CSS personnalisé | Templates et assets | Maîtriser l’interface sans framework CSS détecté | Interface spécifique |
| Utiliser Notion | Exports Git historiques | Découper et suivre 236 tâches | Suivi détaillé mais à actualiser |
| Utiliser GitHub | Remote `origin` | Sauvegarder et centraliser le code | Dépôt distant |
| Gérer les rôles en session | Contrôleurs | Choix d’implémentation rapide | Contrôles manuels répétés |

Les alternatives réellement envisagées et les motivations personnelles restent **[À COMPLÉTER]**. Je ne prétends pas avoir comparé formellement plusieurs solutions sans trace.

# 25. Gestion de la qualité

La qualité a été prise en compte par la validation serveur, la protection CSRF, les requêtes paramétrées, l’échappement Twig, la gestion des rôles et une phase identifiée comme « RGAA et sécurité ». L’historique mentionne également une première phase de tests.

La qualité n’est toutefois pas encore démontrée par une suite automatisée, un rapport d’accessibilité, un outil d’analyse statique ou une recette signée. Je dois donc présenter ces contrôles comme partiels et poursuivre la validation.

| Contrôle | État vérifiable |
|---|---|
| Validation côté serveur | Présente |
| Protection CSRF | Présente sur de nombreuses actions |
| Contrôle des rôles | Présent manuellement |
| Échappement des contenus | Twig et protections ponctuelles |
| Responsive | Styles présents ; validation visuelle à confirmer |
| Accessibilité | Modifications présentes ; audit complet absent |
| Analyse statique | Absente |
| Tests automatisés | Absents |
| Revue de code formelle | Non prouvée |

# 26. Définition de terminé

Aucune définition de terminé formelle n’est présente dans les traces. Je propose donc la définition suivante a posteriori pour finaliser le projet :

- la fonctionnalité répond au besoin et aux règles métier ;
- les accès sont contrôlés selon le profil ;
- les données invalides sont refusées côté serveur ;
- les actions sensibles sont protégées contre les requêtes frauduleuses ;
- l’affichage desktop et mobile est vérifié ;
- les scénarios nominaux et d’erreur sont testés ;
- aucune régression critique n’est constatée ;
- le code et la documentation sont cohérents ;
- la tâche est mise à jour dans Notion ;
- un commit ciblé et compréhensible conserve le changement.

Cette pratique n’a pas été appliquée formellement pendant toute la durée du projet, mais elle constitue une amélioration recommandée.

# 27. Stratégie de tests

PHPUnit et les composants Symfony de test sont installés, mais le dossier `tests` ne contient que le fichier d’amorçage. Aucun test automatisé n’est donc vérifiable.

Le commit `e44dec2` mentionne une « première phase de test » et modifie de nombreux fichiers. Je peux en déduire qu’une phase de correction a eu lieu, mais je ne peux pas lister les scénarios réellement exécutés sans compte rendu complémentaire.

| Fonctionnalité | Scénario | Résultat attendu | État |
|---|---|---|---|
| Inscription | Données valides | Compte créé et mot de passe haché | À tester formellement |
| Connexion | Identifiants valides/invalides | Session ouverte ou erreur claire | À tester formellement |
| Panier | Quantité inférieure au minimum | Minimum imposé ou erreur | À tester formellement |
| Commande | Données valides | Commande et lignes enregistrées | À tester formellement |
| Commande | Échec pendant la transaction | Aucun enregistrement partiel | À tester formellement |
| Client | Accès à la commande d’un autre client | Accès refusé | À tester formellement |
| Employé | Changement de statut | Statut et historique mis à jour | À tester formellement |
| Administrateur | Client vers page administrateur | Accès refusé | À tester formellement |
| Avis | Commande éligible | Avis créé puis soumis à modération | À tester formellement |
| Responsive | Mobile, tablette, bureau | Contenu utilisable sans perte | [À CONFIRMER] |
| Navigateurs | Chrome, Firefox, Edge | Parcours essentiels fonctionnels | [À CONFIRMER] |

# 28. Gestion des anomalies

Le cycle retenu pour la fin du projet est : détection, reproduction, description, qualification de la gravité, priorisation, correction, test de non-régression et fermeture.

| ID | Anomalie vérifiable | Gravité | Priorité | État |
|---|---|---|---|---|
| BUG-01 | Date de fin antérieure à la date de début pour « Saveurs de printemps » | Moyenne | Haute | À corriger après validation |
| BUG-02 | Configuration Docker PostgreSQL incohérente avec MySQL | Moyenne | Moyenne | Ouverte |
| BUG-03 | Fichiers CSS/JS dupliqués avec divergences | Moyenne | Haute | Ouverte |
| BUG-04 | Absence de tests automatisés | Élevée | Haute | Ouverte |

Je dois compléter ce registre avec les anomalies rencontrées pendant mes tests manuels : **[À COMPLÉTER : description, date, correction et preuve du nouveau test]**.

# 29. Gestion de la sécurité

La sécurité est un axe transversal du projet. Les mots de passe nouvellement créés sont hachés, les requêtes SQL utilisent majoritairement des paramètres liés, Twig échappe les contenus et de nombreuses actions POST utilisent un jeton CSRF.

Les espaces protégés contrôlent la session, le rôle et l’état actif de l’utilisateur. Les commandes client sont filtrées par identifiant d’utilisateur. Les fichiers locaux contenant les secrets sont exclus par `.gitignore`.

Plusieurs points doivent toutefois être améliorés : l’authentification repose principalement sur du code personnalisé plutôt que sur le système complet de Symfony Security ; un mécanisme de compatibilité avec d’anciens mots de passe potentiellement non hachés subsiste ; aucune limitation des tentatives de connexion n’a été détectée ; aucun test automatisé des autorisations n’est présent.

La sécurité doit donc être vérifiée tout au long du projet et lors de chaque évolution, et non uniquement avant la livraison.

# 30. Gestion de la documentation

| Document | Public | Format | Emplacement | État |
|---|---|---|---|---|
| README | Développeur/jury | Markdown | Racine | À finaliser |
| Documentation technique | Développeur/jury | Markdown | `docs/documentation-technique.md` | Présente |
| Gestion de projet | Jury | Markdown | `docs/documentation-gestion-projet.md` | Présente |
| Manuel utilisateur | Utilisateurs/jury | [À COMPLÉTER] | [À COMPLÉTER] | Absent |
| Charte graphique | Jury/développeur | PDF ou document | [À COMPLÉTER] | Absente du dépôt |
| Diagrammes | Développeur/jury | Image/PDF | [À COMPLÉTER] | Absents |
| Cahier de tests | Jury/développeur | Markdown ou tableur | [À COMPLÉTER] | Absent |

Les documents Markdown sont stockés avec le code et peuvent être versionnés. Le README étant en cours de finalisation, je devrai effectuer une dernière vérification croisée afin d’éviter toute contradiction sur l’installation, les technologies, les fonctionnalités et le déploiement.

# 31. Communication et traçabilité

Le projet étant réalisé individuellement, la communication d’équipe a été remplacée par une traçabilité écrite des tâches, des décisions, des versions et des corrections.

Notion conserve le découpage prévu, tandis que Git fournit une trace des versions. Les commentaires du code et les documents complètent cette traçabilité. Aucune réunion, revue avec un formateur ou décision collective n’est inventée.

**[À COMPLÉTER : retours réels des formateurs, dates, décisions prises et corrections associées]**.

# 32. Recette et validation finale

La recette finale n’est pas encore démontrée. Avant la livraison, je dois exécuter et conserver les résultats de la checklist suivante :

- [ ] Application installable sur un environnement propre
- [ ] Base MySQL initialisable avec les scripts fournis
- [ ] MongoDB disponible ou erreur gérée clairement
- [ ] Comptes de démonstration fonctionnels
- [ ] Inscription et authentification testées
- [ ] Réinitialisation du mot de passe testée
- [ ] Consultation et filtrage des menus testés
- [ ] Panier et calculs de prix testés
- [ ] Commande et confirmation testées
- [ ] Espace client testé
- [ ] Espace employé testé
- [ ] Espace administrateur testé
- [ ] Autorisations et CSRF contrôlés
- [ ] Avis, messages et e-mails testés
- [ ] Responsive vérifié
- [ ] Navigateurs principaux vérifiés
- [ ] Données de démonstration contrôlées
- [ ] Déploiement vérifié
- [ ] README finalisé
- [ ] Documentation relue

Les cases restent volontairement décochées tant que je ne dispose pas d’une preuve de recette complète.

# 33. Préparation de la démonstration

Le scénario de démonstration proposé est le suivant :

1. présenter le contexte et la problématique ;
2. montrer la page d’accueil et les menus ;
3. filtrer puis ouvrir un menu ;
4. se connecter avec un compte client ;
5. ajouter un menu au panier et expliquer le calcul ;
6. passer une commande ;
7. montrer le suivi depuis l’espace client ;
8. ouvrir l’espace employé et traiter la commande ;
9. présenter la gestion des menus, avis, horaires et messages ;
10. ouvrir l’espace administrateur ;
11. montrer les employés et les statistiques MongoDB ;
12. présenter Notion, Git et les principaux jalons ;
13. expliquer les mesures de sécurité ;
14. conclure avec les limites et améliorations.

**[À COMPLÉTER : durée totale autorisée par le jury avant d’attribuer un temps à chaque partie]**.

# 34. Bilan du projet

## 34.1 Résultats obtenus

J’ai construit une application couvrant les quatre profils attendus et le cycle principal de commande. J’ai mobilisé PHP, Symfony, Twig, JavaScript, MySQL, MongoDB, Git et des outils de suivi. Le code contient également des mesures de sécurité, des e-mails et des fonctions d’administration.

## 34.2 Objectifs partiellement atteints

La validation reste incomplète : aucun test automatisé n’est présent, le cahier de recette manque, le déploiement n’est pas confirmé et plusieurs documents doivent encore être produits. Le README doit être finalisé et le tableau de suivi doit être mis à jour.

## 34.3 Compétences développées

Ce projet m’a permis de travailler l’analyse du besoin, le découpage fonctionnel, la planification, le développement front-end et back-end, la conception de bases relationnelles et non relationnelles, la sécurité, le versionnement, la documentation et la résolution de problèmes.

## 34.4 Retour d’expérience

Les traces montrent une forte progression sur une période courte et une large couverture fonctionnelle. Elles montrent aussi que les tests, la documentation et le déploiement ont été abordés tardivement. Mon retour personnel doit être complété sans inventer : **[À COMPLÉTER : ce qui a bien fonctionné, ce qui a pris plus de temps et les principaux apprentissages]**.

# 35. Améliorations de la gestion de projet

## 35.1 Améliorations organisationnelles

- actualiser Notion après chaque séance ;
- ajouter les états « En cours », « Bloqué » et « À vérifier » ;
- enregistrer le temps réel et comparer avec l’estimation ;
- définir des jalons et une marge de sécurité ;
- utiliser des issues pour les anomalies et décisions ;
- créer de vraies branches fonctionnelles et des pull requests ;
- adopter une convention de commits plus précise ;
- commencer les tests dès les premières fonctionnalités ;
- tenir un journal des décisions et des risques ;
- mettre à jour la documentation en continu.

## 35.2 Améliorations techniques liées au pilotage

- automatiser les tests dans une intégration continue ;
- centraliser les règles métier dans des services ;
- aligner la configuration Docker avec l’environnement réel ;
- définir une source unique pour les assets ;
- utiliser plus complètement Symfony Security ;
- mettre en place une procédure de déploiement et de retour arrière.

Ces propositions sont des améliorations a posteriori et ne sont pas présentées comme des pratiques déjà appliquées.

# 36. Évolutions futures

Les évolutions fonctionnelles possibles sont le paiement en ligne, les notifications, la géolocalisation plus avancée, la facturation, des statistiques enrichies, une gestion plus fine des stocks, une accessibilité auditée, une application mobile et l’automatisation du déploiement.

Ces fonctionnalités ne font pas partie de la version confirmée et devront être priorisées selon leur valeur, leur coût, leur risque et les besoins réels de l’entreprise.

# 37. Conclusion

La réalisation de Vite & Gourmand s’appuie sur une organisation identifiable : un découpage détaillé dans Notion, un classement par domaines fonctionnels, une planification et un suivi par Git. Cette démarche m’a permis de transformer un sujet large en étapes plus petites et de conserver des preuves de progression.

L’analyse montre cependant que la traçabilité doit être consolidée. Le planning historique n’est plus à jour, les branches fonctionnelles ne reflètent pas un véritable workflow de fusion et les tests automatisés sont absents. La dernière phase du projet doit donc se concentrer sur la recette, la correction des incohérences, le déploiement et l’alignement des documents.

Cette expérience m’a permis de comprendre que la gestion de projet ne consiste pas uniquement à établir une liste de tâches. Elle implique de maintenir cette liste, mesurer l’avancement réel, maîtriser les changements, anticiper les risques et conserver des preuves vérifiables jusqu’à la livraison.

# 38. Annexes

## Annexe 1 – Backlog complet

Le backlog historique contient 236 tâches. L’export source est accessible dans le commit Git `22f9eac` sous `outputs/planning-notion-2026.csv`, mais n’est plus présent dans la version courante. Avant livraison, je dois générer un nouvel export actualisé et l’ajouter aux livrables si aucune donnée sensible n’y figure.

## Annexe 2 – Tableau des user stories

Le tableau principal se trouve au chapitre 13. Une version exhaustive pourra être ajoutée après validation de chaque scénario.

## Annexe 3 – Planning

Période historique : 21 juin au 21 juillet 2026.  
Période Git vérifiée : 9 au 17 juillet 2026.  
Planning réel consolidé : **[À COMPLÉTER après mise à jour de Notion]**.

## Annexe 4 – Registre des risques

Le registre initial figure au chapitre 22. Il doit être réévalué avant déploiement.

## Annexe 5 – Registre des problèmes

Le registre initial figure au chapitre 23.

## Annexe 6 – Journal des décisions

Le journal reconstitué figure au chapitre 24. Les alternatives et motivations personnelles doivent être complétées.

## Annexe 7 – Stratégie Git

Branche stable : `main`.  
Branche d’intégration actuelle : `develop`.  
Branches fonctionnelles : préfixe `feature-`.  
Workflow de pull request : non constaté, recommandé pour la suite.  
Tags : aucun tag présent.

## Annexe 8 – Plan de tests

Le plan initial figure au chapitre 27. Un cahier de recette détaillé doit être créé et complété avec la date, l’environnement, le résultat obtenu, l’anomalie et la preuve.

## Annexe 9 – Liste des captures

La liste détaillée figure au chapitre 39.

## Annexe 10 – Liens utiles

| Ressource | Lien ou emplacement |
|---|---|
| Dépôt GitHub | `https://github.com/manonlrt261/Vite-gourmand.git` |
| Application déployée | [À COMPLÉTER] |
| Tableau Notion | [À COMPLÉTER] |
| Maquettes Figma | [À COMPLÉTER] |
| README | `README.md` |
| Manuel utilisateur | [À COMPLÉTER] |
| Documentation technique | `docs/documentation-technique.md` |
| Charte graphique | [À COMPLÉTER] |

# 39. Liste des captures à préparer

| N° | Outil | Capture | Objectif | Données à masquer |
|---:|---|---|---|---|
| 1 | Notion | Vue générale du tableau | Montrer l’organisation des tâches | Liens privés et données personnelles |
| 2 | Notion | Détail d’une tâche | Montrer statut, priorité, durée et dates | Informations privées |
| 3 | Notion | Planning actualisé | Comparer prévision et réalisation | Liens privés |
| 4 | Figma | Vue des maquettes [si confirmé] | Montrer la conception desktop/mobile | Aucune donnée sensible |
| 5 | GitHub | Page du dépôt | Montrer l’hébergement et les branches | Jetons, e-mails privés si nécessaire |
| 6 | Git | Graphe des neuf commits | Montrer la chronologie | Aucune |
| 7 | Git | Liste des branches | Expliquer le workflow réel | Aucune |
| 8 | Tests | Cahier de recette complété | Prouver la validation | Chemins personnels |
| 9 | Application | Parcours client | Prouver le cycle de commande | Données personnelles |
| 10 | Application | Espace employé | Montrer le traitement des commandes | Données clients réelles |
| 11 | Application | Statistiques administrateur | Montrer MongoDB | Données réelles si sensibles |
| 12 | Hébergement | Application en ligne | Prouver la livraison | Identifiants et configuration |

# 40. Règles de rédaction et de sincérité

J’ai appliqué les règles suivantes :

- une information observée dans le code, Git ou un fichier de configuration est formulée affirmativement ;
- une information déclarée mais non vérifiable porte la mention `[À CONFIRMER]` ;
- une information indispensable absente porte la mention `[À COMPLÉTER]` ;
- une fonctionnalité présente dans le code mais non recettée est dite implémentée, et non définitivement validée ;
- une recommandation a posteriori n’est jamais présentée comme une pratique historique ;
- aucune réunion, difficulté personnelle, décision, branche, date ou test n’est inventé ;
- aucune clé, aucun mot de passe et aucun secret local n’est reproduit.

# 41. Contrôle final

Le contrôle documentaire effectué le 17 juillet 2026 confirme les points suivants :

- les 42 chapitres sont numérotés et liés depuis le sommaire ;
- les technologies et fonctionnalités citées ont été croisées avec le projet ;
- les neuf commits et les branches mentionnés existent ;
- la période Git est distinguée de la période du planning ;
- l’export Notion est présenté comme historique ;
- les tests automatisés ne sont pas présentés comme réalisés ;
- le déploiement reste à confirmer ;
- les limites du README sont explicitement prises en compte ;
- les informations sensibles ne sont pas affichées ;
- les éléments manquants sont signalés.

Une dernière relecture par mes soins restera nécessaire après finalisation du README, ajout des captures, mise à jour de Notion et exécution de la recette.

# 42. Compte rendu de réalisation

| Élément | Résultat |
|---|---|
| Fichier produit | `docs/documentation-gestion-projet.md` |
| Nombre de chapitres | 42 chapitres principaux, plus sous-sections et annexes |
| Outils documentés | Notion, Git, GitHub, Composer, environnement local, Figma à confirmer, Codex |
| Phases identifiées | Analyse, planification, données, développement public et métier, e-mails, sécurité/accessibilité, tests, préparation au déploiement |
| Branches analysées | `main`, `develop` et 17 branches `feature-*` |
| Commits analysés | 9 |
| Principaux jalons | Initialisation, panier/MongoDB, espaces métier, e-mails, RGAA/sécurité, première phase de test, matériel/avis, préparation au déploiement |
| Risques recensés | 10 risques principaux |
| Difficultés documentées | 7 problèmes objectivement observés |
| Décisions documentées | 7 décisions reconstituées à partir des preuves |
| Captures à réaliser | 12 captures proposées |
| Informations à compléter | Formation, session, Figma, Notion actuel, tests, déploiement, retours, temps réel et bilan personnel |
| Éléments non vérifiables | Usage quotidien exact des outils, résultats des tests manuels, fournisseur SMTP, maquettes et déploiement |
| Incohérences principales | Planning ancien/code avancé, Docker PostgreSQL/MySQL, assets dupliqués, branches sans fusions, README à finaliser |
| Améliorations prioritaires | Recette, tests automatisés, suivi actualisé, workflow Git réel, journal des décisions, CI/CD et déploiement documenté |

---

**Fin du document**
