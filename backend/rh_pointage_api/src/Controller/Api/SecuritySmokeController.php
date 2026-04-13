<?php

namespace App\Controller\Api;

use App\Entity\Utilisateur;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Auth & Divers')]
final class SecuritySmokeController extends AbstractController
{
    /**
     * Documentation virtuelle pour le point de terminaison de connexion (géré par LexikJWT).
     */
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

    #[OA\Get(
        path: '/api/health',
        summary: 'Vérification de l\'état de l\'API',
        responses: [
            new OA\Response(response: 200, description: 'L\'API est opérationnelle')
        ]
    )]
    #[Route('/api/health', name: 'api_health', methods: ['GET'])]
    public function health(): JsonResponse
    {
        return new JsonResponse(['status' => 'ok']);
    }

    #[OA\Get(
        path: '/api/me',
        summary: 'Récupérer les informations de l\'utilisateur connecté',
        security: [['Bearer' => []]],
        responses: [
            new OA\Response(
                response: 200, 
                description: 'Informations utilisateur',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'email', type: 'string', example: 'admin@grh.ma'),
                        new OA\Property(property: 'roles', type: 'array', items: new OA\Items(type: 'string', example: 'ROLE_ADMIN')),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Non authentifié'),
        ]
    )]
    #[Route('/api/me', name: 'api_me', methods: ['GET'])]
    #[IsGranted('ROLE_MANAGER')]
    public function me(): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof Utilisateur) {
            return new JsonResponse(['message' => 'Not authenticated'], 401);
        }

        return new JsonResponse([
            'email' => $user->getUserIdentifier(),
            'roles' => $user->getRoles(),
        ]);
    }
}
