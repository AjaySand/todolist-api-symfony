<?php

namespace App\Tests\Controller;

use App\Repository\TaskRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class TaskControllerTest extends WebTestCase
{
    public function testCreate(): void
    {
        $client = static::createClient();
        $taskRepository = static::getContainer()->get(TaskRepository::class);
        $user = $this->createUser();

        $tasks = $taskRepository->findAll();
        foreach ($tasks as $task) {
            $taskRepository->remove($task, true);
        }

        $data = [
            'title' => 'test',
            'description' => 'test',
            'deadline' => '2022-09-01',
            'status' => 1,
        ];

        $client->request(
            'POST',
            "{$user->getId()}/tasks",
            content: json_encode($data),
        );
        $this->assertResponseIsSuccessful();

        $task = $taskRepository->findOneBy([
            'title' => $data['title'],
            'user' => $user,
            'description' => $data['description'],
            'status' => $data['status'],
        ]);
        $this->assertNotNull($task);
        $this->assertSame($data['title'], $task->getTitle());
        $this->assertSame($data['description'], $task->getDescription());
        $this->assertSame($data['deadline'], $task->getDeadline()->format('Y-m-d'));
        $this->assertSame($data['status'], $task->getStatus());
    }


    public function testTaskList(): void
    {
        $client = static::createClient();
        $taskRepository = static::getContainer()->get(TaskRepository::class);
        $user = $this->createUser();

        // create 2 tasks

        $task_ids = [];
        for ($i = 0; $i < 2; $i++) {
            $data = [
                'title' => "test $i",
                'description' => 'test',
                'deadline' => '2022-09-01',
                'status' => rand(0, 1),
            ];
            $task = new \App\Entity\Task();
            $task->setTitle($data['title'])
                ->setDescription($data['description'])
                ->setDeadline(new \DateTimeImmutable($data['deadline']))
                ->setStatus($data['status'])
                ->setUser($user);
            $taskRepository->save($task, true);
            $task_ids[] = $task->getId();
        }


        $client->request(
            'GET',
            "{$user->getId()}/tasks",
        );
        $response = $client->getResponse();
        $content = $response->getContent();
        $data = json_decode($content, true);

        $this->assertJson($content);
        $this->assertCount(2, $data);
        $this->assertSame($task_ids[0], $data[0]['id']);
        $this->assertSame($task_ids[1], $data[1]['id']);

        $this->assertSame('test 0', $data[0]['title']);
        $this->assertSame('test 1', $data[1]['title']);
    }

    public function testTaskShow(): void
    {
        $client = static::createClient();
        $taskRepository = static::getContainer()->get(TaskRepository::class);
        $user = $this->createUser();

        $task = new \App\Entity\Task();
        $task->setTitle('test')
            ->setDescription('test')
            ->setDeadline(new \DateTimeImmutable('2022-09-01'))
            ->setStatus(1)
            ->setUser($user);
        $taskRepository->save($task, true);

        $client->request(
            'GET',
            "{$user->getId()}/tasks/{$task->getId()}",
        );

        $response = $client->getResponse();
        $content = $response->getContent();
        $data = json_decode($content, true);

        $this->assertJson($content);
        $this->assertSame($task->getId(), $data['id']);
        $this->assertSame($task->getTitle(), $data['title']);
        $this->assertSame($task->getDescription(), $data['description']);
        $this->assertSame(
            $task->getDeadline()->format('Y-m-d'),
            (new \DateTimeImmutable($data['deadline']['date']))->format('Y-m-d')
        );
        $this->assertSame($task->getStatus(), $data['status']);
    }

    public function testTaskUpdate(): void
    {
        $client = static::createClient();
        $taskRepository = static::getContainer()->get(TaskRepository::class);
        $user = $this->createUser();

        $task = new \App\Entity\Task();
        $task->setTitle('test')
            ->setDescription('test')
            ->setDeadline(new \DateTimeImmutable('2022-09-01'))
            ->setStatus(1)
            ->setUser($user);
        $taskRepository->save($task, true);

        $updateData = [
            'title' => 'updated',
            'description' => 'updated',
        ];

        $client->request(
            'PUT',
            "{$user->getId()}/tasks/{$task->getId()}",
            content: json_encode($updateData),
        );
        $this->assertResponseIsSuccessful();

        $taskRepository->findOneBy(['id' => $task->getId()]);
        $this->assertSame($updateData['title'], $task->getTitle());
        $this->assertSame($updateData['description'], $task->getDescription());
    }

    public function testTaskDelete(): void
    {
        $client = static::createClient();
        $taskRepository = static::getContainer()->get(TaskRepository::class);
        $user = $this->createUser();

        $task = new \App\Entity\Task();
        $task->setTitle('test')
            ->setDescription('test')
            ->setDeadline(new \DateTimeImmutable('2022-09-01'))
            ->setStatus(1)
            ->setUser($user);
        $taskRepository->save($task, true);

        $client->request(
            'DELETE',
            "{$user->getId()}/tasks/{$task->getId()}",
        );

        $this->assertResponseStatusCodeSame(204);

        $this->assertNull($taskRepository->findOneBy(['id' => $task->getId()]));
    }


    private function createUser(): \App\Entity\User
    {
        $userRepository = static::getContainer()->get(UserRepository::class);

        // create a user
        $user = new \App\Entity\User();
        $user->setUsername('test');
        $user->setEmail('test_task_create' . rand(1, 1000) . '@example.com');
        $user->setPassword('test');
        $userRepository->save($user, true);

        return $user;
    }
}
