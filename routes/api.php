<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Models\Employee;
use App\Models\EmployeeSalary;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

Route::middleware('web')->group(function() {
    
    // API untuk data profile karyawan 
    Route::get('/dashboard/karyawan/profile/{id}', function($id) {
        // Verifikasi user yang login
        if (!Auth::check()) {
            return response()->json(['message' => 'Unauthorized - Silakan login terlebih dahulu'], 401);
        }
        
        // Verifikasi akses data
        if (Auth::user()->employee_id != $id) {
            return response()->json(['message' => 'Forbidden - Anda hanya bisa mengakses data sendiri'], 403);
        }

        try {
            $employee = Employee::find($id);
            
            if(!$employee) {
                return response()->json(['message' => 'Data karyawan tidak ditemukan'], 404);
            }
            
            return response()->json([
                'data' => [
                    'id' => $employee->id,
                    'nama' => $employee->nama,
                    'nip' => $employee->nip,
                    'jenis_kelamin' => $employee->jenis_kelamin,
                    'tempat_lahir' => $employee->tempat_lahir,
                    'tgl_lahir' => $employee->tgl_lahir,
                    'alamat' => $employee->alamat,
                    'no_telp' => $employee->no_telp,
                    'no_rek' => $employee->no_rek,
                    'bank' => $employee->bank,
                    'tgl_masuk' => $employee->tgl_masuk,
                    'status' => $employee->status
                ],
                'message' => 'Data profile berhasil diambil'
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Terjadi kesalahan server',
                'error' => $e->getMessage()
            ], 500);
        }
    });



    // API untuk data gaji karyawan 
    Route::get('/dashboard/karyawan/gaji/{id}', function($id) {
        // Verifikasi user yang login
        if (!Auth::check()) {
            return response()->json(['message' => 'Unauthorized - Silakan login terlebih dahulu'], 401);
        }
        
        // Verifikasi akses data
        if (Auth::user()->employee_id != $id) {
            return response()->json(['message' => 'Forbidden - Anda hanya bisa mengakses data gaji sendiri'], 403);
        }

        try {
            $salaries = EmployeeSalary::with(['salary'])
                ->where('karyawan_id', $id)
                ->get();
                
            if($salaries->isEmpty()) {
                return response()->json(['message' => 'Data gaji tidak ditemukan'], 404);
            }
            
            $formattedSalaries = $salaries->map(function($item) {
                return [
                    'id' => $item->id,
                    'tgl_gajian' => $item->tgl_gajian,
                    'jabatan' => $item->salary->jabatan ?? 'Tidak diketahui',
                    'gaji_pokok' => $item->salary->gaji_pokok ?? 0,
                    'tunjangan_transport' => $item->salary->tj_transport ?? 0,
                    'uang_makan' => $item->salary->uang_makan ?? 0,
                    'total_gaji' => ($item->salary->gaji_pokok ?? 0) + 
                                    ($item->salary->tj_transport ?? 0) + 
                                    ($item->salary->uang_makan ?? 0)
                ];
            });
            
            $total = $formattedSalaries->sum('total_gaji');
            
            return response()->json([
                'data' => $formattedSalaries,
                'total_keseluruhan' => $total,
                'message' => 'Data gaji berhasil diambil'
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Terjadi kesalahan server',
                'error' => $e->getMessage()
            ], 500);
        }
    });

    // API tersembunyi dengan proteksi tambahan
    Route::get('/hidden/all-employees', function() {
        // Tambahkan verifikasi role atau akses khusus
        if (!Auth::check() || Auth::user()->role_id != 1) { // hanya admin (role_id 1)
            return response()->json(['message' => 'Forbidden - Akses dibatasi untuk admin'], 403);
        }

        $employees = Employee::with('salary')->get();
        
        return response()->json([
            'data' => $employees,
            'message' => 'Semua data karyawan berhasil diambil'
        ]);
    });
});

// Route Sanctum yang tidak digunakan bisa dihapus atau dikomentari
// Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//     return $request->user();
// });