<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Services\FaceRecognitionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class FaceRecognitionController extends Controller
{
    protected $faceService;

    public function __construct(FaceRecognitionService $faceService)
    {
        $this->faceService = $faceService;
    }

    /**
     * Register a new face for the authenticated user
     */
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'photo' => 'required|string', // Base64 encoded image
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = $request->user();

        try {
            $result = $this->faceService->registerFace($user->id, $request->photo);

            if ($result['success']) {
                Log::info('Face registration successful', [
                    'user_id' => $user->id,
                    'user_name' => $user->name
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Face registered successfully',
                    'data' => [
                        'user_id' => $user->id,
                        'registered_at' => now()
                    ]
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => $result['message']
                ], 400);
            }
        } catch (\Exception $e) {
            Log::error('Face registration error', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Face registration failed. Please try again.'
            ], 500);
        }
    }

    /**
     * Recognize a face (for testing purposes - normally used internally)
     */
    public function recognize(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'photo' => 'required|string', // Base64 encoded image
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $result = $this->faceService->recognizeFace($request->photo);

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'message' => 'Face recognized successfully',
                    'data' => [
                        'user_id' => $result['user_id'],
                        'confidence' => $result['confidence'] ?? 0,
                        'recognized_at' => now()
                    ]
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => $result['message'],
                    'data' => [
                        'user_id' => null,
                        'confidence' => 0
                    ]
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Face recognition error', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Face recognition failed. Please try again.'
            ], 500);
        }
    }

    /**
     * Check face registration status for a user
     */
    public function status(Request $request, $userId = null)
    {
        // If no userId provided, use authenticated user
        if (!$userId) {
            $userId = $request->user()->id;
        }

        // Only admin can check other users' status
        $user = $request->user();
        if ($userId != $user->id && $user->role !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access'
            ], 403);
        }

        try {
            $result = $this->faceService->getFaceStatus($userId);

            return response()->json([
                'success' => true,
                'data' => [
                    'user_id' => $userId,
                    'registered' => $result['registered'] ?? false,
                    'last_updated' => $result['last_updated'] ?? null,
                    'encoding_count' => $result['encoding_count'] ?? 0
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Face status check error', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Unable to check face status. Please try again.'
            ], 500);
        }
    }

    /**
     * Delete face data for a user (admin only or own data)
     */
    public function deleteFace(Request $request, $userId = null)
    {
        // If no userId provided, use authenticated user
        if (!$userId) {
            $userId = $request->user()->id;
        }

        // Only admin can delete other users' face data
        $user = $request->user();
        if ($userId != $user->id && $user->role !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access'
            ], 403);
        }

        try {
            $result = $this->faceService->deleteFaceData($userId);

            if ($result['success']) {
                Log::info('Face data deleted', [
                    'user_id' => $userId,
                    'deleted_by' => $user->id
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Face data deleted successfully'
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => $result['message']
                ], 400);
            }
        } catch (\Exception $e) {
            Log::error('Face data deletion error', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Face data deletion failed. Please try again.'
            ], 500);
        }
    }
}
