<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ToModel;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\File;
use DateTime;
use Illuminate\Support\Facades\ToModel as ToModelFacade;
use Illuminate\Support\Facades\FromArray as FromArrayFacade;
use App\Models\AuditLog;

class UserController extends Controller
{
    /**
     * Display a listing of users/employees
     */
    public function index(Request $request)
    {
        $query = User::query();

        // Filters
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('employee_id', 'like', "%{$search}%")
                    ->orWhere('department', 'like', "%{$search}%");
            });
        }

        if ($request->filled('department')) {
            $query->where('department', $request->department);
        }

        if ($request->filled('role')) {
            $query->where('role', $request->role);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Sort
        $sortBy = $request->get('sort_by', 'name');
        $sortDirection = $request->get('sort_direction', 'asc');
        $query->orderBy($sortBy, $sortDirection);

        $users = $query->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $users
        ]);
    }

    /**
     * Store a newly created user/employee
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6|confirmed',
            'employee_id' => 'nullable|string|unique:users',
            'phone' => 'nullable|string|max:20',
            'department' => 'required|string|max:100',
            'position' => 'required|string|max:100',
            'role' => 'required|in:admin,employee',
            'join_date' => 'required|date',
            'basic_salary' => 'required|numeric|min:0',
            'status' => 'required|in:active,inactive',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'employee_id' => $request->employee_id,
                'phone' => $request->phone,
                'department' => $request->department,
                'position' => $request->position,
                'role' => $request->role,
                'join_date' => $request->join_date,
                'basic_salary' => $request->basic_salary,
                'status' => $request->status,
            ]);

            Log::info('New user created', [
                'user_id' => $user->id,
                'created_by' => $request->user()->id,
                'role' => $user->role
            ]);

            return response()->json([
                'success' => true,
                'message' => 'User created successfully',
                'data' => $user->makeHidden(['password'])
            ], 201);
        } catch (\Exception $e) {
            Log::error('User creation failed', [
                'error' => $e->getMessage(),
                'input' => $request->except(['password', 'password_confirmation'])
            ]);

            return response()->json([
                'success' => false,
                'message' => 'User creation failed. Please try again.'
            ], 500);
        }
    }

    /**
     * Display the specified user
     */
    public function show(User $user)
    {
        // Load relationships
        $user->load([
            'attendances' => function ($query) {
                $query->latest()->limit(10);
            },
            'leaves' => function ($query) {
                $query->latest()->limit(5);
            }
        ]);

        return response()->json([
            'success' => true,
            'data' => $user->makeHidden(['password'])
        ]);
    }

    /**
     * Update the specified user
     */
    public function update(Request $request, User $user)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|string|email|max:255|unique:users,email,' . $user->id,
            'password' => 'sometimes|string|min:6|confirmed',
            'employee_id' => 'sometimes|string|unique:users,employee_id,' . $user->id,
            'phone' => 'nullable|string|max:20',
            'department' => 'sometimes|string|max:100',
            'position' => 'sometimes|string|max:100',
            'role' => 'sometimes|in:admin,employee',
            'join_date' => 'sometimes|date',
            'basic_salary' => 'sometimes|numeric|min:0',
            'status' => 'sometimes|in:active,inactive',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $updateData = $request->except(['password', 'password_confirmation']);

            // Handle password update
            if ($request->filled('password')) {
                $updateData['password'] = Hash::make($request->password);
            }

            $user->update($updateData);

            Log::info('User updated', [
                'user_id' => $user->id,
                'updated_by' => $request->user()->id,
                'changes' => array_keys($updateData)
            ]);

            return response()->json([
                'success' => true,
                'message' => 'User updated successfully',
                'data' => $user->fresh()->makeHidden(['password'])
            ]);
        } catch (\Exception $e) {
            Log::error('User update failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'User update failed. Please try again.'
            ], 500);
        }
    }

    /**
     * Remove the specified user
     */
    public function destroy(Request $request, User $user)
    {
        // Prevent self-deletion
        if ($user->id === $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'You cannot delete your own account'
            ], 400);
        }

        // Prevent deletion of users with attendances (optional soft delete)
        if ($user->attendances()->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete user with existing attendance records. Set status to inactive instead.'
            ], 400);
        }

        try {
            Log::info('User deleted', [
                'user_id' => $user->id,
                'deleted_by' => $request->user()->id,
                'user_data' => $user->only(['name', 'email', 'employee_id'])
            ]);

            $user->delete();

            return response()->json([
                'success' => true,
                'message' => 'User deleted successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('User deletion failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'User deletion failed. Please try again.'
            ], 500);
        }
    }

    /**
     * Download template Excel untuk import data guru/staff
     */
    public function downloadTemplate()
    {
        try {
            // Audit log for template download
            $this->logAuditActivity('template_download', 'Downloaded import template', 'low');

            $templateData = [
                [
                    'Nama Lengkap' => 'Contoh: Ahmad Supriyanto, S.Pd',
                    'Email' => 'contoh: ahmad.supriyanto@sekolah.sch.id',
                    'NIP' => 'contoh: 19800101200212001',
                    'No. Telepon' => 'contoh: 081234567890',
                    'Alamat' => 'contoh: Jl. Merdeka No. 123, Jakarta',
                    'Tanggal Lahir' => 'contoh: 01/01/1980',
                    'Jenis Kelamin' => 'contoh: Laki-laki / Perempuan',
                    'Bidang/Mata Pelajaran' => 'contoh: Matematika / Bahasa Indonesia / Staff Administrasi',
                    'Posisi/Jabatan' => 'contoh: Guru / Wakil Kepala Sekolah / Staff Tata Usaha',
                    'Tanggal Bergabung' => 'contoh: 01/07/2020',
                    'Status' => 'contoh: active / inactive',
                    'Gaji Pokok' => 'contoh: 4500000',
                    'Tunjangan' => 'contoh: 500000',
                    'Pendidikan Terakhir' => 'contoh: S1 Pendidikan Matematika',
                    'Universitas' => 'contoh: Universitas Negeri Jakarta'
                ]
            ];

            $filename = 'Template_Data_Guru_Staff_' . date('Y-m-d') . '.xlsx';

            return Excel::download(new class($templateData) implements FromArray, WithHeadings {
                private $data;

                public function __construct($data)
                {
                    $this->data = $data;
                }

                public function array(): array
                {
                    return $this->data;
                }

                public function headings(): array
                {
                    return array_keys($this->data[0]);
                }
            }, $filename);
        } catch (\Exception $e) {
            // Audit log for failed template download
            $this->logAuditActivity('template_download_failed', 'Failed to download template: ' . $e->getMessage(), 'high');

            return response()->json([
                'success' => false,
                'message' => 'Gagal mendownload template: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Preview import data dari Excel
     */
    public function previewImport(Request $request)
    {
        try {
            // Audit log for import preview attempt
            $this->logAuditActivity('import_preview_attempt', 'Attempting to preview import file', 'medium');

            $request->validate([
                'file' => 'required|file|mimes:xlsx,xls|max:10240', // 10MB max
            ]);

            $file = $request->file('file');

            // Additional security: Check file content type
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($finfo, $file->getRealPath());
            finfo_close($finfo);

            if (!in_array($mimeType, ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/vnd.ms-excel'])) {
                $this->logAuditActivity('import_preview_security_violation', 'Invalid file type attempted: ' . $mimeType, 'high');
                return response()->json([
                    'success' => false,
                    'message' => 'File type not allowed for security reasons'
                ], 400);
            }

            $data = Excel::toArray(new class implements ToModel {
                public function model(array $row)
                {
                    return $row;
                }
            }, $file);

            if (empty($data) || empty($data[0])) {
                return response()->json([
                    'success' => false,
                    'message' => 'File Excel kosong atau tidak valid'
                ], 400);
            }

            $rows = $data[0];
            $headers = array_shift($rows); // Remove header row

            $preview = [];
            $summary = [
                'valid' => 0,
                'warnings' => 0,
                'errors' => 0
            ];

            foreach ($rows as $index => $row) {
                if (empty(array_filter($row))) continue; // Skip empty rows

                $record = [
                    'row_number' => $index + 2, // +2 because header is row 1 and array is 0-indexed
                    'name' => $this->sanitizeInput($row[0] ?? ''),
                    'email' => $this->sanitizeInput($row[1] ?? ''),
                    'nip' => $this->sanitizeInput($row[2] ?? ''),
                    'phone' => $this->sanitizeInput($row[3] ?? ''),
                    'address' => $this->sanitizeInput($row[4] ?? ''),
                    'birth_date' => $this->sanitizeInput($row[5] ?? ''),
                    'gender' => $this->sanitizeInput($row[6] ?? ''),
                    'department' => $this->sanitizeInput($row[7] ?? ''),
                    'position' => $this->sanitizeInput($row[8] ?? ''),
                    'join_date' => $this->sanitizeInput($row[9] ?? ''),
                    'status' => $this->sanitizeInput($row[10] ?? 'active'),
                    'base_salary' => $this->sanitizeNumeric($row[11] ?? 0),
                    'allowance' => $this->sanitizeNumeric($row[12] ?? 0),
                    'education' => $this->sanitizeInput($row[13] ?? ''),
                    'university' => $this->sanitizeInput($row[14] ?? ''),
                    'errors' => [],
                    'warnings' => []
                ];

                // Validation
                if (empty($record['name'])) {
                    $record['errors'][] = 'Nama lengkap wajib diisi';
                }

                if (empty($record['email'])) {
                    $record['errors'][] = 'Email wajib diisi';
                } elseif (!filter_var($record['email'], FILTER_VALIDATE_EMAIL)) {
                    $record['errors'][] = 'Format email tidak valid';
                } else {
                    // Check if email already exists
                    $existingUser = User::where('email', $record['email'])->first();
                    if ($existingUser) {
                        $record['warnings'][] = 'Email sudah terdaftar';
                    }
                }

                if (!empty($record['nip'])) {
                    $existingNip = User::where('nip', $record['nip'])->first();
                    if ($existingNip) {
                        $record['warnings'][] = 'NIP sudah terdaftar';
                    }
                }

                if (!in_array(strtolower($record['status']), ['active', 'inactive'])) {
                    $record['errors'][] = 'Status harus active atau inactive';
                }

                if (!empty($record['birth_date']) && !$this->isValidDate($record['birth_date'])) {
                    $record['errors'][] = 'Format tanggal lahir tidak valid (gunakan DD/MM/YYYY)';
                }

                if (!empty($record['join_date']) && !$this->isValidDate($record['join_date'])) {
                    $record['errors'][] = 'Format tanggal bergabung tidak valid (gunakan DD/MM/YYYY)';
                }

                // Count summary
                if (count($record['errors']) > 0) {
                    $summary['errors']++;
                } elseif (count($record['warnings']) > 0) {
                    $summary['warnings']++;
                } else {
                    $summary['valid']++;
                }

                $preview[] = $record;
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'preview' => $preview,
                    'summary' => $summary,
                    'total_rows' => count($preview)
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal memproses file: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Execute import data dari Excel
     */
    public function executeImport(Request $request)
    {
        try {
            $request->validate([
                'file' => 'required|file|mimes:xlsx,xls|max:10240',
                'skip_duplicates' => 'boolean',
                'update_existing' => 'boolean',
                'validate_only' => 'boolean'
            ]);

            $skipDuplicates = $request->boolean('skip_duplicates', true);
            $updateExisting = $request->boolean('update_existing', false);
            $validateOnly = $request->boolean('validate_only', false);

            $file = $request->file('file');
            $data = Excel::toArray(new class implements ToModel {
                public function model(array $row)
                {
                    return $row;
                }
            }, $file);

            if (empty($data) || empty($data[0])) {
                return response()->json([
                    'success' => false,
                    'message' => 'File Excel kosong atau tidak valid'
                ], 400);
            }

            $rows = $data[0];
            array_shift($rows); // Remove header row

            $results = [
                'imported' => 0,
                'updated' => 0,
                'skipped' => 0,
                'failed' => 0,
                'errors' => []
            ];

            DB::beginTransaction();

            try {
                foreach ($rows as $index => $row) {
                    if (empty(array_filter($row))) continue; // Skip empty rows

                    $rowNumber = $index + 2;

                    $userData = [
                        'name' => $this->sanitizeInput($row[0] ?? ''),
                        'email' => $this->sanitizeInput($row[1] ?? ''),
                        'nip' => $this->sanitizeInput($row[2] ?? ''),
                        'phone' => $this->sanitizeInput($row[3] ?? ''),
                        'address' => $this->sanitizeInput($row[4] ?? ''),
                        'birth_date' => $this->sanitizeInput($row[5] ?? ''),
                        'gender' => $this->sanitizeInput($row[6] ?? ''),
                        'department' => $this->sanitizeInput($row[7] ?? ''),
                        'position' => $this->sanitizeInput($row[8] ?? ''),
                        'join_date' => $this->sanitizeInput($row[9] ?? ''),
                        'status' => $this->sanitizeInput($row[10] ?? 'active'),
                        'base_salary' => $this->sanitizeNumeric($row[11] ?? 0),
                        'allowance' => $this->sanitizeNumeric($row[12] ?? 0),
                        'education' => $this->sanitizeInput($row[13] ?? ''),
                        'university' => $this->sanitizeInput($row[14] ?? ''),
                        'password' => Hash::make($this->generateSecurePassword()), // Secure random password
                        'role' => 'employee'
                    ];

                    // Basic validation
                    if (empty($userData['name']) || empty($userData['email'])) {
                        $results['failed']++;
                        $results['errors'][] = "Baris {$rowNumber}: Nama dan email wajib diisi";
                        continue;
                    }

                    if (!filter_var($userData['email'], FILTER_VALIDATE_EMAIL)) {
                        $results['failed']++;
                        $results['errors'][] = "Baris {$rowNumber}: Format email tidak valid";
                        continue;
                    }

                    if ($validateOnly) {
                        $results['imported']++; // Count as would-be imported
                        continue;
                    }

                    // Check if user exists
                    $existingUser = User::where('email', $userData['email'])->first();

                    if ($existingUser) {
                        if ($updateExisting) {
                            $existingUser->update($userData);
                            $results['updated']++;
                        } elseif ($skipDuplicates) {
                            $results['skipped']++;
                        } else {
                            $results['failed']++;
                            $results['errors'][] = "Baris {$rowNumber}: Email sudah terdaftar";
                        }
                    } else {
                        User::create($userData);
                        $results['imported']++;
                    }
                }

                if ($validateOnly) {
                    DB::rollBack();
                    return response()->json([
                        'success' => true,
                        'message' => 'Validasi selesai. Tidak ada data yang disimpan.',
                        'data' => $results
                    ]);
                }

                DB::commit();

                return response()->json([
                    'success' => true,
                    'message' => "Import selesai. {$results['imported']} data berhasil diimport, {$results['updated']} data diupdate, {$results['skipped']} data diskip, {$results['failed']} data gagal.",
                    'data' => $results
                ]);
            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengimport data: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Helper function to validate date format
     */
    private function isValidDate($date)
    {
        if (empty($date)) return true; // Allow empty dates

        // Try different date formats
        $formats = ['d/m/Y', 'Y-m-d', 'd-m-Y', 'm/d/Y'];

        foreach ($formats as $format) {
            $parsed = DateTime::createFromFormat($format, $date);
            if ($parsed && $parsed->format($format) === $date) {
                return true;
            }
        }

        return false;
    }

    /**
     * Helper function to parse date
     */
    private function parseDate($date)
    {
        if (empty($date)) return null;

        // Try different date formats
        $formats = ['d/m/Y', 'Y-m-d', 'd-m-Y', 'm/d/Y'];

        foreach ($formats as $format) {
            $parsed = DateTime::createFromFormat($format, $date);
            if ($parsed && $parsed->format($format) === $date) {
                return $parsed->format('Y-m-d');
            }
        }

        return null;
    }

    /**
     * Helper function to generate a secure random password
     */
    private function generateSecurePassword()
    {
        // Generate secure random password with 12 characters
        // Contains uppercase, lowercase, numbers, and symbols
        $characters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*';
        $password = '';
        $length = 12;

        // Ensure at least one character from each category
        $password .= chr(rand(65, 90)); // Uppercase
        $password .= chr(rand(97, 122)); // Lowercase  
        $password .= chr(rand(48, 57)); // Number
        $password .= '!@#$%^&*'[rand(0, 7)]; // Symbol

        // Fill remaining positions randomly
        for ($i = 4; $i < $length; $i++) {
            $password .= $characters[rand(0, strlen($characters) - 1)];
        }

        // Shuffle the password to randomize positions
        return str_shuffle($password);
    }

    /**
     * Log audit activity for security monitoring
     */
    private function logAuditActivity($action, $description, $riskLevel = 'low')
    {
        try {
            $user = Auth::user();
            $request = request();

            // Create audit log entry
            AuditLog::create([
                'user_id' => $user ? $user->id : null,
                'action' => $action,
                'description' => $description,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'risk_level' => $riskLevel,
                'metadata' => json_encode([
                    'timestamp' => now(),
                    'route' => $request->route()?->getName(),
                    'method' => $request->method(),
                    'url' => $request->url()
                ])
            ]);
        } catch (\Exception $e) {
            // Log to system log if audit log fails
            Log::error('Failed to create audit log', [
                'action' => $action,
                'description' => $description,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Helper function to sanitize input
     */
    private function sanitizeInput($input)
    {
        return htmlspecialchars(trim($input));
    }

    /**
     * Helper function to sanitize numeric input
     */
    private function sanitizeNumeric($input)
    {
        return filter_var($input, FILTER_VALIDATE_FLOAT);
    }
}
