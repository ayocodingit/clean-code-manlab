<?php

namespace App\Traits;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Register trait
 *
 */
trait RegisterTrait
{
    /**
     * Generate nomor register
     * @param string $date
     * @param string $jenisRegistrasi
     *
     * @return string
     */
    public function generateNomorRegister($date = null, $jenisRegistrasi = null)
    {
        if (!$date) {
            $date = date('Ymd');
        }

        if (empty($jenisRegistrasi)) {
            $kodeRegistrasi = 'L';
        }

        if ($jenisRegistrasi === 'mandiri') {
            $kodeRegistrasi = 'L';
        }

        if ($jenisRegistrasi === 'rujukan') {
            $kodeRegistrasi = 'R';
        }

        $res = DB::select("select max(right(nomor_register, 4))::int8 val from register where nomor_register ilike '{$kodeRegistrasi}{$date}%'");

        if (count($res)) {
            $nextnum = $res[0]->val + 1;
        } else {
            $nextnum = 1;

        }

        return $kodeRegistrasi . $date . str_pad($nextnum, 4, "0", STR_PAD_LEFT);
    }

    public function createLog($register, $registerOrigin, $pasienOrigin, $sampelOrigin, $registerChanges, $pasienChanges, $sampelChanges)
    {
        $registerLogs = array();
        foreach ($registerChanges as $key => $value) {
            if ($key != "updated_at") {
                $registerLogs[$key]["from"] = $key == 'status' && $registerOrigin[$key] ? STATUSES[$registerOrigin[$key]] : $registerOrigin[$key];
                $registerLogs[$key]["to"] = $key == 'status' ? STATUSES[$value] : $value;
            }
        }

        $pasienLogs = array();
        foreach ($pasienChanges as $key => $value) {
            if ($key != "updated_at") {
                $pasienLogs[$key]["from"] = $key == 'tanggal_lahir' ? date('d-m-Y', strtotime($pasienOrigin[$key])) : $pasienOrigin[$key];
                $pasienLogs[$key]["to"] = $key == 'tanggal_lahir' ? date('d-m-Y', strtotime($value)) : $value;
            }
        }

        $sampelLogs = array();
        foreach ($sampelChanges as $key => $value) {
            if ($key != "updated_at") {
                $sampelLogs[$key]["from"] = $sampelOrigin[$key];
                $sampelLogs[$key]["to"] = $value;
            }
        }

        $register->logs()->create([
            "user_id" => Auth::user()->id,
            "info" => json_encode(array(
                "register" => $registerLogs,
                "sampel" => $sampelLogs,
                "pasien" => $pasienLogs,
            )),
        ]);
    }

    public function getRequestPasien(Request $request): array
    {
        return [
            'nama_lengkap' => $request->get('reg_nama_pasien'),
            'kewarganegaraan' => $request->get('reg_kewarganegaraan'),
            'nik' => $request->get('reg_nik'),
            'tempat_lahir' => $request->get('reg_tempatlahir'),
            'tanggal_lahir' => $request->get('reg_tgllahir'),
            'no_hp' => $request->get('reg_nohp'),
            'kota_id' => $request->get('kota_id'),
            'kecamatan_id' => $request->get('kecamatan_id'),
            'kelurahan_id' => $request->get('kelurahan_id'),
            'kecamatan' => $request->get('kecamatan'),
            'kelurahan' => $request->get('kelurahan'),
            'provinsi_id' => $request->get('provinsi_id'),
            'alamat_lengkap' => $request->get('reg_alamat'),
            'no_rt' => $request->get('reg_rt'),
            'no_rw' => $request->get('reg_rw'),
            'suhu' => parseDecimal($request->get('reg_suhu')),
            'jenis_kelamin' => $request->get('reg_jk'),
            'keterangan_lain' => $request->get('reg_keterangan'),
            'usia_tahun' => $request->get('reg_usia_tahun'),
            'usia_bulan' => $request->get('reg_usia_bulan'),
            'status' => $request->get('status'),
        ];
    }

    public function getRequestRegister(Request $request, $typeRequest = 'update'): array
    {
        $register = [
            'sumber_pasien' => $request->get('reg_sumberpasien'),
            'tanggal_kunjungan' => $request->get('reg_tanggalkunjungan'),
            'kunjungan_ke' => $request->get('reg_kunjungan_ke'),
            'rs_kunjungan' => $request->get('reg_rsfasyankes'),
            'sumber_pasien' => $request->get('reg_sumberpasien') == "Umum" ?
            "Umum" :
            $request->get('reg_sumberpasien_isian'),
        ];

        if ($typeRequest != 'update') {
            $nomor_register = $request->input('reg_no');
            if (Register::where('nomor_register', $nomor_register)->exists()) {
                $nomor_register = $this->generateNomorRegister();
            }
            $register['nomor_register'] = $nomor_register;
            $register['register_uuid'] = Str::uuid();
            $register['creator_user_id'] = $request->user()->id;
        }
        return $register;
    }
}
