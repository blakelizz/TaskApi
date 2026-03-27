<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Attribute\Route;
use App\Repository\TaskRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use App\Entity\Task;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManagerInterface;
use OpenApi\Attributes as OA;
use Nelmio\ApiDocBundle\Attribute\Model as AttributeModel;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\SerializerInterface;

#[Route('/api/tasks', name: 'api_tasks_')]
final class TaskController extends AbstractController
{
    #[Route('', name: 'list', methods: ['GET'])]
    #[OA\Response(
        response: 200,
        description: 'Returns the tasks list',
        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(ref: new AttributeModel(type: Task::class, groups: ['task:read']))
        )
    )]
    public function index(EntityManagerInterface $entityManager, SerializerInterface $serializer): JsonResponse
    {
        $tasks = $entityManager->getRepository(Task::class)->findAll();

        // $data = array_map(fn(Task $task) => [
        //     'id' => $task->getId(),
        //     'title' => $task->getTitle(),
        //     'description' => $task->getDescription(),
        //     'status' => $task->getStatus(),
        //     'created_at' => $task->getCreatedAt()->format('c'),
        //     'user' => $task->getUser()?->getId(),
        // ], $tasks);
        $data = $serializer->serialize($tasks, 'json', ['groups' => 'task:read']);

        return new JsonResponse($data, Response::HTTP_OK, [], true);
    }

    //AJOUTER
    #[Route('', name: 'addTask', methods: ['POST'])]
    #[OA\Post]
    #[OA\Response(
        response: 201,
        description: 'Return the created task',
        content: new OA\JsonContent(
            ref: new AttributeModel(type: Task::class, groups: ['task:read'])
        )
    )]
    #[OA\RequestBody(
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'title', type: 'string'),
                new OA\Property(property: 'description', type: 'string'),
                new OA\Property(property: 'status', type: 'string'),]
        )
    )]
    public function addTask(Request $request, EntityManagerInterface $entityManager, SerializerInterface $serializer): JsonResponse
    {
        $task = $serializer->deserialize($request->getContent(), Task::class, 'json');

        $task->setUser($this->getUser());

        $entityManager->persist($task);
        $entityManager->flush();

        $data = $serializer->serialize($task, 'json', ['groups' => 'task:read']);


        return new JsonResponse($data, Response::HTTP_CREATED, [], true);
    }

    //MODIFIER
    #[Route('/{id}', name: 'editTask', methods: ['PUT'])]
    #[OA\Put]
    #[OA\Response(
        response: 200,
        description: 'Return the updated task',
        content: new OA\JsonContent(
            ref: new AttributeModel(type: Task::class, groups: ['task:read'])
        )
    )]
    #[OA\RequestBody(
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'title', type: 'string'),
                new OA\Property(property: 'description', type: 'string'),
                new OA\Property(property: 'status', type: 'string'),
            ]
        )
    )]
    public function editTask(Request $request, EntityManagerInterface $entityManager, SerializerInterface $serializer, int $id): JsonResponse
    {

        $task = $entityManager->find(Task::class, $id);

        if (!$task) {
            return new JsonResponse(['message' => 'Task not found'], JsonResponse::HTTP_NOT_FOUND);
        }

        $updatedTask= $serializer->deserialize($request->getContent(), Task::class, 'json', ['object_to_populate' => $task]);

        $entityManager->persist($task);
        $entityManager->flush();
        
        $data = $serializer->serialize($updatedTask, 'json', ['groups' => 'task:read']);


        return new JsonResponse($data, Response::HTTP_OK, [], true);
    }

    //SUPPRIMER
    #[Route('/{id}', name: 'deleteTask', methods: ['DELETE'])]
    #[OA\Response(
        response: 204,
        description: 'Task deleted successfully',
    )]
    public function deleteTask(EntityManagerInterface $entityManager, int $id): JsonResponse
    {

        $task = $entityManager->find(Task::class, $id);

        if (!$task) {
            return new JsonResponse(['message' => 'Task not found'], JsonResponse::HTTP_NOT_FOUND);
        }

        $entityManager->remove($task);
        $entityManager->flush();

        return new JsonResponse(['message' => 'Task deleted successfully'], JsonResponse::HTTP_NO_CONTENT);
    }
}
