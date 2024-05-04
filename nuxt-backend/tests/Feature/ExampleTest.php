<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Validation\ValidationException;
use Mockery;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

it('responds with resource', function () {
    $mockResource = Mockery::mock(JsonResource::class);
    $mockResource->shouldReceive('toArray')->andReturn(['foo' => 'bar']);
    $mockResource->shouldReceive('jsonSerialize')->andReturn(['foo' => 'bar']);

    $response = $this->respondWithResource($mockResource, 'Test message');

    expect($response)->toBeInstanceOf(JsonResponse::class)
        ->and($response->getStatusCode())->toBe(Response::HTTP_OK)
        ->and($response->getData()->content)->toHaveKey('message', 'Test message');
//        ->and($response->getData()->content->result[0])->toEqual(['foo' => 'bar']);
});

it('responds with resource collection', function () {
    $mockResourceCollection = Mockery::mock(ResourceCollection::class);
    $mockResourceCollection->shouldReceive('response')->andReturn(['foo' => 'bar']);

    $response = $this->respondWithResourceCollection($mockResourceCollection, 'Test message');

    expect($response)->toBeInstanceOf(JsonResponse::class)
        ->and($response->getStatusCode())->toBe(Response::HTTP_OK)
        ->and($response->getData()->content)->toHaveKey('message', 'Test message');
//        ->and($response->getData()->content->result[0])->tohaveObject('result', ['foo' => 'bar']);
});

it('responds with created', function () {
    $response = $this->respondCreated(['foo' => 'bar'], 'Test message');

    expect($response)->toBeInstanceOf(JsonResponse::class)
        ->and($response->getStatusCode())->toBe(Response::HTTP_CREATED)
        ->and($response->getData())->toHaveKey('message', 'Test message');
//        ->and($response->getData())->toHaveKey('result', ['foo' => 'bar']);
});

it('responds with no content', function () {
    $response = $this->respondNoContent();

    expect($response)->toBeInstanceOf(JsonResponse::class)
        ->and($response->getStatusCode())->toBe(Response::HTTP_OK)
        ->and($response->getData()->content)->toHaveKey('message', 'No Content Found');
});

it('responds with unauthorized', function () {
    $response = $this->respondUnAuthorized();
    expect($response)->toBeInstanceOf(JsonResponse::class)
        ->and($response->getStatusCode())->toBe(Response::HTTP_UNAUTHORIZED)
        ->and($response->getData()->content)->toHaveKey('message', 'Unauthorized');
});

it('responds with error', function () {
    $response = $this->respondError('Test error');
    expect($response)->toBeInstanceOf(JsonResponse::class)
        ->and($response->getStatusCode())->toBe(Response::HTTP_BAD_REQUEST)
        ->and($response->getData()->content)->toHaveKey('message', 'Test error');
});

it('responds with forbidden', function () {
    $response = $this->respondForbidden();

    expect($response)->toBeInstanceOf(JsonResponse::class)
        ->and($response->getStatusCode())->toBe(Response::HTTP_FORBIDDEN)
        ->and($response->getData()->content)->toHaveKey('message', 'Forbidden');
});

it('responds with not found', function () {
    $response = $this->respondNotFound();

    expect($response)->toBeInstanceOf(JsonResponse::class)
        ->and($response->getStatusCode())->toBe(Response::HTTP_NOT_FOUND)
        ->and($response->getData()->content)->toHaveKey('message', 'Not Found');
});

it('responds with internal error', function () {
    $response = $this->respondInternalError('Test error');

    expect($response)->toBeInstanceOf(JsonResponse::class)
        ->and($response->getStatusCode())->toBe(Response::HTTP_INTERNAL_SERVER_ERROR)
        ->and($response->getData()->content)->toHaveKey('message', 'Test error');
});

it('responds with validation errors', function () {
    $mockException = Mockery::mock(ValidationException::class);
    $mockException->shouldReceive('getMessage')->andReturn('Test error');
    $mockException->shouldReceive('errors')->andReturn(['foo' => 'bar']);

    $response = $this->respondValidationErrors($mockException);

    expect($response)->toBeInstanceOf(JsonResponse::class)
        ->and($response->getStatusCode())->toBe(Response::HTTP_UNPROCESSABLE_ENTITY);
//        ->and($response->getData()->content->errors)->toBe(['foo' => 'bar']);
});

it('responds with success', function () {
    $response = $this->respondWithSuccess(['foo' => 'bar'], 'Test message');

    expect($response)->toBeInstanceOf(JsonResponse::class)
        ->and($response->getStatusCode())->toBe(Response::HTTP_OK)
        ->and($response->getData()->content)->toHaveKey('message', 'Test message');
//        ->and($response->getData()->content)->toHaveKey('result', ['foo' => 'bar']);
});
