<?php

namespace App\Controller;

use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\MakerBundle\Validator;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class UserController extends AbstractController
{
    private $userRepository;

    public function __construct(UserRepository $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    #[Route('/users', name: 'users_create', methods: ['POST'])]
    public function create(Request $request, ValidatorInterface $validator): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $user = new \App\Entity\User();
        $user->setUsername($data['username']);
        $user->setEmail($data['email']);
        $user->setPassword(hash('sha256', $data['password']));

        $errors = $validator->validate($user);
        if (count($errors) > 0) {
            $error_messages = [];
            foreach ($errors as $error) {
                $error_messages[] = $error->getMessage();
            }

            return new JsonResponse($error_messages, 400);
        }

        $this->userRepository->save($user, true);
        return new JsonResponse(null, JsonResponse::HTTP_CREATED);
    }


    #[Route('/users', name: 'users_list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        $users = $this->userRepository->findAll();
        return new JsonResponse($users);
    }

    #[Route('/users/{id}', name: 'users_show', methods: ['GET'])]
    public function show(int $id): JsonResponse
    {
        return new JsonResponse($this->userRepository->find($id));
    }

    #[Route('/users/{id}', name: 'users_update', methods: ['PUT'])]
    public function update(int $id, Request $request, ValidatorInterface $validator): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $user = $this->userRepository->find($id);
        if (!$user) {
            return new JsonResponse(null, JsonResponse::HTTP_NOT_FOUND);
        }
        $user->setUsername($data['username'] ?? $user->getUsername());
        $user->setEmail($data['email'] ?? $user->getEmail());

        $errors = $validator->validate($user);
        if (count($errors) > 0) {
            $error_messages = [];
            foreach ($errors as $error) {
                $error_messages[] = $error->getMessage();
            }

            return new JsonResponse($error_messages, 400);
        }

        $this->userRepository->save($user, true);
        return new JsonResponse(null, 200);
    }

    #[Route('/users/{id}', name: 'users_delete', methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        $user = $this->userRepository->find($id);
        if (!$user) {
            return new JsonResponse(null, JsonResponse::HTTP_NOT_FOUND);
        }

        $this->userRepository->remove($user);
        return new JsonResponse(null, JsonResponse::HTTP_NO_CONTENT);
    }
}
