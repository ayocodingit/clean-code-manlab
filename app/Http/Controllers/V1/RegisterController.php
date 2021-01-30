<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\RegisterMandiriRequest;
use App\Http\Resources\DetailRegisterMandiriResource;
use App\Models\Pasien;
use App\Models\PasienRegister;
use App\Models\Register;
use App\Models\RegisterLog;
use App\Models\Sampel;
use App\Traits\RegisterTrait;
use Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RegisterController extends Controller
{
    use RegisterTrait;

    public function requestNomor(Request $request)
    {
        $jenis = $request->get('tipe');
        return response()->json([
            'status' => 200,
            'message' => 'success',
            'result' => $this->generateNomorRegister(null, $jenis),
        ]);
    }

    public function storeMandiri(RegisterMandiriRequest $request)
    {
        DB::beginTransaction();
        try {
            $user = Auth::user();
            $register = Register::create($this->getRequestRegister($request), 'create');
            $pasien = Pasien::create($this->getRequestPasien($request), 'create');
            PasienRegister::create([
                'pasien_id' => $pasien->id,
                'register_id' => $register->id,
            ]);

            $sampel = Sampel::create([
                'nomor_sampel' => $request->reg_sampel,
                'nomor_register' => $register->nomor_register,
                'register_id' => $register->id,
                'sampel_status' => 'waiting_sample',
            ]);
            $sampel->updateState('waiting_sample', [
                'user_id' => $user->id,
                'metadata' => $sampel,
                'description' => 'Data Pasien Mandiri Teregistrasi',
            ]);
            DB::commit();
            return response()->json(['message' => 'Proses Registrasi Mandiri Berhasil Ditambahkan']);
        } catch (\Throwable $th) {
            DB::rollBack();
            throw $th;
        }
    }

    public function storeUpdate(RegisterMandiriRequest $request, Register $register, Pasien $pasien)
    {
        DB::beginTransaction();
        try {
            $sampel = Sampel::find($request->sampel_id);
            $registerOrigin = clone $register;
            $pasienOrigin = clone $pasien;
            $sampelOrigin = clone $sampel;
            $register->fill($this->getRequestRegister($request));
            $register->save();

            $pasien->fill($this->getRequestPasien($request));
            $pasien->save();

            $sampel->nomor_sampel = $request->nomor_sampel;
            $sampel->save();

            $registerChanges = $register->getChanges();
            $pasienChanges = $pasien->getChanges();
            $sampelChanges = $sampel->getChanges();
            $this->createLog($register, $registerOrigin, $pasienOrigin, $sampelOrigin, $registerChanges, $pasienChanges, $sampelChanges);
            DB::commit();
            return response()->json(['status' => 200, 'message' => 'Data Register Berhasil Diubah']);
        } catch (\Throwable $th) {
            DB::rollBack();
            throw $th;
        }
    }

    public function getById(Request $request, $register_id, $pasien_id)
    {
        $model = PasienRegister::where('pasien_register.register_id', $register_id)
            ->where('pasien_register.pasien_id', $pasien_id)
            ->first();
        return new DetailRegisterMandiriResource($model);
    }

    public function logs($register_id)
    {
        $model = RegisterLog::where('register_id', $register_id)->join('users', 'users.id', 'register_logs.user_id')
            ->whereRaw("(info->>'pasien' <> '[]'::text or info->>'register' <> '[]'::text or info->>'sampel' <> '[]'::text)")
            ->select('info', 'register_logs.created_at', 'users.name as updated_by')->get();

        foreach ($model as $key => $val) {
            $info = json_decode($val->info);

            if (isset($info->pasien->status)) {
                $info->pasien->status->from = STATUSES[$info->pasien->status->from] ?? null;
                $info->pasien->status->to = STATUSES[$info->pasien->status->to] ?? null;
            }
            $val->info = json_encode($info);
        }

        return response()->json([
            'result' => $model,
            'statys' => 'success',
        ], 200);
    }

    public function delete($id, $pasien)
    {
        DB::beginTransaction();
        try {
            PasienRegister::where('register_id', $id)->where('pasien_id', $pasien)->delete();
            $sampel = Sampel::where('register_id', $id)->first();
            if ($sampel != null) {
                if ($sampel->sampel_status == 'waiting_sample') {
                    $sampel->delete();
                } else {
                    $sampel->register_id = null;
                    $sampel->nomor_register = null;
                    $sampel->save();
                }
            }
            Register::where('id', $id)->delete();
            $pasien = Pasien::where('id', $pasien)->delete();
            DB::commit();
            return response()->json([
                'status' => true,
                'message' => "Berhasil menghapus data register",
            ]);
        } catch (\Throwable $th) {
            DB::rollBack();
            throw $th;
        }
    }
}
