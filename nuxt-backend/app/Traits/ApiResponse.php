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

trait ApiResponse
{
    public function respondWithResource(
        JsonResource $resource,
        string       $message = null,
        int          $statusCode = ResponseAlias::HTTP_OK,
        array        $headers = []
    ): JsonResponse
    {
        return $this->apiResponse(
            [
                'status' => ResponseAlias::HTTP_OK,
                'result' => [
                    $resource,
                ],
                'message' => $message,
            ],
            $statusCode,
            $headers
        );
    }

    private function parseGivenData(
        array $data = [],
        int   $statusCode = ResponseAlias::HTTP_OK,
        array $headers = []
    ): array
    {
        $statusCode = $data['status'] ?? $statusCode;

        $response_structure = [
            'status' => $statusCode,
            'message' => $data['message'] ?? null,
            'result' => $data['result'] ?? null,
            'errors' => $data['errors'] ?? null,
        ];

        if (isset($data['exception']) && $data['exception'] instanceof Throwable) {
            $response_structure['exception'] = $this->handleException($data['exception']);
            $response_structure['error_code'] = $this->getExceptionStatusCode($data['exception']);
        }

        return [
            'content' => $response_structure,
            'statusCode' => $statusCode,
            'headers' => $headers,
        ];
    }

    /**
     * Return generic json response with the given data.
     */
    protected function apiResponse(
        array $data = [],
        int   $statusCode = ResponseAlias::HTTP_OK,
        array $headers = []
    ): JsonResponse
    {
        $content = $this->parseGivenData($data);
        $statusCode = $this->determineStatusCode($data, $statusCode);
        return $this->createJsonResponse($content, $statusCode, $headers);
    }

    private function determineStatusCode(array $data, int $defaultStatusCode): int
    {
        return isset($data['status']) && is_int($data['status']) ? $data['status'] : $defaultStatusCode;
    }

    private function createJsonResponse(array $content, int $statusCode, array $headers): JsonResponse
    {
        return response()->json($content, $statusCode, $headers);
    }

    protected function respondWithResourceCollection(
        ResourceCollection $resourceCollection,
        string             $message,
        int                $statusCode = ResponseAlias::HTTP_OK,
        array              $headers = []
    ): JsonResponse
    {
        $response = $resourceCollection->response();
        $data = is_object($response) && method_exists($response, 'getData')
            ? $response->getData()
            : $response;

        return $this->apiResponse(
            [
                'status' => ResponseAlias::HTTP_OK,
                'message' => $message,
                'result' => $data,
            ],
            $statusCode,
            $headers
        );
    }

    protected function respondSuccess($data = [], string $message = ''): JsonResponse
    {
        return $this->apiResponse(
            [
                'message' => $message,
                'status' => ResponseAlias::HTTP_OK,
                'result' => $data,
            ]
        );
    }

    /**
     * Respond with created.
     */
    protected function respondCreated($data = [], $message = ''): JsonResponse
    {
        return $this->apiResponse(
            [
                'message' => $message,
                'status' => ResponseAlias::HTTP_CREATED,
                'result' => $data,
            ],
            ResponseAlias::HTTP_CREATED
        );
    }

    /**
     * Respond with no content.
     */
    protected function respondNoContent(
        string $message = 'No Content Found'
    ): JsonResponse
    {
        return $this->apiResponse(
            ['status' => false, 'message' => $message],
            ResponseAlias::HTTP_NO_CONTENT
        );
    }

    /**
     * Respond with unauthorized.
     */
    protected function respondUnAuthorized(
        string $message = 'Unauthorized'
    ): JsonResponse
    {
        return $this->respondError($message, ResponseAlias::HTTP_UNAUTHORIZED);
    }

    /**
     * Respond with error.
     */
    protected function respondError(
        string    $message,
        int       $statusCode = 400,
        int       $error_code = 1,
        Throwable $exception = null
    ): JsonResponse
    {
        return $this->apiResponse(
            [
                'status' => $statusCode,
                'message' => $message,
                'exception' => $exception,
                'error_code' => $error_code,
            ],
            $statusCode
        );
    }

    /**
     * Respond with forbidden.
     */
    protected function respondForbidden(
        string $message = 'Forbidden'
    ): JsonResponse
    {
        return $this->respondError($message, ResponseAlias::HTTP_FORBIDDEN);
    }

    /**
     * Respond with not found.
     */
    protected function respondNotFound(
        string $message = 'Not Found'
    ): JsonResponse
    {
        return $this->respondError($message, ResponseAlias::HTTP_NOT_FOUND);
    }

    /**
     * Respond with internal error.
     */
    protected function respondInternalError(
        string    $message = 'Internal Error',
        int       $statusCode = 500,
        int       $error_code = 1,
        Throwable $exception = null
    ): JsonResponse
    {
        return $this->apiResponse(
            [
                'status' => $statusCode,
                'message' => $message,
                'exception' => $exception,
                'error_code' => $error_code,
            ],
            $statusCode
        );
    }

    protected function respondValidationErrors(
        ValidationException $exception
    ): JsonResponse
    {
        return $this->apiResponse(
            [
                'status' => ResponseAlias::HTTP_UNPROCESSABLE_ENTITY,
                'message' => $exception->getMessage(),
                'errors' => $exception->errors(),
            ],
            ResponseAlias::HTTP_UNPROCESSABLE_ENTITY
        );
    }

    protected function respondWithSuccess(
        $data,
        string $message,
        int $statusCode = ResponseAlias::HTTP_OK,
        array $headers = []
    ): JsonResponse
    {
        return $this->apiResponse(
            [
                'status' => ResponseAlias::HTTP_OK,
                'message' => $message,
                'result' => [
                    'data' => $data,
                ],
            ],
            $statusCode,
            $headers
        );
    }

    protected function handleException(Throwable $exception): JsonResponse
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

    private function getExceptionStatusCode(Throwable $exception): int
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
