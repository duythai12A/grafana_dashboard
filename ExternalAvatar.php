<?php

declare(strict_types=1);
/**
 * External Avatar implementation for Viettel VOPS
 */
namespace OC\Avatar;

use OCP\Color;
use OCP\Files\NotFoundException;
use OCP\Files\NotPermittedException;
use OCP\Files\SimpleFS\ISimpleFile;
use OCP\Files\SimpleFS\ISimpleFolder;
use OCP\IAvatar;
use OCP\IConfig;
use OCP\IImage;
use OCP\IUserManager;
use Psr\Log\LoggerInterface;

/**
 * This class represents an avatar fetched from external API
 */
class ExternalAvatar implements IAvatar {
	private const VOPS_API_URL = 'https://vops.viettel.vn/avatar/';

	public function __construct(
		private ISimpleFolder $folder,
		private string $userId,
		private IConfig $config,
		private LoggerInterface $logger,
		private IUserManager $userManager,
	) {
		$this->logger->info('[ExternalAvatar] *** NEW INSTANCE CREATED ***');
		$this->logger->info("[ExternalAvatar] - User ID: $userId");
		$this->logger->info('[ExternalAvatar] - Folder path: ' . $folder->getName());
	}

	/**
	 * Get user email from username
	 */
	private function getUserEmail(string $userId): string {
		$this->logger->info("[ExternalAvatar] Starting getUserEmail for userId: $userId");

		// Get user object from user manager
		$user = $this->userManager->get($userId);

		if ($user === null) {
			$this->logger->warning("[ExternalAvatar] User not found in UserManager: $userId");
			return $userId; // Fallback to userId
		}

		$this->logger->info("[ExternalAvatar] User object found for: $userId");

		// Get email address
		$email = $user->getEMailAddress();

		if (empty($email)) {
			$this->logger->warning("[ExternalAvatar] No email found for user: $userId, using userId as fallback");
			return $userId; // Fallback to userId if no email
		}

		$this->logger->info("[ExternalAvatar] Email retrieved successfully: $email for user: $userId");
		return $email;
	}

	/**
	 * Extract username from email (part before @)
	 */
	private function extractUsernameFromEmail(string $email): string {
		$this->logger->info("[ExternalAvatar] Extracting username from email: $email");

		$atPosition = strpos($email, '@');
		if ($atPosition !== false) {
			$username = substr($email, 0, $atPosition);
			$this->logger->info("[ExternalAvatar] Username extracted: $username from email: $email");
			return $username;
		}

		$this->logger->info("[ExternalAvatar] No @ found in email, using as-is: $email");
		return $email;
	}

	/**
	 * Fetch avatar from external API
	 */
	private function fetchExternalAvatar(bool $darkMode = false): ?string {
		$this->logger->info('[ExternalAvatar] === Starting fetchExternalAvatar ===');
		$this->logger->info("[ExternalAvatar] Parameters - userId: {$this->userId}, darkMode: " . ($darkMode ? 'true' : 'false'));

		// Get user's email first
		$email = $this->getUserEmail($this->userId);
		$this->logger->info("[ExternalAvatar] Retrieved email: $email");

		// Extract username from email (part before @)
		$username = $this->extractUsernameFromEmail($email);
		$this->logger->info("[ExternalAvatar] Extracted username: $username");

		$url = self::VOPS_API_URL . $username;

		if ($darkMode) {
			$url .= '/dark';
		}

		$this->logger->info("[ExternalAvatar] Final API URL: $url");

		try {
			$context = stream_context_create([
				'http' => [
					'method' => 'GET',
					'timeout' => 5,
					'ignore_errors' => true,
				],
			]);

			$this->logger->info("[ExternalAvatar] Sending HTTP request to: $url");
			$startTime = microtime(true);

			$response = @file_get_contents($url, false, $context);

			$endTime = microtime(true);
			$duration = round(($endTime - $startTime) * 1000, 2); // milliseconds

			if ($response === false) {
				$this->logger->error('[ExternalAvatar] Failed to fetch avatar from API');
				$this->logger->error("[ExternalAvatar] - Email: $email");
				$this->logger->error("[ExternalAvatar] - Username: $username");
				$this->logger->error("[ExternalAvatar] - URL: $url");
				$this->logger->error("[ExternalAvatar] - Duration: {$duration}ms");

				// Check HTTP response headers
				if (isset($http_response_header)) {
					$this->logger->error('[ExternalAvatar] HTTP Response Headers: ' . json_encode($http_response_header));
				}

				return null;
			}

			$responseSize = strlen($response);
			$this->logger->info('[ExternalAvatar] Successfully fetched avatar');
			$this->logger->info("[ExternalAvatar] - Response size: $responseSize bytes");
			$this->logger->info("[ExternalAvatar] - Duration: {$duration}ms");
			$this->logger->info("[ExternalAvatar] - URL: $url");

			// Log response headers if available
			if (isset($http_response_header)) {
				$this->logger->info('[ExternalAvatar] HTTP Response Headers: ' . json_encode($http_response_header));
			}

			return $response;
		} catch (\Exception $e) {
			$this->logger->error('[ExternalAvatar] Exception occurred while fetching avatar');
			$this->logger->error('[ExternalAvatar] - Exception message: ' . $e->getMessage());
			$this->logger->error('[ExternalAvatar] - Exception trace: ' . $e->getTraceAsString());
			$this->logger->error("[ExternalAvatar] - URL: $url");
			$this->logger->error("[ExternalAvatar] - Email: $email");
			$this->logger->error("[ExternalAvatar] - Username: $username");
			return null;
		} finally {
			$this->logger->info('[ExternalAvatar] === Finished fetchExternalAvatar ===');
		}
	}

	/**
	 * Get or create cached avatar file
	 */
	//private function getCachedFile(int $size, bool $darkMode = false): ?ISimpleFile {
	//	$fileName = $darkMode ? "avatar_dark_{$size}.jpg" : "avatar_{$size}.jpg";
	//
	//	$this->logger->info("[ExternalAvatar] getCachedFile - Looking for cached file: $fileName for user: {$this->userId}");
	//
	//	try {
	//		// Try to get cached file
	//		$file = $this->folder->getFile($fileName);
	//		$this->logger->info("[ExternalAvatar] Cache HIT - Found cached file: $fileName, size: " . $file->getSize() . ' bytes');
	//		return $file;
	//	} catch (NotFoundException $e) {
	//		$this->logger->info("[ExternalAvatar] Cache MISS - File not found: $fileName, fetching from external API");
	//
	//		// Fetch from external API
	//		$avatarData = $this->fetchExternalAvatar($darkMode);
	//
	//		if ($avatarData === null) {
	//			$this->logger->error("[ExternalAvatar] Failed to fetch avatar data from external API for user: {$this->userId}");
	//			return null;
	//		}
	//
	//		// Save to cache
	//		try {
	//			$this->logger->info("[ExternalAvatar] Saving avatar to cache: $fileName");
	//			$file = $this->folder->newFile($fileName);
	//			$file->putContent($avatarData);
	//			$this->logger->info("[ExternalAvatar] Successfully saved avatar to cache: $fileName, size: " . strlen($avatarData) . ' bytes');
	//			return $file;
	//		} catch (NotPermittedException $e) {
	//			$this->logger->error('[ExternalAvatar] Cannot write avatar cache file: ' . $e->getMessage());
	//			$this->logger->error("[ExternalAvatar] - File: $fileName");
	//			$this->logger->error("[ExternalAvatar] - User: {$this->userId}");
	//			$this->logger->error('[ExternalAvatar] - Exception: ' . $e->getTraceAsString());
	//			return null;
	//		}
	//	}
	//}

	private function getCachedFile(int $size, bool $darkMode = false): ?ISimpleFile {
		$this->logger->info("[ExternalAvatar] Fetching avatar from external API for user: {$this->userId}");

		$avatarData = $this->fetchExternalAvatar($darkMode);

		if ($avatarData === null) {
			$this->logger->error("[ExternalAvatar] Failed to fetch avatar data from external API for user: {$this->userId}");
			return null;
		}

		try {
			$fileName = $darkMode ? "avatar_dark_{$size}.jpg" : "avatar_{$size}.jpg";
			$file = $this->folder->newFile($fileName);
			$file->putContent($avatarData);
			return $file;
		} catch (NotPermittedException $e) {
			$this->logger->error('[ExternalAvatar] Cannot write avatar file: ' . $e->getMessage());
			return null;
		}
	}

	public function get(int $size = 64, bool $darkMode = false): ?IImage {
		// Not implemented - we return files directly
		return null;
	}

	public function getFile(int $size, bool $darkMode = false): ISimpleFile {
		$this->logger->info('[ExternalAvatar] ========================================');
		$this->logger->info('[ExternalAvatar] getFile called');
		$this->logger->info("[ExternalAvatar] - User: {$this->userId}");
		$this->logger->info("[ExternalAvatar] - Size: {$size}px");
		$this->logger->info('[ExternalAvatar] - Dark mode: ' . ($darkMode ? 'YES' : 'NO'));

		$file = $this->getCachedFile($size, $darkMode);

		if ($file === null) {
			$this->logger->error("[ExternalAvatar] FAILED - Avatar not found for user: {$this->userId}");
			$this->logger->info('[ExternalAvatar] ========================================');
			throw new NotFoundException("Avatar not found for user: {$this->userId}");
		}

		$this->logger->info('[ExternalAvatar] SUCCESS - Returning avatar file');
		$this->logger->info('[ExternalAvatar] - File size: ' . $file->getSize() . ' bytes');
		$this->logger->info('[ExternalAvatar] - MIME type: ' . $file->getMimeType());
		$this->logger->info('[ExternalAvatar] ========================================');

		return $file;
	}

	public function isCustomAvatar(): bool {
		return true;
	}

	public function exists(): bool {
		$this->logger->info("[ExternalAvatar] Checking if avatar exists for user: {$this->userId}");
		$avatarData = $this->fetchExternalAvatar();
		$exists = $avatarData !== null;
		$this->logger->info('[ExternalAvatar] Avatar exists: ' . ($exists ? 'YES' : 'NO'));
		return $exists;
	}

	public function set($data): void {
		// Not allowed for external avatars
		throw new \Exception('Cannot set external avatar');
	}

	public function remove(bool $silent = false): void {
		$this->logger->info("[ExternalAvatar] Removing avatar cache for user: {$this->userId}, silent: " . ($silent ? 'true' : 'false'));

		// Clear cache
		try {
			$this->folder->delete();
			$this->logger->info("[ExternalAvatar] Successfully deleted avatar cache folder for user: {$this->userId}");
		} catch (NotFoundException|NotPermittedException $e) {
			$this->logger->warning('[ExternalAvatar] Could not delete avatar cache: ' . $e->getMessage());
			if (!$silent) {
				throw $e;
			}
		}
	}

	public function userChanged(string $feature, $oldValue, $newValue): void {
		$this->logger->info("[ExternalAvatar] User data changed for: {$this->userId}");
		$this->logger->info("[ExternalAvatar] - Feature: $feature");
		$this->logger->info('[ExternalAvatar] - Old value: ' . json_encode($oldValue));
		$this->logger->info('[ExternalAvatar] - New value: ' . json_encode($newValue));
		$this->logger->info('[ExternalAvatar] Clearing avatar cache...');

		// Clear cache when user data changes
		$this->remove(true);
	}

	public function avatarBackgroundColor(string $hash): Color {
		// Generate a color based on the hash
		// This is used for placeholder avatars, but since we use external avatars,
		// we'll provide a simple implementation
		$hash = md5($hash);
		$r = hexdec(substr($hash, 0, 2));
		$g = hexdec(substr($hash, 2, 2));
		$b = hexdec(substr($hash, 4, 2));

		return new Color($r, $g, $b);
	}
}
