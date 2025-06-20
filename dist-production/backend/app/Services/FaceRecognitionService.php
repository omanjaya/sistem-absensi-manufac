<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FaceRecognitionService
{
    protected $baseUrl;
    protected $timeout;

    public function __construct()
    {
        $this->baseUrl = config('services.face_recognition.url', 'http://localhost:5000');
        $this->timeout = config('services.face_recognition.timeout', 30);
    }

    /**
     * Register a new face for a user
     */
    public function registerFace(int $userId, string $photoBase64): array
    {
        try {
            $response = Http::timeout($this->timeout)
                ->post($this->baseUrl . '/register-face', [
                    'user_id' => $userId,
                    'photo' => $photoBase64
                ]);

            if ($response->successful()) {
                $data = $response->json();

                Log::info('Face registration successful', [
                    'user_id' => $userId,
                    'response' => $data
                ]);

                return [
                    'success' => true,
                    'message' => $data['message'] ?? 'Face registered successfully',
                    'data' => $data
                ];
            }

            $errorData = $response->json();
            Log::error('Face registration failed', [
                'user_id' => $userId,
                'status' => $response->status(),
                'response' => $errorData
            ]);

            return [
                'success' => false,
                'message' => $errorData['message'] ?? 'Face registration failed',
                'error' => $errorData
            ];
        } catch (\Exception $e) {
            Log::error('Face registration service error', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Face recognition service unavailable. Please try again later.',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Recognize a face and return user information
     */
    public function recognizeFace(string $photoBase64): array
    {
        try {
            $response = Http::timeout($this->timeout)
                ->post($this->baseUrl . '/recognize', [
                    'photo' => $photoBase64
                ]);

            if ($response->successful()) {
                $data = $response->json();

                Log::info('Face recognition successful', [
                    'recognized_user' => $data['user_id'] ?? 'unknown',
                    'confidence' => $data['confidence'] ?? 0
                ]);

                // Check if face was recognized
                if (isset($data['user_id']) && $data['user_id'] !== 'unknown') {
                    return [
                        'success' => true,
                        'user_id' => $data['user_id'],
                        'confidence' => $data['confidence'] ?? 0,
                        'message' => 'Face recognized successfully'
                    ];
                }

                return [
                    'success' => false,
                    'message' => 'Face not recognized. Please register your face first.',
                    'user_id' => null,
                    'confidence' => 0
                ];
            }

            $errorData = $response->json();
            Log::error('Face recognition failed', [
                'status' => $response->status(),
                'response' => $errorData
            ]);

            return [
                'success' => false,
                'message' => $errorData['message'] ?? 'Face recognition failed',
                'error' => $errorData
            ];
        } catch (\Exception $e) {
            Log::error('Face recognition service error', [
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Face recognition service unavailable. Please try again later.',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Check the status of the face recognition service
     */
    public function checkHealth(): array
    {
        try {
            $response = Http::timeout(10)->get($this->baseUrl . '/health');

            if ($response->successful()) {
                return [
                    'success' => true,
                    'status' => 'online',
                    'response_time' => $response->handlerStats()['total_time'] ?? 0,
                    'data' => $response->json()
                ];
            }

            return [
                'success' => false,
                'status' => 'error',
                'message' => 'Service returned error status',
                'http_status' => $response->status()
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'status' => 'offline',
                'message' => 'Service unreachable',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get face registration status for a user
     */
    public function getFaceStatus(int $userId): array
    {
        try {
            $response = Http::timeout($this->timeout)
                ->get($this->baseUrl . '/status/' . $userId);

            if ($response->successful()) {
                $data = $response->json();

                return [
                    'success' => true,
                    'registered' => $data['registered'] ?? false,
                    'last_updated' => $data['last_updated'] ?? null,
                    'encoding_count' => $data['encoding_count'] ?? 0
                ];
            }

            return [
                'success' => false,
                'registered' => false,
                'message' => 'Unable to check face status'
            ];
        } catch (\Exception $e) {
            Log::error('Face status check error', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'registered' => false,
                'message' => 'Service unavailable'
            ];
        }
    }

    /**
     * Delete face data for a user
     */
    public function deleteFace(int $userId): array
    {
        try {
            $response = Http::timeout($this->timeout)
                ->delete($this->baseUrl . '/face/' . $userId);

            if ($response->successful()) {
                $data = $response->json();

                Log::info('Face data deleted', [
                    'user_id' => $userId
                ]);

                return [
                    'success' => true,
                    'message' => $data['message'] ?? 'Face data deleted successfully'
                ];
            }

            $errorData = $response->json();
            Log::error('Face deletion failed', [
                'user_id' => $userId,
                'status' => $response->status(),
                'response' => $errorData
            ]);

            return [
                'success' => false,
                'message' => $errorData['message'] ?? 'Face deletion failed'
            ];
        } catch (\Exception $e) {
            Log::error('Face deletion service error', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Face recognition service unavailable',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Update face encoding for a user (re-register)
     */
    public function updateFace(int $userId, string $photoBase64): array
    {
        // First delete existing face data
        $deleteResult = $this->deleteFace($userId);

        if (!$deleteResult['success']) {
            // Continue anyway, maybe face data didn't exist
            Log::warning('Could not delete existing face data, proceeding with registration', [
                'user_id' => $userId
            ]);
        }

        // Register new face
        return $this->registerFace($userId, $photoBase64);
    }

    /**
     * Batch process multiple faces (for testing or migration)
     */
    public function batchProcess(array $faces): array
    {
        $results = [];
        $successful = 0;
        $failed = 0;

        foreach ($faces as $face) {
            $result = $this->registerFace($face['user_id'], $face['photo']);
            $results[] = $result;

            if ($result['success']) {
                $successful++;
            } else {
                $failed++;
            }
        }

        return [
            'total' => count($faces),
            'successful' => $successful,
            'failed' => $failed,
            'results' => $results
        ];
    }
}
