# Schéma base de données — synd_gest

## Users & Profils
| Table | Description |
|-------|-------------|
| `users` | Tous les comptes — `password_hash` + `password_plain` (visible admin) |
| `roles` | 18 rôles : slug, catégorie, couleur, icône, ordre_affichage |
| `user_residence` | Liaison user ↔ résidence (staff, direction) |
| `coproprietaires` | Profil propriétaire — FK `user_id` |
| `residents_seniors` | Profil résident senior complet (34 champs : santé, CNI, contact urgence, etc.) |
| `hotes_temporaires` | Séjours court terme — **PAS de user_id** |
| `exploitants` | Sociétés exploitantes — Domitys = `id 1` |

## Résidences & Lots
| Table | Description |
|-------|-------------|
| `coproprietees` | Résidences — `type_residence='residence_seniors'` uniquement, `actif` pour soft delete |
| `lots` | `type` ENUM('studio','t2','t2_bis','t3','parking','cave'), `terrasse` ENUM('non','oui','loggia') |
| `exploitant_residences` | Many-to-many exploitant ↔ résidence avec `pourcentage_gestion` (Domitys 100% par défaut) |

## Occupations & Contrats
| Table | Description |
|-------|-------------|
| `occupations_residents` | Résident ↔ lot — loyer, forfait, services, aides sociales |
| `contrats_gestion` | Propriétaire ↔ lot ↔ exploitant — loyer garanti, dispositif fiscal |
| `revenus_fiscaux_proprietaires` | Fiscalité annuelle par propriétaire |

## Services
| Table | Description |
|-------|-------------|
| `services` | Catalogue — inclus/supplémentaire, prix, icône |
| `occupation_services` | Pivot occupation ↔ service avec `prix_applique` |

## Planning
| Table | Description |
|-------|-------------|
| `planning_shifts` | Shifts staff — `user_id`, `residence_id`, dates, `heures_calculees` (colonne GENERATED), `type_heures` |
| `planning_categories` | 13 catégories : ménage, restauration, technique, etc. |

## Comptabilité & Documents
| Table | Description |
|-------|-------------|
| `comptes_comptables` | Plan comptable |
| `ecritures_comptables` | Écritures |
| `exercices_comptables` | Exercices annuels |
| `factures_fournisseurs` | Factures fournisseurs |
| `fournisseurs` | Répertoire fournisseurs |
| `devis` | Devis |
| `documents` | GED documents |
| `appels_fonds` | Appels de fonds |
| `lignes_appel_fonds` | Lignes détail appels |
| `assemblees_generales` | AG |
| `baux` | Baux |

## Maintenance technique (migration 019)
| Table | Description |
|-------|-------------|
| `specialites` | Référentiel 6 spécialités (piscine, ascenseur, travaux, plomberie, electricite, peinture) |
| `user_specialites` | Pivot user × spécialité (niveau `debutant`/`confirme`/`expert`) |
| `user_certifications` | Certifications pro avec date_obtention, date_expiration, fichier preuve |
| `maintenance_interventions` | Interventions courantes — workflow 4 statuts, photos avant/après |
| `chantiers` | Chantiers travaux — workflow 9 phases, FK `ag_id` |
| `chantier_devis` | Devis multi-prestataires |
| `chantier_jalons` | Jalons d'avancement (% complétion) |
| `chantier_documents` | Devis, plans, photos, PV, factures, garanties |
| `chantier_receptions` | PV de réception avec/sans réserves |
| `chantier_garanties` | Parfait achèvement / biennale / décennale |
| `chantier_lots_impactes` | Quote-part propriétaires (chantier × lot) |
| `maintenance_produits` | Catalogue (consommable, pièce détachée, outillage, EPI, chimique) |
| `maintenance_inventaire` | Stock par résidence |
| `maintenance_inventaire_mouvements` | Historique avec lien intervention OU chantier |

`coproprietees.piscine` + `coproprietees.ascenseur` (TINYINT) ajoutés pour masquer les sections inutiles.

## Logs
| Table | Description |
|-------|-------------|
| `logs_activite` | Activité utilisateurs — `user_id` nullable (ON DELETE SET NULL) |

## Relations clés
```
users ──FK──> roles
users ──FK──> coproprietaires (1:1)
users ──FK──> residents_seniors (1:1)
users ──FK──> exploitants (via exploitant.user_id)
users ←──> coproprietees (via user_residence)

coproprietees ──> lots (1:N)
lots ──> occupations_residents (1:N, 1 active max)
lots ──> contrats_gestion (1:N, 1 actif max)

residents_seniors ──> occupations_residents (N illimité)
occupations_residents ──> occupation_services (pivot)
```
