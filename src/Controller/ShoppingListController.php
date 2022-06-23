<?php

namespace App\Controller;

use App\Entity\Item;
use App\Entity\ShoppingList;
use App\Repository\ShoppingListRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Serializer\Exception\NotEncodableValueException;

#[Route('/api/shopping-lists', name: 'shoppingLists_')]
class ShoppingListController extends AbstractController
{
    #[Route('/', name: 'all', methods: 'GET')]
    public function getAllShoppingLists(ShoppingListRepository $shoppingListRepository): Response
    {
        $shoppingLists = $shoppingListRepository->findAll();

        return $this->json(
            $shoppingLists,
            Response::HTTP_OK,
            [],
            ['groups' => 'get_all_lists']
        );
    }

    #[Route('/not-archived', name: 'not-archived', methods: 'GET')]
    public function getShoppingListsNotArchvied(ShoppingListRepository $shoppingListRepository): Response
    {
        $shoppingLists = $shoppingListRepository->findBy(['archived' => false]);

        return $this->json(
            $shoppingLists,
            Response::HTTP_OK,
            [],
            ['groups' => 'get_all_lists']
        );
    }

    #[Route('/{id<\d+>}', name: 'show', methods: 'GET')]
    public function getOneShoppingList(ShoppingListRepository $shoppingListRepository, int $id): Response
    {
        $shoppingList = $shoppingListRepository->find($id);

         if ($shoppingList === null) {
            return $this->json(['error' => 'Liste de course n\'existe pas.'], Response::HTTP_NOT_FOUND);
        }

        return $this->json(
            $shoppingList,
            Response::HTTP_OK,
            [],
            ['groups' => 'get_one_list']
        );
    }

    #[Route('/', name: 'create', methods: 'POST')]
    public function createShoppingList(Request $request, SerializerInterface $serializer, ManagerRegistry $doctrine, ValidatorInterface $validator): Response
    {
        $jsonContent = $request->getContent();

        try {
            $shoppingList = $serializer->deserialize($jsonContent, ShoppingList::class, 'json');
        } catch (NotEncodableValueException $e) {
            return $this->json(
                ['error' => 'JSON invalide'],
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }

        $errors = $validator->validate($shoppingList);

        if (count($errors) > 0) {
            $errorsClean = [];
            foreach ($errors as $error) {
                $errorsClean[$error->getPropertyPath()][] = $error->getMessage();
            };

            return $this->json($errorsClean, Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $entityManager = $doctrine->getManager();
        $entityManager->persist($shoppingList);
        $entityManager->flush();

        return $this->json(
            $shoppingList,
            Response::HTTP_CREATED,
            [
                'Location' => $this->generateUrl('shoppingLists_show', ['id' => $shoppingList->getId()])
            ],
            ['groups' => 'get_one_list']
        );
    }

    #[Route('/{idShoppingList<\d+>}/items', name: 'create_item', methods: 'POST')]
    public function createItemInOneShoppingList(Request $request, SerializerInterface $serializer, ShoppingListRepository $shoppingListRepository, ManagerRegistry $doctrine, ValidatorInterface $validator, int $idShoppingList): Response
    {
        $shoppingList = $shoppingListRepository->find($idShoppingList);

        if ($shoppingList === null) {
            return $this->json(['error' => 'Liste de course n\'existe pas.'], Response::HTTP_NOT_FOUND);
        }

        $jsonContent = $request->getContent();

        try {
            $item = $serializer->deserialize($jsonContent, Item::class, 'json');
        } catch (NotEncodableValueException $e) {
            return $this->json(
                ['error' => 'JSON invalide'],
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }

        $item->setShoppingList($shoppingList);

        $errors = $validator->validate($item);

        if (count($errors) > 0) {
            $errorsClean = [];
            foreach ($errors as $error) {
                $errorsClean[$error->getPropertyPath()][] = $error->getMessage();
            };

            return $this->json($errorsClean, Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $entityManager = $doctrine->getManager();
        $entityManager->persist($item);
        $entityManager->flush();

        return $this->json(
            $shoppingList,
            Response::HTTP_CREATED,
            [
                'Location' => $this->generateUrl('shoppingLists_show', ['id' => $shoppingList->getId()])
            ],
            ['groups' => 'get_one_list']
        );
    }

    #[Route('/{idShoppingList<\d+>}/archive', name: 'archive', methods: 'PUT')]
    public function archivedShoppingList(Request $request, SerializerInterface $serializer, ShoppingListRepository $shoppingListRepository, ManagerRegistry $doctrine, ValidatorInterface $validator, int $idShoppingList): Response
    {
        $shoppingList = $shoppingListRepository->find($idShoppingList);

        if ($shoppingList === null) {
            return $this->json(['error' => 'Liste de course n\'existe pas.'], Response::HTTP_NOT_FOUND);
        }

        if ($shoppingList->getArchived()) {
            return $this->json(['error'=> 'Cette liste est déjà archivée'], Response::HTTP_BAD_REQUEST);
        }

        $shoppingList->setArchived(true);

        $entityManager = $doctrine->getManager();
        $entityManager->persist($shoppingList);
        $entityManager->flush();

        return $this->json(
            $shoppingList,
            Response::HTTP_CREATED,
            [
                'Location' => $this->generateUrl('shoppingLists_show', ['id' => $shoppingList->getId()])
            ],
            ['groups' => 'get_one_list']
        );
    }
}
