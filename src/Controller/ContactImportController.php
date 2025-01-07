<?php

namespace App\Controller;

use App\Entity\Contact;
use App\Repository\ContactRepository;
use Doctrine\ORM\EntityManagerInterface;
use Google_Client;
use Google_Service_PeopleService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Filesystem\Filesystem;

class ContactImportController extends AbstractController
{
    private $entityManager;
    private $contactRepository;
    private $logger;

    public function __construct(
        EntityManagerInterface $entityManager,
        ContactRepository $contactRepository,
        LoggerInterface $logger
    ) {
        $this->entityManager = $entityManager;
        $this->contactRepository = $contactRepository;
        $this->logger = $logger;
    }

    /**
     * Redirects the user to Google's OAuth consent screen.
     */
    #[Route('/import/google', name: 'import_google', methods: ['GET'])]
    public function importGoogle(Request $request): Response
    {
        $client = new Google_Client();
        $client->setAuthConfig($this->getParameter('kernel.project_dir') . '/config/google_client_secret.json');
        $client->addScope(Google_Service_PeopleService::CONTACTS_READONLY);

        // Generate an absolute URL for the redirect URI
        $redirectUri = $this->generateUrl('import_google_callback', [], UrlGeneratorInterface::ABSOLUTE_URL);
        $client->setRedirectUri($redirectUri);

        $client->setAccessType('offline');
        $client->setPrompt('select_account consent');

        $authUrl = $client->createAuthUrl();
        return $this->redirect($authUrl);
    }

    /**
     * Handles the OAuth callback and imports contacts.
     */
    #[Route('/import/google/callback', name: 'import_google_callback', methods: ['GET'])]
    public function importGoogleCallback(Request $request): Response
    {
        $this->logger->info('Starting Google contact import process.');

        $client = new Google_Client();
        $client->setAuthConfig($this->getParameter('kernel.project_dir') . '/config/google_client_secret.json');
        $client->addScope(Google_Service_PeopleService::CONTACTS_READONLY);

        // Generate an absolute URL for the redirect URI
        $redirectUri = $this->generateUrl('import_google_callback', [], UrlGeneratorInterface::ABSOLUTE_URL);
        $client->setRedirectUri($redirectUri);

        $code = $request->query->get('code');
        if (!$code) {
            $this->addFlash('error', 'Authorization failed. Please try again.');
            return $this->redirectToRoute('app_contact_index');
        }

        $token = $client->fetchAccessTokenWithAuthCode($code);
        if (isset($token['error'])) {
            $this->addFlash('error', 'Failed to fetch access token: ' . $token['error_description']);
            return $this->redirectToRoute('app_contact_index');
        }

        $client->setAccessToken($token);
        $service = new Google_Service_PeopleService($client);

        $results = $service->people_connections->listPeopleConnections(
            'people/me',
            ['personFields' => 'names,emailAddresses,phoneNumbers,photos']
        );

        $importedCount = 0;
        $duplicateCount = 0;
        $batchSize = 20;
        $connections = $results->getConnections();

        // Log the total number of contacts found
        $this->logger->info(sprintf('Total contacts found: %d', count($connections)));

        foreach ($connections as $i => $person) {
            $email = $this->extractPrimaryEmail($person->getEmailAddresses());
            $firstName = $this->extractFirstName($person->getNames()) ?? 'Unknown'; // Default to 'Unknown' if null
            $lastName = $this->extractLastName($person->getNames()) ?? 'Unknown'; // Default to 'Unknown' if null
            $phone = $this->extractPrimaryPhone($person->getPhoneNumbers()) ?? 'N/A'; // Default to 'N/A' if null
            $photoUrl = $this->extractPhotoUrl($person->getPhotos());

            // Set default email if none is provided
            if (!$email) {
                $email = 'no@email.com';
            }

            // Remove spaces from the phone number
            if ($phone !== 'N/A') {
                $phone = str_replace(' ', '', $phone);
            }

            // Log the contact details
            $this->logger->info(sprintf(
                'Processing contact: %s %s, Email: %s, Phone: %s',
                $firstName,
                $lastName,
                $email,
                $phone
            ));

            // Check for duplicate by email
            $existingContact = $this->contactRepository->findOneBy(['email' => $email]);
            if ($existingContact) {
                $duplicateCount++;
                continue; // Skip duplicate
            }

            // Create and save new contact
            $contact = new Contact();
            $contact->setFirstName($firstName);
            $contact->setLastName($lastName);
            $contact->setEmail($email);
            $contact->setPhone($phone);

            // Handle photo upload
            if ($photoUrl) {
                $newFilename = uniqid().'.jpg'; // Assuming the photo is in JPG format
                $photoPath = $this->getParameter('photos_directory') . '/' . $newFilename;

                try {
                    file_put_contents($photoPath, file_get_contents($photoUrl));
                    $contact->setPhotoFilename($newFilename);
                } catch (\Exception $e) {
                    $this->logger->error('Error downloading photo: ' . $e->getMessage());
                }
            }

            $this->entityManager->persist($contact);
            $importedCount++;

            // Batch processing to avoid memory issues
            if (($i % $batchSize) === 0) {
                $this->entityManager->flush();
                $this->entityManager->clear(); // Detach objects to free memory
            }
        }

        // Final flush to save any remaining contacts
        $this->entityManager->flush();
        $this->entityManager->clear();

        // Provide feedback to the user
        $this->addFlash('success', sprintf('%d contacts imported, %d duplicates skipped.', $importedCount, $duplicateCount));
        return $this->redirectToRoute('app_contact_index');
    }

    /**
     * Extracts the primary email from a list of email addresses.
     */
    private function extractPrimaryEmail(array $emailAddresses): ?string
    {
        foreach ($emailAddresses as $email) {
            if ($email->getMetadata()->getPrimary()) {
                return $email->getValue();
            }
        }
        return null;
    }

    /**
     * Extracts the first name from a list of names.
     */
    private function extractFirstName(array $names): ?string
    {
        foreach ($names as $name) {
            if ($name->getMetadata()->getPrimary()) {
                return $name->getGivenName();
            }
        }
        return null;
    }

    /**
     * Extracts the last name from a list of names.
     */
    private function extractLastName(array $names): ?string
    {
        foreach ($names as $name) {
            if ($name->getMetadata()->getPrimary()) {
                return $name->getFamilyName();
            }
        }
        return null;
    }

    /**
     * Extracts the primary phone number from a list of phone numbers.
     * Replaces the French indicator (+33) with '0' to format the number correctly.
     */
    private function extractPrimaryPhone(array $phoneNumbers): ?string
    {
        foreach ($phoneNumbers as $phone) {
            if ($phone->getMetadata()->getPrimary()) {
                $phoneNumber = $phone->getValue();

                // Replace +33 with 0 for French phone numbers
                if (strpos($phoneNumber, '+33') === 0) {
                    $phoneNumber = '0' . substr($phoneNumber, 3);
                }

                return $phoneNumber;
            }
        }
        return null;
    }

    /**
     * Extracts the photo URL from a list of photos.
     */
    private function extractPhotoUrl(array $photos): ?string
    {
        foreach ($photos as $photo) {
            if ($photo->getMetadata()->getPrimary()) {
                return $photo->getUrl();
            }
        }
        return null;
    }
}