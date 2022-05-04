<?php

/**
 * this file is part of tech-test-iad-bt REST api
 * 
 * (c) Bandiougou TRAORE <tbandiougou3@gmail.com>
 */

namespace App\Controller;

use Exception;
use App\Entity\Contact;
use OpenApi\Annotations\Items as Items;
use OpenApi\Annotations\JsonContent as JsonContent;
use App\Repository\ContactRepository;
use OpenApi\Annotations\Response as OA;
use Doctrine\ORM\EntityManagerInterface;
use Nelmio\ApiDocBundle\Annotation\Model;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/**
 * provides CRUD actions for Entity Contaact
 * 
 * @author Bandiougou TRAORE <tbandiougou3@gmail.com>
 * 
 * @Route("/api", name="contact_api")
 */
class ContactController extends AbstractController
{
    /**
     * gets all contact data from database
     * @param ContactRepository $contactRepository
     * 
     * @Route("/contacts", name="contacts", methods={"GET"})
     * @OA(
     *     response=200,
     *     description="Returns all contacts.",
     *     @JsonContent(
     *        type="array",
     *        @Items(ref=@Model(type=Contact::class, groups={"full"}))
     *     )
     * )
     * @return JsonResponse
     */
    public function getAllContactsAction(ContactRepository $contactRepository): JsonResponse
    {
        if (empty($contactRepository->findAll())) {
            return $this->json([
                'error' => 'No contact registered yet. Please feed the database by creating new contact.'
            ], 404);
        }
        return $this->json([
            'contact' => $contactRepository->findAll()
        ], 200);
    }
    /**
     * creates new contact
     * @param ContactRepository $contactRepository
     * @param ValidatorInterface $validator
     * @param EntityManagerInterface $entityManager
     * @param Request $request
     * @param SerializerInterface $serializer
     * @Route("/contacts", name="new_contact", methods={"POST"})
     * @OA(
     *     response=201,
     *     description="Returns new contacts.",
     *     @JsonContent(
     *        type="array",
     *        @Items(ref=@Model(type=Contact::class, groups={"one"}))
     *     )
     * )
     * @return JsonResponse
     */
    public function newContactAction(ContactRepository $contactRepository, ValidatorInterface $validator, EntityManagerInterface $entityManager, Request $request, SerializerInterface $serializer): JsonResponse
    {
        $data = $request->getContent();
        if (!$this->isJson($data)) {
            return $this->json([
                "errors" => "please provide a valid json and complet contact data."
            ], 406);
        }
        $contact = $serializer->deserialize($data, Contact::class, 'json');
        $error = $validator->validate($contact);
        if (count($error) > 0) {
            return $this->json([
                "errors" => $error,
            ], 422);
        }
        if (!empty($contactRepository->findAll())) {
            $elderContact = $contactRepository->findOneBy(['email' => $contact->getEmail()]);
            if ($elderContact) {
                return $this->json([
                    'erroe' => 'this email adress already exists'
                ], 406);
            }
        }
        $entityManager->persist($contact);
        $entityManager->flush();

        return $this->json([
            'success' => 'New contact has been created succefully',
            'contact' => $contact
        ], 201);
    }

    /**
     * gets details of one contact
     * @param int $id
     * @param ContactRepository $contactRepository
     * @Route("/contacts/{id}", name="get_contact", methods={"GET"})
     * @OA(
     *     response=200,
     *     description="Returns one contact.",
     *     @JsonContent(
     *        type="array",
     *        @Items(ref=@Model(type=Contact::class, groups={"one"}))
     *     )
     * )
     * @return JsonResponse
     */
    public function getOneContactAction(int $id, ContactRepository $contactRepository): JsonResponse
    {
        $contact = $contactRepository->find($id);
        if (!$contact) {
            return $this->json([
                'error' => 'contact not found',
            ], 404);
        }

        return $this->json([
            'contact' => $contact
        ], 200);
    }

    /**
     * Updates a contact
     * @param int $id
     * @param ValidatorInterface $validator
     * @param EntityManagerInterface $entityManager
     * @param Request $request
     * @param ContactRepository $contactRepository
     * @Route("/contacts/{id}", name="edit_contact", methods={"PUT"})
     * @OA(
     *     response=202,
     *     description="Returns one contact.",
     *     @JsonContent(
     *        type="array",
     *        @Items(ref=@Model(type=Contact::class, groups={"one"}))
     *     )
     * )
     * @return JsonResponse
     */
    public function editContactAction(int $id, ContactRepository $contactRepository, ValidatorInterface $validator, EntityManagerInterface $entityManager, Request $request): JsonResponse
    {
        $gotContact = $contactRepository->find($id);
        if (!$gotContact) {
            return $this->json([
                'error' => 'contact not found',
            ], 404);
        }
        $data = $request->getContent();
        if (!$this->isJson($data)) {
            return $this->json([
                "errors" => "please provide a valid json and complet contact data."
            ], 406);
        }
        $contact = json_decode($data, true);
        $elderContact = $contactRepository->findOneBy(['email' => $contact['email']]);
        if ($elderContact != $gotContact && ($elderContact)) {
            return $this->json([
                'erroe' => 'this email adress already exists'
            ], 406);
        }

        ($contact['nom'] != $gotContact->getNom()) ? $gotContact->setNom($contact['nom']) : $gotContact->getNom();
        ($contact['prenom'] != $gotContact->getPrenom()) ? $gotContact->setPrenom($contact['prenom']) : $gotContact->getPrenom();
        ($contact['email'] != $gotContact->getEmail()) ? $gotContact->setEmail($contact['email']) : $gotContact->getEmail();
        ($contact['adresse'] != $gotContact->getAdresse()) ? $gotContact->setAdresse($contact['adresse']) : $gotContact->getAdresse();
        ($contact['telephone'] != $gotContact->getTelephone()) ? $gotContact->setTelephone($contact['telephone']) : $gotContact->getTelephone();
        ($contact['age'] != $gotContact->getAge()) ? $gotContact->setAge($contact['age']) : $gotContact->getAge();

        $error = $validator->validate($gotContact);
        if (count($error) > 0) {
            return $this->json([
                "errors" => $error,
            ], 422);
        }

        $entityManager->flush();

        return $this->json([
            'success' => 'Contact has been update succefully',
            'contact' => $gotContact
        ], 202);
    }
    /**
     * deletes a contact form the database
     * @param int $id
     * @param ContactRepository $contactRepository
     * @param EntityManagerInterface $entityManager
     * @Route("/contacts/{id}", name="delete_contact", methods={"DELETE"})
     * @OA(
     *     response=202,
     *     description="Returns one contact.",
     *     @JsonContent(
     *        type="array",
     *        @Items(ref=@Model(type=Contact::class, groups={"one"}))
     *     )
     * )
     * @return JsonResponse
     */
    public function deleteContactAction(int $id, ContactRepository $contactRepository, EntityManagerInterface $entityManager): JsonResponse
    {
        $contact = $contactRepository->find($id);
        if (!$contact) {
            return $this->json([
                'error' => 'contact not found',
            ], 404);
        }
        $entityManager->remove($contact);
        $entityManager->flush();
        return $this->json([
            'success' => 'the contact has been deleted successfully'
        ], 202);
    }
    protected function isJson($data)
    {
        return is_string($data) && is_array(json_decode($data, true)) && (json_last_error() == JSON_ERROR_NONE) ? true : false;
    }
}
