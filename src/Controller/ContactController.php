<?php

namespace App\Controller;

use App\Entity\Contact;
use App\Form\ContactType;
use App\Repository\ContactRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Filesystem\Filesystem;

class ContactController extends AbstractController
{
    public function __construct(private readonly LoggerInterface $logger)
    {
    }

    #[Route('/', name: 'app_contact_index', methods: ['GET'])]
    public function index(ContactRepository $contactRepository, Request $request): Response
    {
        $search = $request->query->get('search');
        $contacts = $search
            ? $contactRepository->search($search)
            : $contactRepository->findAllSorted();

        return $this->render('contact/index.html.twig', [
            'contacts' => $contacts,
            'search' => $search
        ]);
    }

    #[Route('/contacts/new', name: 'app_contact_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $contact = new Contact();
        $form = $this->createForm(ContactType::class, $contact);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $firstName = $contact->getFirstName();
            $lastName = $contact->getLastName();

            if (!$this->isValidName($firstName) || !$this->isValidName($lastName)) {
                $this->addFlash('error', 'Names can only contain letters and accented characters.');
                return $this->render('contact/new.html.twig', [
                    'contact' => $contact,
                    'form' => $form,
                ]);
            }

            // Handle custom fields
            $customFields = $request->request->all('custom_fields');
            if (is_array($customFields)) {
                $processedFields = [];
                foreach ($customFields as $field) {
                    if (isset($field['name']) && isset($field['value']) && !empty($field['name'])) {
                        $processedFields[$field['name']] = $field['value'];
                    }
                }
                $contact->setCustomFields($processedFields);
            }

            // Handle photo upload
            $photoFile = $form->get('photo')->getData();
            if ($photoFile) {
                $newFilename = uniqid().'.'.$photoFile->guessExtension();
                try {
                    $photoFile->move(
                        $this->getParameter('photos_directory'),
                        $newFilename
                    );
                    $contact->setPhotoFilename($newFilename);
                } catch (\Exception $e) {
                    $this->addFlash('error', 'Error uploading photo');
                }
            }

            $entityManager->persist($contact);
            $entityManager->flush();

            return $this->redirectToRoute('app_contact_index');
        }

        return $this->render('contact/new.html.twig', [
            'contact' => $contact,
            'form' => $form,
        ]);
    }

    private function isValidName(string $name): bool
    {
        return preg_match('/^[\p{L}\s]+$/u', $name) === 1;
    }

    #[Route('/contacts/{id}/edit', name: 'app_contact_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Contact $contact, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(ContactType::class, $contact);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Handle custom fields
            $customFields = $request->request->all('custom_fields');
            if (is_array($customFields)) {
                $processedFields = [];
                foreach ($customFields as $field) {
                    if (isset($field['name']) && isset($field['value']) && !empty($field['name'])) {
                        $processedFields[$field['name']] = $field['value'];
                    }
                }
                $contact->setCustomFields($processedFields);
            }

            // Handle photo upload
            $photoFile = $form->get('photo')->getData();
            if ($photoFile) {
                // Delete old photo if it exists
                $this->deleteContactPhoto($contact);

                $newFilename = uniqid().'.'.$photoFile->guessExtension();
                try {
                    $photoFile->move(
                        $this->getParameter('photos_directory'),
                        $newFilename
                    );
                    $contact->setPhotoFilename($newFilename);
                } catch (\Exception $e) {
                    $this->addFlash('error', 'Error uploading photo');
                }
            }

            $entityManager->flush();

            return $this->redirectToRoute('app_contact_index');
        }

        return $this->render('contact/edit.html.twig', [
            'contact' => $contact,
            'form' => $form,
        ]);
    }

    private function deleteContactPhoto(Contact $contact): void
    {
        $filesystem = new Filesystem();
        $photoFilename = $contact->getPhotoFilename();

        if ($photoFilename) {
            $photoPath = $this->getParameter('photos_directory') . '/' . $photoFilename;
            if ($filesystem->exists($photoPath)) {
                try {
                    $filesystem->remove($photoPath);
                } catch (\Exception $e) {
                    // Log error if needed
                }
            }
        }
    }

    #[Route('/contacts/{id}', name: 'app_contact_delete', methods: ['POST'])]
    public function delete(Request $request, Contact $contact, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$contact->getId(), $request->request->get('_token'))) {
            // Delete the photo file first
            $this->deleteContactPhoto($contact);

            $entityManager->remove($contact);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_contact_index');
    }

    #[Route('/bulk-delete', name: 'app_contact_bulk_delete', methods: ['POST'])]
    public function bulkDelete(
        Request $request,
        ContactRepository $contactRepository,
        EntityManagerInterface $entityManager
    ): Response
    {
        // Validate CSRF token
        if (!$this->isCsrfTokenValid('bulk_delete', $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }

        // Retrieve and decode contact IDs from the request
        $contactIds = json_decode($request->request->get('contact_ids'), true) ?? [];

        // Validate contact IDs
        if (empty($contactIds)) {
            $this->addFlash('error', 'No contacts selected for deletion.');
            return $this->redirectToRoute('app_contact_index');
        }

        // Fetch contacts to be deleted
        $contacts = $contactRepository->findBy(['id' => $contactIds]);

        // Delete each contact
        $successCount = 0;
        foreach ($contacts as $contact) {
            try {
                // Delete the photo file (if applicable)
                $this->deleteContactPhoto($contact);

                // Remove the contact from the database
                $entityManager->remove($contact);
                $successCount++;
            } catch (\Exception $e) {
                // Log the error and continue
                error_log('Error deleting contact ID ' . $contact->getId() . ': ' . $e->getMessage());
            }
        }

        // Flush changes to the database
        $entityManager->flush();

        // Add feedback message
        if ($successCount > 0) {
            $this->addFlash('success', $successCount . ' contacts deleted successfully.');
        } else {
            $this->addFlash('error', 'No contacts were deleted.');
        }

        return $this->redirectToRoute('app_contact_index');
    }

}