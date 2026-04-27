<?php

namespace App\Controller\Api;

use App\Exception\ApiException;
use App\Service\Terminal\AuthentificationTerminalService;
use Lexik\Bundle\JWTAuthenticationBundle\Encoder\JWTEncoderInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Uid\Ulid;

#[Route('/api/tablet', name: 'api_tablet')]
class TabletAuthController extends AbstractController
{
    use ApiControllerHelperTrait;

    public function __construct(
        private AuthentificationTerminalService $authService,
        private JWTEncoderInterface $jwtEncoder
    ) {}

    #[Route('/token', methods: ['POST'])]
    public function getToken(Request $request): JsonResponse
    {
        $payload = $this->parseJson($request);
        $this->requireFields($payload, ['identifiant', 'secret']);

        try {
            $identifiant = new Ulid((string) $payload['identifiant']);
        } catch (\Throwable) {
            throw new ApiException(
                'Identifiant terminal invalide.',
                422,
                'INVALID_TERMINAL_IDENTIFIER',
                ['identifiant' => ['Ce champ doit être un ULID valide.']]
            );
        }

        $terminalPointage = $this->authService->authentifier($identifiant, (string) $payload['secret']);

        if ($terminalPointage === null) {
            throw new ApiException(
                'Identifiants terminal invalides.',
                401,
                'INVALID_TERMINAL_CREDENTIALS'
            );
        }

        $payload = [
            'username' => $terminalPointage->getIdentifiantAsString(),
            'roles' => ['ROLE_TABLET'],
            'terminal_id' => $terminalPointage->getIdentifiantAsString(),
            'type' => 'terminal',
            'exp' => time() + 900,
        ];

        $jwt = $this->jwtEncoder->encode($payload);

        return new JsonResponse([
            'token' => $jwt,
            'expires_in' => 900,
        ]);
    }
}
