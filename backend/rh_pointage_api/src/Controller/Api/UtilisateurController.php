<?php

namespace App\Controller\Api;

use App\Entity\Utilisateur;
use App\Repository\UtilisateurRepository;
use App\Service\Utilisateur\CreateUtilisateurService;
use App\Service\Utilisateur\UpdateUtilisateurService;
use Doctrine\ORM\EntityManagerInterface;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[OA\Tag(name: 'Utilisateurs (admin)')]
#[IsGranted('ROLE_ADMIN')]
#[Route('/api/utilisateurs')]
class UtilisateurController extends AbstractController
{
    use ApiControllerHelperTrait;

    public function __construct(
        private UtilisateurRepository $utilisateurRepository,
        private CreateUtilisateurService $createUtilisateurService,
        private UpdateUtilisateurService $updateUtilisateurService,
        private EntityManagerInterface $entityManager
    ) {}

    private function serializeUtilisateur(Utilisateur $utilisateur): array
    {
        return [
            'id' => $utilisateur->getId(),
            'matricule' => $utilisateur->getMatricule(),
            'nom' => $utilisateur->getNom(),
            'prenom' => $utilisateur->getPrenom(),
            'email' => $utilisateur->getEmail(),
            'telephone' => $utilisateur->getTelephone(),
            'poste' => $utilisateur->getPoste(),
            'dateEmbauche' => $utilisateur->getDateEmbauche()?->format('Y-m-d'),
            'actif' => $utilisateur->isActif(),
            'role' => $utilisateur->getRole()?->value,
            'departement' => $utilisateur->getDepartement() ? [
                'id' => $utilisateur->getDepartement()->getId(),
                'label' => $utilisateur->getDepartement()->getLabel(),
            ] : null,
        ];
    }

    #[Route('', methods: ['GET'])]
    public function index(): JsonResponse
    {
        $utilisateurs = $this->utilisateurRepository->findAll();

        return new JsonResponse(array_map(
            fn(Utilisateur $u) => $this->serializeUtilisateur($u),
            $utilisateurs
        ));
    }

    #[Route('/{id}', methods: ['GET'])]
    public function show(int $id): JsonResponse
    {
        $utilisateur = $this->utilisateurRepository->find($id);
        $this->assertFound($utilisateur, 'Utilisateur non trouvé.', 'UTILISATEUR_NOT_FOUND');

        return new JsonResponse($this->serializeUtilisateur($utilisateur));
    }

    #[Route('', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = $this->parseJson($request);
        $utilisateur = $this->createUtilisateurService->create($data);

        return new JsonResponse($this->serializeUtilisateur($utilisateur), 201);
    }

    #[Route('/{id}', methods: ['PUT'])]
    public function update(int $id, Request $request): JsonResponse
    {
        $data = $this->parseJson($request);
        $utilisateur = $this->updateUtilisateurService->update($id, $data);

        return new JsonResponse($this->serializeUtilisateur($utilisateur));
    }

    #[Route('/{id}', methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        $utilisateur = $this->utilisateurRepository->find($id);
        $this->assertFound($utilisateur, 'Utilisateur non trouvé.', 'UTILISATEUR_NOT_FOUND');

        $this->entityManager->remove($utilisateur);
        $this->entityManager->flush();

        return new JsonResponse(null, 204);
    }
}
