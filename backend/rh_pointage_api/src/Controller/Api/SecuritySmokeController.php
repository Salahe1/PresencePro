<?php

namespace App\Controller\Api;

use App\Entity\Utilisateur;
use App\Exception\ApiException;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[OA\Tag(name: 'Auth & Divers')]
final class SecuritySmokeController extends AbstractController
{
    #[OA\Post(
        path: '/api/login_check',
        summary: 'S\'authentifier pour obtenir un token JWT',
        description: 'Envoie les identifiants pour recevoir un token de porteur (Bearer token).',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/LoginRequest')
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Authentification réussie',
                content: new OA\JsonContent(ref: '#/components/schemas/TokenJWT')
            ),
            new OA\Response(
                response: 401,
                description: 'Identifiants invalides',
                content: new OA\JsonContent(ref: '#/components/schemas/ApiError')
            ),
        ]
    )]
    public function loginCheckProxy() {}

    #[Route('/api/health', name: 'api_health', methods: ['GET'])]
    public function health(): JsonResponse
    {
        return new JsonResponse(['status' => 'ok']);
    }

    #[Route('/api/me', name: 'api_me', methods: ['GET'])]
    #[IsGranted('ROLE_MANAGER')]
    public function me(): JsonResponse
    {
        $user = $this->getUser();

        if (!$user instanceof Utilisateur) {
            throw new ApiException(
                'Utilisateur non authentifié.',
                401,
                'UNAUTHENTICATED'
            );
        }

        return new JsonResponse([
            'email' => $user->getUserIdentifier(),
            'roles' => $user->getRoles(),
        ]);
    }
}
