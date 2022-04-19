<?php

namespace App\Controller;

use App\Entity\Item;
use App\Repository\ItemRepository;
use App\Repository\ShoppingListRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\Exception\NotEncodableValueException;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/items', name: 'items_')]
class ItemController extends AbstractController
{
    #[Route('/{idItem<\d+>}', name: 'update', methods: 'PUT')]
    public function updateItemInOneShoppingList(Request $request, SerializerInterface $serializer, ShoppingListRepository $shoppingListRepository, ItemRepository $itemRepository, ManagerRegistry $doctrine, ValidatorInterface $validator, int $idItem): Response
    {
        $item = $itemRepository->find($idItem);

        if ($item === null) {
            return $this->json(['error' => 'L\'item n\'existe pas.'], Response::HTTP_NOT_FOUND);
        }

        $jsonContent = $request->getContent();

        try {
            $item = $serializer->deserialize($jsonContent, Item::class, 'json', [AbstractNormalizer::OBJECT_TO_POPULATE => $item]);
        } catch (NotEncodableValueException $e) {
            return $this->json(
                ['error' => 'JSON invalide'],
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }

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
            $item->getShoppingList(),
            Response::HTTP_CREATED,
            [
                'Location' => $this->generateUrl('shoppingLists_show', ['id' => $item->getShoppingList()->getId()])
            ],
            ['groups' => 'get_one_list']
        );
    }
}
