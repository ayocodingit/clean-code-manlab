<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class DetailRegisterMandiriResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $pasien = $this->pasien;
        $register = $this->register;
        $sampel = $register->sampel;
        return [
            "nomor_register" => $register->nomor_sampel,
            "kewarganegaraan" => $pasien->kewarganegaraan,
            "nama_lengkap" => $pasien->nama_lengkap,
            "tempat_lahir" => $pasien->tempat_lahir,
            "tanggal_lahir" => $pasien->tanggal_lahir,
            "no_hp" => $pasien->no_hp,
            "kota_id" => $pasien->kota_id,
            "provinsi_id" => $pasien->provinsi_id,
            "kecamatan_id" => $pasien->kecamatan_id,
            "kelurahan_id" => $pasien->kelurahan_id,
            "kota" => optional($pasien->kota)->nama,
            "kecamatan" => $pasien->kecamatan_id,
            "kelurahan" => $pasien->kelurahan_id,
            "provinsi" => "JAWA BARAT",
            "alamat_lengkap" => $pasien->alamat_lengkap,
            "no_rt" => $pasien->no_rt,
            "no_rw" => $pasien->no_rw,
            "suhu" => $pasien->suhu,
            "status" => $pasien->status,
            "sampel_id" => $sampel->id,
            "nomor_sampel" => $sampel->nomor_sampel,
            "keterangan_lain" => $pasien->keterangan_lain,
            "nik" => $pasien->nik,
            "jenis_kelamin" => $pasien->jenis_kelamin,
            "kunjungan_ke" => $register->kunjungan_ke,
            "tanggal_kunjungan" => $register->tanggal_kunjungan,
            "rs_kunjungan" => $register->rs_kunjungan,
            "usia_bulan" => $pasien->usia_bulan,
            "usia_tahun" => $pasien->usia_tahun,
            "sumber_pasien" => $register->sumber_pasien
        ];
    }
}
