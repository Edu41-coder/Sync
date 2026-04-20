# Module Messagerie Interne

## Périmètre
Controller : `MessageController`
Vues : `app/views/messages/`
Modèle : `Message`
Accès : **tous les utilisateurs authentifiés** (pas de `requireRole`, juste `requireAuth`)

## Concept clé
Messagerie bidirectionnelle interne (pas de temps réel) accessible à **tous les rôles** :
- admin, comptable, directeur_residence, exploitant
- proprietaire
- locataire_permanent (résident senior)
- tous les rôles staff (employe_residence, technicien, jardinier_*, entretien_*, menage_*, restauration_*, employe_laverie)

**Exclus** : les **hôtes temporaires** ne sont PAS des users → pas d'accès messagerie (par construction, ils n'ont pas de compte).

## Modèle de données

### `messages`
| Colonne | Type | Description |
|---------|------|-------------|
| `id` | INT PK | |
| `expediteur_id` | FK users | Auteur |
| `destinataire_id` | FK users | Destinataire principal |
| `copropriete_id` | FK nullable | Contexte résidence (optionnel) |
| `sujet` | VARCHAR(255) | |
| `contenu` | TEXT | |
| `date_envoi` | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP |
| `lu` | TINYINT | 0/1 |
| `date_lecture` | TIMESTAMP | NULL si pas lu |
| `archive_expediteur` | TINYINT | Archivé côté expéditeur |
| `archive_destinataire` | TINYINT | Archivé côté destinataire |

### `messages_destinataires` (multi-destinataires)
| Colonne | Type | Description |
|---------|------|-------------|
| `id` | INT PK | |
| `message_id` | FK messages | |
| `destinataire_id` | FK users | |
| `lu` | TINYINT | Statut lecture par destinataire |
| `date_lecture` | DATETIME | |
| `archive` | TINYINT | Archivé par ce destinataire |

### `messages_internes`
Table additionnelle (vérifier l'usage spécifique selon le contexte d'utilisation).

## Endpoints (`MessageController`)
```
GET  message/index                      → Boîte de réception
GET  message/show/{id}                  → Voir un message + fil conversation
GET  message/compose                    → Formulaire nouveau message
POST message/send                       → Envoi message
GET  message/unreadCount                → Compteur AJAX (badge navbar)
```

## Règles métier
- `requireAuth()` suffit (pas de `requireRole`) — tous les users connectés ont accès
- Marquage comme lu automatique à l'ouverture (`markAsRead`)
- Fil de conversation via `parent_id` ou `thread_id`
- Archive séparée expéditeur / destinataire (un peut archiver sans l'autre)
- Compteur non-lus affiché dans la navbar (badge dynamique)

## À vérifier lors du dev (intégration dans un nouveau module)
- [ ] Lien "Messagerie" présent dans la navbar pour TOUS les rôles
- [ ] Badge "non lus" fonctionnel via `MessageController::unreadCount()`
- [ ] Lien "Contacter" / "Envoyer message" disponible depuis les fiches utilisateur (proprio, résident, staff)
- [ ] Pré-remplissage du destinataire si action depuis une fiche (`?to=user_id`)
- [ ] Contexte résidence (`copropriete_id`) rempli si pertinent

## Checklist générale module Messagerie
- [ ] **Toutes les listes** ont tri colonnes + recherche + pagination (voir CLAUDE.md § Tableaux)
- [ ] CSRF sur tous les POST (envoi, archivage, suppression)
- [ ] `htmlspecialchars()` sur sujet ET contenu (XSS)
- [ ] Vérification ownership avant affichage : seul expéditeur OU destinataire peut voir
- [ ] Marquage lu uniquement si destinataire == user_id session
- [ ] Pas de référence à `users` pour les hôtes temporaires (ils n'ont pas de compte)
