<?php

namespace App\Services;

use App\Models\FcmToken;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;
use Illuminate\Support\Facades\Log;

class FcmNotificationService
{
    private $messaging;

    public function __construct()
    {
        $factory = (new Factory)->withServiceAccount(env('FIREBASE_CREDENTIALS'));
        $this->messaging = $factory->createMessaging();
    }

    /**
     * Envoyer une notification à un rôle spécifique (et optionnellement une boutique)
     */
    public function envoyerAuRole(string $role, string $titre, string $message, ?int $boutiqueId = null, array $data = []): void
    {
        $query = FcmToken::where('role', $role);
        if ($boutiqueId) $query->where('boutique_id', $boutiqueId);
        $tokens = $query->pluck('token')->toArray();

        if (empty($tokens)) return;

        $this->envoyerAuxTokens($tokens, $titre, $message, $data);
    }

    /**
     * Envoyer à un utilisateur spécifique (par rôle + user_id)
     */
    public function envoyerAUtilisateur(string $role, int $userId, string $titre, string $message, array $data = []): void
    {
        $tokens = FcmToken::where('role', $role)->where('user_id', $userId)->pluck('token')->toArray();

        if (empty($tokens)) return;

        $this->envoyerAuxTokens($tokens, $titre, $message, $data);
    }

    /**
     * Envoyer à plusieurs rôles
     */
    public function envoyerAuxRoles(array $roles, string $titre, string $message, ?int $boutiqueId = null, array $data = []): void
    {
        $query = FcmToken::whereIn('role', $roles);
        if ($boutiqueId) $query->where('boutique_id', $boutiqueId);
        $tokens = $query->pluck('token')->toArray();

        if (empty($tokens)) return;

        $this->envoyerAuxTokens($tokens, $titre, $message, $data);
    }

    /**
     * Envoyer aux tokens
     */
    private function envoyerAuxTokens(array $tokens, string $titre, string $message, array $data = []): void
    {
        try {
            $notification = Notification::create($titre, $message);

            foreach ($tokens as $token) {
                try {
                    $msg = CloudMessage::withTarget('token', $token)
                        ->withNotification($notification)
                        ->withData(array_map('strval', $data));

                    $this->messaging->send($msg);
                } catch (\Kreait\Firebase\Exception\Messaging\NotFound $e) {
                    // Token invalide → supprimer
                    FcmToken::where('token', $token)->delete();
                } catch (\Kreait\Firebase\Exception\Messaging\InvalidMessage $e) {
                    FcmToken::where('token', $token)->delete();
                } catch (\Exception $e) {
                    Log::warning('FCM envoi échoué: ' . $e->getMessage());
                }
            }
        } catch (\Exception $e) {
            Log::error('FCM erreur globale: ' . $e->getMessage());
        }
    }
}
