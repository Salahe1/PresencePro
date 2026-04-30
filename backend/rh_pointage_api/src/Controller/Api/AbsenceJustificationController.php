<?php

namespace App\Controller\Api;

use App\Entity\Absence;
use App\Entity\Utilisateur;
use App\Service\AbsenceJustification\AbsenceJustificationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

#[Route('/api/absences')]
class AbsenceJustificationController extends AbstractController
{
    public function __construct(
        private AbsenceJustificationService $absenceJustificationService
    ) {}

    #[Route('/{id}/justification', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function createJustification(int $id, Request $request): JsonResponse
    {
        $user = $this->getUser();

        if (!$user instanceof Utilisateur) {
            return new JsonResponse(['error' => 'Utilisateur connecté invalide.'], 401);
        }

        try {
            $data = json_decode( $request->getContent(), true, 512, JSON_THROW_ON_ERROR );

            if (!is_array($data)) {
                return new JsonResponse(['error' => 'Le corps de la requête doit être un objet JSON.' ], 400);
            }

            $absence = $this->absenceJustificationService->justifier($id, $data, $user);

            return new JsonResponse(  $this->serializeAbsence($absence), 201 );

        } catch (\JsonException) {
            return new JsonResponse([
                'error' => 'JSON invalide.'
            ], 400);
        } catch (\RuntimeException $exception) {
            if ($exception->getMessage() === 'ABSENCE_NOT_FOUND') {
                return new JsonResponse([
                    'error' => 'Absence non trouvée.'
                ], 404);
            }

            return new JsonResponse([
                'error' => 'Erreur métier.'
            ], 400);
        } catch (\LogicException $exception) {
            if ($exception->getMessage() === 'ABSENCE_ALREADY_JUSTIFIED') {
                return new JsonResponse([
                    'error' => 'Cette absence est déjà justifiée.'
                ], 409);
            }

            return new JsonResponse([
                'error' => 'Action impossible.'
            ], 409);
        } catch (\InvalidArgumentException $exception) {
            return new JsonResponse([
                'error' => $exception->getMessage()
            ], 422);
        }
    }

    #[Route('/{id}/justification/justificatif', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function uploadJustificatif(int $id, Request $request): JsonResponse
    {
        $file = $request->files->get('file');

        if (!$file) {
            return new JsonResponse([ 'error' => 'Aucun fichier envoyé. Le champ attendu est : file.' ], 400);
        }

        try {
            $absence = $this->absenceJustificationService->uploaderJustificatif($id, $file);

            return new JsonResponse( $this->serializeAbsence($absence), 200  );
        } catch (\RuntimeException $exception) {
            if ($exception->getMessage() === 'ABSENCE_NOT_FOUND') {
                return new JsonResponse([ 'error' => 'Absence non trouvée.' ], 404); }

            return new JsonResponse([ 'error' => 'Erreur métier.'  ], 400);
        } catch (\LogicException $exception) {
            if ($exception->getMessage() === 'JUSTIFICATION_NOT_FOUND') {
                return new JsonResponse([ 'error' => 'Cette absence n’a pas encore de justification.' ], 404);
            }

            return new JsonResponse([ 'error' => 'Action impossible.' ], 409);
        } catch (\InvalidArgumentException $exception) {
            return new JsonResponse([ 'error' => $exception->getMessage() ], 422);
        }
    }
    #[Route('/{id}/justification', requirements: ['id' => '\d+'], methods: ['PATCH'])]
    public function updateJustification(int $id, Request $request): JsonResponse
    {
        $user = $this->getUser();

        if (!$user instanceof Utilisateur) {
            return new JsonResponse([ 'error' => 'Utilisateur connecté invalide.' ], 401);
        }

        try {
            $data = json_decode( $request->getContent(), true, 512, JSON_THROW_ON_ERROR );

            if (!is_array($data)) {
                return new JsonResponse([ 'error' => 'Le corps de la requête doit être un objet JSON.' ], 400);
            }

            if ($data === []) {
                return new JsonResponse([ 'error' => 'Aucune donnée fournie pour la modification.' ], 422);
            }

            $absence = $this->absenceJustificationService->modifierJustification($id, $data);

            return new JsonResponse( $this->serializeAbsence($absence), 200  );

        } catch (\JsonException) {
            return new JsonResponse([ 'error' => 'JSON invalide.'  ], 400);
        } catch (\RuntimeException $exception) {
            if ($exception->getMessage() === 'ABSENCE_NOT_FOUND') {
                return new JsonResponse([  'error' => 'Absence non trouvée.'  ], 404);
            }

            return new JsonResponse([ 'error' => 'Erreur métier.' ], 400);
        } catch (\LogicException $exception) {
            if ($exception->getMessage() === 'JUSTIFICATION_NOT_FOUND') {
                return new JsonResponse([  'error' => 'Cette absence n’a pas encore de justification à modifier.' ], 404);
            }

            return new JsonResponse([ 'error' => 'Action impossible.' ], 409);
        } catch (\InvalidArgumentException $exception) {
            return new JsonResponse([
                'error' => $exception->getMessage()
            ], 422);
        }
    }

    #[Route('/{id}/justification', requirements: ['id' => '\d+'], methods: ['DELETE'])]
    public function deleteJustification(int $id): Response
    {
        try {
            $this->absenceJustificationService->supprimerJustification($id);

            return new Response(null, Response::HTTP_NO_CONTENT);
        } catch (\RuntimeException $exception) {

            if ($exception->getMessage() === 'ABSENCE_NOT_FOUND') {
                return new JsonResponse([ 'error' => 'Absence non trouvée.' ], Response::HTTP_NOT_FOUND);
            }

            return new JsonResponse([ 'error' => 'Erreur métier.' ], Response::HTTP_BAD_REQUEST);
        } catch (\LogicException $exception) {

            if ($exception->getMessage() === 'JUSTIFICATION_NOT_FOUND') {
                return new JsonResponse([ 'error' => 'Cette absence n’a pas de justification à supprimer.' ], Response::HTTP_NOT_FOUND);
            }

            return new JsonResponse([  'error' => 'Action impossible.' ], Response::HTTP_CONFLICT);
        }
    }
    
    #[Route('/{id}/justification/justificatif', requirements: ['id' => '\d+'], methods: ['GET'])]
public function downloadJustificatif(int $id): Response
{
    try {
        $path = $this->absenceJustificationService->getJustificatifPath($id);

        $response = new BinaryFileResponse($path);

        $response->setContentDisposition(
            ResponseHeaderBag::DISPOSITION_INLINE,      // DISPOSITION_ATTACHMENT,
            basename($path)
        );

        return $response;
    } catch (\RuntimeException $exception) {
        if ($exception->getMessage() === 'ABSENCE_NOT_FOUND') {
            return new JsonResponse([
                'error' => 'Absence non trouvée.'
            ], Response::HTTP_NOT_FOUND);
        }

        if (
            $exception->getMessage() === 'JUSTIFICATIF_NOT_FOUND'
            || $exception->getMessage() === 'JUSTIFICATIF_FILE_NOT_FOUND'
        ) {
            return new JsonResponse([
                'error' => 'Fichier justificatif non trouvé.'
            ], Response::HTTP_NOT_FOUND);
        }

        return new JsonResponse([
            'error' => 'Erreur métier.'
        ], Response::HTTP_BAD_REQUEST);
    } catch (\LogicException $exception) {
        if ($exception->getMessage() === 'JUSTIFICATION_NOT_FOUND') {
            return new JsonResponse([
                'error' => 'Cette absence n’a pas encore de justification.'
            ], Response::HTTP_NOT_FOUND);
        }

        return new JsonResponse([
            'error' => 'Action impossible.'
        ], Response::HTTP_CONFLICT);
    }
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

            'ordrePlage' =>  $absence->getOrdrePlage() ,

            'heureDebutPrevue' => $absence->getHeureDebutPrevue()->format('H:i'),

            'heureFinPrevue' => $absence->getHeureFinPrevue(),

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