-- ====================================================================
-- Migration 036 — Drop trigger trg_log_user_changes (Phase 11)
-- ====================================================================
-- Le trigger BDD `trg_log_user_changes` insérait `user_status_changed`
-- dans `logs_activite` à chaque modification de `users.actif`.
--
-- Cette table est désormais réservée à l'audit trail métier comptable
-- (Phase 11), alimentée explicitement par `Logger::audit()` depuis les
-- models compta. Les changements de statut user devraient être tracés
-- via `Logger::logSensitiveAction()` (security.log fichier) si besoin.
--
-- On nettoie aussi les entrées historiques `user_status_changed` qui
-- polluaient l'affichage de /comptabilite/auditTrail.
-- ====================================================================

-- 1. Drop du trigger (idempotent)
DROP TRIGGER IF EXISTS trg_log_user_changes;

-- 2. Nettoyage des entrées historiques non comptables.
--    On garde uniquement les actions à préfixes compta légitimes
--    (ecriture_, exercice_, bulletin_, tva_, bank_, salarie_, export_).
DELETE FROM logs_activite
WHERE action NOT REGEXP '^(ecriture_|exercice_|bulletin_|tva_|bank_|salarie_|export_)';
