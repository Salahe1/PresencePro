<?php

namespace App\Controller\Api;

use App\Entity\Employe;
use App\Exception\ApiException;
use App\Repository\DepartementRepository;
use App\Repository\EmployeRepository;
use App\Service\Biometrique\BiometriqueEnrollmentService;
use App\Service\Employe\EmployePhotoUploadService;
use Doctrine\ORM\EntityManagerInterface;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/employes')]
class EmployeController extends AbstractController
{
    use ApiControllerHelperTrait;

    public function __construct(
        private EmployeRepository $employeRepository,
        private DepartementRepository $departementRepository,
        private BiometriqueEnrollmentService $biometriqueEnrollmentService,
        private EntityManagerInterface $entityManager,
        private EmployePhotoUploadService $photoUploadService,
    ) {}

    private function serializeEmploye(Employe $employe): array
    {
        return [
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
            'biometriqueData' => $employe->getBiometriqueData(),
            'departement' => $employe->getDepartement() ? [
                'id' => $employe->getDepartement()->getId(),
                'label' => $employe->getDepartement()->getLabel(),
            ] : null,
        ];
    }

    #[Route('', methods: ['GET'])]
    public function index(): JsonResponse
    {
        $employes = $this->employeRepository->findAll();

        return new JsonResponse(array_map(
            fn(Employe $employe) => $this->serializeEmploye($employe),
            $employes
        ));
    }

    #[Route('/{id}', methods: ['GET'])]
    public function show(int $id): JsonResponse
    {
        $employe = $this->employeRepository->find($id);
        $this->assertFound($employe, 'Employe non trouve.', 'EMPLOYE_NOT_FOUND');

        return new JsonResponse($this->serializeEmploye($employe));
    }

    #[Route('', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $isMultipart = str_starts_with((string) $request->headers->get('Content-Type'), 'multipart/form-data');
        $data = $isMultipart ? $request->request->all() : $this->parseJson($request);

        $this->requireFields($data, [
            'nom',
            'prenom',
            'email',
            'telephone',
            'matricule',
            'poste',
            'dateEmbauche',
            'departementId',
        ]);

        $departement = $this->departementRepository->find($data['departementId']);
        $this->assertFound($departement, 'Departement introuvable.', 'DEPARTEMENT_NOT_FOUND');

        $employe = new Employe();
        $employe->setNom($data['nom']);
        $employe->setPrenom($data['prenom']);
        $employe->setEmail($data['email']);
        $employe->setTelephone($data['telephone']);
        $employe->setMatricule($data['matricule']);
        $employe->setPoste($data['poste']);
        $employe->setDateEmbauche($this->parseDate($data['dateEmbauche'], 'dateEmbauche', 'Y-m-d'));
        $employe->setDepartement($departement);

        if (array_key_exists('actif', $data)) {
            $employe->setActif((bool) $data['actif']);
        }

        $photo = $request->files->get('photo');

        if ($photo) {
            try {
                $employe->setPhoto($this->photoUploadService->upload($photo));
            } catch (\InvalidArgumentException $exception) {
                throw new ApiException($exception->getMessage(), 422, 'VALIDATION_ERROR');
            }
        } elseif (array_key_exists('photo', $data)) {
            $employe->setPhoto($data['photo']);
        }

        if (array_key_exists('biometriqueData', $data)) {
            if (!is_array($data['biometriqueData']) && $data['biometriqueData'] !== null) {
                throw new ApiException(
                    'Les donnees envoyees sont invalides.',
                    422,
                    'VALIDATION_ERROR',
                    ['biometriqueData' => ['Ce champ doit etre un tableau ou null.']]
                );
            }

            $employe->setBiometriqueData($data['biometriqueData']);
        }

        $this->entityManager->persist($employe);
        $this->entityManager->flush();

        return new JsonResponse($this->serializeEmploye($employe), 201);
    }

    #[Route('/{id}/photo', methods: ['POST'])]
    public function uploadPhoto(int $id, Request $request): JsonResponse
    {
        $employe = $this->employeRepository->find($id);
        $this->assertFound($employe, 'Employe non trouve.', 'EMPLOYE_NOT_FOUND');

        $file = $request->files->get('file');

        if (!$file) {
            throw new ApiException(
                'Aucune photo envoyee.',
                400,
                'PHOTO_FILE_REQUIRED',
                ['file' => ['Le champ attendu est : file.']]
            );
        }

        try {
            $filename = $this->photoUploadService->upload($file);
        } catch (\InvalidArgumentException $exception) {
            throw new ApiException($exception->getMessage(), 422, 'VALIDATION_ERROR');
        }

        $employe->setPhoto($filename);
        $this->entityManager->flush();

        return new JsonResponse($this->serializeEmploye($employe));
    }

    #[Route('/{id}', methods: ['PUT'])]
    public function update(int $id, Request $request): JsonResponse
    {
        $employe = $this->employeRepository->find($id);
        $this->assertFound($employe, 'Employe non trouve.', 'EMPLOYE_NOT_FOUND');

        $data = $this->parseJson($request);

        if (array_key_exists('nom', $data)) {
            $employe->setNom($data['nom']);
        }

        if (array_key_exists('prenom', $data)) {
            $employe->setPrenom($data['prenom']);
        }

        if (array_key_exists('email', $data)) {
            $employe->setEmail($data['email']);
        }

        if (array_key_exists('matricule', $data)) {
            $employe->setMatricule($data['matricule']);
        }

        if (array_key_exists('poste', $data)) {
            $employe->setPoste($data['poste']);
        }

        if (array_key_exists('dateEmbauche', $data)) {
            $employe->setDateEmbauche($this->parseDate($data['dateEmbauche'], 'dateEmbauche', 'Y-m-d'));
        }

        if (array_key_exists('telephone', $data)) {
            $employe->setTelephone($data['telephone']);
        }

        if (array_key_exists('actif', $data)) {
            $employe->setActif((bool) $data['actif']);
        }

        if (array_key_exists('photo', $data)) {
            $employe->setPhoto($data['photo']);
        }

        if (array_key_exists('biometriqueData', $data)) {
            if (!is_array($data['biometriqueData']) && $data['biometriqueData'] !== null) {
                throw new ApiException(
                    'Les donnees envoyees sont invalides.',
                    422,
                    'VALIDATION_ERROR',
                    ['biometriqueData' => ['Ce champ doit etre un tableau ou null.']]
                );
            }

            $employe->setBiometriqueData($data['biometriqueData']);
        }

        if (array_key_exists('departementId', $data)) {
            if ($data['departementId'] === null || $data['departementId'] === '') {
                throw new ApiException(
                    'Le departement est obligatoire.',
                    422,
                    'VALIDATION_ERROR',
                    ['departementId' => ['Ce champ ne peut pas etre vide.']]
                );
            }

            $departement = $this->departementRepository->find($data['departementId']);
            $this->assertFound($departement, 'Departement introuvable.', 'DEPARTEMENT_NOT_FOUND');
            $employe->setDepartement($departement);
        }

        $this->entityManager->flush();

        return new JsonResponse($this->serializeEmploye($employe));
    }

    #[Route('/{id}', methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        $employe = $this->employeRepository->find($id);
        $this->assertFound($employe, 'Employe non trouve.', 'EMPLOYE_NOT_FOUND');

        $this->entityManager->remove($employe);
        $this->entityManager->flush();

        return new JsonResponse(null, 204);
    }

    #[Route('/{id}/desactiver', methods: ['PATCH'])]
    public function desactiver(int $id): JsonResponse
    {
        $employe = $this->employeRepository->find($id);
        $this->assertFound($employe, 'Employe non trouve.', 'EMPLOYE_NOT_FOUND');

        $employe->setActif(false);
        $this->entityManager->flush();

        return new JsonResponse($this->serializeEmploye($employe));
    }

    #[Route('/{id}/biometrie', methods: ['POST'])]
    public function enrollBiometrie(int $id, Request $request): JsonResponse
    {
        $employe = $this->employeRepository->find($id);
        $this->assertFound($employe, 'Employe non trouve.', 'EMPLOYE_NOT_FOUND');

        $data = $this->parseJson($request);
        $this->requireFields($data, ['descriptors']);

        if (!is_array($data['descriptors'])) {
            throw new ApiException(
                'Les donnees envoyees sont invalides.',
                422,
                'VALIDATION_ERROR',
                ['descriptors' => ['Ce champ doit etre un tableau de descriptors face-api.js.']]
            );
        }

        $biometriqueData = $this->biometriqueEnrollmentService->enrollFromFaceApiDescriptors($employe, $data['descriptors']);

        return new JsonResponse([
            'employe' => $this->serializeEmploye($employe),
            'biometriqueData' => $biometriqueData,
        ]);
    }
}
