<?php

namespace App\Controller\Api;

use App\Entity\Employe;
use App\Repository\EmployeRepository;
use App\Repository\DepartementRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use OpenApi\Attributes as OA;

#[Route('/api/employes')]
class EmployeController extends AbstractController
{
    public function __construct(
        private EmployeRepository $employeRepository,
        private DepartementRepository $departementRepository,
        private EntityManagerInterface $entityManager
    ){}

    #[OA\Get(
        path: '/api/employes',
        summary: 'Liste tous les employés',
        description: 'Retourne la liste complète des employés (actifs et inactifs).',
        security: [['Bearer' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Liste des employés',
                content: new OA\JsonContent(
                    type: 'array',
                    items: new OA\Items(ref: '#/components/schemas/Employe')
                )
            ),
            new OA\Response(response: 401, description: 'Non authentifié'),
        ]
    )]
    
    #[Route('', methods: ['GET'])]
    public function index(): JsonResponse
    {
        $employes = $this->employeRepository->findAll();
        $data = array_map(fn(Employe $employe) => [
            'id' => $employe->getId(),
            'nom' => $employe->getNom(),
            'prenom' => $employe->getPrenom(),
            'email' => $employe->getEmail(),
            'matricule' => $employe->getMatricule(),
            'telephone' => $employe->getTelephone(),
            'poste' => $employe->getPoste(),
            'dateEmbauche' => $employe->getDateEmbauche()?->format('Y-m-d'),
            'actif' => $employe->isActif(),
            'photo' => $employe->getPhoto(),
            'departement' => $employe->getDepartement() ? [
                'id' => $employe->getDepartement()->getId(),
                'label' => $employe->getDepartement()->getLabel(),
            ] : null,
        ], $employes);

        return new JsonResponse($data);
    }


     #[OA\Get(
        path: '/api/employes/{id}',
        summary: 'Détail d\'un employé',
        security: [['Bearer' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Employé trouvé',     content: new OA\JsonContent(ref: '#/components/schemas/Employe')),
            new OA\Response(response: 404, description: 'Employé non trouvé', content: new OA\JsonContent(properties: [new OA\Property(property: 'error', type: 'string')])),
            new OA\Response(response: 401, description: 'Non authentifié'),
        ]
    )]

    #[Route('/{id}', methods: ['GET'])]
    public function show(int $id): JsonResponse
    {
        $employe = $this->employeRepository->find($id);
        if (!$employe) {
            return new JsonResponse(['error' => 'Employé non trouvé'], 404);
        }

        return new JsonResponse([
            'id' => $employe->getId(),
            'nom' => $employe->getNom(),
            'prenom' => $employe->getPrenom(),
            'email' => $employe->getEmail(),
            'matricule' => $employe->getMatricule(),
            'telephone' => $employe->getTelephone(),
            'poste' => $employe->getPoste(),
            'dateEmbauche' => $employe->getDateEmbauche()?->format('Y-m-d'),
            'actif' => $employe->isActif(),
            'photo' => $employe->getPhoto(),
            'departement' => $employe->getDepartement() ? [
                'id' => $employe->getDepartement()->getId(),
                'label' => $employe->getDepartement()->getLabel(),
            ] : null,
        ]);
    }

    #[OA\Post(
        path: '/api/employes',
        summary: 'Créer un employé',
        security: [['Bearer' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['nom', 'prenom', 'email', 'matricule', 'poste', 'dateEmbauche'],
                properties: [
                    new OA\Property(property: 'nom',          type: 'string',  example: 'Dupont'),
                    new OA\Property(property: 'prenom',       type: 'string',  example: 'Jean'),
                    new OA\Property(property: 'email',        type: 'string',  format: 'email', example: 'jean.dupont@entreprise.com'),
                    new OA\Property(property: 'matricule',    type: 'string',  example: 'EMP-001'),
                    new OA\Property(property: 'poste',        type: 'string',  example: 'Développeur'),
                    new OA\Property(property: 'dateEmbauche', type: 'string',  format: 'date',  example: '2024-01-15'),
                    new OA\Property(property: 'telephone',    type: 'string',  example: '+212600000000'),
                    new OA\Property(property: 'departementId',type: 'integer', example: 1),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Employé créé',              content: new OA\JsonContent(ref: '#/components/schemas/Employe')),
            new OA\Response(response: 400, description: 'Champs requis manquants',   content: new OA\JsonContent(properties: [new OA\Property(property: 'error', type: 'string')])),
            new OA\Response(response: 401, description: 'Non authentifié'),
        ]
    )]

    #[Route('', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        if (!isset($data['nom'], $data['prenom'], $data['email'], $data['matricule'], $data['poste'], $data['dateEmbauche'])) {
            return new JsonResponse(['error' => 'Champs requis manquants'], 400);
        }

        $employe = new Employe();
        $employe->setNom($data['nom']);
        $employe->setPrenom($data['prenom']);
        $employe->setEmail($data['email']);
        $employe->setMatricule($data['matricule']);
        $employe->setPoste($data['poste']);
        $employe->setDateEmbauche(new \DateTimeImmutable($data['dateEmbauche']));
        
        if (isset($data['departementId'])) {
            $departement = $this->departementRepository->find($data['departementId']);
            if ($departement) {
                $employe->setDepartement($departement);
            }
        }
        if (isset($data['telephone'])) {
            $employe->setTelephone($data['telephone']);
        }

        $this->entityManager->persist($employe);
        $this->entityManager->flush();

        return new JsonResponse([
            'id' => $employe->getId(),
            'nom' => $employe->getNom(),
            'prenom' => $employe->getPrenom(),
            'email' => $employe->getEmail(),
            'matricule' => $employe->getMatricule(),
            'poste' => $employe->getPoste(),
            'dateEmbauche' => $employe->getDateEmbauche()?->format('Y-m-d'),
            'actif' => $employe->isActif(),
            'photo' => $employe->getPhoto(),
            'departement' => $employe->getDepartement() ? [
                'id' => $employe->getDepartement()->getId(),
                'label' => $employe->getDepartement()->getLabel(),
            ] : null,
        ], 201);
    }


     #[OA\Put(
        path: '/api/employes/{id}',
        summary: 'Modifier un employé',
        security: [['Bearer' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'nom',           type: 'string'),
                    new OA\Property(property: 'prenom',        type: 'string'),
                    new OA\Property(property: 'email',         type: 'string', format: 'email'),
                    new OA\Property(property: 'matricule',     type: 'string'),
                    new OA\Property(property: 'poste',         type: 'string'),
                    new OA\Property(property: 'dateEmbauche',  type: 'string', format: 'date'),
                    new OA\Property(property: 'telephone',     type: 'string'),
                    new OA\Property(property: 'actif',         type: 'boolean'),
                    new OA\Property(property: 'departementId', type: 'integer', nullable: true),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Employé mis à jour', content: new OA\JsonContent(ref: '#/components/schemas/Employe')),
            new OA\Response(response: 404, description: 'Employé non trouvé'),
            new OA\Response(response: 401, description: 'Non authentifié'),
        ]
    )]

    #[Route('/{id}', methods: ['PUT'])]
    public function update(int $id, Request $request): JsonResponse
    {
        $employe = $this->employeRepository->find($id);
        if (!$employe) {
            return new JsonResponse(['error' => 'Employé non trouvé'], 404);
        }

        $data = json_decode($request->getContent(), true);
        if (isset($data['nom'])) {
            $employe->setNom($data['nom']);
        }
        if (isset($data['prenom'])) {
            $employe->setPrenom($data['prenom']);
        }
        if (isset($data['email'])) {
            $employe->setEmail($data['email']);
        }
        if (isset($data['matricule'])) {
            $employe->setMatricule($data['matricule']);
        }
        if (isset($data['poste'])) {
            $employe->setPoste($data['poste']);
        }
        if (isset($data['dateEmbauche'])) {
            $employe->setDateEmbauche(new \DateTimeImmutable($data['dateEmbauche']));
        }
        if (isset($data['telephone'])) {
            $employe->setTelephone($data['telephone']);
        }
        if (isset($data['actif'])) {
            $employe->setActif((bool)$data['actif']);
        }
        if (isset($data['departementId'])) {
            $departement = $this->departementRepository->find($data['departementId']);
            if ($departement) {
                $employe->setDepartement($departement);
            }
        } else if (array_key_exists('departementId', $data) && $data['departementId'] === null) {
            $employe->setDepartement(null);
        }

        $this->entityManager->flush();

        return new JsonResponse([
            'id' => $employe->getId(),
            'nom' => $employe->getNom(),
            'prenom' => $employe->getPrenom(),
            'email' => $employe->getEmail(),
            'matricule' => $employe->getMatricule(),
            'poste' => $employe->getPoste(),
            'dateEmbauche' => $employe->getDateEmbauche()?->format('Y-m-d'),
            'actif' => $employe->isActif(),
            'photo' => $employe->getPhoto(),
            'departement' => $employe->getDepartement() ? [
                'id' => $employe->getDepartement()->getId(),
                'label' => $employe->getDepartement()->getLabel(),
            ] : null,
        ]);
    }


    #[OA\Delete(
        path: '/api/employes/{id}',
        summary: 'Supprimer un employé',
        description: 'Supprime définitivement un employé. Préférer /desactiver pour l\'archivage.',
        security: [['Bearer' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 204, description: 'Supprimé avec succès'),
            new OA\Response(response: 404, description: 'Employé non trouvé'),
            new OA\Response(response: 401, description: 'Non authentifié'),
        ]
    )]
    #[Route('/{id}', methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        $employe = $this->employeRepository->find($id);
        if (!$employe) {
            return new JsonResponse(['error' => 'Employé non trouvé'], 404);
        }

        $this->entityManager->remove($employe);
        $this->entityManager->flush();

        return new JsonResponse(null, 204);
    }


    #[OA\Patch(
        path: '/api/employes/{id}/desactiver',
        summary: 'Désactiver (archiver) un employé',
        description: 'Passe l\'employé en inactif sans le supprimer. Les données biométriques sont conservées jusqu\'à suppression manuelle.',
        security: [['Bearer' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Employé désactivé', content: new OA\JsonContent(ref: '#/components/schemas/Employe')),
            new OA\Response(response: 404, description: 'Employé non trouvé'),
            new OA\Response(response: 401, description: 'Non authentifié'),
        ]
    )]
    #[Route('/{id}/desactiver', methods: ['PATCH'])]
    public function desactiver(int $id): JsonResponse
    {
        $employe = $this->employeRepository->find($id);
        if (!$employe) {
            return new JsonResponse(['error' => 'Employé non trouvé'], 404);
        }

        $employe->setActif(false);
        $this->entityManager->flush();

        return new JsonResponse([
            'id' => $employe->getId(),
            'nom' => $employe->getNom(),
            'prenom' => $employe->getPrenom(),
            'email' => $employe->getEmail(),
            'matricule' => $employe->getMatricule(),
            'actif' => $employe->isActif(),
            'departement' => $employe->getDepartement() ? [
                'id' => $employe->getDepartement()->getId(),
                'label' => $employe->getDepartement()->getLabel(),
            ] : null,
        ]);
    }
}