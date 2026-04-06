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

#[Route('/api/employes')]
class EmployeController extends AbstractController
{
    public function __construct(
        private EmployeRepository $employeRepository,
        private DepartementRepository $departementRepository,
        private EntityManagerInterface $entityManager
    ){}

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