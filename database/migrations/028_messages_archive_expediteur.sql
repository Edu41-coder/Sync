-- ====================================================================
-- Migration 028 : Boîte d'envoi de la messagerie
-- ====================================================================
-- Permet à un expéditeur d'archiver ses messages envoyés (soft delete côté
-- sortants) sans affecter la copie reçue par les destinataires.
--
-- Symétrie avec `messages_destinataires.archive` (côté reçus).
-- ====================================================================

ALTER TABLE messages_internes
    ADD COLUMN archive_expediteur TINYINT(1) NOT NULL DEFAULT 0
        COMMENT 'Soft delete côté expéditeur (boîte envoyés)';
