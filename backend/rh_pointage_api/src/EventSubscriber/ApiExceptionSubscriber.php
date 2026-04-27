<?php

namespace App\EventSubscriber;

use App\Exception\ApiException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Symfony\Component\HttpKernel\KernelEvents;


class ApiExceptionSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::EXCEPTION => 'onKernelException',
        ];
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        $request = $event->getRequest();

        if (!str_starts_with($request->getPathInfo(), '/api')) {
            return;
        }

        $exception = $event->getThrowable();

        $status = Response::HTTP_INTERNAL_SERVER_ERROR;
        $code = 'INTERNAL_SERVER_ERROR';
        $message = 'Une erreur interne est survenue.';
        $details = null;

        if ($exception instanceof ApiException) {
            $status = $exception->getStatusCode();
            $code = $exception->getErrorCode();
            $message = $exception->getMessage();
            $details = $exception->getDetails() ?: null;
        } elseif ($exception instanceof AccessDeniedHttpException) {
            $status = Response::HTTP_FORBIDDEN;
            $code = 'ACCESS_DENIED';
            $message = 'Accès refusé.';
        } elseif ($exception instanceof NotFoundHttpException) {
            $status = Response::HTTP_NOT_FOUND;
            $code = 'NOT_FOUND';
            $message = 'Ressource non trouvée.';
        } elseif ($exception instanceof \JsonException) {
            $status = Response::HTTP_BAD_REQUEST;
            $code = 'INVALID_JSON';
            $message = 'JSON invalide.';
        } elseif ($exception instanceof \InvalidArgumentException) {
            $status = Response::HTTP_UNPROCESSABLE_ENTITY;
            $code = 'INVALID_ARGUMENT';
            $message = $exception->getMessage() ?: 'Requête invalide.';
        } elseif ($exception instanceof \DomainException) {
            $status = Response::HTTP_UNPROCESSABLE_ENTITY;
            $code = 'DOMAIN_ERROR';
            $message = $exception->getMessage() ?: 'Erreur métier.';
        } elseif ($exception instanceof UniqueConstraintViolationException) {
            $status = Response::HTTP_CONFLICT;
            $code = 'UNIQUE_CONSTRAINT_VIOLATION';
            $message = 'Une ressource avec une valeur unique identique existe déjà.';
            $details = $this->extractUniqueConstraintDetails($exception);
        } elseif ($exception instanceof HttpExceptionInterface) {
            $status = $exception->getStatusCode();
            $code = 'HTTP_EXCEPTION';
            $message = $exception->getMessage() ?: Response::$statusTexts[$status];
        }

        $event->setResponse(new JsonResponse([
            'success' => false,
            'error' => [
                'code' => $code,
                'message' => $message,
                'status' => $status,
                'details' => $details,
            ],
        ], $status));
    }

    private function extractUniqueConstraintDetails(UniqueConstraintViolationException $exception): ?array
    {
        $rawMessage = $exception->getPrevious()?->getMessage() ?? $exception->getMessage();
        $rawMessage = mb_strtolower($rawMessage);

        $fieldHints = [];

        if (str_contains($rawMessage, 'email')) {
         $fieldHints['email'] = ['Cette valeur existe déjà.'];
        }

        if (str_contains($rawMessage, 'matricule')) {
            $fieldHints['matricule'] = ['Cette valeur existe déjà.'];
        }

        if (str_contains($rawMessage, 'telephone')) {
            $fieldHints['telephone'] = ['Cette valeur existe déjà.'];
        }

        return !empty($fieldHints)
            ? $fieldHints
            : ['database' => ['Violation de contrainte d’unicité.']];
    }
}
