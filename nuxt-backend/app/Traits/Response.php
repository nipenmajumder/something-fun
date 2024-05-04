<?php

declare(strict_types=1);

namespace App\Traits;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response as ResponseAlias;
use Throwable;

trait Response
{
    private function createResponseStructure(
        int    $statusCode,
        string $message = null,
               $result = null,
               $errors = null
    ): array
    {
        return [
            'status' => $statusCode,
            'message' => $message,
            'result' => $result,
            'errors' => $errors,
        ];
    }

    private function createJsonResponse(
        array $content,
        int   $statusCode,
        array $headers
    ): JsonResponse
    {
        $defaultHeaders = ['Content-Type' => 'application/json'];
        $headers = array_merge($defaultHeaders, $headers);

        return response()->json($content, $statusCode, $headers);
    }

    protected function respond(
        int    $statusCode,
        string $message = null,
               $result = null,
               $errors = null,
        array  $headers = []
    ): JsonResponse
    {
        try {
            $responseStructure = $this->createResponseStructure($statusCode, $message, $result, $errors);
            return $this->createJsonResponse($responseStructure, $statusCode, $headers);
        } catch (Throwable $exception) {
            return $this->handleException($exception);
        }
    }

    protected function respondWithResource(
        JsonResource $resource,
        string       $message = null,
        int          $statusCode = ResponseAlias::HTTP_OK,
        array        $headers = []
    ): JsonResponse
    {
        return $this->respond($statusCode,
            $message,
            $resource,
            null,
            $headers);
    }

    protected function respondWithResourceCollection(
        ResourceCollection $resourceCollection,
        string             $message,
        int                $statusCode = ResponseAlias::HTTP_OK,
        array              $headers = []
    ): JsonResponse
    {
        $response = $resourceCollection->response();
        $data = is_object($response) && method_exists($response, 'getData') ? $response->getData() : $response;
        return $this->respond($statusCode, $message, $data, null, $headers);
    }

    protected function respondSuccess(
        $data = [],
        string $message = ''
    ): JsonResponse
    {
        return $this->respond(ResponseAlias::HTTP_OK, $message, $data);
    }

    protected function respondCreated(
        $data = [],
        $message = ''
    ): JsonResponse
    {
        return $this->respond(ResponseAlias::HTTP_CREATED, $message, $data);
    }

    protected function respondNoContent(
        string $message = 'No Content Found'
    ): JsonResponse
    {
        return $this->respond(ResponseAlias::HTTP_NO_CONTENT, $message);
    }

    protected function respondUnAuthorized(
        string $message = 'Unauthorized'
    ): JsonResponse
    {
        return $this->respond(ResponseAlias::HTTP_UNAUTHORIZED, $message);
    }

    protected function respondError(
        string    $message,
        int       $statusCode = 400,
        int       $error_code = 1,
        Throwable $exception = null
    ): JsonResponse
    {
        return $this->respond($statusCode, $message, null, ['error_code' => $error_code, 'exception' => $exception]);
    }

    protected function respondForbidden(
        string $message = 'Forbidden'
    ): JsonResponse
    {
        return $this->respond(ResponseAlias::HTTP_FORBIDDEN, $message);
    }

    protected function respondNotFound(
        string $message = 'Not Found'
    ): JsonResponse
    {
        return $this->respond(ResponseAlias::HTTP_NOT_FOUND, $message);
    }

    protected function respondInternalError(
        string    $message = 'Internal Error',
        int       $statusCode = 500,
        int       $error_code = 1,
        Throwable $exception = null
    ): JsonResponse
    {
        return $this->respond($statusCode, $message, null, ['error_code' => $error_code, 'exception' => $exception]);
    }

    protected function respondValidationErrors(
        ValidationException $exception
    ): JsonResponse
    {
        return $this->respond(ResponseAlias::HTTP_UNPROCESSABLE_ENTITY, $exception->getMessage(), null, $exception->errors());
    }

    protected function respondWithSuccess(
        $data, string $message,
        int $statusCode = ResponseAlias::HTTP_OK,
        array $headers = []
    ): JsonResponse
    {
        return $this->respond($statusCode, $message, $data, null, $headers);
    }

    protected function handleException(
        Throwable $exception
    ): JsonResponse
    {
        // Log the error
        Log::error($exception->getMessage(), ['exception' => $exception]);

        // Determine the status code
        $statusCode = $this->getExceptionStatusCode($exception);

        // Create the error response
        $response = [
            'status' => $statusCode,
            'message' => $exception->getMessage(),
        ];

        // Add additional debug info in non-production environments
        if (config('app.env') !== 'production') {
            $response['debug'] = [
                'exception' => get_class($exception),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'trace' => $exception->getTrace(),
            ];
        }

        return response()->json($response, $statusCode);
    }

    private function getExceptionStatusCode(
        Throwable $exception
    ): int
    {
        // Default status code is 500
        $statusCode = ResponseAlias::HTTP_INTERNAL_SERVER_ERROR;

        if ($exception instanceof ValidationException) {
            $statusCode = ResponseAlias::HTTP_UNPROCESSABLE_ENTITY;
        } elseif ($exception instanceof ModelNotFoundException) {
            $statusCode = ResponseAlias::HTTP_NOT_FOUND;
        } elseif ($exception instanceof AuthenticationException) {
            $statusCode = ResponseAlias::HTTP_UNAUTHORIZED;
        } elseif ($exception instanceof AuthorizationException) {
            $statusCode = ResponseAlias::HTTP_FORBIDDEN;
        }

        return $statusCode;
    }

}
