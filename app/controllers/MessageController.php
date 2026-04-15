<?php
/**
 * ====================================================================
 * SYND_GEST - Contrôleur Messagerie Interne
 * ====================================================================
 * Messagerie bidirectionnelle (pas temps réel) pour tous les utilisateurs.
 */

class MessageController extends Controller {

    /**
     * Boîte de réception
     */
    public function index() {
        $this->requireAuth();

        $model = $this->model('Message');
        $userId = (int)$_SESSION['user_id'];

        $this->view('messages/index', [
            'title'    => 'Messagerie - ' . APP_NAME,
            'showNavbar' => true,
            'messages' => $model->getInbox($userId),
            'nonLus'   => $model->getUnreadCount($userId),
            'flash'    => $this->getFlash()
        ], true);
    }

    /**
     * Voir un message + son fil de conversation
     */
    public function show($id) {
        $this->requireAuth();

        $model = $this->model('Message');
        $userId = (int)$_SESSION['user_id'];

        $message = $model->findWithSender($id);

        if (!$message) {
            $this->setFlash('error', 'Message introuvable');
            $this->redirect('message/index');
            return;
        }

        // Marquer comme lu
        $model->markAsRead($id, $userId);

        // Fil de conversation
        $threadId = $message['parent_id'] ?? $message['id'];
        $thread = $model->getThread($threadId);

        // Marquer tous les messages du fil comme lus
        $model->markThreadAsRead(array_column($thread, 'id'), $userId);

        $this->view('messages/show', [
            'title'    => $message['sujet'] . ' - ' . APP_NAME,
            'showNavbar' => true,
            'message'  => $message,
            'thread'   => $thread,
            'threadId' => $threadId,
            'flash'    => $this->getFlash()
        ], true);
    }

    /**
     * Formulaire nouveau message
     */
    public function compose() {
        $this->requireAuth();

        $model = $this->model('Message');
        $currentRole = $_SESSION['user_role'] ?? '';
        $userId = (int)$_SESSION['user_id'];

        $users = $model->getDestinataires($currentRole, $userId);
        $residences = ($currentRole === 'admin') ? $model->getResidencesForGroupSend() : [];

        // Pré-remplir si c'est une réponse
        $replyTo = (int)($_GET['reply'] ?? 0);
        $replyData = $replyTo ? $model->getReplyData($replyTo) : null;

        $this->view('messages/compose', [
            'title'      => 'Nouveau message - ' . APP_NAME,
            'showNavbar' => true,
            'users'      => $users,
            'residences' => $residences,
            'preTo'      => (int)($_GET['to'] ?? 0),
            'replyData'  => $replyData,
            'currentRole'=> $currentRole,
            'flash'      => $this->getFlash()
        ], true);
    }

    /**
     * Envoyer un message (POST)
     */
    public function send() {
        $this->requireAuth();
        $this->verifyCsrf();

        $model = $this->model('Message');
        $userId = (int)$_SESSION['user_id'];
        $db = $model->getDb();

        try {
            $sujet = trim($_POST['sujet'] ?? '');
            $contenu = trim($_POST['contenu'] ?? '');
            $priorite = $_POST['priorite'] ?? 'normale';
            $typeEnvoi = $_POST['type_envoi'] ?? 'individuel';
            $parentId = (int)($_POST['parent_id'] ?? 0) ?: null;
            $residenceId = (int)($_POST['residence_id'] ?? 0) ?: null;

            if (empty($sujet) || empty($contenu)) {
                throw new Exception("Sujet et contenu requis.");
            }

            $db->beginTransaction();

            // Insérer le message
            $messageId = $model->createMessage($userId, [
                'parent_id'    => $parentId,
                'sujet'        => $sujet,
                'contenu'      => $contenu,
                'priorite'     => $priorite,
                'type_envoi'   => $typeEnvoi,
                'residence_id' => $residenceId,
            ]);

            // Déterminer les destinataires
            if ($typeEnvoi === 'individuel') {
                $destinataires = array_filter(array_map('intval', $_POST['destinataires'] ?? []), fn($id) => $id > 0);
            } else {
                $destinataires = $model->resolveDestinataires($typeEnvoi, ['residence_id' => $residenceId]);
            }

            // Exclure l'expéditeur
            $destinataires = array_filter($destinataires, fn($id) => (int)$id !== $userId);

            if (empty($destinataires)) {
                throw new Exception("Aucun destinataire valide.");
            }

            // Insérer les destinataires
            $model->addDestinataires($messageId, $destinataires);

            $db->commit();

            $nbDest = count($destinataires);
            $this->setFlash('success', "Message envoyé à $nbDest destinataire(s).");
            $this->redirect($parentId ? 'message/show/' . $parentId : 'message/index');

        } catch (Exception $e) {
            if ($db->inTransaction()) $db->rollBack();
            $this->setFlash('error', $e->getMessage());
            $this->redirect('message/compose');
        }
    }

    /**
     * Nombre de messages non lus (AJAX pour le badge navbar)
     */
    public function unreadCount() {
        $this->requireAuth();
        header('Content-Type: application/json');

        $model = $this->model('Message');
        echo json_encode(['count' => $model->getUnreadCount((int)$_SESSION['user_id'])]);
        exit;
    }
}
