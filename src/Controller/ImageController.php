<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ImageController extends AbstractController
{
    #[Route('/images/{path}', name: 'app_image', requirements: ['path' => '.+'], methods: ['GET'])]
    public function serveImage(string $path): Response
    {
        $projectDir = $this->getParameter('kernel.project_dir');
        $imagePath = $projectDir . '/public/images/' . $path;

        // Check if file exists
        if (!file_exists($imagePath) || !is_file($imagePath)) {
            // Return default image instead of 404
            $defaultImagePath = $projectDir . '/public/default-image.svg';

            if (file_exists($defaultImagePath)) {
                $response = new BinaryFileResponse($defaultImagePath);
                $response->headers->set('Content-Type', 'image/svg+xml');
                // Cache default image for 1 hour
                $response->setMaxAge(3600);
                $response->setSharedMaxAge(3600);
                $response->setPublic();
                return $response;
            }

            // Fallback if default image doesn't exist
            return new Response('Image not found', Response::HTTP_NOT_FOUND);
        }

        // Check if it's a valid image
        $mimeType = mime_content_type($imagePath);
        if (!str_starts_with($mimeType, 'image/')) {
            return new Response('Invalid file type', Response::HTTP_NOT_FOUND);
        }

        // Serve the image
        $response = new BinaryFileResponse($imagePath);
        $response->headers->set('Content-Type', $mimeType);

        // Cache for 1 year (immutable images)
        $response->setMaxAge(31536000);
        $response->setSharedMaxAge(31536000);
        $response->setPublic();

        return $response;
    }
}
