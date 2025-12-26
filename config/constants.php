<?php
/**
 * ====================================================================
 * SYND_GEST - Constantes de l'Application
 * ====================================================================
 * Définition des constantes utilisées dans toute l'application
 * 
 * @author Synd_Gest Team
 * @version 1.0
 * @date 2025-11-29
 */

// ====================================================================
// STATUTS GÉNÉRAUX
// ====================================================================

// Statuts actif/inactif
define('STATUS_ACTIVE', 1);
define('STATUS_INACTIVE', 0);

// États de validation
define('STATE_PENDING', 'attente');
define('STATE_VALIDATED', 'validee');
define('STATE_REJECTED', 'rejete');

// ====================================================================
// TYPES DE LOTS
// ====================================================================

define('LOT_TYPE_APPARTEMENT', 'appartement');
define('LOT_TYPE_PARKING', 'parking');
define('LOT_TYPE_CAVE', 'cave');
define('LOT_TYPE_COMMERCE', 'commerce');
define('LOT_TYPE_BUREAU', 'bureau');
define('LOT_TYPE_LOCAL', 'local');

define('LOT_TYPES', [
    LOT_TYPE_APPARTEMENT => 'Appartement',
    LOT_TYPE_PARKING => 'Parking',
    LOT_TYPE_CAVE => 'Cave',
    LOT_TYPE_COMMERCE => 'Commerce',
    LOT_TYPE_BUREAU => 'Bureau',
    LOT_TYPE_LOCAL => 'Local'
]);

// ====================================================================
// TYPES DE BAUX
// ====================================================================

define('BAIL_TYPE_VIDE', 'vide');
define('BAIL_TYPE_MEUBLE', 'meuble');
define('BAIL_TYPE_COMMERCIAL', 'commercial');
define('BAIL_TYPE_PROFESSIONNEL', 'professionnel');

define('BAIL_TYPES', [
    BAIL_TYPE_VIDE => 'Location vide',
    BAIL_TYPE_MEUBLE => 'Location meublée',
    BAIL_TYPE_COMMERCIAL => 'Bail commercial',
    BAIL_TYPE_PROFESSIONNEL => 'Bail professionnel'
]);

// États des baux
define('BAIL_STATE_ACTIF', 'actif');
define('BAIL_STATE_RESILIE', 'resilie');
define('BAIL_STATE_TERMINE', 'termine');
define('BAIL_STATE_SUSPENDU', 'suspendu');

define('BAIL_STATES', [
    BAIL_STATE_ACTIF => 'Actif',
    BAIL_STATE_RESILIE => 'Résilié',
    BAIL_STATE_TERMINE => 'Terminé',
    BAIL_STATE_SUSPENDU => 'Suspendu'
]);

// ====================================================================
// TYPES DE COMPTES COMPTABLES
// ====================================================================

define('ACCOUNT_TYPE_ACTIF', 'actif');
define('ACCOUNT_TYPE_PASSIF', 'passif');
define('ACCOUNT_TYPE_CHARGE', 'charge');
define('ACCOUNT_TYPE_PRODUIT', 'produit');

define('ACCOUNT_TYPES', [
    ACCOUNT_TYPE_ACTIF => 'Actif',
    ACCOUNT_TYPE_PASSIF => 'Passif',
    ACCOUNT_TYPE_CHARGE => 'Charge',
    ACCOUNT_TYPE_PRODUIT => 'Produit'
]);

// ====================================================================
// TYPES D'APPELS DE FONDS
// ====================================================================

define('APPEL_TYPE_PROVISION', 'provision');
define('APPEL_TYPE_REGULARISATION', 'regularisation');
define('APPEL_TYPE_TRAVAUX', 'travaux');
define('APPEL_TYPE_EXCEPTIONNEL', 'exceptionnel');

define('APPEL_TYPES', [
    APPEL_TYPE_PROVISION => 'Provision sur charges',
    APPEL_TYPE_REGULARISATION => 'Régularisation',
    APPEL_TYPE_TRAVAUX => 'Appel pour travaux',
    APPEL_TYPE_EXCEPTIONNEL => 'Appel exceptionnel'
]);

// Statuts des appels de fonds
define('APPEL_STATUS_BROUILLON', 'brouillon');
define('APPEL_STATUS_EMIS', 'emis');
define('APPEL_STATUS_CLOTURE', 'cloture');

define('APPEL_STATUSES', [
    APPEL_STATUS_BROUILLON => 'Brouillon',
    APPEL_STATUS_EMIS => 'Émis',
    APPEL_STATUS_CLOTURE => 'Clôturé'
]);

// ====================================================================
// STATUTS DE PAIEMENT
// ====================================================================

define('PAYMENT_STATUS_ATTENTE', 'attente');
define('PAYMENT_STATUS_PARTIEL', 'partiel');
define('PAYMENT_STATUS_PAYE', 'paye');
define('PAYMENT_STATUS_IMPAYE', 'impaye');
define('PAYMENT_STATUS_CONTENTIEUX', 'contentieux');

define('PAYMENT_STATUSES', [
    PAYMENT_STATUS_ATTENTE => 'En attente',
    PAYMENT_STATUS_PARTIEL => 'Paiement partiel',
    PAYMENT_STATUS_PAYE => 'Payé',
    PAYMENT_STATUS_IMPAYE => 'Impayé',
    PAYMENT_STATUS_CONTENTIEUX => 'Contentieux'
]);

// ====================================================================
// MODES DE PAIEMENT
// ====================================================================

define('PAYMENT_METHOD_VIREMENT', 'virement');
define('PAYMENT_METHOD_CHEQUE', 'cheque');
define('PAYMENT_METHOD_PRELEVEMENT', 'prelevement');
define('PAYMENT_METHOD_ESPECES', 'especes');
define('PAYMENT_METHOD_CARTE', 'carte');

define('PAYMENT_METHODS', [
    PAYMENT_METHOD_VIREMENT => 'Virement bancaire',
    PAYMENT_METHOD_CHEQUE => 'Chèque',
    PAYMENT_METHOD_PRELEVEMENT => 'Prélèvement automatique',
    PAYMENT_METHOD_ESPECES => 'Espèces',
    PAYMENT_METHOD_CARTE => 'Carte bancaire'
]);

// ====================================================================
// TYPES DE TRAVAUX
// ====================================================================

define('TRAVAUX_TYPE_ENTRETIEN', 'entretien');
define('TRAVAUX_TYPE_REPARATION', 'reparation');
define('TRAVAUX_TYPE_AMELIORATION', 'amelioration');
define('TRAVAUX_TYPE_URGENT', 'urgent');

define('TRAVAUX_TYPES', [
    TRAVAUX_TYPE_ENTRETIEN => 'Entretien courant',
    TRAVAUX_TYPE_REPARATION => 'Réparation',
    TRAVAUX_TYPE_AMELIORATION => 'Amélioration',
    TRAVAUX_TYPE_URGENT => 'Travaux urgents'
]);

// Niveaux d'urgence
define('URGENCE_BASSE', 'basse');
define('URGENCE_MOYENNE', 'moyenne');
define('URGENCE_HAUTE', 'haute');
define('URGENCE_CRITIQUE', 'critique');

define('URGENCE_LEVELS', [
    URGENCE_BASSE => 'Basse',
    URGENCE_MOYENNE => 'Moyenne',
    URGENCE_HAUTE => 'Haute',
    URGENCE_CRITIQUE => 'Critique'
]);

// Statuts des travaux
define('TRAVAUX_STATUS_DEMANDE', 'demande');
define('TRAVAUX_STATUS_DEVIS', 'devis');
define('TRAVAUX_STATUS_VOTE', 'vote');
define('TRAVAUX_STATUS_APPROUVE', 'approuve');
define('TRAVAUX_STATUS_PLANIFIE', 'planifie');
define('TRAVAUX_STATUS_ENCOURS', 'encours');
define('TRAVAUX_STATUS_TERMINE', 'termine');
define('TRAVAUX_STATUS_ANNULE', 'annule');

define('TRAVAUX_STATUSES', [
    TRAVAUX_STATUS_DEMANDE => 'Demande',
    TRAVAUX_STATUS_DEVIS => 'En attente de devis',
    TRAVAUX_STATUS_VOTE => 'À voter en AG',
    TRAVAUX_STATUS_APPROUVE => 'Approuvé',
    TRAVAUX_STATUS_PLANIFIE => 'Planifié',
    TRAVAUX_STATUS_ENCOURS => 'En cours',
    TRAVAUX_STATUS_TERMINE => 'Terminé',
    TRAVAUX_STATUS_ANNULE => 'Annulé'
]);

// ====================================================================
// TYPES DE SINISTRES
// ====================================================================

define('SINISTRE_TYPE_DEGAT_EAUX', 'degat_eaux');
define('SINISTRE_TYPE_INCENDIE', 'incendie');
define('SINISTRE_TYPE_VOL', 'vol');
define('SINISTRE_TYPE_VANDALISME', 'vandalisme');
define('SINISTRE_TYPE_NATUREL', 'naturel');
define('SINISTRE_TYPE_AUTRE', 'autre');

define('SINISTRE_TYPES', [
    SINISTRE_TYPE_DEGAT_EAUX => 'Dégât des eaux',
    SINISTRE_TYPE_INCENDIE => 'Incendie',
    SINISTRE_TYPE_VOL => 'Vol',
    SINISTRE_TYPE_VANDALISME => 'Vandalisme',
    SINISTRE_TYPE_NATUREL => 'Catastrophe naturelle',
    SINISTRE_TYPE_AUTRE => 'Autre'
]);

// Statuts des sinistres
define('SINISTRE_STATUS_DECLARE', 'declare');
define('SINISTRE_STATUS_EXPERTISE', 'expertise');
define('SINISTRE_STATUS_INDEMNISATION', 'indemnisation');
define('SINISTRE_STATUS_TRAVAUX', 'travaux');
define('SINISTRE_STATUS_CLOS', 'clos');
define('SINISTRE_STATUS_REJETE', 'rejete');

define('SINISTRE_STATUSES', [
    SINISTRE_STATUS_DECLARE => 'Déclaré',
    SINISTRE_STATUS_EXPERTISE => 'En expertise',
    SINISTRE_STATUS_INDEMNISATION => 'Indemnisation en cours',
    SINISTRE_STATUS_TRAVAUX => 'Travaux en cours',
    SINISTRE_STATUS_CLOS => 'Clos',
    SINISTRE_STATUS_REJETE => 'Rejeté'
]);

// ====================================================================
// TYPES D'ASSEMBLÉES GÉNÉRALES
// ====================================================================

define('AG_TYPE_ORDINAIRE', 'ordinaire');
define('AG_TYPE_EXTRAORDINAIRE', 'extraordinaire');

define('AG_TYPES', [
    AG_TYPE_ORDINAIRE => 'Assemblée Générale Ordinaire',
    AG_TYPE_EXTRAORDINAIRE => 'Assemblée Générale Extraordinaire'
]);

// Statuts des AG
define('AG_STATUS_PLANIFIEE', 'planifiee');
define('AG_STATUS_CONVOQUEE', 'convoquee');
define('AG_STATUS_TENUE', 'tenue');
define('AG_STATUS_ANNULEE', 'annulee');

define('AG_STATUSES', [
    AG_STATUS_PLANIFIEE => 'Planifiée',
    AG_STATUS_CONVOQUEE => 'Convoquée',
    AG_STATUS_TENUE => 'Tenue',
    AG_STATUS_ANNULEE => 'Annulée'
]);

// Résultats des votes
define('VOTE_RESULTAT_ADOPTE', 'adopte');
define('VOTE_RESULTAT_REJETE', 'rejete');
define('VOTE_RESULTAT_REPORTE', 'reporte');

define('VOTE_RESULTATS', [
    VOTE_RESULTAT_ADOPTE => 'Adopté',
    VOTE_RESULTAT_REJETE => 'Rejeté',
    VOTE_RESULTAT_REPORTE => 'Reporté'
]);

// ====================================================================
// CATÉGORIES DE DOCUMENTS
// ====================================================================

define('DOC_CATEGORY_REGLEMENT', 'reglement');
define('DOC_CATEGORY_AG', 'ag');
define('DOC_CATEGORY_CONTRAT', 'contrat');
define('DOC_CATEGORY_FACTURE', 'facture');
define('DOC_CATEGORY_DEVIS', 'devis');
define('DOC_CATEGORY_PLAN', 'plan');
define('DOC_CATEGORY_PHOTO', 'photo');
define('DOC_CATEGORY_CORRESPONDANCE', 'correspondance');
define('DOC_CATEGORY_AUTRE', 'autre');

define('DOC_CATEGORIES', [
    DOC_CATEGORY_REGLEMENT => 'Règlement de copropriété',
    DOC_CATEGORY_AG => 'Assemblée générale',
    DOC_CATEGORY_CONTRAT => 'Contrat',
    DOC_CATEGORY_FACTURE => 'Facture',
    DOC_CATEGORY_DEVIS => 'Devis',
    DOC_CATEGORY_PLAN => 'Plan',
    DOC_CATEGORY_PHOTO => 'Photo',
    DOC_CATEGORY_CORRESPONDANCE => 'Correspondance',
    DOC_CATEGORY_AUTRE => 'Autre'
]);

// ====================================================================
// TYPES DE NOTIFICATIONS
// ====================================================================

define('NOTIF_TYPE_INFO', 'info');
define('NOTIF_TYPE_WARNING', 'warning');
define('NOTIF_TYPE_ERROR', 'error');
define('NOTIF_TYPE_SUCCESS', 'success');

define('NOTIF_TYPES', [
    NOTIF_TYPE_INFO => 'Information',
    NOTIF_TYPE_WARNING => 'Avertissement',
    NOTIF_TYPE_ERROR => 'Erreur',
    NOTIF_TYPE_SUCCESS => 'Succès'
]);

// ====================================================================
// CIVILITÉS
// ====================================================================

define('CIVILITE_M', 'M');
define('CIVILITE_MME', 'Mme');
define('CIVILITE_MLLE', 'Mlle');

define('CIVILITES', [
    CIVILITE_M => 'Monsieur',
    CIVILITE_MME => 'Madame',
    CIVILITE_MLLE => 'Mademoiselle'
]);

// ====================================================================
// EXERCICES COMPTABLES
// ====================================================================

define('EXERCICE_STATUS_OUVERT', 'ouvert');
define('EXERCICE_STATUS_CLOTURE', 'cloture');
define('EXERCICE_STATUS_ARCHIVE', 'archive');

define('EXERCICE_STATUSES', [
    EXERCICE_STATUS_OUVERT => 'Ouvert',
    EXERCICE_STATUS_CLOTURE => 'Clôturé',
    EXERCICE_STATUS_ARCHIVE => 'Archivé'
]);

// ====================================================================
// MOYENS DE RELANCE
// ====================================================================

define('RELANCE_EMAIL', 'email');
define('RELANCE_COURRIER', 'courrier');
define('RELANCE_TELEPHONE', 'telephone');
define('RELANCE_SMS', 'sms');

define('RELANCE_MOYENS', [
    RELANCE_EMAIL => 'Email',
    RELANCE_COURRIER => 'Courrier',
    RELANCE_TELEPHONE => 'Téléphone',
    RELANCE_SMS => 'SMS'
]);

// ====================================================================
// ACTIONS DES LOGS
// ====================================================================

define('LOG_ACTION_LOGIN', 'login');
define('LOG_ACTION_LOGOUT', 'logout');
define('LOG_ACTION_CREATE', 'create');
define('LOG_ACTION_UPDATE', 'update');
define('LOG_ACTION_DELETE', 'delete');
define('LOG_ACTION_VIEW', 'view');
define('LOG_ACTION_EXPORT', 'export');
define('LOG_ACTION_IMPORT', 'import');

define('LOG_ACTIONS', [
    LOG_ACTION_LOGIN => 'Connexion',
    LOG_ACTION_LOGOUT => 'Déconnexion',
    LOG_ACTION_CREATE => 'Création',
    LOG_ACTION_UPDATE => 'Modification',
    LOG_ACTION_DELETE => 'Suppression',
    LOG_ACTION_VIEW => 'Consultation',
    LOG_ACTION_EXPORT => 'Export',
    LOG_ACTION_IMPORT => 'Import'
]);

// ====================================================================
// MESSAGES DE SUCCÈS/ERREUR
// ====================================================================

define('MSG_SUCCESS_SAVE', 'Enregistrement effectué avec succès.');
define('MSG_SUCCESS_UPDATE', 'Modification effectuée avec succès.');
define('MSG_SUCCESS_DELETE', 'Suppression effectuée avec succès.');
define('MSG_SUCCESS_LOGIN', 'Connexion réussie. Bienvenue !');
define('MSG_SUCCESS_LOGOUT', 'Déconnexion réussie.');

define('MSG_ERROR_SAVE', 'Erreur lors de l\'enregistrement.');
define('MSG_ERROR_UPDATE', 'Erreur lors de la modification.');
define('MSG_ERROR_DELETE', 'Erreur lors de la suppression.');
define('MSG_ERROR_LOGIN', 'Identifiants incorrects.');
define('MSG_ERROR_ACCESS_DENIED', 'Accès refusé. Vous n\'avez pas les permissions nécessaires.');
define('MSG_ERROR_SESSION_EXPIRED', 'Votre session a expiré. Veuillez vous reconnecter.');
define('MSG_ERROR_INVALID_TOKEN', 'Token de sécurité invalide. Veuillez réessayer.');
define('MSG_ERROR_FILE_UPLOAD', 'Erreur lors de l\'upload du fichier.');
define('MSG_ERROR_FILE_TYPE', 'Type de fichier non autorisé.');
define('MSG_ERROR_FILE_SIZE', 'La taille du fichier dépasse la limite autorisée.');
define('MSG_ERROR_REQUIRED_FIELDS', 'Veuillez remplir tous les champs obligatoires.');
define('MSG_ERROR_INVALID_EMAIL', 'Adresse email invalide.');
define('MSG_ERROR_INVALID_DATE', 'Format de date invalide.');
define('MSG_ERROR_DATABASE', 'Erreur de connexion à la base de données.');
define('MSG_ERROR_NOT_FOUND', 'Élément non trouvé.');
define('MSG_ERROR_DUPLICATE', 'Cet élément existe déjà.');

// ====================================================================
// RÉGULATIONS ET DÉLAIS
// ====================================================================

// Délai de préavis pour résiliation de bail (en mois)
define('PREAVIS_BAIL_VIDE', 3);
define('PREAVIS_BAIL_MEUBLE', 1);

// Délai de convocation pour AG (en jours)
define('DELAI_CONVOCATION_AG', 21);

// Délai de paiement des appels de fonds (en jours)
define('DELAI_PAIEMENT_APPEL', 30);

// ====================================================================
// FIN DES CONSTANTES
// ====================================================================
