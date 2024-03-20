<?php

namespace App\Tests\Controller;

use App\Repository\UserRepository;
use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class UserControllerTest extends WebTestCase
{
    public function testListWithNoUsers(): void
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get(UserRepository::class);
        $users = $userRepository->findAll();
        foreach ($users as $user) {
            $userRepository->remove($user, true);
        }


        $crawler = $client->request('GET', '/users');
        $this->assertResponseIsSuccessful();
        $response = $client->getResponse();
        $content = $response->getContent();
        $this->assertJson($content);
        $this->assertJsonStringEqualsJsonString('[]', $content);
    }

    public function testListWithUsers(): void
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get(UserRepository::class);
        $users = $userRepository->findAll();
        foreach ($users as $user) {
            $userRepository->remove($user, true);
        }

        $data = [
            'username' => 'test',
            'password' => 'test',
            'email' => 'test' . rand(1, 1000) . '@example.com',
        ];
        $user = new \App\Entity\User();
        $user->setUsername($data['username']);
        $user->setEmail($data['email']);
        $user->setPassword($data['password']);
        $userRepository->save($user, true);


        $crawler = $client->request('GET', '/users');
        $this->assertResponseIsSuccessful();
        $response = $client->getResponse();
        $content = $response->getContent();

        $this->assertJson($content);
        $this->assertSame($data['username'], json_decode($content, true)[0]['username']);
        $this->assertSame($data['email'], json_decode($content, true)[0]['email']);
    }

    public function testCreateUser(): void
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get(UserRepository::class);
        $users = $userRepository->findAll();
        foreach ($users as $user) {
            $userRepository->remove($user, true);
        }

        $data = [
            'username' => 'test',
            'password' => 'test',
            'email' => 'test' . rand(1, 1000) . '@example.com',
        ];
        $client->request(
            'POST',
            '/users',
            server: [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_ACCEPT' => 'application/json',
            ],
            content: json_encode($data)
        );
        $this->assertResponseStatusCodeSame(201);

        // get user from database
        $user = $userRepository->findOneBy(['email' => $data['email']],);

        $this->assertSame($data['username'], $user->getUsername());
        $this->assertSame($data['email'], $user->getEmail());
        $this->assertNotEmpty($user->getPassword());
        $this->assertNotSame($data['password'], $user->getPassword());
        $this->assertNotEmpty($user->getCreatedAt());
        $this->assertInstanceOf(\DateTimeInterface::class, $user->getCreatedAt());
        $this->assertNotEmpty($user->getUpdatedAt());
        $this->assertInstanceOf(\DateTimeInterface::class, $user->getUpdatedAt());
    }

    public function testUpdateUser(): void
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get(UserRepository::class);

        $data = [
            'username' => 'test',
            'password' => 'test',
            'email' => 'test' . rand(1, 1000) . '@example.com',
        ];
        $user = new \App\Entity\User();
        $user->setUsername($data['username']);
        $user->setEmail($data['email']);
        $user->setPassword($data['password']);
        $userRepository->save($user, true);


        $data['username'] = 'updated';

        $client->request(
            'PUT',
            '/users/' . $user->getId(),
            server: [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_ACCEPT' => 'application/json',
            ],
            content: json_encode($data)
        );
        $this->assertResponseIsSuccessful();

        $user = $userRepository->findOneBy(['email' => $data['email']],);
        $this->assertSame($data['username'], $user->getUsername());
    }

    public function testDeleteUser(): void
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get(UserRepository::class);

        $users = $userRepository->findAll();
        foreach ($users as $user) {
            $userRepository->remove($user, true);
        }

        $data = [
            'username' => 'test',
            'password' => 'test',
            'email' => 'test' . rand(1, 1000) . '@example.com',
        ];

        $user = new \App\Entity\User();
        $user->setUsername($data['username']);
        $user->setEmail($data['email']);
        $user->setPassword($data['password']);
        $userRepository->save($user, true);

        $client->request('DELETE', '/users/' . $user->getId());
        $this->assertResponseStatusCodeSame(204);

        $user = $userRepository->findOneBy(['email' => $data['email']]);
        $this->assertNull($user);
    }
}
