## vérifications d’accessibilité réalisées selon les principaux critères RGAA

## Contrôle d’accessibilité des pages principales

Le code contient plusieurs améliorations d’accessibilité. Cependant, aucun audit RGAA complet et formalisé n’a été réalisé. Les résultats présentés ci-dessous correspondent donc à un contrôle de premier niveau et ne permettent pas de garantir une conformité complète au RGAA.

Ce contrôle combine l’examen du code HTML et CSS avec la vérification des principales pages publiques à différentes largeurs d’affichage.

### Légende

- **OK** : aucun écart détecté pendant le contrôle réalisé ;
- **Partiel** : amélioration présente, mais contrôle manuel complémentaire nécessaire ;
- **NC** : écart constaté ;
- **N/A** : contrôle non applicable à la page observée ;
- **À vérifier** : mesure ou vérification spécialisée nécessaire.

### Tableau de contrôle

| Page contrôlée | Navigation au clavier | Focus visible | Textes alternatifs | Structure des titres | Labels des champs | Messages d’erreur accessibles | Contrastes | Zoom à 200 % | Affichage mobile |
|---|---|---|---|---|---|---|---|---|---|
| Accueil | Partiel | OK | OK | OK | N/A | N/A | À vérifier | OK | OK |
| Liste des menus | Partiel | OK | OK | OK | OK | N/A | À vérifier | OK | OK |
| Contact | Partiel | OK | OK | NC | OK | NC | À vérifier | OK | OK |
| Connexion | Partiel | OK | OK | NC | OK | NC | À vérifier | OK | OK |
| Inscription | Partiel | OK | OK | NC | OK | NC | À vérifier | OK | OK |
| Panier vide | Partiel | OK | OK | OK | N/A | N/A | À vérifier | OK | OK |

### Résultats détaillés

#### Navigation au clavier

Les éléments interactifs utilisent principalement des éléments HTML natifs : liens, boutons, champs de saisie, listes déroulantes et zones de texte.

Le résultat reste classé « Partiel », car l’ordre complet de tabulation, les fenêtres modales, les composants dynamiques et les espaces nécessitant une authentification n’ont pas été testés manuellement jusqu’au terme de chaque parcours.

#### Visibilité du focus

Une règle CSS globale `:focus-visible` ajoute un contour de trois pixels et une ombre autour des liens, boutons et champs sélectionnés au clavier.

Aucune règle globale supprimant complètement l’indicateur de focus n’a été relevée.

#### Textes alternatifs

Aucune image dépourvue d’attribut `alt` n’a été détectée sur les pages contrôlées.

Les images porteuses d’information possèdent un texte alternatif. Plusieurs éléments uniquement décoratifs sont également masqués aux technologies d’assistance avec `aria-hidden="true"`.

#### Structure des titres

La page d’accueil, la liste des menus et le panier vide possèdent un seul titre principal `<h1>` et une hiérarchie globalement cohérente.

Les pages Contact, Connexion et Inscription possèdent chacune deux titres `<h1>` :

- le titre du bandeau commun ;
- le titre du contenu principal.

Le second titre devrait être transformé en `<h2>`, ou le bandeau commun devrait pouvoir être affiché sans produire un nouveau `<h1>`.

#### Labels des champs

Tous les champs visibles contrôlés possèdent un libellé associé ou un nom accessible.

Les champs cachés utilisés pour la protection CSRF ne nécessitent pas de label visible.

#### Messages d’erreur accessibles

Le panier contient une zone utilisant `role="alert"` pour annoncer certaines erreurs.

En revanche, les messages d’erreur des pages Contact, Connexion et Inscription sont affichés dans de simples paragraphes ou éléments `<div>`. Ils ne possèdent pas de rôle d’alerte ou de zone `aria-live`.

Ces messages devraient recevoir `role="alert"` ou être placés dans une zone `aria-live`. Lorsqu’une erreur concerne un champ particulier, le message devrait également être relié au champ avec `aria-describedby`, et le champ devrait recevoir `aria-invalid="true"`.

#### Contrastes

Les couleurs, les arrière-plans et les voiles appliqués sur certaines images montrent une prise en compte de la lisibilité.

Cependant, les rapports de contraste n’ont pas été mesurés pour toutes les combinaisons de texte, de fond et d’état des composants. Ce point ne peut donc pas être déclaré conforme sans une mesure complémentaire.


#### Affichage mobile

Aucun débordement horizontal n’a été relevé sur les pages principales contrôlées à 640 pixels de large.

Les feuilles de style contiennent plusieurs règles responsive. Une vérification sur différents téléphones reste néanmoins nécessaire pour contrôler les menus, les formulaires, les fenêtres modales et les contenus dynamiques.

### Conclusion

Le projet présente une base d’accessibilité satisfaisante concernant :

- les textes alternatifs ;
- les labels des champs ;
- la visibilité du focus ;
- l’utilisation d’éléments HTML interactifs natifs ;
- l’adaptation générale aux écrans étroits.

Plusieurs améliorations restent nécessaires :

- corriger les pages contenant plusieurs titres `<h1>` ;
- rendre les messages d’erreur détectables par les technologies d’assistance ;
- mesurer précisément les contrastes ;
- réaliser un parcours complet au clavier ;

Ces résultats ne constituent ni une déclaration d’accessibilité ni une preuve de conformité complète au RGAA. 