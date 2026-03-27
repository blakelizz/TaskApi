<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Attribute\Route;
use App\Repository\TaskRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use App\Entity\Task;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManagerInterface;

#[Route('/api/tasks', name: 'api_tasks_')]
final class TaskController extends AbstractController
{
    #[Route('', name: 'list', methods: ['GET'])]
    public function index(TaskRepository $taskRepository): JsonResponse
    {
        $tasks = $taskRepository->findAll();

        $data = array_map(fn(Task $task) => [
            'id' => $task->getId(),
            'title' => $task->getTitle(),
            'description' => $task->getDescription(),
            'status' => $task->getStatus(),
            'created_at' => $task->getCreatedAt()->format('c'),
            'user' => $task->getUser()?->getId(),
        ], $tasks);

        return new JsonResponse($data, 200);
    }

    //AJOUTER
    #[Route('', name: 'addTask', methods: ['POST'])]
    public function addTask(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $data = json_decode($request->getContent(), true);

        $task = new Task();
        $task->setTitle($data['title'] ?? '');
        $task->setDescription($data['description'] ?? null);
        $task->setStatus($data['status'] ?? 'todo');
        $task->setUser($this->getUser());

        $em->persist($task);
        $em->flush();

        return new JsonResponse(['id' => $task->getId()], 201);
    }

    //MODIFIER
    #[Route('/{id}', name: 'editTask', methods: ['PUT'])]
    public function editTask(Request $request, EntityManagerInterface $entityManager, int $id): JsonResponse
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $task = $entityManager->find(Task::class, $id);

        if (!$task) {
            return new JsonResponse(['message' => 'Task not found'], JsonResponse::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true);

        $task->setTitle($data['title'] ?? $task->getTitle());
        $task->setDescription($data['description'] ?? $task->getDescription());
        $task->setStatus($data['status'] ?? $task->getStatus());

        $entityManager->flush();

        return new JsonResponse(['message' => 'Task updated successfully'], JsonResponse::HTTP_OK);
    }

    //SUPPRIMER
    #[Route('/{id}', name: 'deleteTask', methods: ['DELETE'])]
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
