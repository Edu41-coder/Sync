<?php
/**
 * ====================================================================
 * SYND_GEST - Modèle Message (Messagerie Interne)
 * ====================================================================
 * Encapsule toutes les requêtes SQL liées à la messagerie.
 */

class Message extends Model {

    protected $table = 'messages_internes';

    // ─────────────────────────────────────────────────────────────
    //  BOÎTE DE RÉCEPTION
    // ─────────────────────────────────────────────────────────────

    /**
     * Messages reçus (non archivés) pour un utilisateur
     */
    public function getInbox(int $userId): array {
        $sql = "SELECT m.id, m.parent_id, m.sujet, m.contenu, m.priorite, m.created_at,
                   md.lu, md.date_lecture,
                   u.prenom as exp_prenom, u.nom as exp_nom, u.role as exp_role,
                   (SELECT COUNT(*) FROM messages_internes r WHERE r.parent_id = COALESCE(m.parent_id, m.id)) as nb_reponses
                FROM messages_destinataires md
                JOIN messages_internes m ON md.message_id = m.id
                JOIN users u ON m.expediteur_id = u.id
                WHERE md.destinataire_id = ? AND md.archive = 0
                ORDER BY m.created_at DESC";
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$userId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->logError($e->getMessage(), $sql, [$userId]);
            return [];
        }
    }

    /**
     * Nombre de messages non lus
     */
    public function getUnreadCount(int $userId): int {
        $sql = "SELECT COUNT(*) FROM messages_destinataires WHERE destinataire_id=? AND lu=0 AND archive=0";
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$userId]);
            return (int)$stmt->fetchColumn();
        } catch (PDOException $e) {
            $this->logError($e->getMessage(), $sql, [$userId]);
            return 0;
        }
    }

    // ─────────────────────────────────────────────────────────────
    //  LECTURE MESSAGE / THREAD
    // ─────────────────────────────────────────────────────────────

    /**
     * Trouver un message avec infos expéditeur
     */
    public function findWithSender(int $id): ?array {
        $sql = "SELECT m.*, u.prenom as exp_prenom, u.nom as exp_nom, u.role as exp_role
                FROM messages_internes m JOIN users u ON m.expediteur_id=u.id WHERE m.id=?";
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$id]);
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        } catch (PDOException $e) {
            $this->logError($e->getMessage(), $sql, [$id]);
            return null;
        }
    }

    /**
     * Marquer un message comme lu pour un destinataire
     */
    public function markAsRead(int $messageId, int $userId): void {
        $sql = "UPDATE messages_destinataires SET lu=1, date_lecture=NOW() WHERE message_id=? AND destinataire_id=? AND lu=0";
        try {
            $this->db->prepare($sql)->execute([$messageId, $userId]);
        } catch (PDOException $e) {
            $this->logError($e->getMessage(), $sql, [$messageId, $userId]);
        }
    }

    /**
     * Récupérer un fil de conversation complet
     */
    public function getThread(int $threadId): array {
        $sql = "SELECT m.*, u.prenom as exp_prenom, u.nom as exp_nom, u.role as exp_role
                FROM messages_internes m JOIN users u ON m.expediteur_id=u.id
                WHERE m.id=? OR m.parent_id=?
                ORDER BY m.created_at ASC";
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$threadId, $threadId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->logError($e->getMessage(), $sql, [$threadId]);
            return [];
        }
    }

    /**
     * Marquer tous les messages d'un fil comme lus
     */
    public function markThreadAsRead(array $messageIds, int $userId): void {
        if (empty($messageIds)) return;
        try {
            $sql = "UPDATE messages_destinataires SET lu=1, date_lecture=NOW() WHERE message_id=? AND destinataire_id=? AND lu=0";
            $stmt = $this->db->prepare($sql);
            foreach ($messageIds as $msgId) {
                $stmt->execute([$msgId, $userId]);
            }
        } catch (PDOException $e) {
            $this->logError($e->getMessage());
        }
    }

    // ─────────────────────────────────────────────────────────────
    //  DESTINATAIRES (selon rôle)
    // ─────────────────────────────────────────────────────────────

    /**
     * Destinataires possibles selon le rôle de l'expéditeur
     */
    public function getDestinataires(string $role, int $userId): array {
        try {
            if ($role === 'admin') {
                return $this->db->query("SELECT id, prenom, nom, role FROM users WHERE actif=1 ORDER BY nom, prenom")
                    ->fetchAll(PDO::FETCH_ASSOC);
            }

            if ($role === 'proprietaire') {
                $stmt = $this->db->prepare("
                    SELECT DISTINCT u.id, u.prenom, u.nom, u.role FROM users u
                    WHERE u.actif=1 AND (
                        u.role IN ('admin','comptable')
                        OR (u.role = 'directeur_residence' AND u.id IN (
                            SELECT ur.user_id FROM user_residence ur
                            JOIN lots l ON l.copropriete_id = ur.residence_id
                            JOIN contrats_gestion cg ON cg.lot_id = l.id
                            JOIN coproprietaires cp ON cg.coproprietaire_id = cp.id
                            WHERE cp.user_id = ? AND cg.statut = 'actif' AND ur.statut = 'actif'
                        ))
                    )
                    ORDER BY u.role, u.nom
                ");
                $stmt->execute([$userId]);
                return $stmt->fetchAll(PDO::FETCH_ASSOC);
            }

            // Staff : admin + comptable + collègues de la même résidence
            $stmt = $this->db->prepare("
                SELECT DISTINCT u.id, u.prenom, u.nom, u.role FROM users u
                WHERE u.actif=1 AND (
                    u.role IN ('admin','comptable')
                    OR u.id IN (
                        SELECT ur2.user_id FROM user_residence ur2
                        WHERE ur2.residence_id IN (SELECT ur3.residence_id FROM user_residence ur3 WHERE ur3.user_id=?)
                        AND ur2.statut='actif'
                    )
                ) AND u.id != ?
                ORDER BY u.role, u.nom
            ");
            $stmt->execute([$userId, $userId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->logError($e->getMessage());
            return [];
        }
    }

    /**
     * Résidences pour envoi groupé (admin uniquement)
     */
    public function getResidencesForGroupSend(): array {
        $sql = "SELECT id, nom FROM coproprietees WHERE actif=1 ORDER BY nom";
        try {
            return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->logError($e->getMessage(), $sql);
            return [];
        }
    }

    /**
     * Données d'un message pour pré-remplir une réponse
     */
    public function getReplyData(int $messageId): ?array {
        $sql = "SELECT m.*, u.prenom as exp_prenom, u.nom as exp_nom
                FROM messages_internes m JOIN users u ON m.expediteur_id=u.id WHERE m.id=?";
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$messageId]);
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        } catch (PDOException $e) {
            $this->logError($e->getMessage(), $sql, [$messageId]);
            return null;
        }
    }

    // ─────────────────────────────────────────────────────────────
    //  ENVOI
    // ─────────────────────────────────────────────────────────────

    /**
     * Créer un message (retourne l'ID)
     */
    public function createMessage(int $userId, array $data): int {
        $sql = "INSERT INTO messages_internes (parent_id, expediteur_id, sujet, contenu, priorite, type_envoi, residence_id)
                VALUES (?,?,?,?,?,?,?)";
        $this->db->prepare($sql)->execute([
            $data['parent_id'],
            $userId,
            $data['sujet'],
            $data['contenu'],
            $data['priorite'],
            $data['type_envoi'],
            $data['residence_id']
        ]);
        return (int)$this->db->lastInsertId();
    }

    /**
     * Résoudre la liste des destinataires selon le type d'envoi
     */
    public function resolveDestinataires(string $type, array $params = []): array {
        try {
            if ($type === 'tous_proprios') {
                return $this->db->query("SELECT id FROM users WHERE role='proprietaire' AND actif=1")->fetchAll(PDO::FETCH_COLUMN);
            }

            if ($type === 'tous_staff') {
                $staffRoles = "'employe_residence','technicien','jardinier_manager','jardinier_employe','entretien_manager','menage_interieur','menage_exterieur','restauration_manager','restauration_serveur','restauration_cuisine','employe_laverie'";
                return $this->db->query("SELECT id FROM users WHERE role IN ($staffRoles) AND actif=1")->fetchAll(PDO::FETCH_COLUMN);
            }

            if ($type === 'residence' && !empty($params['residence_id'])) {
                $residenceId = (int)$params['residence_id'];
                $stmt = $this->db->prepare("
                    SELECT DISTINCT u.id FROM users u
                    LEFT JOIN coproprietaires cp ON cp.user_id=u.id
                    LEFT JOIN contrats_gestion cg ON cg.coproprietaire_id=cp.id AND cg.statut='actif'
                    LEFT JOIN lots l ON cg.lot_id=l.id
                    LEFT JOIN user_residence ur ON ur.user_id=u.id AND ur.residence_id=?
                    WHERE u.actif=1 AND (l.copropriete_id=? OR ur.residence_id=?)
                ");
                $stmt->execute([$residenceId, $residenceId, $residenceId]);
                return $stmt->fetchAll(PDO::FETCH_COLUMN);
            }

            return [];
        } catch (PDOException $e) {
            $this->logError($e->getMessage());
            return [];
        }
    }

    /**
     * Ajouter les destinataires à un message
     */
    public function addDestinataires(int $messageId, array $destinataireIds): void {
        try {
            $stmt = $this->db->prepare("INSERT IGNORE INTO messages_destinataires (message_id, destinataire_id) VALUES (?,?)");
            foreach ($destinataireIds as $destId) {
                $stmt->execute([$messageId, (int)$destId]);
            }
        } catch (PDOException $e) {
            $this->logError($e->getMessage());
        }
    }
}
