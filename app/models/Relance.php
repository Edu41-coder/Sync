<?php
/**
 * ====================================================================
 * SYND_GEST — Modèle Relance impayé (Phase 13)
 * ====================================================================
 *
 * Workflow 3 niveaux :
 *   Niveau 1 — Rappel amiable (J+15 du retard)
 *   Niveau 2 — Mise en demeure (J+45)
 *   Niveau 3 — Saisine procédure (J+60)
 *
 * FK polymorphe : 1 relance peut concerner :
 *   - quittance_resident   → table `quittances_residents`
 *   - paiement_proprio     → table `paiements_loyers_exploitant`
 *   - facture_fournisseur  → table `factures_fournisseurs`
 *
 * L'envoi se fait via la messagerie interne (réutilise MessageController::send).
 * En V2 : email externe + courrier.
 */

class Relance extends Model {

    public const NIVEAUX = [
        1 => 'Rappel amiable',
        2 => 'Mise en demeure',
        3 => 'Saisine procédure',
    ];

    public const SOURCES = [
        'quittance_resident'    => 'Quittance résident',
        'paiement_proprio'      => 'Loyer propriétaire',
        'facture_fournisseur'   => 'Facture fournisseur',
    ];

    public const STATUTS = [
        'envoyee'   => 'Envoyée',
        'reglee'    => 'Réglée',
        'escaladee' => 'Escaladée niveau supérieur',
        'annulee'   => 'Annulée',
    ];

    /** Délai en jours après émission avant chaque palier de relance. */
    public const DELAIS_JOURS = [1 => 15, 2 => 45, 3 => 60];

    /**
     * Templates de message par source × niveau.
     * Variables disponibles : {prenom}, {nom}, {montant}, {periode},
     *                          {numero}, {residence}, {date_n1}, {contact}
     */
    public const TEMPLATES = [
        'quittance_resident' => [
            1 => [
                'sujet' => 'Rappel amiable — Quittance {numero} en attente de règlement',
                'corps' => "Bonjour {prenom},\n\nNotre service comptable n'a pas reçu votre paiement de {montant} € pour la quittance n° {numero} du mois de {periode}.\n\nNous vous serions reconnaissants de bien vouloir régulariser votre situation sous 15 jours.\n\nSi vous avez déjà effectué le règlement, merci de nous transmettre la référence du virement.\n\nCordialement,\nLe service comptable\n{residence}",
            ],
            2 => [
                'sujet' => 'Mise en demeure — Quittance {numero} impayée',
                'corps' => "Madame, Monsieur {nom},\n\nMalgré notre relance amiable du {date_n1}, votre paiement de {montant} € pour la quittance n° {numero} ({periode}) reste impayé à ce jour.\n\nNous vous mettons en demeure de régulariser votre situation sous 30 jours, faute de quoi nous serons contraints d'engager une procédure de recouvrement.\n\nNous restons à votre disposition pour étudier toute solution amiable (échéancier, rapprochement avec votre travailleur social).\n\nCordialement,\nLe service contentieux\n{residence}",
            ],
            3 => [
                'sujet' => 'Saisine procédure — Quittance {numero}',
                'corps' => "Madame, Monsieur {nom},\n\nVotre dette de {montant} € (quittance n° {numero}, période {periode}) fait désormais l'objet d'une transmission à notre service contentieux pour engagement d'une procédure judiciaire.\n\nPour éviter cette procédure, nous vous invitons à contacter immédiatement notre service au plus tard sous 8 jours.\n\nCordialement,\nLe service contentieux\n{residence}\nContact : {contact}",
            ],
        ],
        'paiement_proprio' => [
            1 => [
                'sujet' => 'Rappel — Loyer garanti {periode} en attente',
                'corps' => "Bonjour,\n\nNotre service comptable n'a pas reçu le règlement de votre loyer garanti pour la période {periode} ({montant} €).\n\nNous vous serions reconnaissants de régulariser sous 15 jours, ou de nous transmettre la référence du virement effectué.\n\nCordialement,\nLe service comptable Domitys\n{residence}",
            ],
            2 => [
                'sujet' => 'Mise en demeure — Loyer garanti {periode} impayé',
                'corps' => "Madame, Monsieur,\n\nMalgré notre relance du {date_n1}, votre loyer garanti pour {periode} ({montant} €) reste impayé.\n\nNous vous mettons en demeure de régulariser sous 30 jours.\n\nCordialement,\nLe service contentieux\n{residence}",
            ],
            3 => [
                'sujet' => 'Saisine procédure — Loyer garanti {periode}',
                'corps' => "Madame, Monsieur,\n\nVotre dette de {montant} € fait l'objet d'une transmission à notre service contentieux. Une procédure pourra être engagée.\n\nContactez notre service sous 8 jours pour éviter cette procédure.\n\nCordialement,\n{residence}\nContact : {contact}",
            ],
        ],
        'facture_fournisseur' => [
            1 => [
                'sujet' => 'Rappel — Facture {numero} en retard de paiement',
                'corps' => "Bonjour,\n\nNotre service comptable indique que votre facture n° {numero} d'un montant de {montant} € est en retard de paiement.\n\nMerci de nous transmettre une nouvelle date de règlement ou la référence si déjà payée.\n\nCordialement,\nLe service comptable\n{residence}",
            ],
            2 => [
                'sujet' => 'Mise en demeure — Facture {numero} impayée',
                'corps' => "Madame, Monsieur,\n\nVotre facture n° {numero} ({montant} €) reste impayée depuis notre relance du {date_n1}.\n\nNous vous mettons en demeure sous 30 jours.\n\nCordialement,\n{residence}",
            ],
            3 => [
                'sujet' => 'Saisine procédure — Facture {numero}',
                'corps' => "Madame, Monsieur,\n\nVotre dette ({montant} €) sera transmise à notre service contentieux faute de règlement sous 8 jours.\n\nCordialement,\n{residence}",
            ],
        ],
    ];

    /**
     * Crée une relance + crée le message interne associé (si moyen=messagerie).
     * Met aussi à jour le statut des relances précédentes (escaladee).
     */
    public function creer(string $sourceType, int $sourceId, int $niveau, int $residenceId, float $montantDu, ?int $destinataireUserId = null, ?string $destinataireExterne = null, ?string $sujet = null, ?string $corps = null, string $moyen = 'messagerie', ?int $userId = null): int {
        if (!array_key_exists($sourceType, self::SOURCES)) {
            throw new InvalidArgumentException("Source invalide : $sourceType");
        }
        if (!in_array($niveau, [1, 2, 3], true)) {
            throw new InvalidArgumentException("Niveau invalide : $niveau (1, 2 ou 3)");
        }

        // Marque les relances précédentes du même impayé comme 'escaladee'
        $stmt = $this->db->prepare(
            "UPDATE impayes_relances SET statut = 'escaladee'
             WHERE source_type = ? AND source_id = ? AND statut = 'envoyee' AND niveau < ?"
        );
        $stmt->execute([$sourceType, $sourceId, $niveau]);

        // Détermine le template utilisé
        $template = $this->getTemplate($sourceType, $niveau);
        $templateKey = $sourceType . '_n' . $niveau;

        // Insertion
        $stmt = $this->db->prepare(
            "INSERT INTO impayes_relances
                (source_type, source_id, residence_id, niveau, template_utilise,
                 sujet, corps_message, montant_du, moyen,
                 destinataire_user_id, destinataire_externe, statut, sent_by)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'envoyee', ?)"
        );
        $stmt->execute([
            $sourceType, $sourceId, $residenceId, $niveau, $templateKey,
            $sujet ?: $template['sujet'],
            $corps ?: $template['corps'],
            $montantDu, $moyen,
            $destinataireUserId, $destinataireExterne, $userId,
        ]);
        $newId = (int)$this->db->lastInsertId();

        Logger::audit('relance_envoyee', 'impayes_relances', $newId, [
            'source_type'     => $sourceType,
            'source_id'       => $sourceId,
            'niveau'          => $niveau,
            'montant'         => $montantDu,
            'destinataire_id' => $destinataireUserId,
            'moyen'           => $moyen,
        ], $userId);

        return $newId;
    }

    /**
     * Récupère le template pour (source × niveau) avec fallback sur niveau supérieur.
     */
    public function getTemplate(string $sourceType, int $niveau): array {
        if (!isset(self::TEMPLATES[$sourceType])) {
            return ['sujet' => 'Relance', 'corps' => 'Veuillez régulariser votre situation.'];
        }
        return self::TEMPLATES[$sourceType][$niveau] ?? self::TEMPLATES[$sourceType][1];
    }

    /**
     * Remplace les variables {var} par les valeurs réelles.
     */
    public static function appliquerVariables(string $texte, array $vars): string {
        foreach ($vars as $k => $v) {
            $texte = str_replace('{' . $k . '}', (string)$v, $texte);
        }
        return $texte;
    }

    /**
     * Marque une relance comme réglée (paiement effectué après la relance).
     */
    public function marquerReglee(int $id, ?int $userId = null): bool {
        $stmt = $this->db->prepare("UPDATE impayes_relances SET statut = 'reglee' WHERE id = ? AND statut != 'reglee'");
        $stmt->execute([$id]);
        $ok = $stmt->rowCount() > 0;
        if ($ok) Logger::audit('relance_reglee', 'impayes_relances', $id, [], $userId);
        return $ok;
    }

    /**
     * Annule une relance (erreur d'envoi, paiement vérifié finalement, etc.).
     */
    public function annuler(int $id, ?int $userId = null): bool {
        $stmt = $this->db->prepare("UPDATE impayes_relances SET statut = 'annulee' WHERE id = ? AND statut != 'annulee'");
        $stmt->execute([$id]);
        $ok = $stmt->rowCount() > 0;
        if ($ok) Logger::audit('relance_annulee', 'impayes_relances', $id, [], $userId);
        return $ok;
    }

    /**
     * Niveau de la dernière relance pour un impayé donné. Retourne 0 si aucune.
     */
    public function getDernierNiveau(string $sourceType, int $sourceId): int {
        $stmt = $this->db->prepare(
            "SELECT MAX(niveau) FROM impayes_relances
             WHERE source_type = ? AND source_id = ? AND statut != 'annulee'"
        );
        $stmt->execute([$sourceType, $sourceId]);
        return (int)($stmt->fetchColumn() ?: 0);
    }

    /**
     * Liste des relances pour un impayé (timeline).
     */
    public function getHistorique(string $sourceType, int $sourceId): array {
        $stmt = $this->db->prepare(
            "SELECT r.*, u.username AS sent_by_username
             FROM impayes_relances r
             LEFT JOIN users u ON u.id = r.sent_by
             WHERE r.source_type = ? AND r.source_id = ?
             ORDER BY r.date_envoi ASC"
        );
        $stmt->execute([$sourceType, $sourceId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Liste filtrée pour la page admin /comptabilite/impayes.
     */
    public function listFiltered(array $residenceIds, ?string $sourceType = null, ?int $niveau = null, ?string $statut = null): array {
        if (empty($residenceIds)) return [];
        $resPh = implode(',', array_fill(0, count($residenceIds), '?'));
        $sql = "SELECT r.*, c.nom AS residence_nom, u.username AS sent_by_username
                FROM impayes_relances r
                LEFT JOIN coproprietees c ON c.id = r.residence_id
                LEFT JOIN users u ON u.id = r.sent_by
                WHERE r.residence_id IN ($resPh)";
        $params = array_map('intval', $residenceIds);

        if ($sourceType && array_key_exists($sourceType, self::SOURCES)) {
            $sql .= " AND r.source_type = ?"; $params[] = $sourceType;
        }
        if ($niveau && in_array($niveau, [1, 2, 3], true)) {
            $sql .= " AND r.niveau = ?"; $params[] = $niveau;
        }
        if ($statut && array_key_exists($statut, self::STATUTS)) {
            $sql .= " AND r.statut = ?"; $params[] = $statut;
        }
        $sql .= " ORDER BY r.date_envoi DESC LIMIT 500";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
