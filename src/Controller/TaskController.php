<?php

namespace App\Controller;

use App\Entity\Task;
use App\Repository\TaskRepository;
use App\Repository\UserRepository;
use DateTimeImmutable;
use Doctrine\DBAL\Types\DateTimeImmutableType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class TaskController extends AbstractController
{
    private $taskRepository;
    private $userRepository;
    private $validator;

    public function __construct(TaskRepository $taskRepository, UserRepository $userRepository, ValidatorInterface $validator)
    {
        $this->taskRepository = $taskRepository;
        $this->userRepository = $userRepository;
        $this->validator = $validator;
    }

    #[Route('/{user}/tasks', name: 'app_task_create', methods: ['POST'])]
    public function create(int $user, Request $request): JsonResponse
    {
        $user = $this->userRepository->find($user);
        if (!$user) {
            return new JsonResponse(['error' => 'User not found'], JsonResponse::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true);
        $task = new \App\Entity\Task();
        $task->setTitle($data['title']);
        $task->setDescription($data['description'] ?? null);
        $task->setDeadlineFromString($data['deadline'] ?? null);
        $task->setUser($user);
        $task->setStatus($data['status'] ?? 0);

        $errors = $this->validate($task);
        if (count($errors) > 0) {
            return new JsonResponse($errors, JsonResponse::HTTP_BAD_REQUEST);
        }

        $this->taskRepository->save($task, true);
        return new JsonResponse(null, JsonResponse::HTTP_CREATED);
    }

    #[Route('/{user}/tasks', name: 'app_task', methods: ['GET'])]
    public function list(int $user): JsonResponse
    {
        $user = $this->userRepository->find($user);
        if (!$user) {
            return new JsonResponse(['error' => 'User not found'], JsonResponse::HTTP_NOT_FOUND);
        }

        $tasks = $this->taskRepository->findBy(['user' => $user]);

        return new JsonResponse($tasks);
    }

    #[Route('/{user}/tasks/{id}', name: 'app_task_show', methods: ['GET'])]
    public function show(int $user, int $id, Request $request): JsonResponse
    {
        $user = $this->userRepository->find($user);
        if (!$user) {
            return new JsonResponse(['error' => 'User not found'], JsonResponse::HTTP_NOT_FOUND);
        }

        $task = $this->taskRepository->find($id);
        return new JsonResponse($task);
    }

    #[Route('/{user}/tasks/{id}', name: 'app_task_update', methods: ['PUT'])]
    public function update(int $user, int $id, Request $request): JsonResponse
    {
        $user = $this->userRepository->find($user);
        if (!$user) {
            return new JsonResponse(['error' => 'User not found'], JsonResponse::HTTP_NOT_FOUND);
        }

        $task = $this->taskRepository->find($id);
        if (!$task) {
            return new JsonResponse(['error' => 'Task not found'], JsonResponse::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true);
        $task->setTitle($data['title']);
        $task->setDescription($data['description'] ?? null);
        $task->setDeadlineFromString($data['deadline'] ?? null);
        $task->setUser($user);
        $task->setStatus($data['status'] ?? 0);

        $errors = $this->validate($task);
        if (count($errors) > 0) {
            return new JsonResponse($errors, JsonResponse::HTTP_BAD_REQUEST);
        }

        $this->taskRepository->save($task, true);
        return new JsonResponse(null, JsonResponse::HTTP_CREATED);
    }

    #[Route('/{user}/tasks/{id}', name: 'app_task_delete', methods: ['DELETE'])]
    public function delete(int $user, int $id): JsonResponse
    {
        $user = $this->userRepository->find($user);
        if (!$user) {
            return new JsonResponse(['error' => 'User not found'], JsonResponse::HTTP_NOT_FOUND);
        }

        $task = $this->taskRepository->find($id);
        if (!$task) {
            return new JsonResponse(['error' => 'Task not found'], JsonResponse::HTTP_NOT_FOUND);
        }

        $this->taskRepository->remove($task);
        return new JsonResponse(null, JsonResponse::HTTP_NO_CONTENT);
    }

    private function validate(Task $taks) : Array
    {
        $errors = $this->validator->validate($taks);
        $error_messages = [];

        if (count($errors) > 0) {
            foreach ($errors as $error) {
                $error_messages[] = $error->getMessage();
            }
        }

        return $error_messages;
    }

}
