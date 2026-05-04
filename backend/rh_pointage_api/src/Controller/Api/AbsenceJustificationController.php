<?php

namespace App\Controller\Api;

use App\Entity\Absence;
use App\Entity\Utilisateur;
use App\Exception\ApiException;
use App\Service\AbsenceJustification\AbsenceJustificationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/absences')]
class AbsenceJustificationController extends AbstractController
{
    use ApiControllerHelperTrait;

    public function __construct(
        private AbsenceJustificationService $absenceJustificationService
    ) {}

    #[Route('/{id}/justification', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function createJustification(int $id, Request $request): JsonResponse
    {
        $user = $this->getAuthenticatedUtilisateur();
        $data = $this->parseJson($request);

        try {
            $absence = $this->absenceJustificationService->justifier($id, $data, $user);
        } catch (\Throwable $exception) {
            throw $this->mapJustificationException($exception);
        }

        return new JsonResponse($this->serializeAbsence($absence), Response::HTTP_CREATED);
    }

    #[Route('/{id}/justification/justificatif', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function uploadJustificatif(int $id, Request $request): JsonResponse
    {
        $file = $request->files->get('file');

        if (!$file) {
            throw new ApiException(
                'Aucun fichier envoyé.',
                Response::HTTP_BAD_REQUEST,
                'JUSTIFICATIF_FILE_REQUIRED',
                ['file' => ['Le champ attendu est : file.']]
            );
        }

        try {
            $absence = $this->absenceJustificationService->uploaderJustificatif($id, $file);
        } catch (\Throwable $exception) {
            throw $this->mapJustificationException($exception);
        }

        return new JsonResponse($this->serializeAbsence($absence));
    }

    #[Route('/{id}/justification', requirements: ['id' => '\d+'], methods: ['PATCH'])]
    public function updateJustification(int $id, Request $request): JsonResponse
    {
        $this->getAuthenticatedUtilisateur();
        $data = $this->parseJson($request);

        if ($data === []) {
            throw new ApiException(
                'Aucune donnée fournie pour la modification.',
                Response::HTTP_UNPROCESSABLE_ENTITY,
                'VALIDATION_ERROR',
                ['body' => ['Au moins un champ doit être fourni.']]
            );
        }

        try {
            $absence = $this->absenceJustificationService->modifierJustification($id, $data);
        } catch (\Throwable $exception) {
            throw $this->mapJustificationException($exception);
        }

        return new JsonResponse($this->serializeAbsence($absence));
    }

    #[Route('/{id}/justification', requirements: ['id' => '\d+'], methods: ['DELETE'])]
    public function deleteJustification(int $id): Response
    {
        try {
            $this->absenceJustificationService->supprimerJustification($id);
        } catch (\Throwable $exception) {
            throw $this->mapJustificationException($exception);
        }

        return new Response(null, Response::HTTP_NO_CONTENT);
    }

    #[Route('/{id}/justification/justificatif', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function downloadJustificatif(int $id): Response
    {
        try {
            $path = $this->absenceJustificationService->getJustificatifPath($id);
        } catch (\Throwable $exception) {
            throw $this->mapJustificationException($exception);
        }

        $response = new BinaryFileResponse($path);
        $response->setContentDisposition(
            ResponseHeaderBag::DISPOSITION_INLINE,
            basename($path)
        );

        return $response;
    }

    private function getAuthenticatedUtilisateur(): Utilisateur
    {
        $user = $this->getUser();

        if (!$user instanceof Utilisateur) {
            throw new ApiException(
                'Utilisateur connecté invalide.',
                Response::HTTP_UNAUTHORIZED,
                'INVALID_AUTHENTICATED_USER'
            );
        }

        return $user;
    }

    private function mapJustificationException(\Throwable $exception): ApiException
    {
        if ($exception instanceof ApiException) {
            return $exception;
        }

        return match ($exception->getMessage()) {
            'ABSENCE_NOT_FOUND' => new ApiException(
                'Absence non trouvée.',
                Response::HTTP_NOT_FOUND,
                'ABSENCE_NOT_FOUND'
            ),
            'ABSENCE_ALREADY_JUSTIFIED' => new ApiException(
                'Cette absence est déjà justifiée.',
                Response::HTTP_CONFLICT,
                'ABSENCE_ALREADY_JUSTIFIED'
            ),
            'JUSTIFICATION_NOT_FOUND' => new ApiException(
                'Cette absence n’a pas encore de justification.',
                Response::HTTP_NOT_FOUND,
                'JUSTIFICATION_NOT_FOUND'
            ),
            'JUSTIFICATIF_NOT_FOUND', 'JUSTIFICATIF_FILE_NOT_FOUND' => new ApiException(
                'Fichier justificatif non trouvé.',
                Response::HTTP_NOT_FOUND,
                $exception->getMessage()
            ),
            default => $exception instanceof \InvalidArgumentException
                ? new ApiException(
                    $exception->getMessage(),
                    Response::HTTP_UNPROCESSABLE_ENTITY,
                    'VALIDATION_ERROR'
                )
                : new ApiException(
                    'Erreur métier.',
                    Response::HTTP_BAD_REQUEST,
                    'BUSINESS_ERROR'
                ),
        };
    }

    private function serializeAbsence(Absence $absence): array
    {
        $employe = $absence->getEmploye();
        $justification = $absence->getJustification();

        return [
            'id' => $absence->getId(),
            'date' => $absence->getDate()?->format('Y-m-d'),
            'statut' => $absence->getStatut()->value,
            'typeAbsence' => $absence->getTypeAbsence()?->value,
            'ordrePlage' => $absence->getOrdrePlage(),
            'heureDebutPrevue' => $absence->getHeureDebutPrevue()?->format('H:i'),
            'heureFinPrevue' => $absence->getHeureFinPrevue()?->format('H:i'),
            'employe' => $employe ? [
                'id' => $employe->getId(),
                'matricule' => $employe->getMatricule(),
                'nomComplet' => $employe->getNomComplet(),
            ] : null,
            'justification' => $justification ? [
                'id' => $justification->getId(),
                'motif' => $justification->getMotif(),
                'commentaire' => $justification->getCommentaire(),
                'justificatifPath' => $justification->getJustificatifPath(),
                'dateJustification' => $justification->getDateJustification()?->format('Y-m-d'),
                'saisieParAdmin' => $justification->getSaisieParAdmin() ? [
                    'id' => $justification->getSaisieParAdmin()->getId(),
                    'nomComplet' => $justification->getSaisieParAdmin()->getNomComplet(),
                    'matricule' => $justification->getSaisieParAdmin()->getMatricule(),
                ] : null,
            ] : null,
        ];
    }
}
