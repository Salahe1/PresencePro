<?php

namespace App\Controller\Api;


use App\Service\Terminal\AuthentificationTerminalService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Lexik\Bundle\JWTAuthenticationBundle\Encoder\JWTEncoderInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Uid\Ulid;


#[Route('/api/tablet', name: 'api_tablet')]
class TabletAuthController extends AbstractController 
{
    public function __construct(private AuthentificationTerminalService $authService,
                                    private JWTEncoderInterface $jwtEncoder      ) {}

    #[Route('/token', methods:['POST'])]
    public function getToken(Request $request): JsonResponse
    {
        try {
            $payload = $request->toArray();
        } catch (\JsonException $e) {
            return new JsonResponse(['error' => 'JSON invalide'], 400);
        }

        $identifiantRaw = $payload['identifiant'] ?? null;
        $secret = $payload['secret'] ?? null;

        if ($identifiantRaw === null || $secret === null) {
            return new JsonResponse(['error' => 'Données manquantes'], 400);
        }

        try {
             $identifiant = new Ulid($identifiantRaw);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Identifiant invalide'], 400);
        }

        $terminalPointage = $this->authService->authentifier($identifiant, $secret);

        if ($terminalPointage === null) {
             return new JsonResponse(['error' => 'Identifiants terminal invalides'], 401);
         }
        $payload = [
        'username' => $terminalPointage->getIdentifiantAsString(),
        'roles' => ['ROLE_TABLET'],
        'terminal_id' => $terminalPointage->getIdentifiantAsString(),
        'type' => 'terminal',
        'exp' => time() + 900,];

        $jwt = $this->jwtEncoder->encode($payload);

        return new JsonResponse(['token' => $jwt, 'expires_in' => 900,]);

    }
}
